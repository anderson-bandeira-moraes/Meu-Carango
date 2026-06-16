<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;  // <-- importa a interface
use App\Service\AuthService;

/**
 * Controlador de autenticação (login, registro, logout).
 */
class AuthController
{
    public function __construct(
        private AuthService $authService,
        private ViewRenderer $view,
        private SessionInterface $session,   // <-- nova dependência injetada
    ) {}

    /**
     * Exibe o formulário de login.
     */
    public function formLogin(Request $request): string
    {
        if ($this->authService->check()) {
            header('Location: /dashboard');
            exit;
        }

        $erro = $this->session->get('flash_error', null);
        $this->session->delete('flash_error');

        return $this->view->render('auth/login', [
            'erro' => $erro,
        ]);
    }

    /**
     * Processa o login.
     */
    public function login(Request $request): void
    {
        $email = trim($request->getPost('email') ?? '');
        $senha = $request->getPost('senha') ?? '';

        if ($email === '' || $senha === '') {
            $this->session->set('flash_error', 'Preencha e-mail e senha.');
            header('Location: /login');
            exit;
        }

        $resultado = $this->authService->login($email, $senha);

        if ($resultado['sucesso']) {
            $redirectTo = $this->session->get('intended_url', '/dashboard');
            $this->session->delete('intended_url');
            header('Location: ' . $redirectTo);
            exit;
        }

        $this->session->set('flash_error', $resultado['erro']);
        header('Location: /login');
        exit;
    }

    /**
     * Exibe o formulário de registro.
     */
    public function formRegistro(Request $request): string
    {
        if ($this->authService->check()) {
            header('Location: /dashboard');
            exit;
        }

        $erro = $this->session->get('flash_error', null);
        $dados = $this->session->get('flash_old', []);
        $this->session->delete('flash_error');
        $this->session->delete('flash_old');

        return $this->view->renderWithLayout(
            'auth/registro',
            ['erro' => $erro, 'dados' => $dados],
            'layouts/main',
            ['title' => 'Cadastro de Lojista']
        );
    }

    /**
     * Processa o registro de um novo lojista.
     */
    public function registrar(Request $request): void
    {
        $dados = [
            'nome'      => trim($request->getPost('nome') ?? ''),
            'email'     => trim($request->getPost('email') ?? ''),
            'senha'     => $request->getPost('senha') ?? '',
            'nome_loja' => trim($request->getPost('nome_loja') ?? ''),
            'slug'      => trim($request->getPost('slug') ?? ''),
            'telefone'  => trim($request->getPost('telefone') ?? ''),
        ];

        $erros = [];

        if (empty($dados['nome'])) {
            $erros[] = 'Nome é obrigatório.';
        }
        if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido.';
        }
        if (empty($dados['senha']) || strlen($dados['senha']) < 6) {
            $erros[] = 'Senha deve ter pelo menos 6 caracteres.';
        }
        if (empty($dados['nome_loja'])) {
            $erros[] = 'Nome da loja é obrigatório.';
        }
        if (empty($dados['slug']) || !preg_match('/^[a-z0-9-]+$/', $dados['slug'])) {
            $erros[] = 'Slug deve conter apenas letras minúsculas, números e hífens.';
        }
        if (empty($dados['telefone'])) {
            $erros[] = 'Telefone é obrigatório.';
        }

        if (!empty($erros)) {
            $this->session->set('flash_error', implode('<br>', $erros));
            $this->session->set('flash_old', $dados);
            header('Location: /registro');
            exit;
        }

        $resultado = $this->authService->registrar($dados);

        if ($resultado['sucesso']) {
            $this->session->set('flash_success', 'Conta criada com sucesso! Faça login.');
            header('Location: /login');
            exit;
        }

        $this->session->set('flash_error', $resultado['erro']);
        $this->session->set('flash_old', $dados);
        header('Location: /registro');
        exit;
    }

    /**
     * Logout do usuário.
     */
    public function logout(Request $request): void
    {
        $this->authService->logout();
        header('Location: /login');
        exit;
    }
}