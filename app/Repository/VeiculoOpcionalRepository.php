<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela veiculo_opcionais.
 * Gerencia o relacionamento muitos-para-muitos entre veículos e opcionais.
 */
class VeiculoOpcionalRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Sincroniza os opcionais de um veículo: remove todos os existentes e insere a nova lista.
     * A operação é feita em uma transação para garantir atomicidade.
     *
     * @param int $veiculoId
     * @param array $opcionaisIds Lista de IDs de opcionais a serem associados
     * @return bool
     */
    public function sync(int $veiculoId, array $opcionaisIds): bool
    {
        // Remove duplicatas e garante que são inteiros
        $opcionaisIds = array_unique(array_map('intval', $opcionaisIds));

        try {
            $this->pdo->beginTransaction();

            // Remove todos os opcionais existentes
            $stmtDelete = $this->pdo->prepare('DELETE FROM veiculo_opcionais WHERE veiculo_id = ?');
            $stmtDelete->execute([$veiculoId]);

            // Se não houver opcionais, apenas confirma a remoção e retorna
            if (empty($opcionaisIds)) {
                $this->pdo->commit();
                $this->logger->debug('Opcionais sincronizados (vazio)', ['veiculo_id' => $veiculoId]);
                return true;
            }

            // Insere os novos relacionamentos
            $sqlInsert = 'INSERT INTO veiculo_opcionais (veiculo_id, opcional_id) VALUES (?, ?)';
            $stmtInsert = $this->pdo->prepare($sqlInsert);

            foreach ($opcionaisIds as $opcionalId) {
                $stmtInsert->execute([$veiculoId, $opcionalId]);
            }

            $this->pdo->commit();

            $this->logger->info('Opcionais sincronizados com sucesso', [
                'veiculo_id'    => $veiculoId,
                'count'         => count($opcionaisIds),
                'opcionais_ids' => $opcionaisIds,
            ]);

            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logger->error('Erro ao sincronizar opcionais', [
                'veiculo_id'    => $veiculoId,
                'opcionais_ids' => $opcionaisIds,
                'error'         => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retorna todos os IDs de opcionais associados a um veículo.
     *
     * @param int $veiculoId
     * @return array Lista de IDs (inteiros)
     */
    public function findByVeiculo(int $veiculoId): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT opcional_id FROM veiculo_opcionais WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return array_map('intval', $results);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar opcionais do veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Remove um relacionamento específico entre veículo e opcional.
     *
     * @param int $veiculoId
     * @param int $opcionalId
     * @return bool
     */
    public function deleteByVeiculoAndOpcional(int $veiculoId, int $opcionalId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM veiculo_opcionais WHERE veiculo_id = ? AND opcional_id = ?');
            $success = $stmt->execute([$veiculoId, $opcionalId]);

            if ($success) {
                $this->logger->info('Relacionamento veículo-opcional removido', [
                    'veiculo_id'  => $veiculoId,
                    'opcional_id' => $opcionalId,
                ]);
            } else {
                $this->logger->warning('Falha ao remover relacionamento veículo-opcional', [
                    'veiculo_id'  => $veiculoId,
                    'opcional_id' => $opcionalId,
                ]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover relacionamento veículo-opcional', [
                'veiculo_id'  => $veiculoId,
                'opcional_id' => $opcionalId,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retorna todos os IDs de veículos que possuem um determinado opcional.
     *
     * @param int $opcionalId
     * @return array Lista de IDs de veículos (inteiros)
     */
    public function findByOpcional(int $opcionalId): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT veiculo_id FROM veiculo_opcionais WHERE opcional_id = ?');
            $stmt->execute([$opcionalId]);
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return array_map('intval', $results);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar veículos por opcional', [
                'opcional_id' => $opcionalId,
                'error'       => $e->getMessage(),
            ]);
            return [];
        }
    }
}