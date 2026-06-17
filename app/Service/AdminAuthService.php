<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Contracts\SessionInterface;
use App\Repository\AdministradorRepository;

class AdminAuthService
{
    public function __construct(
        private AdministradorRepository $adminRepository,
        private SessionInterface $session,
    ) {}

    /**
     * Tenta realizar o login do administrador.
     *
     * @param string $email
     * @param string $senha (plain text)
     * @return array{sucesso: bool, erro?: string}
     */
    public function login(string $email, string $senha): array
    {
        $admin = $this->adminRepository->findByEmail($email);

        if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
            return [
                'sucesso' => false,
                'erro'    => 'E-mail ou senha inválidos.',
            ];
        }

        // Regenera o ID da sessão (segurança)
        $this->session->regenerate();

        // Armazena dados do administrador na sessão
        $this->session->set('admin_id', $admin['id']);
        $this->session->set('admin_nome', $admin['nome']);
        $this->session->set('admin_email', $admin['email']);

        // Regenera o token CSRF (evita fixação de token)
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        return ['sucesso' => true];
    }

    /**
     * Realiza logout (destrói a sessão).
     */
    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * Verifica se o administrador está autenticado.
     */
    public function check(): bool
    {
        return $this->session->has('admin_id');
    }

    /**
     * Retorna os dados do administrador logado atualmente (da sessão).
     *
     * @return array|null
     */
    public function getCurrentAdmin(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id'    => $this->session->get('admin_id'),
            'nome'  => $this->session->get('admin_nome'),
            'email' => $this->session->get('admin_email'),
        ];
    }
}