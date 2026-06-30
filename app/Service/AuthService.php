<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Contracts\SessionInterface;
use App\Repository\UsuarioRepository;
use App\Repository\LoginAttemptRepository;
use App\Service\UserTwoFactorService;
use Psr\Log\LoggerInterface;

/**
 * Serviço de autenticação para lojistas.
 * Gerencia login, logout, verificação de status, limite de tentativas e 2FA.
 */
class AuthService
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private SessionInterface $session,
        private LoggerInterface $logger,
        private LoginAttemptRepository $attemptRepository,
        private UserTwoFactorService $twoFactorService,
    ) {}

    /**
     * Tenta realizar o login do lojista.
     *
     * @param string $email
     * @param string $senha (plain text)
     * @param string $clientIp IP do cliente (para logs)
     * @return array{sucesso: bool, erro?: string, 2fa_required?: bool}
     */
    public function login(string $email, string $senha, string $clientIp): array
    {
        // --- Verificar se o bloqueio expirou e resetar automaticamente ---
        $attemptData = $this->attemptRepository->getAttempts($email, $clientIp);
        if ($attemptData && $attemptData['blocked_until'] !== null && strtotime($attemptData['blocked_until']) < time()) {
            $this->attemptRepository->resetAttempts($email, $clientIp);
            $this->logger->info('Bloqueio expirado, contador de tentativas resetado', [
                'email' => $email,
                'ip'    => $clientIp,
                'previous_attempts' => $attemptData['attempts'],
            ]);
        }

        // --- Verificar bloqueio antes da consulta ao banco ---
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

        // --- Busca o lojista pelo e-mail ---
        $user = $this->usuarioRepository->findByEmail($email);

        // --- Usuário não encontrado: registra falha e retorna erro ---
        if (!$user) {
            $this->attemptRepository->recordFailedAttempt($email, $clientIp);
            $this->logger->warning('Tentativa de login falhou (lojista)', [
                'email'  => $email,
                'ip'     => $clientIp,
                'motivo' => 'Usuário não encontrado',
            ]);
            return [
                'sucesso' => false,
                'erro'    => 'E-mail ou senha inválidos.',
            ];
        }

        // --- Verifica se o lojista está ativo (ANTES de validar a senha) ---
        if (($user['status'] ?? '') !== 'ativo') {
            $this->logger->warning('Tentativa de login bloqueada (conta inativa)', [
                'email'  => $email,
                'ip'     => $clientIp,
                'status' => $user['status'] ?? 'unknown',
            ]);
            // NÃO registra tentativa falha para contas inativas
            return [
                'sucesso' => false,
                'erro'    => 'Conta inativa. Entre em contato com o suporte.',
            ];
        }

        // --- Valida a senha (usuário ativo) ---
        if (!password_verify($senha, $user['senha_hash'])) {
            $this->attemptRepository->recordFailedAttempt($email, $clientIp);
            $this->logger->warning('Tentativa de login falhou (lojista)', [
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
            $minutesLeft = ceil((strtotime($twoFactorRecord['blocked_until']) - time()) / 60);
            $this->logger->warning('Tentativa de login durante bloqueio de reenvio 2FA (lojista)', [
                'email' => $email,
                'blocked_until' => $twoFactorRecord['blocked_until'],
                'minutes_left' => $minutesLeft,
            ]);
            return [
                'sucesso' => false,
                'erro'    => "Você está bloqueado para reenviar códigos de verificação. Aguarde {$minutesLeft} minutos para tentar novamente.",
            ];
        }

        // --- Senha correta e conta ativa: inicia fluxo 2FA ---
        // Armazena dados pendentes na sessão
        $this->session->set('pending_user_id', $user['id']);
        $this->session->set('pending_user_email', $user['email']);
        $this->session->set('pending_user_nome', $user['nome']);
        $this->session->set('pending_user_slug', $user['slug']);

        // Normaliza e guarda o status (seguro)
        $userStatus = ($user['status'] ?? '') === 'ativo' ? 'ativo' : 'inativo';
        $this->session->set('pending_user_status', $userStatus);

        // Gera e envia código 2FA
        $result = $this->twoFactorService->generateAndSend($user['email']);

        if (!$result['success']) {
            // Limpa pendências em caso de falha
            $this->session->delete('pending_user_id');
            $this->session->delete('pending_user_email');
            $this->session->delete('pending_user_nome');
            $this->session->delete('pending_user_slug');
            $this->session->delete('pending_user_status');

            $this->logger->error('Falha ao enviar código 2FA (lojista)', [
                'email' => $user['email'],
                'error' => $result['error'] ?? 'unknown',
            ]);

            return [
                'sucesso' => false,
                'erro'    => 'Erro ao enviar código de verificação. Tente novamente.',
            ];
        }

        // Atualiza o último login (sucesso parcial)
        $this->usuarioRepository->updateLastLogin($user['id']);

        // Reseta tentativas de login (sucesso parcial)
        $this->attemptRepository->resetAttempts($email, $clientIp);
        $this->attemptRepository->deleteOldRecords();

        $this->logger->info('Login parcial: 2FA iniciado (lojista)', [
            'email' => $user['email'],
            'ip'    => $clientIp,
        ]);

        return [
            'sucesso'       => true,
            '2fa_required'  => true,
            'redirect'      => '/logista/2fa',
        ];
    }

    /**
     * Realiza logout (destrói a sessão) e registra a ação.
     *
     * @param string $clientIp IP do cliente (para logs)
     */
    public function logout(string $clientIp): void
    {
        // Obtém o email do lojista antes de destruir a sessão
        $userEmail = $this->session->get('user_email') ?? 'unknown';

        // Log de logout
        $this->logger->info('Logout de lojista', [
            'email' => $userEmail,
            'ip'    => $clientIp,
        ]);

        $this->session->destroy();
    }

    /**
     * Verifica se o lojista está autenticado.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->session->has('user_id');
    }

    /**
     * Retorna os dados do lojista logado atualmente (da sessão).
     *
     * @return array|null
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id'    => $this->session->get('user_id'),
            'nome'  => $this->session->get('user_nome'),
            'email' => $this->session->get('user_email'),
            'slug'  => $this->session->get('user_slug'),
        ];
    }
}