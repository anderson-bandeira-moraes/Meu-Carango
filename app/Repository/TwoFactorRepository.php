<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;
use Monolog\Logger;

/**
 * Repositório para gerenciar códigos de verificação em duas etapas (2FA).
 */
class TwoFactorRepository
{
    public function __construct(
        private PDO $pdo,
        private Logger $logger,
    ) {}

    /**
     * Salva ou atualiza um código 2FA para o e-mail informado.
     * Se já existir, sobrescreve com os novos valores.
     */
    public function save(string $email, string $code, int $expiryMinutes): void
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));

            $sql = '
                INSERT INTO two_factor_codes (email, code, expires_at, attempts, resend_count, blocked_until)
                VALUES (?, ?, ?, 0, 0, NULL)
                ON DUPLICATE KEY UPDATE
                    code = VALUES(code),
                    expires_at = VALUES(expires_at),
                    attempts = 0,
                    blocked_until = NULL,
                    updated_at = CURRENT_TIMESTAMP
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $code, $expiresAt]);

            $this->logger->debug('Código 2FA salvo/atualizado', [
                'email' => $email,
                'expires_at' => $expiresAt,
            ]);

        } catch (PDOException $e) {
            $this->logger->error('Falha ao salvar código 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Erro ao salvar código 2FA: ' . $e->getMessage());
        }
    }

    /**
     * Busca o registro ativo para o e-mail.
     * Retorna null se não existir.
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, email, code, expires_at, attempts, resend_count, blocked_until
                FROM two_factor_codes
                WHERE email = ?
                LIMIT 1
            ');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;

        } catch (PDOException $e) {
            $this->logger->error('Falha ao buscar código 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Incrementa o contador de tentativas de verificação.
     */
    public function incrementAttempts(string $email): void
    {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE two_factor_codes
                SET attempts = attempts + 1, updated_at = CURRENT_TIMESTAMP
                WHERE email = ?
            ');
            $stmt->execute([$email]);

        } catch (PDOException $e) {
            $this->logger->error('Falha ao incrementar tentativas 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Erro ao incrementar tentativas 2FA: ' . $e->getMessage());
        }
    }

    /**
     * Incrementa o contador de reenvios.
     * Se o limite for atingido, define blocked_until para 30 minutos.
     */
    public function incrementResendCount(string $email, int $maxResend, int $blockMinutes): void
    {
        try {
            // Primeiro, obtém o resend_count atual
            $stmt = $this->pdo->prepare('
                SELECT resend_count FROM two_factor_codes WHERE email = ?
            ');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $newCount = ($row ? (int) $row['resend_count'] : 0) + 1;

            $blockedUntil = null;
            if ($newCount >= $maxResend) {
                $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$blockMinutes} minutes"));
                $this->logger->warning('Bloqueio de reenvio 2FA ativado', [
                    'email' => $email,
                    'resend_count' => $newCount,
                    'blocked_until' => $blockedUntil,
                ]);
            }

            $stmt = $this->pdo->prepare('
                UPDATE two_factor_codes
                SET resend_count = ?, blocked_until = ?, updated_at = CURRENT_TIMESTAMP
                WHERE email = ?
            ');
            $stmt->execute([$newCount, $blockedUntil, $email]);

        } catch (PDOException $e) {
            $this->logger->error('Falha ao incrementar reenvio 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Erro ao incrementar reenvio 2FA: ' . $e->getMessage());
        }
    }

    /**
     * Remove o registro da tabela (após login bem-sucedido).
     */
    public function reset(string $email): void
    {
        try {
            $stmt = $this->pdo->prepare('
                DELETE FROM two_factor_codes WHERE email = ?
            ');
            $stmt->execute([$email]);

            $this->logger->info('Registro 2FA removido após login bem-sucedido', [
                'email' => $email,
            ]);

        } catch (PDOException $e) {
            $this->logger->error('Falha ao remover registro 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Erro ao remover registro 2FA: ' . $e->getMessage());
        }
    }

    /**
     * Remove registros com expires_at < NOW() (limpeza automática).
     * Pode ser chamado periodicamente (ex: via cron).
     */
    public function deleteExpired(): void
    {
        try {
            $stmt = $this->pdo->prepare('
                DELETE FROM two_factor_codes
                WHERE expires_at < NOW()
            ');
            $stmt->execute();

            $deleted = $stmt->rowCount();
            if ($deleted > 0) {
                $this->logger->info('Registros 2FA expirados removidos', [
                    'deleted' => $deleted,
                ]);
            }

        } catch (PDOException $e) {
            $this->logger->error('Falha ao remover registros 2FA expirados', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica se o reenvio está bloqueado para o e-mail.
     */
    public function isBlockedFromResend(string $email): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT blocked_until
                FROM two_factor_codes
                WHERE email = ?
                  AND blocked_until IS NOT NULL
                  AND blocked_until > NOW()
                LIMIT 1
            ');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row !== false;

        } catch (PDOException $e) {
            $this->logger->error('Falha ao verificar bloqueio de reenvio 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false; // Em caso de erro, permitir reenvio (fallback seguro)
        }
    }

    /**
     * Retorna o número atual de tentativas de verificação.
     */
    public function getAttempts(string $email): int
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT attempts FROM two_factor_codes WHERE email = ?
            ');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? (int) $row['attempts'] : 0;

        } catch (PDOException $e) {
            $this->logger->error('Falha ao obter tentativas 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}