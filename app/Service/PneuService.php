<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PneuRepository;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciamento de pneus de veículos.
 * Orquestra as operações entre o repositório e a lógica de formatação.
 */
class PneuService
{
    public function __construct(
        private PneuRepository $pneuRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Salva os pneus de um veículo (dianteiro, traseiro e estepe).
     *
     * @param int $veiculoId
     * @param array $dados Deve conter as chaves 'dianteiro', 'traseiro' e 'estepe',
     *                     cada uma com largura, perfil, aro, indice_carga, simbolo_velocidade.
     * @return bool
     */
    public function salvar(int $veiculoId, array $dados): bool
    {
        $dianteiro = $dados['dianteiro'] ?? [];
        $traseiro  = $dados['traseiro'] ?? [];
        $estepe    = $dados['estepe'] ?? [];

        // Verifica se os dados essenciais estão presentes (validação adicional por segurança)
        if (empty($dianteiro) || empty($traseiro) || empty($estepe)) {
            $this->logger->error('Dados de pneus incompletos', [
                'veiculo_id' => $veiculoId,
                'dianteiro' => $dianteiro ? 'presente' : 'ausente',
                'traseiro'  => $traseiro ? 'presente' : 'ausente',
                'estepe'    => $estepe ? 'presente' : 'ausente',
            ]);
            return false;
        }

        $resultado = $this->pneuRepository->salvarLote($veiculoId, $dianteiro, $traseiro, $estepe);

        if ($resultado) {
            $this->logger->info('Pneus salvos com sucesso', [
                'veiculo_id' => $veiculoId,
                'dianteiro' => $this->formatarMedida($dianteiro),
                'traseiro'  => $this->formatarMedida($traseiro),
                'estepe'    => $this->formatarMedida($estepe),
            ]);
        } else {
            $this->logger->error('Falha ao salvar pneus', ['veiculo_id' => $veiculoId]);
        }

        return $resultado;
    }

    /**
     * Busca os pneus de um veículo.
     *
     * @param int $veiculoId
     * @return array Retorna um array com as chaves 'dianteiro', 'traseiro' e 'estepe',
     *               cada uma contendo os dados do pneu ou null se não existir.
     */
    public function buscar(int $veiculoId): array
    {
        $pneus = $this->pneuRepository->buscarPorVeiculo($veiculoId);

        $this->logger->debug('Pneus buscados', [
            'veiculo_id' => $veiculoId,
            'dianteiro' => $pneus['dianteiro'] ? $this->formatarMedida($pneus['dianteiro']) : 'null',
            'traseiro'  => $pneus['traseiro'] ? $this->formatarMedida($pneus['traseiro']) : 'null',
            'estepe'    => $pneus['estepe'] ? $this->formatarMedida($pneus['estepe']) : 'null',
        ]);

        return $pneus;
    }

    /**
     * Formata a medida do pneu em uma string legível.
     *
     * @param array|null $pneu Array com as chaves: largura, perfil, aro, indice_carga, simbolo_velocidade
     * @return string Ex: "165/60 R14 88T" ou "Não informado" se algum dado estiver ausente.
     */
    public function formatarMedida(?array $pneu): string
    {
        if ($pneu === null) {
            return 'Não informado';
        }

        $largura = $pneu['largura'] ?? null;
        $perfil = $pneu['perfil'] ?? null;
        $aro = $pneu['aro'] ?? null;
        $indiceCarga = $pneu['indice_carga'] ?? null;
        $simboloVelocidade = $pneu['simbolo_velocidade'] ?? null;

        // Se algum campo obrigatório estiver ausente, retorna "Não informado"
        if ($largura === null || $perfil === null || $aro === null || $indiceCarga === null || $simboloVelocidade === null) {
            return 'Não informado';
        }

        return sprintf('%s/%s R%s %s%s', $largura, $perfil, $aro, $indiceCarga, $simboloVelocidade);
    }

    /**
     * Compara dois arrays de pneus e verifica se são idênticos.
     *
     * @param array|null $pneu1
     * @param array|null $pneu2
     * @return bool True se todos os campos forem iguais (incluindo null), false caso contrário.
     */
    public function saoIguais(?array $pneu1, ?array $pneu2): bool
    {
        // Se ambos são null, são iguais
        if ($pneu1 === null && $pneu2 === null) {
            return true;
        }

        // Se um é null e o outro não, são diferentes
        if ($pneu1 === null || $pneu2 === null) {
            return false;
        }

        // Compara as cinco chaves
        return ($pneu1['largura'] ?? null) === ($pneu2['largura'] ?? null)
            && ($pneu1['perfil'] ?? null) === ($pneu2['perfil'] ?? null)
            && ($pneu1['aro'] ?? null) === ($pneu2['aro'] ?? null)
            && ($pneu1['indice_carga'] ?? null) === ($pneu2['indice_carga'] ?? null)
            && ($pneu1['simbolo_velocidade'] ?? null) === ($pneu2['simbolo_velocidade'] ?? null);
    }

    /**
     * Verifica se os três pneus (dianteiro, traseiro, estepe) são todos iguais.
     *
     * @param array|null $dianteiro
     * @param array|null $traseiro
     * @param array|null $estepe
     * @return bool
     */
    public function saoTodosIguais(?array $dianteiro, ?array $traseiro, ?array $estepe): bool
    {
        return $this->saoIguais($dianteiro, $traseiro)
            && $this->saoIguais($traseiro, $estepe);
    }
}