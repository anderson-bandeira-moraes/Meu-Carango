<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela veiculo_hibrido.
 * Gerencia operações de banco para veículos híbridos (HEV, MHEV, PHEV).
 */
class VeiculoHibridoRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Insere ou atualiza os dados híbridos de um veículo.
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para operação atômica.
     *
     * @param array $dados Dados do veículo (deve conter veiculo_id)
     * @return bool
     */
    public function save(array $dados): bool
    {
        try {
            $sql = 'INSERT INTO veiculo_hibrido (
                veiculo_id, tipo, combustivel,
                motor_combustao_tipo, motor_combustao_potencia_cv, motor_combustao_torque_kgfm,
                motor_eletrico_potencia_cv, motor_eletrico_torque_kgfm,
                potencia_combinada_cv, torque_combinado_kgfm,
                tracao_tipo, transmissao_tipo, numero_marchas,
                bateria_capacidade_kwh, bateria_tipo, modo_eletrico_puro,
                autonomia_eletrica_pbev_km, autonomia_combinada_km,
                bateria_garantia,
                carregamento_potencia_ac_kw, carregamento_tempo_ac_horas,
                carregamento_potencia_dc_kw, carregamento_tipo_conector_ac,
                consumo_cidade_kml, consumo_estrada_kml, consumo_medio_kml,
                consumo_cidade_etanol_kml, consumo_estrada_etanol_kml, consumo_medio_etanol_kml,
                capacidade_tanque_l
            ) VALUES (
                :veiculo_id, :tipo, :combustivel,
                :motor_combustao_tipo, :motor_combustao_potencia_cv, :motor_combustao_torque_kgfm,
                :motor_eletrico_potencia_cv, :motor_eletrico_torque_kgfm,
                :potencia_combinada_cv, :torque_combinado_kgfm,
                :tracao_tipo, :transmissao_tipo, :numero_marchas,
                :bateria_capacidade_kwh, :bateria_tipo, :modo_eletrico_puro,
                :autonomia_eletrica_pbev_km, :autonomia_combinada_km,
                :bateria_garantia,
                :carregamento_potencia_ac_kw, :carregamento_tempo_ac_horas,
                :carregamento_potencia_dc_kw, :carregamento_tipo_conector_ac,
                :consumo_cidade_kml, :consumo_estrada_kml, :consumo_medio_kml,
                :consumo_cidade_etanol_kml, :consumo_estrada_etanol_kml, :consumo_medio_etanol_kml,
                :capacidade_tanque_l
            ) ON DUPLICATE KEY UPDATE
                tipo = VALUES(tipo),
                combustivel = VALUES(combustivel),
                motor_combustao_tipo = VALUES(motor_combustao_tipo),
                motor_combustao_potencia_cv = VALUES(motor_combustao_potencia_cv),
                motor_combustao_torque_kgfm = VALUES(motor_combustao_torque_kgfm),
                motor_eletrico_potencia_cv = VALUES(motor_eletrico_potencia_cv),
                motor_eletrico_torque_kgfm = VALUES(motor_eletrico_torque_kgfm),
                potencia_combinada_cv = VALUES(potencia_combinada_cv),
                torque_combinado_kgfm = VALUES(torque_combinado_kgfm),
                tracao_tipo = VALUES(tracao_tipo),
                transmissao_tipo = VALUES(transmissao_tipo),
                numero_marchas = VALUES(numero_marchas),
                bateria_capacidade_kwh = VALUES(bateria_capacidade_kwh),
                bateria_tipo = VALUES(bateria_tipo),
                modo_eletrico_puro = VALUES(modo_eletrico_puro),
                autonomia_eletrica_pbev_km = VALUES(autonomia_eletrica_pbev_km),
                autonomia_combinada_km = VALUES(autonomia_combinada_km),
                bateria_garantia = VALUES(bateria_garantia),
                carregamento_potencia_ac_kw = VALUES(carregamento_potencia_ac_kw),
                carregamento_tempo_ac_horas = VALUES(carregamento_tempo_ac_horas),
                carregamento_potencia_dc_kw = VALUES(carregamento_potencia_dc_kw),
                carregamento_tipo_conector_ac = VALUES(carregamento_tipo_conector_ac),
                consumo_cidade_kml = VALUES(consumo_cidade_kml),
                consumo_estrada_kml = VALUES(consumo_estrada_kml),
                consumo_medio_kml = VALUES(consumo_medio_kml),
                consumo_cidade_etanol_kml = VALUES(consumo_cidade_etanol_kml),
                consumo_estrada_etanol_kml = VALUES(consumo_estrada_etanol_kml),
                consumo_medio_etanol_kml = VALUES(consumo_medio_etanol_kml),
                capacidade_tanque_l = VALUES(capacidade_tanque_l)';

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($this->prepareDados($dados));

            if ($success) {
                $this->logger->debug('Dados híbridos salvos com sucesso', [
                    'veiculo_id' => $dados['veiculo_id'],
                    'tipo'       => $dados['tipo'] ?? 'unknown',
                ]);
            } else {
                $this->logger->warning('Falha ao salvar dados híbridos', [
                    'veiculo_id' => $dados['veiculo_id'],
                ]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao salvar dados híbridos', [
                'veiculo_id' => $dados['veiculo_id'] ?? 'unknown',
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Busca os dados híbridos de um veículo pelo veiculo_id.
     *
     * @param int $veiculoId
     * @return array|null
     */
    public function findByVeiculoId(int $veiculoId): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM veiculo_hibrido WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar dados híbridos por veiculo_id', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Remove os dados híbridos de um veículo.
     *
     * @param int $veiculoId
     * @return bool
     */
    public function delete(int $veiculoId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM veiculo_hibrido WHERE veiculo_id = ?');
            $success = $stmt->execute([$veiculoId]);

            if ($success) {
                $this->logger->info('Dados híbridos removidos', ['veiculo_id' => $veiculoId]);
            } else {
                $this->logger->warning('Falha ao remover dados híbridos', ['veiculo_id' => $veiculoId]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover dados híbridos', [
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
            ':veiculo_id'                        => $dados['veiculo_id'] ?? null,
            ':tipo'                              => $dados['tipo'] ?? null,
            ':combustivel'                       => $dados['combustivel'] ?? null,
            ':motor_combustao_tipo'              => $dados['motor_combustao_tipo'] ?? null,
            ':motor_combustao_potencia_cv'       => $dados['motor_combustao_potencia_cv'] ?? null,
            ':motor_combustao_torque_kgfm'       => $dados['motor_combustao_torque_kgfm'] ?? null,
            ':motor_eletrico_potencia_cv'        => $dados['motor_eletrico_potencia_cv'] ?? null,
            ':motor_eletrico_torque_kgfm'        => $dados['motor_eletrico_torque_kgfm'] ?? null,
            ':potencia_combinada_cv'             => $dados['potencia_combinada_cv'] ?? null,
            ':torque_combinado_kgfm'             => $dados['torque_combinado_kgfm'] ?? null,
            ':tracao_tipo'                       => $dados['tracao_tipo'] ?? null,
            ':transmissao_tipo'                  => $dados['transmissao_tipo'] ?? null,
            ':numero_marchas'                    => $dados['numero_marchas'] ?? null,
            ':bateria_capacidade_kwh'            => $dados['bateria_capacidade_kwh'] ?? null,
            ':bateria_tipo'                      => $dados['bateria_tipo'] ?? null,
            ':modo_eletrico_puro'                => $dados['modo_eletrico_puro'] ?? 0,
            ':autonomia_eletrica_pbev_km'        => $dados['autonomia_eletrica_pbev_km'] ?? null,
            ':autonomia_combinada_km'            => $dados['autonomia_combinada_km'] ?? null,
            ':bateria_garantia'                  => $dados['bateria_garantia'] ?? null,
            ':carregamento_potencia_ac_kw'       => $dados['carregamento_potencia_ac_kw'] ?? null,
            ':carregamento_tempo_ac_horas'       => $dados['carregamento_tempo_ac_horas'] ?? null,
            ':carregamento_potencia_dc_kw'       => $dados['carregamento_potencia_dc_kw'] ?? null,
            ':carregamento_tipo_conector_ac'     => $dados['carregamento_tipo_conector_ac'] ?? null,
            ':consumo_cidade_kml'                => $dados['consumo_cidade_kml'] ?? null,
            ':consumo_estrada_kml'               => $dados['consumo_estrada_kml'] ?? null,
            ':consumo_medio_kml'                 => $dados['consumo_medio_kml'] ?? null,
            // NOVOS CAMPOS DE CONSUMO COM ETANOL
            ':consumo_cidade_etanol_kml'         => $dados['consumo_cidade_etanol_kml'] ?? null,
            ':consumo_estrada_etanol_kml'        => $dados['consumo_estrada_etanol_kml'] ?? null,
            ':consumo_medio_etanol_kml'          => $dados['consumo_medio_etanol_kml'] ?? null,
            ':capacidade_tanque_l'               => $dados['capacidade_tanque_l'] ?? null,
        ];
    }

}