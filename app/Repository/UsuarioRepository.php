<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a entidade Usuário (lojista).
 * Gerencia todas as operações de banco de dados relacionadas a usuários.
 */
class UsuarioRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Busca um usuário pelo e-mail.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar usuário por e-mail', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca um usuário pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar usuário por ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca um usuário pelo slug (identificador da loja).
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE slug = ?');
            $stmt->execute([$slug]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar usuário por slug', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verifica se um e-mail já está cadastrado.
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao verificar existência de e-mail', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se um slug já está em uso.
     *
     * @param string $slug
     * @return bool
     */
    public function slugExists(string $slug): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao verificar existência de slug', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cria um novo usuário (lojista).
     *
     * @param array $data Campos obrigatórios: nome, email, senha_hash, nome_loja, slug, telefone.
     *                    Opcionais: plano (padrão: 'teste'), status (padrão: 'ativo').
     * @return int|false ID do usuário criado ou false em caso de erro.
     */
    public function create(array $data): int|false
    {
        try {
            // Define valores padrão
            $plano = $data['plano'] ?? 'teste';
            $status = $data['status'] ?? 'ativo';

            $sql = 'INSERT INTO usuarios 
                    (nome, email, senha_hash, nome_loja, slug, telefone, plano, status, criado_em, atualizado_em) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['nome'],
                $data['email'],
                $data['senha_hash'],
                $data['nome_loja'],
                $data['slug'],
                $data['telefone'],
                $plano,
                $status,
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $this->logger->info('Usuário criado com sucesso', [
                'id' => $id,
                'email' => $data['email'],
                'slug' => $data['slug'],
            ]);
            return $id;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao criar usuário', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza dados de um usuário.
     *
     * @param int $userId ID do usuário.
     * @param array $data Colunas e valores a serem atualizados (ex: ['status' => 'inativo']).
     * @return bool True se atualizou, false em caso de erro.
     */
    public function update(int $userId, array $data): bool
    {
        if (empty($data)) {
            $this->logger->warning('Tentativa de atualizar usuário sem dados', ['user_id' => $userId]);
            return false;
        }

        try {
            $fields = [];
            $values = [];

            foreach ($data as $coluna => $valor) {
                $fields[] = "$coluna = ?";
                $values[] = $valor;
            }
            $values[] = $userId;

            $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ', atualizado_em = NOW() WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($values);

            if ($success) {
                $this->logger->info('Usuário atualizado com sucesso', [
                    'user_id' => $userId,
                    'fields' => array_keys($data),
                ]);
            } else {
                $this->logger->warning('Falha ao atualizar usuário', ['user_id' => $userId]);
            }
            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao atualizar usuário', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza a data/hora do último login do usuário.
     *
     * @param int $userId ID do usuário.
     * @return bool True se atualizou, false em caso de erro.
     */
    public function updateLastLogin(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?');
            $success = $stmt->execute([$userId]);

            if ($success) {
                $this->logger->debug('Último login atualizado', ['user_id' => $userId]);
            }
            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao atualizar último login', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}