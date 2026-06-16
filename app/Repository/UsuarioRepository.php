<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

/**
 * Repositório para a entidade Usuário (lojista).
 * Gerencia todas as operações de banco de dados relacionadas a usuários.
 */
class UsuarioRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Busca um usuário pelo e-mail.
     *
     * @param string $email E-mail do usuário.
     * @return array|null Dados do usuário (associativo) ou null se não encontrado.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca um usuário pelo slug (identificador da loja).
     *
     * @param string $slug Slug único da loja.
     * @return array|null Dados do usuário ou null se não encontrado.
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE slug = ?');
        $stmt->execute([$slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca um usuário pelo ID.
     *
     * @param int $id ID do usuário.
     * @return array|null Dados do usuário ou null se não encontrado.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Cria um novo usuário (lojista).
     *
     * @param array $dados Campos obrigatórios: nome, email, senha_hash, nome_loja, slug, telefone, plano, status.
     * @return int|false ID do usuário criado ou false em caso de erro.
     */
    public function create(array $dados): int|false
    {
        $sql = 'INSERT INTO usuarios 
                (nome, email, senha_hash, nome_loja, slug, telefone, plano, status, criado_em, atualizado_em) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute([
                $dados['nome'],
                $dados['email'],
                $dados['senha_hash'],
                $dados['nome_loja'],
                $dados['slug'],
                $dados['telefone'],
                $dados['plano'],
                $dados['status'],
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Log do erro pode ser adicionado aqui
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
        $stmt = $this->pdo->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?');
        return $stmt->execute([$userId]);
    }

    /**
     * Atualiza dados genéricos do usuário.
     *
     * @param int $userId ID do usuário.
     * @param array $dados Colunas e valores a serem atualizados (ex: ['status' => 'inativo']).
     * @return bool True se atualizou, false em caso de erro.
     */
    public function update(int $userId, array $dados): bool
    {
        if (empty($dados)) {
            return false;
        }

        $fields = [];
        $values = [];

        foreach ($dados as $coluna => $valor) {
            $fields[] = "$coluna = ?";
            $values[] = $valor;
        }
        $values[] = $userId;

        $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ', atualizado_em = NOW() WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Verifica se existe um e-mail já cadastrado.
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Verifica se existe um slug já cadastrado.
     *
     * @param string $slug
     * @return bool
     */
    public function slugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetchColumn() !== false;
    }
}