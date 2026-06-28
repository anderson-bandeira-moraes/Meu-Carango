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
            // Armazena old input (dados enviados) para repopular o formulário
            $this->session->set('old_user_input', $this->loginRequest->all());

            // Verifica se é uma requisição AJAX
            if ($this->loginRequest->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => $this->loginRequest->getAllErrorsAsString('<br>') ?: 'Dados inválidos.',
                ]);
                exit;
            }

            // Armazena erros em flash
            $this->session->set('flash_user_error', $this->loginRequest->getAllErrorsAsString('<br>') ?: 'Dados inválidos.');
            header('Location: /logista/login');
            exit;
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

        // Falha no login
        if ($this->loginRequest->isAjax()) {
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
}