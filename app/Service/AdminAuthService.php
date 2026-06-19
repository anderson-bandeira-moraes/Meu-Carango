<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Contracts\SessionInterface;
use App\Repository\AdministradorRepository;
use App\Repository\LoginAttemptRepository;
use Monolog\Logger;

class AdminAuthService
{
    public function __construct(
        private AdministradorRepository $adminRepository,
        private SessionInterface $session,
        private Logger $logger,
        private LoginAttemptRepository $attemptRepository, 
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
        // --- 4.6 Verificar se o bloqueio expirou e resetar automaticamente ---
        $attemptData = $this->attemptRepository->getAttempts($email, $clientIp);
        if ($attemptData && $attemptData['blocked_until'] !== null && strtotime($attemptData['blocked_until']) < time()) {
            // Bloqueio expirou → resetar contador e logar
            $this->attemptRepository->resetAttempts($email, $clientIp);
            $this->logger->info('Bloqueio expirado, contador de tentativas resetado', [
                'email' => $email,
                'ip'    => $clientIp,
                'previous_attempts' => $attemptData['attempts'],
            ]);
            // Após resetar, a verificação de bloqueio (próximo passo) será false
        }

        // --- 4.2 Verificar bloqueio antes da consulta ao banco ---
        if ($this->attemptRepository->isBlocked($email, $clientIp)) {
            $this->logger->warning('Tentativa de login durante bloqueio ativo', [
                'email' => $email,
                'ip'    => $clientIp,
            ]);
            return [
                'sucesso' => false,
                'erro'    => 'Muitas tentativas de login. Tente novamente mais tarde.',
            ];
        }

        $admin = $this->adminRepository->findByEmail($email);

        // --- Falha de login (credenciais inválidas) ---
        if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
            // --- 4.3 Registrar falha ---
            $this->attemptRepository->recordFailedAttempt($email, $clientIp);

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

        // --- 4.4 Resetar tentativas em caso de sucesso ---
        $this->attemptRepository->resetAttempts($email, $clientIp);

        // Limpeza de registros antigos (a cada login bem-sucedido)
        $this->attemptRepository->deleteOldRecords();

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