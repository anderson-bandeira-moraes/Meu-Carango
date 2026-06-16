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

        $erro = $this->session->get('flash_admin_error', null);
        $this->session->delete('flash_admin_error');

        return $this->view->renderWithLayout(
            'admin/login',      // <-- corrigido: antes estava 'auth/login'
            ['erro' => $erro],
            'layouts/main',
            ['title' => 'Admin - Login']
        );
    }

    /**
     * Processa o login do administrador.
     */
    public function login(Request $request): void
    {
        $email = trim($request->getPost('email') ?? '');
        $senha = $request->getPost('senha') ?? '';

        if ($email === '' || $senha === '') {
            $this->session->set('flash_admin_error', 'Preencha e-mail e senha.');
            header('Location: /admin/login');
            exit;
        }

        $resultado = $this->adminAuthService->login($email, $senha);

        if ($resultado['sucesso']) {
            header('Location: /admin');
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
        $this->adminAuthService->logout();
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