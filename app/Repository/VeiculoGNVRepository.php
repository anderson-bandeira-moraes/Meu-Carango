<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela veiculo_gnv.
 * Gerencia operações de banco para veículos com kit GNV instalado.
 */
class VeiculoGNVRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Insere ou atualiza os dados GNV de um veículo.
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para operação atômica.
     *
     * @param array $dados Dados do veículo (deve conter veiculo_id)
     * @return bool
     */
    public function save(array $dados): bool
    {
        try {
            $sql = 'INSERT INTO veiculo_gnv (
                veiculo_id, tipo_sistema, geracao_kit, marca_kit,
                data_instalacao, data_inspecao, data_validade_cilindro,
                possui_csv, possui_selo_gnv,
                capacidade_cilindro_m3, quantidade_cilindros,
                material_cilindro, localizacao_cilindro,
                consumo_cidade_m3km, consumo_estrada_m3km,
                autonomia_media_km, autonomia_cidade_km, autonomia_estrada_km,
                instaladora_certificada, observacoes
            ) VALUES (
                :veiculo_id, :tipo_sistema, :geracao_kit, :marca_kit,
                :data_instalacao, :data_inspecao, :data_validade_cilindro,
                :possui_csv, :possui_selo_gnv,
                :capacidade_cilindro_m3, :quantidade_cilindros,
                :material_cilindro, :localizacao_cilindro,
                :consumo_cidade_m3km, :consumo_estrada_m3km,
                :autonomia_media_km, :autonomia_cidade_km, :autonomia_estrada_km,
                :instaladora_certificada, :observacoes
            ) ON DUPLICATE KEY UPDATE
                tipo_sistema = VALUES(tipo_sistema),
                geracao_kit = VALUES(geracao_kit),
                marca_kit = VALUES(marca_kit),
                data_instalacao = VALUES(data_instalacao),
                data_inspecao = VALUES(data_inspecao),
                data_validade_cilindro = VALUES(data_validade_cilindro),
                possui_csv = VALUES(possui_csv),
                possui_selo_gnv = VALUES(possui_selo_gnv),
                capacidade_cilindro_m3 = VALUES(capacidade_cilindro_m3),
                quantidade_cilindros = VALUES(quantidade_cilindros),
                material_cilindro = VALUES(material_cilindro),
                localizacao_cilindro = VALUES(localizacao_cilindro),
                consumo_cidade_m3km = VALUES(consumo_cidade_m3km),
                consumo_estrada_m3km = VALUES(consumo_estrada_m3km),
                autonomia_media_km = VALUES(autonomia_media_km),
                autonomia_cidade_km = VALUES(autonomia_cidade_km),
                autonomia_estrada_km = VALUES(autonomia_estrada_km),
                instaladora_certificada = VALUES(instaladora_certificada),
                observacoes = VALUES(observacoes)';

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($this->prepareDados($dados));

            if ($success) {
                $this->logger->debug('Dados GNV salvos com sucesso', [
                    'veiculo_id' => $dados['veiculo_id'],
                ]);
            } else {
                $this->logger->warning('Falha ao salvar dados GNV', [
                    'veiculo_id' => $dados['veiculo_id'],
                ]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao salvar dados GNV', [
                'veiculo_id' => $dados['veiculo_id'] ?? 'unknown',
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Busca os dados GNV de um veículo pelo veiculo_id.
     *
     * @param int $veiculoId
     * @return array|null
     */
    public function findByVeiculoId(int $veiculoId): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM veiculo_gnv WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar dados GNV por veiculo_id', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Remove os dados GNV de um veículo.
     *
     * @param int $veiculoId
     * @return bool
     */
    public function delete(int $veiculoId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM veiculo_gnv WHERE veiculo_id = ?');
            $success = $stmt->execute([$veiculoId]);

            if ($success) {
                $this->logger->info('Dados GNV removidos', ['veiculo_id' => $veiculoId]);
            } else {
                $this->logger->warning('Falha ao remover dados GNV', ['veiculo_id' => $veiculoId]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover dados GNV', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Prepara os dados para a query, garantindo tipos e valores padrão.
     *
     * @param array $dados
     * @return array
     */
    private function prepareDados(array $dados): array
    {
        return [
            ':veiculo_id'                 => $dados['veiculo_id'] ?? null,
            ':tipo_sistema'               => $dados['tipo_sistema'] ?? null,
            ':geracao_kit'                => $dados['geracao_kit'] ?? null,
            ':marca_kit'                  => $dados['marca_kit'] ?? null,
            ':data_instalacao'            => $dados['data_instalacao'] ?? null,
            ':data_inspecao'              => $dados['data_inspecao'] ?? null,
            ':data_validade_cilindro'     => $dados['data_validade_cilindro'] ?? null,
            ':possui_csv'                 => $dados['possui_csv'] ?? 0,
            ':possui_selo_gnv'            => $dados['possui_selo_gnv'] ?? 0,
            ':capacidade_cilindro_m3'     => $dados['capacidade_cilindro_m3'] ?? null,
            ':quantidade_cilindros'       => $dados['quantidade_cilindros'] ?? null,
            ':material_cilindro'          => $dados['material_cilindro'] ?? null,
            ':localizacao_cilindro'       => $dados['localizacao_cilindro'] ?? null,
            ':consumo_cidade_m3km'        => $dados['consumo_cidade_m3km'] ?? null,
            ':consumo_estrada_m3km'       => $dados['consumo_estrada_m3km'] ?? null,
            ':autonomia_media_km'         => $dados['autonomia_media_km'] ?? null,
            ':autonomia_cidade_km'        => $dados['autonomia_cidade_km'] ?? null,
            ':autonomia_estrada_km'       => $dados['autonomia_estrada_km'] ?? null,
            ':instaladora_certificada'    => $dados['instaladora_certificada'] ?? null,
            ':observacoes'                => $dados['observacoes'] ?? null,
        ];
    }

}