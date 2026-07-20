<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela pneus_veiculo.
 * Gerencia as operações de banco para pneus de veículos (dianteiro, traseiro e estepe).
 */
class PneuRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Insere ou atualiza um pneu para uma posição específica do veículo.
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para operação atômica.
     *
     * @param int $veiculoId
     * @param string $posicao 'dianteiro', 'traseiro' ou 'estepe'
     * @param array $dados Deve conter: largura, perfil, aro, indice_carga, simbolo_velocidade
     * @return bool
     */
    public function salvar(int $veiculoId, string $posicao, array $dados): bool
    {
        try {
            $sql = 'INSERT INTO pneus_veiculo (
                veiculo_id, posicao, largura, perfil, aro, indice_carga, simbolo_velocidade
            ) VALUES (
                :veiculo_id, :posicao, :largura, :perfil, :aro, :indice_carga, :simbolo_velocidade
            ) ON DUPLICATE KEY UPDATE
                largura = VALUES(largura),
                perfil = VALUES(perfil),
                aro = VALUES(aro),
                indice_carga = VALUES(indice_carga),
                simbolo_velocidade = VALUES(simbolo_velocidade),
                updated_at = CURRENT_TIMESTAMP';

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                ':veiculo_id'          => $veiculoId,
                ':posicao'             => $posicao,
                ':largura'             => $dados['largura'] ?? null,
                ':perfil'              => $dados['perfil'] ?? null,
                ':aro'                 => $dados['aro'] ?? null,
                ':indice_carga'        => $dados['indice_carga'] ?? null,
                ':simbolo_velocidade'  => $dados['simbolo_velocidade'] ?? null,
            ]);

            if ($success) {
                $this->logger->debug('Pneu salvo com sucesso', [
                    'veiculo_id' => $veiculoId,
                    'posicao'    => $posicao,
                ]);
            } else {
                $this->logger->warning('Falha ao salvar pneu', [
                    'veiculo_id' => $veiculoId,
                    'posicao'    => $posicao,
                ]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao salvar pneu', [
                'veiculo_id' => $veiculoId,
                'posicao'    => $posicao,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Salva as três posições de pneus (dianteiro, traseiro e estepe) de uma vez.
     * A transação deve ser gerenciada pelo Service que chama este método.
     *
     * @param int $veiculoId
     * @param array $dianteiro Dados do pneu dianteiro (largura, perfil, aro, indice_carga, simbolo_velocidade)
     * @param array $traseiro  Dados do pneu traseiro
     * @param array $estepe    Dados do pneu estepe
     * @return bool
     */
    public function salvarLote(int $veiculoId, array $dianteiro, array $traseiro, array $estepe): bool
    {
        $posicoes = [
            'dianteiro' => $dianteiro,
            'traseiro'  => $traseiro,
            'estepe'    => $estepe,
        ];

        foreach ($posicoes as $posicao => $dados) {
            if (!$this->salvar($veiculoId, $posicao, $dados)) {
                return false;
            }
        }

        $this->logger->info('Pneus salvos em lote com sucesso', [
            'veiculo_id' => $veiculoId,
        ]);

        return true;
    }

    /**
     * Busca todos os pneus de um veículo, retornando um array associativo
     * com as chaves 'dianteiro', 'traseiro' e 'estepe'.
     *
     * @param int $veiculoId
     * @return array Ex: ['dianteiro' => [...], 'traseiro' => [...], 'estepe' => [...]]
     */
    public function buscarPorVeiculo(int $veiculoId): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM pneus_veiculo WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Inicializa o array com null para cada posição
            $pneus = [
                'dianteiro' => null,
                'traseiro'  => null,
                'estepe'    => null,
            ];

            foreach ($resultados as $row) {
                $posicao = $row['posicao'];
                if (isset($pneus[$posicao])) {
                    $pneus[$posicao] = [
                        'largura'             => $row['largura'],
                        'perfil'              => $row['perfil'],
                        'aro'                 => $row['aro'],
                        'indice_carga'        => $row['indice_carga'],
                        'simbolo_velocidade'  => $row['simbolo_velocidade'],
                    ];
                }
            }

            $this->logger->debug('Pneus buscados com sucesso', [
                'veiculo_id' => $veiculoId,
                'encontrados' => count($resultados),
            ]);

            return $pneus;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar pneus do veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return [
                'dianteiro' => null,
                'traseiro'  => null,
                'estepe'    => null,
            ];
        }
    }

    /**
     * Remove todos os pneus de um veículo (usado quando o veículo é deletado).
     * Nota: a chave estrangeira com ON DELETE CASCADE já faz isso automaticamente,
     * mas este método pode ser útil para remoções manuais.
     *
     * @param int $veiculoId
     * @return bool
     */
    public function deletarPorVeiculo(int $veiculoId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM pneus_veiculo WHERE veiculo_id = ?');
            $success = $stmt->execute([$veiculoId]);

            if ($success) {
                $this->logger->info('Pneus removidos do veículo', ['veiculo_id' => $veiculoId]);
            } else {
                $this->logger->warning('Falha ao remover pneus do veículo', ['veiculo_id' => $veiculoId]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover pneus do veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }
}