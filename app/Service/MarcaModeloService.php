<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MarcaRepository;
use App\Repository\ModeloRepository;
use Psr\Log\LoggerInterface;

/**
 * Service para gerenciamento de marcas e modelos.
 * Orquestra as operações entre repositórios e helpers.
 */
class MarcaModeloService
{
    public function __construct(
        private MarcaRepository $marcaRepo,
        private ModeloRepository $modeloRepo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Lista todas as marcas, com suporte a busca por nome.
     * Inclui a URL da logo via helper logo_url().
     *
     * @param string|null $busca Termo para filtrar marcas pelo nome (opcional)
     * @return array Lista de marcas com os campos: id, nome, slug, logo, logo_url
     */
    public function listarMarcas(?string $busca = null): array
    {
        try {
            // Busca todas as marcas (já ordenadas por nome no repositório)
            $marcas = $this->marcaRepo->findAll();

            // Se houver termo de busca, filtra pelo nome (case-insensitive)
            if (!empty($busca)) {
                $buscaLower = mb_strtolower($busca);
                $marcas = array_filter($marcas, function ($marca) use ($buscaLower) {
                    return str_contains(mb_strtolower($marca['nome'] ?? ''), $buscaLower);
                });
                // Reindexa o array após o filtro
                $marcas = array_values($marcas);
            }

            // Adiciona a URL da logo a cada marca
            foreach ($marcas as &$marca) {
                $marca['logo_url'] = logo_url($marca['logo'] ?? null);
            }

            $this->logger->debug('Listagem de marcas realizada', [
                'total' => count($marcas),
                'busca' => $busca,
            ]);

            return $marcas;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar marcas', [
                'busca' => $busca,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}