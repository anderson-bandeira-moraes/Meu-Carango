<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Contracts\SessionInterface;
use App\Repository\TwoFactorRepository;
use Monolog\Logger;

/**
 * Serviço responsável pela lógica de autenticação em duas etapas (2FA).
 * Gerencia geração, envio, verificação, reenvio e status de códigos 2FA.
 */
class TwoFactorService
{
    private int $expiryMinutes;
    private int $maxAttempts;
    private int $maxResend;
    private int $resendBlockMinutes;

    public function __construct(
        private TwoFactorRepository $repository,
        private MailService $mailService,
        private SessionInterface $session,
        private Logger $logger,
    ) {
        $this->expiryMinutes = (int) ($_ENV['TWO_FACTOR_EXPIRY_MINUTES'] ?? 5);
        $this->maxAttempts = (int) ($_ENV['TWO_FACTOR_MAX_ATTEMPTS'] ?? 3);
        $this->maxResend = (int) ($_ENV['TWO_FACTOR_MAX_RESEND'] ?? 3);
        $this->resendBlockMinutes = (int) ($_ENV['TWO_FACTOR_RESEND_BLOCK_MINUTES'] ?? 30);
    }

    /**
     * Gera um código 2FA, salva no banco e envia por e-mail.
     * Cada geração (login) conta como uma tentativa de envio para o bloqueio.
     *
     * @param string $email E-mail do administrador
     * @return array{success: bool, error?: string}
     */
    public function generateAndSend(string $email): array
    {
        try {
            // Verifica se o bloqueio expirou e, se sim, reseta o contador
            $this->resetCounterIfBlockedExpired($email);

            $code = $this->generateCode();

            // Salva o código no banco (preserva resend_count)
            $this->repository->save($email, $code, $this->expiryMinutes);

            // Incrementa o contador de envios (login conta como 1 envio)
            $this->repository->incrementResendCount($email, $this->maxResend, $this->resendBlockMinutes);

            // --- LOG: resend_count incrementado (login) ---
            $record = $this->repository->findByEmail($email);
            $this->logger->debug('resend_count incrementado (login)', [
                'email' => $email,
                'new_count' => $record['resend_count'] ?? 'unknown',
            ]);

            // Envia o e-mail
            $sent = $this->mailService->sendTwoFactorCode($email, $code, $this->expiryMinutes);

            if (!$sent) {
                $this->logger->error('Falha ao enviar código 2FA por e-mail', ['email' => $email]);
                return [
                    'success' => false,
                    'error' => 'Não foi possível enviar o código por e-mail. Tente novamente.',
                ];
            }

            $this->logger->info('Código 2FA gerado e enviado com sucesso', ['email' => $email]);
            return ['success' => true];

        } catch (\Throwable $e) {
            $this->logger->error('Erro ao gerar/enviar código 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Erro interno ao gerar código. Tente novamente.',
            ];
        }
    }

