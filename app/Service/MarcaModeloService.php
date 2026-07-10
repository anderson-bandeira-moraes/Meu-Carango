<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MarcaRepository;
use App\Repository\ModeloRepository;
use App\Helpers\SlugGenerator;
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
        $nome = $this->normalizarNome($nome);
        
        try {
            // 1. Valida se o nome já existe
            if ($this->validarNomeMarca($nome)) {
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

    /**
     * Lista todos os modelos de uma marca específica.
     *
     * @param int $marcaId ID da marca
     * @return array Lista de modelos com os campos: id, nome
     */
    public function listarModelosPorMarca(int $marcaId): array
    {
        try {
            // Verifica se a marca existe
            $marca = $this->marcaRepo->findById($marcaId);
            if (!$marca) {
                $this->logger->warning('Tentativa de listar modelos de marca inexistente', ['marca_id' => $marcaId]);
                return [];
            }

            // Busca os modelos da marca
            $modelos = $this->modeloRepo->findByMarcaId($marcaId);

            // Retorna apenas id e nome (e opcionalmente slug, se quiser)
            $resultado = array_map(function ($modelo) {
                return [
                    'id'   => (int) $modelo['id'],
                    'nome' => $modelo['nome'],
                ];
            }, $modelos);

            $this->logger->debug('Modelos listados por marca', [
                'marca_id' => $marcaId,
                'total'    => count($resultado),
            ]);

            return $resultado;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar modelos por marca', [
                'marca_id' => $marcaId,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Cria um novo modelo para uma marca específica.
     *
     * @param int $marcaId ID da marca
     * @param string $nome Nome do modelo (obrigatório)
     * @return int|false ID do modelo criado ou false em caso de erro
     */
    public function criarModelo(int $marcaId, string $nome): int|false
    {
        $nome = $this->normalizarNome($nome);

        try {
            // 1. Valida se a marca existe
            $marca = $this->marcaRepo->findById($marcaId);
            if (!$marca) {
                $this->logger->warning('Tentativa de criar modelo para marca inexistente', ['marca_id' => $marcaId]);
                return false;
            }

            // 2. Valida se já existe modelo com o mesmo nome para a marca
            if ($this->validarNomeModelo($marcaId, $nome)) {
                $this->logger->warning('Tentativa de criar modelo com nome duplicado para a marca', [
                    'marca_id' => $marcaId,
                    'nome'     => $nome,
                ]);
                return false;
            }

            // 3. Gera slug a partir do nome
            $slug = SlugGenerator::fromString($nome);

            // 4. Verifica duplicidade de slug (global) - adiciona sufixo numérico se necessário
            $slug = $this->gerarSlugUnico($slug, 'modelo');

            // 5. Salva o modelo no banco
            $dados = [
                'marca_id' => $marcaId,
                'nome'     => $nome,
                'slug'     => $slug,
            ];

            $id = $this->modeloRepo->save($dados);
            if ($id === false) {
                $this->logger->error('Falha ao salvar modelo no banco', [
                    'marca_id' => $marcaId,
                    'nome'     => $nome,
                    'slug'     => $slug,
                ]);
                return false;
            }

            $this->logger->info('Modelo criado com sucesso', [
                'id'       => $id,
                'marca_id' => $marcaId,
                'nome'     => $nome,
                'slug'     => $slug,
            ]);

            return $id;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao criar modelo', [
                'marca_id' => $marcaId,
                'nome'     => $nome,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se um nome de marca já existe no banco.
     *
     * @param string $nome Nome da marca a ser verificado
     * @return bool True se o nome já existe, false caso contrário
     */
    private function validarNomeMarca(string $nome): bool
    {
        try {
            $existe = (bool) $this->marcaRepo->findByNome($nome);
            
            if ($existe) {
                $this->logger->debug('Verificação de nome de marca: nome já existe', ['nome' => $nome]);
            }
            
            return $existe;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao verificar existência de nome de marca', [
                'nome'  => $nome,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se já existe um modelo com o mesmo nome para uma determinada marca.
     *
     * @param int $marcaId ID da marca
     * @param string $nome Nome do modelo a ser verificado
     * @return bool True se o modelo já existe para a marca, false caso contrário
     */
    private function validarNomeModelo(int $marcaId, string $nome): bool
    {
        try {
            $existe = (bool) $this->modeloRepo->marcaIdAndNomeExists($marcaId, $nome);
            
            if ($existe) {
                $this->logger->debug('Verificação de nome de modelo: nome já existe para a marca', [
                    'marca_id' => $marcaId,
                    'nome'     => $nome,
                ]);
            }
            
            return $existe;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao verificar existência de nome de modelo para a marca', [
                'marca_id' => $marcaId,
                'nome'     => $nome,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Busca uma marca pelo ID, com logo_url incluída.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarMarcaPorId(int $id): ?array
    {
        $marca = $this->marcaRepo->findById($id);
        if (!$marca) {
            return null;
        }
        $marca['logo_url'] = logo_url($marca['logo'] ?? null);
        return $marca;
    }

    /**
     * Normaliza um nome para capitalização padrão, preservando siglas.
     *
     * - Se a string estiver toda em maiúsculas, mantém como está (sigla).
     * - Caso contrário, aplica ucwords(mb_strtolower()) para capitalizar corretamente.
     *
     * @param string $nome
     * @return string
     */
    private function normalizarNome(string $nome): string
    {
        // Remove espaços extras e normaliza espaços entre palavras
        $nome = trim(preg_replace('/\s+/', ' ', $nome));
        // Se estiver toda em maiúsculas, mantém (sigla)
        if (mb_strtoupper($nome) === $nome) {
            return $nome;
        }
        // Caso contrário, capitaliza a primeira letra de cada palavra e converte o restante para minúsculas
        return ucwords(mb_strtolower($nome));
    }
}