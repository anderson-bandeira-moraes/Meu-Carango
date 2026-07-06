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

    /**
     * Cria uma nova marca.
     *
     * @param string $nome Nome da marca (obrigatório)
     * @param string|null $logoPath Caminho relativo da logo (opcional)
     * @return int|false ID da marca criada ou false em caso de erro
     */
    public function criarMarca(string $nome, ?string $logoPath = null): int|false
    {
        try {
            // 1. Valida se o nome já existe
            if ($this->marcaRepo->findByNome($nome)) {
                $this->logger->warning('Tentativa de criar marca com nome já existente', ['nome' => $nome]);
                return false;
            }

            // 2. Gera slug a partir do nome
            $slug = SlugGenerator::fromString($nome);

            // 3. Verifica duplicidade de slug (adiciona sufixo numérico se necessário)
            $slug = $this->gerarSlugUnico($slug, 'marca');

            // 4. Salva a marca no banco
            $dados = [
                'nome' => $nome,
                'slug' => $slug,
                'logo' => $logoPath,
            ];

            $id = $this->marcaRepo->save($dados);
            if ($id === false) {
                $this->logger->error('Falha ao salvar marca no banco', ['nome' => $nome, 'slug' => $slug]);
                return false;
            }

            $this->logger->info('Marca criada com sucesso', [
                'id'   => $id,
                'nome' => $nome,
                'slug' => $slug,
                'logo' => $logoPath ?? 'N/A',
            ]);

            return $id;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao criar marca', [
                'nome'  => $nome,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Gera um slug único para marca ou modelo, adicionando sufixo numérico se necessário.
     *
     * @param string $slugBase Slug base (já normalizado)
     * @param string $tipo 'marca' ou 'modelo'
     * @return string Slug único
     */
    private function gerarSlugUnico(string $slugBase, string $tipo): string
    {
        $slug = $slugBase;
        $contador = 1;

        if ($tipo === 'marca') {
            while ($this->marcaRepo->slugExists($slug)) {
                $slug = $slugBase . '-' . $contador++;
            }
        } else {
            while ($this->modeloRepo->slugExists($slug)) {
                $slug = $slugBase . '-' . $contador++;
            }
        }

        return $slug;
    }
}