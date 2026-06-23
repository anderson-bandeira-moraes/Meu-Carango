<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;
use App\Service\AdminAuthService;

class AdminAuthController
{
    public function __construct(
        private AdminAuthService $adminAuthService,
        private ViewRenderer $view,
        private SessionInterface $session,
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

        // Verifica se o parâmetro de expiração do bloqueio foi passado
        if ($request->getQuery('blockage_expired')) {
            $this->session->set('flash_admin_success', 'O bloqueio de 30 minutos expirou. Você pode tentar fazer login novamente.');
        }

        // Recupera mensagens flash
        $erro = $this->session->get('flash_admin_error', null);
        $this->session->delete('flash_admin_error');

        $sucesso = $this->session->get('flash_admin_success', null);
        $this->session->delete('flash_admin_success');

        return $this->view->renderWithLayout(
            'admin/login',
            [
                'erro'   => $erro,
                'sucesso' => $sucesso,
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
        // Obtém o IP do cliente (para logs)
        $clientIp = $request->getClientIp();

        // Se for AJAX, lê o corpo JSON
        if ($request->isAjax()) {
            $data = $request->getJson();
            $email = trim($data['email'] ?? '');
            $senha = $data['senha'] ?? '';
        } else {
            $email = trim($request->getPost('email') ?? '');
            $senha = $request->getPost('senha') ?? '';
        }

        if ($email === '' || $senha === '') {
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Preencha e-mail e senha.']);
                exit;
            }
            $this->session->set('flash_admin_error', 'Preencha e-mail e senha.');
            header('Location: /admin/login');
            exit;
        }

        // Passa o IP para o service
        $resultado = $this->adminAuthService->login($email, $senha, $clientIp);

        if ($resultado['sucesso']) {
            // Se 2FA for necessário, redireciona para /admin/2fa
            if (isset($resultado['2fa_required']) && $resultado['2fa_required'] === true) {
                $redirectTo = $resultado['redirect'] ?? '/admin/2fa';
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'sucesso' => true,
                        '2fa_required' => true,
                        'redirect' => $redirectTo,
                    ]);
                    exit;
                }
                header('Location: ' . $redirectTo);
                exit;
            }

            // Login completo (sem 2FA)
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'redirect' => '/admin']);
                exit;
            }
            header('Location: /admin');
            exit;
        }

        if ($request->isAjax()) {
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
        $clientIp = $request->getClientIp();
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