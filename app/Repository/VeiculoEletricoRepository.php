<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela veiculo_eletrico.
 * Gerencia operações de banco para veículos 100% elétricos (BEV).
 */
class VeiculoEletricoRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Insere ou atualiza os dados elétricos de um veículo.
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para operação atômica.
     *
     * @param array $dados Dados do veículo (deve conter veiculo_id)
     * @return bool
     */
    public function save(array $dados): bool
    {
        try {
            $sql = 'INSERT INTO veiculo_eletrico (
                veiculo_id, tracao_tipo, transmissao_tipo, bateria_tipo,
                potencia_max_cv, torque_max_nm, torque_max_kgfm,
                aceleracao_0_100_seg, velocidade_max_kmh,
                capacidade_liquida_kwh, saude_bateria_soh,
                autonomia_wltp_km, autonomia_inmetro_km,
                garantia_bateria,
                potencia_max_dc_kw, tipo_conector_dc, tipo_conector_ac,
                tempo_carga_dc_min, consumo_energetico_kwh_100km, sistema_eletrico_tensao
            ) VALUES (
                :veiculo_id, :tracao_tipo, :transmissao_tipo, :bateria_tipo,
                :potencia_max_cv, :torque_max_nm, :torque_max_kgfm,
                :aceleracao_0_100_seg, :velocidade_max_kmh,
                :capacidade_liquida_kwh, :saude_bateria_soh,
                :autonomia_wltp_km, :autonomia_inmetro_km,
                :garantia_bateria,
                :potencia_max_dc_kw, :tipo_conector_dc, :tipo_conector_ac,
                :tempo_carga_dc_min, :consumo_energetico_kwh_100km, :sistema_eletrico_tensao
            ) ON DUPLICATE KEY UPDATE
                tracao_tipo = VALUES(tracao_tipo),
                transmissao_tipo = VALUES(transmissao_tipo),
                bateria_tipo = VALUES(bateria_tipo),
                potencia_max_cv = VALUES(potencia_max_cv),
                torque_max_nm = VALUES(torque_max_nm),
                torque_max_kgfm = VALUES(torque_max_kgfm),
                aceleracao_0_100_seg = VALUES(aceleracao_0_100_seg),
                velocidade_max_kmh = VALUES(velocidade_max_kmh),
                capacidade_liquida_kwh = VALUES(capacidade_liquida_kwh),
                saude_bateria_soh = VALUES(saude_bateria_soh),
                autonomia_wltp_km = VALUES(autonomia_wltp_km),
                autonomia_inmetro_km = VALUES(autonomia_inmetro_km),
                garantia_bateria = VALUES(garantia_bateria),
                potencia_max_dc_kw = VALUES(potencia_max_dc_kw),
                tipo_conector_dc = VALUES(tipo_conector_dc),
                tipo_conector_ac = VALUES(tipo_conector_ac),
                tempo_carga_dc_min = VALUES(tempo_carga_dc_min),
                consumo_energetico_kwh_100km = VALUES(consumo_energetico_kwh_100km),
                sistema_eletrico_tensao = VALUES(sistema_eletrico_tensao)';

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($this->prepareDados($dados));

            if ($success) {
                $this->logger->debug('Dados elétricos salvos com sucesso', [
                    'veiculo_id' => $dados['veiculo_id'],
                ]);
            } else {
                $this->logger->warning('Falha ao salvar dados elétricos', [
                    'veiculo_id' => $dados['veiculo_id'],
                ]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao salvar dados elétricos', [
                'veiculo_id' => $dados['veiculo_id'] ?? 'unknown',
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
            ':veiculo_id'                    => $dados['veiculo_id'] ?? null,
            ':tracao_tipo'                   => $dados['tracao_tipo'] ?? null,
            ':transmissao_tipo'              => $dados['transmissao_tipo'] ?? null,
            ':bateria_tipo'                  => $dados['bateria_tipo'] ?? null,
            ':potencia_max_cv'               => $dados['potencia_max_cv'] ?? null,
            ':torque_max_nm'                 => $dados['torque_max_nm'] ?? null,
            ':torque_max_kgfm'               => $dados['torque_max_kgfm'] ?? null,
            ':aceleracao_0_100_seg'          => $dados['aceleracao_0_100_seg'] ?? null,
            ':velocidade_max_kmh'            => $dados['velocidade_max_kmh'] ?? null,
            ':capacidade_liquida_kwh'        => $dados['capacidade_liquida_kwh'] ?? null,
            ':saude_bateria_soh'             => $dados['saude_bateria_soh'] ?? null,
            ':autonomia_wltp_km'             => $dados['autonomia_wltp_km'] ?? null,
            ':autonomia_inmetro_km'          => $dados['autonomia_inmetro_km'] ?? null,
            ':garantia_bateria'              => $dados['garantia_bateria'] ?? null,
            ':potencia_max_dc_kw'            => $dados['potencia_max_dc_kw'] ?? null,
            ':tipo_conector_dc'              => $dados['tipo_conector_dc'] ?? null,
            ':tipo_conector_ac'              => $dados['tipo_conector_ac'] ?? null,
            ':tempo_carga_dc_min'            => $dados['tempo_carga_dc_min'] ?? null,
            ':consumo_energetico_kwh_100km'  => $dados['consumo_energetico_kwh_100km'] ?? null,
            ':sistema_eletrico_tensao' => $dados['sistema_eletrico_tensao'] ?? null,
        ];
    }

    /**
     * Busca os dados elétricos de um veículo pelo veiculo_id.
     *
     * @param int $veiculoId
     * @return array|null
     */
    public function findByVeiculoId(int $veiculoId): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM veiculo_eletrico WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar dados elétricos por veiculo_id', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Remove os dados elétricos de um veículo.
     *
     * @param int $veiculoId
     * @return bool
     */
    public function delete(int $veiculoId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM veiculo_eletrico WHERE veiculo_id = ?');
            $success = $stmt->execute([$veiculoId]);

            if ($success) {
                $this->logger->info('Dados elétricos removidos', ['veiculo_id' => $veiculoId]);
            } else {
                $this->logger->warning('Falha ao remover dados elétricos', ['veiculo_id' => $veiculoId]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover dados elétricos', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }
}