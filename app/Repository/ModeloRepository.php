<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a entidade Modelo.
 * Gerencia todas as operações de banco de dados relacionadas a modelos de veículos.
 */
class ModeloRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Retorna todos os modelos ordenados por nome.
     *
     * @return array
     */
    public function findAll(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT * FROM modelos ORDER BY nome ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar todos os modelos', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Retorna todos os modelos com os dados da marca associada (JOIN).
     * Útil para exibir em selects com o nome da marca.
     *
     * @return array
     */
    public function findAllWithMarca(): array
    {
        try {
            $sql = 'SELECT modelos.*, marcas.nome as marca_nome 
                    FROM modelos 
                    JOIN marcas ON modelos.marca_id = marcas.id 
                    ORDER BY marcas.nome, modelos.nome';
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar modelos com marca', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Busca um modelo pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM modelos WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar modelo por ID', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca um modelo pelo nome (global).
     *
     * @param string $nome
     * @return array|null
     */
    public function findByNome(string $nome): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM modelos WHERE nome = ?');
            $stmt->execute([$nome]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar modelo por nome', [
                'nome'  => $nome,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca um modelo pelo slug.
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM modelos WHERE slug = ?');
            $stmt->execute([$slug]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar modelo por slug', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca modelos de uma marca específica.
     *
     * @param int $marcaId
     * @return array
     */
    public function findByMarcaId(int $marcaId): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM modelos WHERE marca_id = ? ORDER BY nome ASC');
            $stmt->execute([$marcaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar modelos por marca_id', [
                'marca_id' => $marcaId,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Busca um modelo pelo par (marca_id, nome).
     * Útil para verificar duplicatas antes de salvar.
     *
     * @param int $marcaId
     * @param string $nome
     * @return array|null
     */
    public function findByMarcaIdAndNome(int $marcaId, string $nome): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM modelos WHERE marca_id = ? AND nome = ?');
            $stmt->execute([$marcaId, $nome]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar modelo por (marca_id, nome)', [
                'marca_id' => $marcaId,
                'nome'     => $nome,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verifica se um nome de modelo já existe (global).
     *
     * @param string $nome
     * @return bool
     */
    public function nomeExists(string $nome): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM modelos WHERE nome = ? LIMIT 1');
            $stmt->execute([$nome]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao verificar existência de nome de modelo', [
                'nome'  => $nome,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se um slug de modelo já existe.
     *
     * @param string $slug
     * @return bool
     */
    public function slugExists(string $slug): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM modelos WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao verificar existência de slug de modelo', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se já existe um modelo com o mesmo nome para uma determinada marca.
     *
     * @param int $marcaId
     * @param string $nome
     * @return bool
     */
    public function marcaIdAndNomeExists(int $marcaId, string $nome): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM modelos WHERE marca_id = ? AND nome = ? LIMIT 1');
            $stmt->execute([$marcaId, $nome]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao verificar existência de modelo por (marca_id, nome)', [
                'marca_id' => $marcaId,
                'nome'     => $nome,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Insere um novo modelo.
     *
     * @param array $dados Deve conter 'marca_id', 'nome' e opcionalmente 'slug'.
     * @return int|false ID do modelo inserido ou false em caso de erro.
     */
    public function save(array $dados): int|false
    {
        try {
            $sql = 'INSERT INTO modelos (marca_id, nome, slug) VALUES (:marca_id, :nome, :slug)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':marca_id' => $dados['marca_id'] ?? null,
                ':nome'     => $dados['nome'] ?? null,
                ':slug'     => $dados['slug'] ?? null,
            ]);

            $id = (int) $this->pdo->lastInsertId();

            $this->logger->info('Modelo criado com sucesso', [
                'id'       => $id,
                'marca_id' => $dados['marca_id'],
                'nome'     => $dados['nome'],
                'slug'     => $dados['slug'] ?? 'N/A',
            ]);

            return $id;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao criar modelo', [
                'marca_id' => $dados['marca_id'] ?? 'unknown',
                'nome'     => $dados['nome'] ?? 'unknown',
                'slug'     => $dados['slug'] ?? 'unknown',
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza um modelo existente.
     *
     * @param int $id
     * @param array $dados
     * @return bool
     */
    public function update(int $id, array $dados): bool
    {
        if (empty($dados)) {
            $this->logger->warning('Tentativa de atualizar modelo sem dados', ['modelo_id' => $id]);
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

            $sql = 'UPDATE modelos SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($values);

            if ($success) {
                $this->logger->info('Modelo atualizado com sucesso', [
                    'id'     => $id,
                    'fields' => array_keys($dados),
                ]);
            } else {
                $this->logger->warning('Falha ao atualizar modelo', ['modelo_id' => $id]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao atualizar modelo', [
                'modelo_id' => $id,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove um modelo (fisicamente).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            // Busca o modelo para log antes de deletar
            $modelo = $this->findById($id);

            $stmt = $this->pdo->prepare('DELETE FROM modelos WHERE id = ?');
            $success = $stmt->execute([$id]);

            if ($success) {
                $this->logger->info('Modelo removido com sucesso', [
                    'modelo_id' => $id,
                    'marca_id'  => $modelo['marca_id'] ?? 'N/A',
                    'nome'      => $modelo['nome'] ?? 'N/A',
                    'slug'      => $modelo['slug'] ?? 'N/A',
                ]);
            } else {
                $this->logger->warning('Falha ao remover modelo', ['modelo_id' => $id]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover modelo', [
                'modelo_id' => $id,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }
}