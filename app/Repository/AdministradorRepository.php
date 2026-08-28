<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class AdministradorRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Busca um administrador pelo e-mail.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM administradores WHERE email = ?');
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->logger->debug('Administrador encontrado por e-mail', ['email' => $email]);
            } else {
                $this->logger->debug('Administrador não encontrado por e-mail', ['email' => $email]);
            }
            
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar administrador por e-mail', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca um administrador pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM administradores WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->logger->debug('Administrador encontrado por ID', ['id' => $id]);
            } else {
                $this->logger->debug('Administrador não encontrado por ID', ['id' => $id]);
            }
            
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar administrador por ID', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}