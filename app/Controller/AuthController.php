<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;
use App\Service\AuthService;
use App\Requests\LoginRequest;

/**
 * Controlador de autenticação do lojista.
 * Gerencia login, logout e dashboard do lojista.
 */
class AuthController
{
    public function __construct(
        private AuthService $authService,
        private ViewRenderer $view,
        private SessionInterface $session,
        private LoginRequest $loginRequest, // FormRequest injetada
    ) {}

    /**
     * Exibe o formulário de login do lojista.
     *
     * @param Request $request
     * @return string
     */
    public function formLogin(Request $request): string
    {
        // Se já estiver logado, redireciona para o dashboard
        if ($this->authService->check()) {
            header('Location: /logista/dashboard');
            exit;
        }

        // Recupera mensagens flash
        $erro = $this->session->get('flash_user_error', null);
        $this->session->delete('flash_user_error');

        // Recupera old input para repopular o formulário
        $old = $this->session->get('old_user_input', []);
        $this->session->delete('old_user_input');

        return $this->view->renderWithLayout(
            'logista/login',
            [
                'erro' => $erro,
                'old'  => $old,
            ],
            'layouts/main',
            ['title' => 'Login - Meu Carango']
        );
    }

    /**
     * Processa o login do lojista.
     *
     * @param Request $request
     * @return void
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
        $resultado = $this->authService->login($email, $senha, $clientIp);

        if ($resultado['sucesso']) {
            // Se 2FA for necessário, redireciona para /logista/2fa
            if (isset($resultado['2fa_required']) && $resultado['2fa_required'] === true) {
                $redirectTo = $resultado['redirect'] ?? '/logista/2fa';
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
                echo json_encode(['sucesso' => true, 'redirect' => '/logista/dashboard']);
                exit;
            }
            header('Location: /logista/dashboard');
            exit;
        }

        // Falha no login (senha incorreta, usuário inativo, etc.)
        $this->handleLoginFailure(
            $resultado['erro'] ?? 'Erro ao fazer login.',
            $this->loginRequest->isAjax()
        );
    }

    /**
     * Logout do lojista.
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request): void
    {
        $clientIp = $this->loginRequest->getClientIp(); // usa a FormRequest para obter o IP
        $this->authService->logout($clientIp);
        header('Location: /logista/login');
        exit;
    }

    /**
     * Dashboard do lojista (página inicial protegida).
     *
     * @param Request $request
     * @return string
     */
    public function index(Request $request): string
    {
        $user = $this->authService->getCurrentUser();

        return $this->view->renderWithLayout(
            'logista/dashboard',
            ['user' => $user],
            'layouts/main',
            ['title' => 'Dashboard - Meu Carango']
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
        $this->session->set('old_user_input', $this->loginRequest->all());

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $error]);
            exit;
        }

        $this->session->set('flash_user_error', $error);
        header('Location: /logista/login');
        exit;
    }
}