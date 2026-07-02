<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela veiculo_imagens.
 * Gerencia as operações de banco para imagens de veículos (capa e slide).
 */
class VeiculoImagemRepository
{
    /**
     * Limite máximo de imagens por veículo (capa + slide).
     */
    private const MAX_IMAGENS = 16;

    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Insere uma nova imagem no banco.
     *
     * @param array $dados Deve conter: veiculo_id, caminho.
     *                     Opcionais: nome_original, mime_type, tamanho_bytes, capa, ordem.
     * @return int|false ID da imagem inserida ou false em caso de erro.
     */
    public function save(array $dados): int|false
    {
        try {
            $sql = 'INSERT INTO veiculo_imagens (
                veiculo_id, caminho, nome_original, mime_type,
                tamanho_bytes, capa, ordem
            ) VALUES (
                :veiculo_id, :caminho, :nome_original, :mime_type,
                :tamanho_bytes, :capa, :ordem
            )';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->prepareDados($dados));

            $id = (int) $this->pdo->lastInsertId();

            $this->logger->debug('Imagem salva com sucesso', [
                'imagem_id'  => $id,
                'veiculo_id' => $dados['veiculo_id'],
            ]);

            return $id;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao salvar imagem', [
                'veiculo_id' => $dados['veiculo_id'] ?? 'unknown',
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Busca todas as imagens de um veículo, ordenadas por ordem.
     *
     * @param int $veiculoId
     * @return array
     */
    public function findByVeiculo(int $veiculoId): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM veiculo_imagens WHERE veiculo_id = ? ORDER BY ordem');
            $stmt->execute([$veiculoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar imagens do veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Busca a imagem de capa de um veículo.
     *
     * @param int $veiculoId
     * @return array|null
     */
    public function findCapa(int $veiculoId): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM veiculo_imagens WHERE veiculo_id = ? AND capa = 1 LIMIT 1');
            $stmt->execute([$veiculoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar capa do veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca uma imagem específica por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM veiculo_imagens WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar imagem por ID', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Remove uma imagem específica (fisicamente do banco).
     * Opcionalmente, valida se a imagem pertence ao veículo informado.
     *
     * @param int $id
     * @param int|null $veiculoId (opcional) Se informado, valida pertencimento antes de deletar.
     * @return bool
     */
    public function deleteById(int $id, ?int $veiculoId = null): bool
    {
        try {
            // Valida pertencimento se veiculoId for informado
            if ($veiculoId !== null) {
                $imagem = $this->findById($id);
                if (!$imagem) {
                    $this->logger->warning('Tentativa de deletar imagem inexistente', [
                        'imagem_id' => $id,
                        'veiculo_id' => $veiculoId,
                    ]);
                    return false;
                }
                if ($imagem['veiculo_id'] != $veiculoId) {
                    $this->logger->warning('Tentativa de deletar imagem de outro veículo', [
                        'imagem_id'       => $id,
                        'veiculo_id'      => $veiculoId,
                        'veiculo_imagem'  => $imagem['veiculo_id'],
                    ]);
                    return false;
                }
            }

            $stmt = $this->pdo->prepare('DELETE FROM veiculo_imagens WHERE id = ?');
            $success = $stmt->execute([$id]);

            if ($success) {
                $this->logger->debug('Imagem removida do banco', ['imagem_id' => $id]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover imagem', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove todas as imagens de um veículo (fisicamente do banco).
     *
     * @param int $veiculoId
     * @return bool
     */
    public function deleteByVeiculo(int $veiculoId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM veiculo_imagens WHERE veiculo_id = ?');
            $success = $stmt->execute([$veiculoId]);

            if ($success) {
                $this->logger->info('Todas as imagens removidas do veículo', ['veiculo_id' => $veiculoId]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover imagens do veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sincroniza a lista completa de imagens de um veículo.
     *
     * Substitui a lista atual por uma nova, mantendo integridade de capa e ordem.
     * A transação deve ser gerenciada pelo Service.
     *
     * @param int $veiculoId
     * @param array $imagensData Estrutura esperada:
     *   - ids_manter: int[] (IDs das imagens que devem permanecer)
     *   - novas: array[] (cada uma com veiculo_id, caminho, nome_original, mime_type, tamanho_bytes)
     *   - capa_id: int|null (ID da imagem que será capa; se null, nenhuma será capa)
     * @return bool
     */
    public function sync(int $veiculoId, array $imagensData): bool
    {
        $idsManter = $imagensData['ids_manter'] ?? [];
        $novas = $imagensData['novas'] ?? [];
        $capaId = $imagensData['capa_id'] ?? null;

        // Garante que ids_manter seja um array de inteiros
        $idsManter = array_map('intval', $idsManter);

        try {
            // 1. Buscar imagens atuais
            $atuais = $this->findByVeiculo($veiculoId);

            // 2. Calcular imagens a remover
            $idsAtuais = array_column($atuais, 'id');
            $idsRemover = array_diff($idsAtuais, $idsManter);

            // 3. Validar limite máximo (após remoção + novas)
            $totalAposRemocao = count($idsAtuais) - count($idsRemover) + count($novas);
            if ($totalAposRemocao > self::MAX_IMAGENS) {
                $this->logger->error('Limite de imagens excedido', [
                    'veiculo_id'        => $veiculoId,
                    'limite'            => self::MAX_IMAGENS,
                    'tentativa'         => $totalAposRemocao,
                ]);
                return false;
            }

            // 4. Remover imagens que não estão em ids_manter
            if (!empty($idsRemover)) {
                $placeholders = implode(',', array_fill(0, count($idsRemover), '?'));
                $sql = "DELETE FROM veiculo_imagens WHERE id IN ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($idsRemover);
                $this->logger->debug('Imagens removidas na sincronização', [
                    'veiculo_id' => $veiculoId,
                    'ids_removidos' => $idsRemover,
                ]);
            }

            // 5. Resetar capa para todas as imagens restantes
            $this->resetarCapa($veiculoId);

            // 6. Inserir novas imagens
            $ordemAtual = $this->getProximaOrdem($veiculoId);
            foreach ($novas as $nova) {
                $nova['veiculo_id'] = $veiculoId;
                $nova['capa'] = 0;
                $nova['ordem'] = $ordemAtual++;
                $this->save($nova);
            }

            // 7. Definir capa (se fornecido)
            if ($capaId !== null) {
                $stmt = $this->pdo->prepare('UPDATE veiculo_imagens SET capa = 1 WHERE id = ? AND veiculo_id = ?');
                $stmt->execute([$capaId, $veiculoId]);
                if ($stmt->rowCount() === 0) {
                    $this->logger->warning('Capa não encontrada para definir', [
                        'veiculo_id' => $veiculoId,
                        'capa_id'    => $capaId,
                    ]);
                }
            }

            // 8. Reordenar todas as imagens restantes
            $this->reordenarSequencial($veiculoId);

            $this->logger->info('Sincronização de imagens concluída', [
                'veiculo_id' => $veiculoId,
                'total_imagens' => $this->contarImagens($veiculoId),
            ]);

            return true;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao sincronizar imagens', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Conta o total de imagens de um veículo.
     *
     * @param int $veiculoId
     * @return int
     */
    private function contarImagens(int $veiculoId): int
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM veiculo_imagens WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao contar imagens', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Obtém a próxima ordem disponível para um veículo.
     *
     * @param int $veiculoId
     * @return int
     */
    private function getProximaOrdem(int $veiculoId): int
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM veiculo_imagens WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error('Erro ao obter próxima ordem', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Reseta a capa para 0 para todas as imagens de um veículo.
     *
     * @param int $veiculoId
     * @return bool
     */
    private function resetarCapa(int $veiculoId): bool
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE veiculo_imagens SET capa = 0 WHERE veiculo_id = ?');
            return $stmt->execute([$veiculoId]);
        } catch (PDOException $e) {
            $this->logger->error('Erro ao resetar capa', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Reordena sequencialmente todas as imagens de um veículo.
     *
     * @param int $veiculoId
     * @return bool
     */
    private function reordenarSequencial(int $veiculoId): bool
    {
        try {
            // Busca as imagens ordenadas atualmente por ordem (ou ID como fallback)
            $sql = 'SELECT id FROM veiculo_imagens WHERE veiculo_id = ? ORDER BY ordem, id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$veiculoId]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($ids)) {
                return true;
            }

            // Atualiza a ordem sequencialmente
            $sql = 'UPDATE veiculo_imagens SET ordem = ? WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            foreach ($ids as $index => $id) {
                $stmt->execute([$index, $id]);
            }

            return true;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao reordenar imagens', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Prepara os dados para inserção.
     *
     * @param array $dados
     * @return array
     */
    private function prepareDados(array $dados): array
    {
        return [
            ':veiculo_id'    => $dados['veiculo_id'] ?? null,
            ':caminho'       => $dados['caminho'] ?? null,
            ':nome_original' => $dados['nome_original'] ?? null,
            ':mime_type'     => $dados['mime_type'] ?? null,
            ':tamanho_bytes' => $dados['tamanho_bytes'] ?? null,
            ':capa'          => (int) ($dados['capa'] ?? 0),
            ':ordem'         => (int) ($dados['ordem'] ?? 0),
        ];
    }
}