    /**
     * Verifica o código informado pelo usuário.
     *
     * @param string $email E-mail do administrador
     * @param string $code  Código de 6 dígitos informado
     * @return array{success: bool, error?: string, attempts_left?: int}
     */
    public function verify(string $email, string $code): array
    {
        try {
            $record = $this->repository->findByEmail($email);

            // Se não há registro ativo
            if (!$record) {
                return [
                    'success' => false,
                    'error' => 'Nenhum código ativo encontrado.',
                ];
            }

            // Verifica expiração (mantém reset, pois é expiração)
            if (strtotime($record['expires_at']) < time()) {
                $this->logger->warning('Código 2FA expirado', ['email' => $email]);
                return [
                    'success' => false,
                    'error' => 'Código expirado. Clique em "Reenviar" para obter um novo código.',
                ];
            }

            // Verifica se já excedeu o limite de tentativas
            if ((int) $record['attempts'] >= $this->maxAttempts) {
                // Mantém o registro, não remove
                $this->logger->warning('Tentativas de verificação 2FA excedidas', [
                    'email' => $email,
                    'attempts' => $record['attempts'],
                ]);
                return [
                    'success'   => false,
                    'error'     => 'Número máximo de tentativas excedido.',
                    'exhausted' => true,
                ];
            }

            // Compara o código
            if ($code !== $record['code']) {
                $this->repository->incrementAttempts($email);
                $attemptsLeft = $this->maxAttempts - ((int) $record['attempts'] + 1);

                // Se após incrementar atingir o limite, mantém o registro
                if ($attemptsLeft <= 0) {
                    $this->logger->warning('Tentativas de verificação 2FA esgotadas', [
                        'email' => $email,
                    ]);
                    return [
                        'success'   => false,
                        'error'     => 'Número máximo de tentativas excedido.',
                        'exhausted' => true,
                    ];
                }

                $this->logger->warning('Código 2FA inválido', [
                    'email' => $email,
                    'attempts_left' => $attemptsLeft,
                ]);

                return [
                    'success' => false,
                    'error' => "Código inválido. Você tem {$attemptsLeft} tentativa(s) restante(s).",
                    'attempts_left' => $attemptsLeft,
                ];
            }

            // Código válido: remove registro e marca verificação concluída
            $this->repository->reset($email);
            $this->session->set('2fa_verified', true);

            $this->logger->info('Verificação 2FA concluída com sucesso', ['email' => $email]);

            return ['success' => true];

        } catch (\Throwable $e) {
            $this->logger->error('Erro ao verificar código 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Erro interno ao verificar código. Tente novamente.',
            ];
        }
    }

