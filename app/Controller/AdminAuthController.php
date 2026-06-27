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
            // Obtém os erros
            $errors = $this->loginRequest->getErrors();
            
            // Armazena old input (dados enviados) para repopular o formulário
            $this->session->set('old_admin_input', $this->loginRequest->all());

            // Verifica se é uma requisição AJAX
            if ($this->loginRequest->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => implode('<br>', $errors['email'] ?? []) ?: 'Dados inválidos.',
                ]);
                exit;
            }

            // Armazena erros em flash
            $this->session->set('flash_admin_error', implode('<br>', $errors['email'] ?? []) ?: 'Dados inválidos.');
            header('Location: /admin/login');
            exit;
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

        // Falha no login: exibe erro
        if ($this->loginRequest->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $resultado['erro']]);
            exit;
        }

        $this->session->set('flash_admin_error', $resultado['erro']);
        header('Location: /admin/login');
        exit;
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
}