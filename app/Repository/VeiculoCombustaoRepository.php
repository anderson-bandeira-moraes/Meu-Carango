<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

/**
 * Repositório para a tabela veiculo_combustao.
 * Gerencia operações de banco para veículos a combustão.
 */
class VeiculoCombustaoRepository
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Insere ou atualiza os dados de combustão de um veículo.
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para operação atômica.
     *
     * @param array $dados Dados do veículo (deve conter veiculo_id)
     * @return bool
     */
    public function save(array $dados): bool
    {
        try {
            $sql = 'INSERT INTO veiculo_combustao (
                veiculo_id, combustivel, motor_tipo,
                potencia_cv, potencia_etanol_cv,
                torque_kgfm, torque_etanol_kgfm,
                regime_potencia_rpm, regime_torque_rpm,
                aceleracao_0_100_seg, velocidade_max_kmh,
                tracao_tipo,
                consumo_cidade_kml, consumo_estrada_kml,
                consumo_cidade_etanol_kml, consumo_estrada_etanol_kml,
                capacidade_tanque_l, transmissao_tipo, numero_marchas
            ) VALUES (
                :veiculo_id, :combustivel, :motor_tipo,
                :potencia_cv, :potencia_etanol_cv,
                :torque_kgfm, :torque_etanol_kgfm,
                :regime_potencia_rpm, :regime_torque_rpm,
                :aceleracao_0_100_seg, :velocidade_max_kmh,
                :tracao_tipo,
                :consumo_cidade_kml, :consumo_estrada_kml,
                :consumo_cidade_etanol_kml, :consumo_estrada_etanol_kml,
                :capacidade_tanque_l, :transmissao_tipo, :numero_marchas
            ) ON DUPLICATE KEY UPDATE
                combustivel = VALUES(combustivel),
                motor_tipo = VALUES(motor_tipo),
                potencia_cv = VALUES(potencia_cv),
                potencia_etanol_cv = VALUES(potencia_etanol_cv),
                torque_kgfm = VALUES(torque_kgfm),
                torque_etanol_kgfm = VALUES(torque_etanol_kgfm),
                regime_potencia_rpm = VALUES(regime_potencia_rpm),
                regime_torque_rpm = VALUES(regime_torque_rpm),
                aceleracao_0_100_seg = VALUES(aceleracao_0_100_seg),
                velocidade_max_kmh = VALUES(velocidade_max_kmh),
                tracao_tipo = VALUES(tracao_tipo),
                consumo_cidade_kml = VALUES(consumo_cidade_kml),
                consumo_estrada_kml = VALUES(consumo_estrada_kml),
                consumo_cidade_etanol_kml = VALUES(consumo_cidade_etanol_kml),
                consumo_estrada_etanol_kml = VALUES(consumo_estrada_etanol_kml),
                capacidade_tanque_l = VALUES(capacidade_tanque_l),
                transmissao_tipo = VALUES(transmissao_tipo),
                numero_marchas = VALUES(numero_marchas)';

            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($this->prepareDados($dados));

            if ($success) {
                $this->logger->debug('Dados de combustão salvos com sucesso', [
                    'veiculo_id' => $dados['veiculo_id'],
                ]);
            } else {
                $this->logger->warning('Falha ao salvar dados de combustão', [
                    'veiculo_id' => $dados['veiculo_id'],
                ]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao salvar dados de combustão', [
                'veiculo_id' => $dados['veiculo_id'] ?? 'unknown',
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Busca os dados de combustão de um veículo pelo veiculo_id.
     *
     * @param int $veiculoId
     * @return array|null
     */
    public function findByVeiculoId(int $veiculoId): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM veiculo_combustao WHERE veiculo_id = ?');
            $stmt->execute([$veiculoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao buscar dados de combustão por veiculo_id', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Remove os dados de combustão de um veículo.
     *
     * @param int $veiculoId
     * @return bool
     */
    public function delete(int $veiculoId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM veiculo_combustao WHERE veiculo_id = ?');
            $success = $stmt->execute([$veiculoId]);

            if ($success) {
                $this->logger->info('Dados de combustão removidos', ['veiculo_id' => $veiculoId]);
            } else {
                $this->logger->warning('Falha ao remover dados de combustão', ['veiculo_id' => $veiculoId]);
            }

            return $success;
        } catch (PDOException $e) {
            $this->logger->error('Erro ao remover dados de combustão', [
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
            ':veiculo_id'                    => $dados['veiculo_id'] ?? null,
            ':combustivel'                   => $dados['combustivel'] ?? null,
            ':motor_tipo'                    => $dados['motor_tipo'] ?? null,
            ':potencia_cv'                   => $dados['potencia_cv'] ?? null,
            ':potencia_etanol_cv'            => $dados['potencia_etanol_cv'] ?? null,
            ':torque_kgfm'                   => $dados['torque_kgfm'] ?? null,
            ':torque_etanol_kgfm'            => $dados['torque_etanol_kgfm'] ?? null,
            ':regime_potencia_rpm'           => $dados['regime_potencia_rpm'] ?? null,
            ':regime_torque_rpm'             => $dados['regime_torque_rpm'] ?? null,
            ':aceleracao_0_100_seg'          => $dados['aceleracao_0_100_seg'] ?? null,
            ':velocidade_max_kmh'            => $dados['velocidade_max_kmh'] ?? null,
            ':tracao_tipo'                   => $dados['tracao_tipo'] ?? null,
            ':consumo_cidade_kml'            => $dados['consumo_cidade_kml'] ?? null,
            ':consumo_estrada_kml'           => $dados['consumo_estrada_kml'] ?? null,
            ':consumo_cidade_etanol_kml'     => $dados['consumo_cidade_etanol_kml'] ?? null,
            ':consumo_estrada_etanol_kml'    => $dados['consumo_estrada_etanol_kml'] ?? null,
            ':capacidade_tanque_l'           => $dados['capacidade_tanque_l'] ?? null,
            ':transmissao_tipo'              => $dados['transmissao_tipo'] ?? null,
            ':numero_marchas'                => $dados['numero_marchas'] ?? null,
        ];
    }
}