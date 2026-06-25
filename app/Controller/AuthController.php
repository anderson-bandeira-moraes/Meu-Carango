<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;
use App\Service\AuthService;

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

        return $this->view->renderWithLayout(
            'logista/login',
            ['erro' => $erro],
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
        // Obtém o IP do cliente (para logs e limite de tentativas)
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

        // Validação básica
        if ($email === '' || $senha === '') {
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Preencha e-mail e senha.']);
                exit;
            }
            $this->session->set('flash_user_error', 'Preencha e-mail e senha.');
            header('Location: /logista/login');
            exit;
        }

        // Chama o service com o IP
        $resultado = $this->authService->login($email, $senha, $clientIp);

        if ($resultado['sucesso']) {
            // Se 2FA for necessário, redireciona para /logista/2fa
            if (isset($resultado['2fa_required']) && $resultado['2fa_required'] === true) {
                $redirectTo = $resultado['redirect'] ?? '/logista/2fa';
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
                echo json_encode(['sucesso' => true, 'redirect' => '/logista/dashboard']);
                exit;
            }
            header('Location: /logista/dashboard');
            exit;
        }

        // Falha no login
        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $resultado['erro']]);
            exit;
        }

        $this->session->set('flash_user_error', $resultado['erro']);
        header('Location: /logista/login');
        exit;
    }

    /**
     * Logout do lojista.
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request): void
    {
        $clientIp = $request->getClientIp();
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
}