    /**
     * Reenvia um novo código 2FA para o e-mail do administrador.
     * Respeita limites de reenvio e bloqueio.
     *
     * @param string $email E-mail do administrador
     * @return array{success: bool, error?: string, blocked_until?: string}
     */
    public function resend(string $email): array
    {
        try {
            // Verifica se o bloqueio expirou e, se sim, reseta o contador
            $this->resetCounterIfBlockedExpired($email);

            // Verifica se está bloqueado para reenvio
            if ($this->repository->isBlockedFromResend($email)) {
                $blockedUntil = $this->getBlockedUntil($email);
                $minutesLeft = $this->calculateRemainingMinutes($blockedUntil);

                $this->logger->warning('Tentativa de reenvio 2FA bloqueada', [
                    'email' => $email,
                    'blocked_until' => $blockedUntil,
                ]);

                return [
                    'success' => false,
                    'error' => "Reenvio bloqueado. Tente novamente em {$minutesLeft} minutos.",
                    'blocked_until' => $blockedUntil,
                ];
            }

            // Gera novo código
            $newCode = $this->generateCode();
            $this->repository->save($email, $newCode, $this->expiryMinutes);

            // Incrementa contador de reenvios
            $this->repository->incrementResendCount($email, $this->maxResend, $this->resendBlockMinutes);

            // --- LOG: resend_count incrementado (reenvio) ---
            $record = $this->repository->findByEmail($email);
            $this->logger->debug('resend_count incrementado (reenvio)', [
                'email' => $email,
                'new_count' => $record['resend_count'] ?? 'unknown',
            ]);

            // Envia e-mail
            $sent = $this->mailService->sendTwoFactorCode($email, $newCode, $this->expiryMinutes);

            if (!$sent) {
                $this->logger->error('Falha ao reenviar código 2FA', ['email' => $email]);
                return [
                    'success' => false,
                    'error' => 'Não foi possível reenviar o código. Tente novamente.',
                ];
            }

            // Registra o reenvio bem-sucedido (sem aviso de bloqueio)
            $record = $this->repository->findByEmail($email);
            $this->logger->info('Código 2FA reenviado com sucesso', [
                'email' => $email,
                'resend_count' => $record['resend_count'] ?? '?',
                'blocked_until' => $record['blocked_until'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Novo código enviado. Verifique sua caixa de e-mail.',
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Erro ao reenviar código 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Erro interno ao reenviar código. Tente novamente.',
            ];
        }
    }

    /**
     * Retorna o status atual do processo 2FA para o e-mail.
     *
     * @param string $email
     * @return array{exists: bool, expires_at?: string, attempts?: int, attempts_left?: int, is_blocked?: bool, blocked_until?: string}
     */
    public function getStatus(string $email): array
    {
        try {
            $record = $this->repository->findByEmail($email);

            if (!$record) {
                return ['exists' => false];
            }

            $attempts = (int) $record['attempts'];
            $isBlocked = $record['blocked_until'] !== null && strtotime($record['blocked_until']) > time();

            return [
                'exists' => true,
                'expires_at' => $record['expires_at'],
                'attempts' => $attempts,
                'is_blocked' => $isBlocked,
                'blocked_until' => $record['blocked_until'],
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Erro ao obter status 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return ['exists' => false];
        }
    }

    /**
     * Remove o registro 2FA para o e-mail (após login bem-sucedido ou limpeza manual).
     *
     * @param string $email
     * @return void
     */
    public function reset(string $email): void
    {
        try {
            $this->repository->reset($email);
            $this->logger->info('Registro 2FA removido manualmente', ['email' => $email]);
        } catch (\Throwable $e) {
            $this->logger->error('Falha ao resetar registro 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove registros expirados (pode ser chamado periodicamente, ex: após verificação).
     *
     * @return void
     */
    public function cleanup(): void
    {
        try {
            $this->repository->deleteExpired();
        } catch (\Throwable $e) {
            $this->logger->error('Falha na limpeza de registros 2FA expirados', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gera um código aleatório de 6 dígitos.
     *
     * @return string
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Obtém o valor de blocked_until para o e-mail.
     *
     * @param string $email
     * @return string|null
     */
    private function getBlockedUntil(string $email): ?string
    {
        $record = $this->repository->findByEmail($email);
        return $record['blocked_until'] ?? null;
    }

    /**
     * Calcula quantos minutos faltam para o bloqueio expirar.
     *
     * @param string $blockedUntil
     * @return int
     */
    private function calculateRemainingMinutes(string $blockedUntil): int
    {
        $diff = strtotime($blockedUntil) - time();
        return max(0, (int) ceil($diff / 60));
    }

    /**
     * Obtém o registro 2FA completo para o e-mail informado.
     *
     * Este método é útil para verificar o status atual do código (expiração,
     * tentativas, bloqueio) sem remover pendências ou alterar o estado.
     *
     * @param string $email E-mail do administrador
     * @return array|null Retorna o registro como array associativo ou null se não encontrado
     */
    public function getRecord(string $email): ?array
    {
        try {
            return $this->repository->findByEmail($email);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao buscar registro 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verifica se o reenvio de código 2FA está bloqueado para o e-mail informado.
     *
     * @param string $email
     * @return bool
     */
    public function isResendBlocked(string $email): bool
    {
        return $this->repository->isBlockedFromResend($email);
    }

    /**
     * Verifica se o bloqueio de reenvio expirou e, se sim, reseta o contador.
     * Esta lógica é centralizada para ser reutilizada em generateAndSend() e resend().
     *
     * @param string $email
     * @return void
     */
    private function resetCounterIfBlockedExpired(string $email): void
    {
        try {
            $record = $this->repository->findByEmail($email);
            if (!$record) {
                return;
            }

            $blockedUntil = $record['blocked_until'] ?? null;
            if ($blockedUntil !== null && strtotime($blockedUntil) < time()) {
                $this->repository->resetResendCounter($email);
                $this->logger->info('Bloqueio de reenvio expirado, contador resetado para 0', [
                    'email' => $email,
                    'blocked_until' => $blockedUntil,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao verificar expiração do bloqueio de reenvio', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            // Não relançamos a exceção para não quebrar o fluxo
        }
    }
}