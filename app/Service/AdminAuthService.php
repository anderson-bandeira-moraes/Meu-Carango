<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Contracts\SessionInterface;
use App\Repository\AdministradorRepository;
use Monolog\Logger;

class AdminAuthService
{
    public function __construct(
        private AdministradorRepository $adminRepository,
        private SessionInterface $session,
        private Logger $logger,
    ) {}

    /**
     * Tenta realizar o login do administrador.
     *
     * @param string $email
     * @param string $senha (plain text)
     * @param string $clientIp IP do cliente (para logs)
     * @return array{sucesso: bool, erro?: string}
     */
    public function login(string $email, string $senha, string $clientIp): array
    {
        $admin = $this->adminRepository->findByEmail($email);

        // --- Falha de login (credenciais inválidas) ---
        if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
            $this->logger->warning('Tentativa de login falhou (admin)', [
                'email'  => $email,
                'ip'     => $clientIp,
                'motivo' => 'Credenciais inválidas',
            ]);

            return [
                'sucesso' => false,
                'erro'    => 'E-mail ou senha inválidos.',
            ];
        }

        // --- Login bem-sucedido ---
        // Regenera o ID da sessão (segurança)
        $this->session->regenerate();

        // Armazena dados do administrador na sessão
        $this->session->set('admin_id', $admin['id']);
        $this->session->set('admin_nome', $admin['nome']);
        $this->session->set('admin_email', $admin['email']);

        // Regenera o token CSRF (evita fixação de token)
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        // Log de sucesso
        $this->logger->info('Login de administrador bem-sucedido', [
            'email' => $admin['email'],
            'ip'    => $clientIp,
        ]);

        return ['sucesso' => true];
    }

    /**
     * Realiza logout (destrói a sessão) e registra a ação.
     *
     * @param string $clientIp IP do cliente (para logs)
     */
    public function logout(string $clientIp): void
    {
        // Obtém o email do admin antes de destruir a sessão
        $adminEmail = $this->session->get('admin_email') ?? 'unknown';

        // Log de logout
        $this->logger->info('Logout de administrador', [
            'email' => $adminEmail,
            'ip'    => $clientIp,
        ]);

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