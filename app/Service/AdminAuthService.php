<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Contracts\SessionInterface;
use App\Repository\AdministradorRepository;
use App\Repository\LoginAttemptRepository;
use Psr\Log\LoggerInterface;

class AdminAuthService
{
    public function __construct(
        private AdministradorRepository $adminRepository,
        private SessionInterface $session,
        private LoggerInterface $logger,
        private LoginAttemptRepository $attemptRepository,
        private TwoFactorService $twoFactorService,
    ) {}

    /**
     * Tenta realizar o login do administrador.
     *
     * @param string $email
     * @param string $senha (plain text)
     * @param string $clientIp IP do cliente (para logs)
     * @return array{sucesso: bool, erro?: string, 2fa_required?: bool}
     */
    public function login(string $email, string $senha, string $clientIp): array
    {
        // --- 4.6 Verificar se o bloqueio expirou e resetar automaticamente ---
        $attemptData = $this->attemptRepository->getAttempts($email, $clientIp);
        if ($attemptData && $attemptData['blocked_until'] !== null && strtotime($attemptData['blocked_until']) < time()) {
            $this->attemptRepository->resetAttempts($email, $clientIp);
            $this->logger->info('Bloqueio expirado, contador de tentativas resetado', [
                'email' => $email,
                'ip'    => $clientIp,
                'previous_attempts' => $attemptData['attempts'],
            ]);
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

        // --- Verifica se há bloqueio de reenvio ativo na tabela two_factor_codes ---
        $twoFactorRecord = $this->twoFactorService->getRecord($email);
        if ($twoFactorRecord && $twoFactorRecord['blocked_until'] !== null && strtotime($twoFactorRecord['blocked_until']) > time()) {
            // --- LOG OPCIONAL (DEBUG) para rastreamento ---
            $this->logger->debug('Bloqueio de reenvio detectado antes do login', [
                'email' => $email,
                'blocked_until' => $twoFactorRecord['blocked_until'],
            ]);

            $minutesLeft = ceil((strtotime($twoFactorRecord['blocked_until']) - time()) / 60);
            $this->logger->warning('Tentativa de login durante bloqueio de reenvio 2FA', [
                'email' => $email,
                'blocked_until' => $twoFactorRecord['blocked_until'],
                'minutes_left' => $minutesLeft,
            ]);
            return [
                'sucesso' => false,
                'erro'    => "Você está bloqueado para reenviar códigos de verificação. Aguarde {$minutesLeft} minutos para tentar novamente.",
            ];
        }

        // --- Senha correta: inicia fluxo 2FA ---
        // Armazena dados pendentes na sessão
        $this->session->set('pending_admin_id', $admin['id']);
        $this->session->set('pending_admin_email', $admin['email']);
        $this->session->set('pending_admin_nome', $admin['nome']);

        // Gera e envia código 2FA
        $result = $this->twoFactorService->generateAndSend($admin['email']);

        if (!$result['success']) {
            // Limpa pendências em caso de falha
            $this->session->delete('pending_admin_id');
            $this->session->delete('pending_admin_email');
            $this->session->delete('pending_admin_nome');

            $this->logger->error('Falha ao enviar código 2FA', [
                'email' => $admin['email'],
                'error' => $result['error'] ?? 'unknown',
            ]);

            return [
                'sucesso' => false,
                'erro'    => 'Erro ao enviar código de verificação. Tente novamente.',
            ];
        }

        // Reseta tentativas de login (sucesso parcial)
        $this->attemptRepository->resetAttempts($email, $clientIp);
        $this->attemptRepository->deleteOldRecords();

        $this->logger->info('Login parcial: 2FA iniciado', [
            'email' => $admin['email'],
            'ip'    => $clientIp,
        ]);

        return [
            'sucesso'       => true,
            '2fa_required'  => true,
            'redirect'      => '/admin/2fa',
        ];
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