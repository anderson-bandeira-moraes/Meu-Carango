<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;
use App\Service\AdminAuthService;
use App\Requests\LoginRequest;

class AdminAuthController
{
    public function __construct(
        private AdminAuthService $adminAuthService,
        private ViewRenderer $view,
        private SessionInterface $session,
        private LoginRequest $loginRequest, // FormRequest injetada
    ) {}

    /**
     * Exibe o formulário de login do administrador.
     */
    public function formLogin(Request $request): string
    {
        if ($this->adminAuthService->check()) {
            header('Location: /admin');
            exit;
        }

        // Recupera mensagens flash
        $erro = $this->session->get('flash_admin_error', null);
        $this->session->delete('flash_admin_error');

        // Recupera old input para repopular o formulário
        $old = $this->session->get('old_admin_input', []);
        $this->session->delete('old_admin_input');

        return $this->view->renderWithLayout(
            'admin/login',
            [
                'erro' => $erro,
                'old'  => $old,
            ],
            'layouts/main',
            ['title' => 'Admin - Login']
        );
    }

    /**
     * Processa o login do administrador.
     */
    public function login(Request $request): void
    {
        // Valida os dados usando a LoginRequest
        if (!$this->loginRequest->validate()) {
            $errors = $this->loginRequest->getAllErrorsAsString(
                $this->loginRequest->isAjax() ? '<br>' : "\n"
            ) ?: 'Dados inválidos.';

            $this->handleLoginFailure($errors, $this->loginRequest->isAjax());
        }

        // Obtém os dados validados (email e senha)
        $dados = $this->loginRequest->validated();
        $email = $dados['email'] ?? '';
        $senha = $dados['senha'] ?? '';

        // Obtém o IP do cliente (através da FormRequest, que estende Request)
        $clientIp = $this->loginRequest->getClientIp();

        // Chama o serviço de autenticação
        $resultado = $this->adminAuthService->login($email, $senha, $clientIp);

        if ($resultado['sucesso']) {
            // Se 2FA for necessário, redireciona para /admin/2fa
            if (isset($resultado['2fa_required']) && $resultado['2fa_required'] === true) {
                $redirectTo = $resultado['redirect'] ?? '/admin/2fa';
                if ($this->loginRequest->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'sucesso'      => true,
                        '2fa_required' => true,
                        'redirect'     => $redirectTo,
                    ]);
                    exit;
                }
                header('Location: ' . $redirectTo);
                exit;
            }

            // Login completo (sem 2FA)
            if ($this->loginRequest->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'redirect' => '/admin']);
                exit;
            }
            header('Location: /admin');
            exit;
        }

        // Falha no login (senha incorreta, conta inativa, etc.)
        $this->handleLoginFailure(
            $resultado['erro'] ?? 'Erro ao fazer login.',
            $this->loginRequest->isAjax()
        );
    }

    /**
     * Logout do administrador.
     */
    public function logout(Request $request): void
    {
        // Obtém o IP do cliente e passa para o service
        $clientIp = $this->loginRequest->getClientIp(); // usa a FormRequest para obter o IP
        $this->adminAuthService->logout($clientIp);
        header('Location: /admin/login');
        exit;
    }

    /**
     * Dashboard administrativo (página inicial protegida).
     */
    public function index(Request $request): string
    {
        $admin = $this->adminAuthService->getCurrentAdmin();

        return $this->view->renderWithLayout(
            'admin/dashboard',
            ['admin' => $admin],
            'layouts/main',
            ['title' => 'Painel Administrativo']
        );
    }

    /**
     * Trata uma falha no login, salvando old input e mensagem de erro.
     *
     * Este método centraliza o tratamento de erros durante o processo de login,
     * seja por falha de validação ou por credenciais inválidas. Ele armazena
     * os dados submetidos (old input) para repopulação do formulário e exibe
     * a mensagem de erro apropriada, seja via redirecionamento com flash message
     * ou via resposta JSON para requisições AJAX.
     *
     * @param string $error   Mensagem de erro a ser exibida ao usuário.
     * @param bool   $isAjax  Indica se a requisição é AJAX, alterando o formato da resposta.
     *
     * @return void  Este método encerra a execução com exit() após enviar a resposta.
     */
    private function handleLoginFailure(string $error, bool $isAjax): void
    {
        $this->session->set('old_admin_input', $this->loginRequest->all());

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $error]);
            exit;
        }

        $this->session->set('flash_admin_error', $error);
        header('Location: /admin/login');
        exit;
    }
}