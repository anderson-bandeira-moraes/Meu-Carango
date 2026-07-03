<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a entidade Marca.
 * Gerencia todas as operações de banco de dados relacionadas a marcas de veículos.
 */
class MarcaRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Retorna todas as marcas ordenadas por nome.
     *
     * @return array
     */
    public function findAll(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT * FROM marcas ORDER BY nome ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar todas as marcas', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Busca uma marca pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM marcas WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar marca por ID', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca uma marca pelo nome exato.
     *
     * @param string $nome
     * @return array|null
     */
    public function findByNome(string $nome): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM marcas WHERE nome = ?');
            $stmt->execute([$nome]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar marca por nome', [
                'nome'  => $nome,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca uma marca pelo slug.
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM marcas WHERE slug = ?');
            $stmt->execute([$slug]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar marca por slug', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verifica se um nome de marca já existe.
     *
     * @param string $nome
     * @return bool
     */
    public function nomeExists(string $nome): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM marcas WHERE nome = ? LIMIT 1');
            $stmt->execute([$nome]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao verificar existência de nome de marca', [
                'nome'  => $nome,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se um slug de marca já existe.
     *
     * @param string $slug
     * @return bool
     */
    public function slugExists(string $slug): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM marcas WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao verificar existência de slug de marca', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Insere uma nova marca.
     *
     * @param array $dados Deve conter 'nome' e 'slug'.
     * @return int|false ID da marca inserida ou false em caso de erro.
     */
    public function save(array $dados): int|false
    {
        try {
            $sql = 'INSERT INTO marcas (nome, slug) VALUES (:nome, :slug)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $dados['nome'] ?? null,
                ':slug' => $dados['slug'] ?? null,
            ]);

            $id = (int) $this->pdo->lastInsertId();

            $this->logger->info('Marca criada com sucesso', [
                'id'   => $id,
                'nome' => $dados['nome'],
                'slug' => $dados['slug'],
            ]);

            return $id;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao criar marca', [
                'nome'  => $dados['nome'] ?? 'unknown',
                'slug'  => $dados['slug'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza uma marca existente.
     *
     * @param int $id
     * @param array $dados
     * @return bool
     */
    public function update(int $id, array $dados): bool
    {
        if (empty($dados)) {
            $this->logger->warning('Tentativa de atualizar marca sem dados', ['marca_id' => $id]);
            return false;
        }

        try {
            $fields = [];
            $values = [];

            foreach ($dados as $coluna => $valor) {
                $fields[] = "$coluna = ?";
                $values[] = $valor;
            }

            $values[] = $id;

            $sql = 'UPDATE marcas SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($values);

            if ($success) {
                $this->logger->info('Marca atualizada com sucesso', [
                    'id'     => $id,
                    'fields' => array_keys($dados),
                ]);
            } else {
                $this->logger->warning('Falha ao atualizar marca', ['marca_id' => $id]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao atualizar marca', [
                'marca_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove uma marca (fisicamente).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            // Busca a marca para log antes de deletar
            $marca = $this->findById($id);

            $stmt = $this->pdo->prepare('DELETE FROM marcas WHERE id = ?');
            $success = $stmt->execute([$id]);

            if ($success) {
                $this->logger->info('Marca removida com sucesso', [
                    'marca_id' => $id,
                    'nome'     => $marca['nome'] ?? 'N/A',
                    'slug'     => $marca['slug'] ?? 'N/A',
                ]);
            } else {
                $this->logger->warning('Falha ao remover marca', ['marca_id' => $id]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover marca', [
                'marca_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }
}