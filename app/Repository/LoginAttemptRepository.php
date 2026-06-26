<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para gerenciar tentativas de login (bloqueio por brute force).
 */
class LoginAttemptRepository
{
    private int $maxAttempts;
    private int $blockDurationMinutes;
    private int $retentionDays;

    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {
        $this->maxAttempts = (int) ($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5);
        $this->blockDurationMinutes = (int) ($_ENV['BLOCK_DURATION_MINUTES'] ?? 60);
        $this->retentionDays = (int) ($_ENV['LOGIN_ATTEMPTS_RETENTION_DAYS'] ?? 30);
    }

    /**
     * Registra uma tentativa falha para o e-mail e IP informados.
     * Se o limite for atingido, define blocked_until e registra log WARNING.
     */
    public function recordFailedAttempt(string $email, string $ip): void
    {
        try {
            // Verifica se já existe registro para este email+IP
            $stmt = $this->pdo->prepare('
                SELECT id, attempts, blocked_until
                FROM login_attempts
                WHERE email = ? AND ip_address = ?
            ');
            $stmt->execute([$email, $ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Se já estiver bloqueado, apenas atualiza last_attempt_at e loga
                if ($row['blocked_until'] !== null && strtotime($row['blocked_until']) > time()) {
                    $this->updateLastAttempt($row['id']);
                    $this->logger->info('Tentativa bloqueada (ainda em bloqueio)', [
                        'email' => $email,
                        'ip' => $ip,
                        'blocked_until' => $row['blocked_until'],
                    ]);
                    return;
                }

                $newAttempts = $row['attempts'] + 1;
                $blockedUntil = null;
                $wasBlocked = false;

                if ($newAttempts >= $this->maxAttempts) {
                    $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$this->blockDurationMinutes} minutes"));
                    $wasBlocked = true;
                }

                $stmt = $this->pdo->prepare('
                    UPDATE login_attempts
                    SET attempts = ?, last_attempt_at = NOW(), blocked_until = ?
                    WHERE id = ?
                ');
                $stmt->execute([$newAttempts, $blockedUntil, $row['id']]);

                if ($wasBlocked) {
                    $this->logger->warning('Bloqueio ativado por excesso de tentativas', [
                        'email' => $email,
                        'ip' => $ip,
                        'attempts' => $newAttempts,
                        'blocked_until' => $blockedUntil,
                        'max_attempts' => $this->maxAttempts,
                    ]);
                }

            } else {
                // Primeira tentativa para este email+IP
                $stmt = $this->pdo->prepare('
                    INSERT INTO login_attempts (email, ip_address, attempts, last_attempt_at)
                    VALUES (?, ?, 1, NOW())
                ');
                $stmt->execute([$email, $ip]);

                $this->logger->debug('Primeira tentativa falha registrada', [
                    'email' => $email,
                    'ip' => $ip,
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error('Falha ao registrar tentativa de login', [
                'email' => $email,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Erro ao registrar tentativa de login: ' . $e->getMessage());
        }
    }

    /**
     * Verifica se o par email+IP está bloqueado no momento.
     */
    public function isBlocked(string $email, string $ip): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT blocked_until
                FROM login_attempts
                WHERE email = ? AND ip_address = ?
                  AND blocked_until IS NOT NULL
                  AND blocked_until > NOW()
                LIMIT 1
            ');
            $stmt->execute([$email, $ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $this->logger->debug('Tentativa de login bloqueada detectada', [
                    'email' => $email,
                    'ip' => $ip,
                    'blocked_until' => $row['blocked_until'],
                ]);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            $this->logger->error('Falha ao verificar bloqueio de login', [
                'email' => $email,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            // Em caso de erro, consideramos não bloqueado para não prejudicar o usuário
            return false;
        }
    }

    /**
     * Reseta tentativas e bloqueio para o par email+IP (após login bem-sucedido).
     */
    public function resetAttempts(string $email, string $ip): void
    {
        try {
            // Primeiro, verifica se havia bloqueio ativo
            $stmt = $this->pdo->prepare('
                SELECT blocked_until
                FROM login_attempts
                WHERE email = ? AND ip_address = ?
                LIMIT 1
            ');
            $stmt->execute([$email, $ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $wasBlocked = ($row && $row['blocked_until'] !== null && strtotime($row['blocked_until']) > time());

            $stmt = $this->pdo->prepare('
                UPDATE login_attempts
                SET attempts = 0, blocked_until = NULL, updated_at = NOW()
                WHERE email = ? AND ip_address = ?
            ');
            $stmt->execute([$email, $ip]);

            if ($wasBlocked) {
                $this->logger->info('Bloqueio removido após login bem-sucedido', [
                    'email' => $email,
                    'ip' => $ip,
                ]);
            } else {
                $this->logger->debug('Tentativas resetadas após login bem-sucedido', [
                    'email' => $email,
                    'ip' => $ip,
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error('Falha ao resetar tentativas de login', [
                'email' => $email,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Erro ao resetar tentativas: ' . $e->getMessage());
        }
    }

    /**
     * Remove registros mais antigos que X dias (padrão: 30).
     * Registra log INFO com a quantidade removida.
     */
    public function deleteOldRecords(?int $days = null): void
    {
        $days = $days ?? $this->retentionDays; // usa o valor do .env ou o passado
        try {
            $stmt = $this->pdo->prepare('
                DELETE FROM login_attempts
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                   OR updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ');
            $stmt->execute([$days, $days]);

            $deleted = $stmt->rowCount();
            if ($deleted > 0) {
                $this->logger->info('Registros antigos removidos (limpeza automática)', [
                    'deleted' => $deleted,
                    'days' => $days,
                ]);
            } else {
                $this->logger->debug('Nenhum registro antigo para remover', [
                    'days' => $days,
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error('Falha ao limpar registros antigos de tentativas', [
                'error' => $e->getMessage(),
                'days' => $days,
            ]);
        }
    }

    /**
     * Retorna os dados da tentativa (para depuração).
     */
    public function getAttempts(string $email, string $ip): ?array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT attempts, last_attempt_at, blocked_until
                FROM login_attempts
                WHERE email = ? AND ip_address = ?
                LIMIT 1
            ');
            $stmt->execute([$email, $ip]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Falha ao buscar dados de tentativas', [
                'email' => $email,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Atualiza apenas a data da última tentativa (quando já bloqueado).
     */
    private function updateLastAttempt(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE login_attempts
                SET last_attempt_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error('Falha ao atualizar data da última tentativa', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}