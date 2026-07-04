<?php

declare(strict_types=1);

namespace App\Service;

use App\Helpers\RandomGenerator;
use App\Helpers\SlugGenerator;
use App\Repository\VeiculoRepository;
use App\Repository\VeiculoCombustaoRepository;
use App\Repository\VeiculoEletricoRepository;
use App\Repository\VeiculoHibridoRepository;
use App\Repository\VeiculoGNVRepository;
use App\Repository\VeiculoOpcionalRepository;
use App\Repository\OpcionalRepository;
use App\Repository\VeiculoImagemRepository;
use App\Repository\MarcaRepository;
use App\Repository\ModeloRepository;
use App\Helpers\UploadHelper;
use Psr\Log\LoggerInterface;
use PDO;

/**
 * Service para gerenciamento de veículos.
 * Orquestra as operações entre os repositórios e helpers.
 */
class VeiculoService
{
    private const TIPO_COMBUSTAO = 'combustao';
    private const TIPO_ELETRICO  = 'eletrico';
    private const TIPO_HIBRIDO   = 'hibrido';

    public function __construct(
        private VeiculoRepository $veiculoRepo,
        private VeiculoCombustaoRepository $combustaoRepo,
        private VeiculoEletricoRepository $eletricoRepo,
        private VeiculoHibridoRepository $hibridoRepo,
        private VeiculoGNVRepository $gnvRepo,
        private VeiculoOpcionalRepository $opcionalRelRepo,
        private OpcionalRepository $opcionalRepo,
        private VeiculoImagemRepository $veiculoImagemRepo,
        private MarcaRepository $marcaRepo,
        private ModeloRepository $modeloRepo,
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    /**
     * Cria um novo veículo com todos os dados e opcionais.
     *
     * @param array $dados Dados principais e específicos do veículo.
     * @param array $opcionaisIds Lista de IDs de opcionais selecionados.
     * @param string $tipoVeiculo 'combustao', 'eletrico' ou 'hibrido'.
     * @return int|false ID do veículo criado ou false em caso de erro.
     */
    public function salvar(array $dados, array $opcionaisIds, string $tipoVeiculo): int|false
    {
        // Valida tipo de veículo
        if (!in_array($tipoVeiculo, [self::TIPO_COMBUSTAO, self::TIPO_ELETRICO, self::TIPO_HIBRIDO])) {
            $this->logger->error('Tipo de veículo inválido', ['tipo' => $tipoVeiculo]);
            return false;
        }

        // Valida se GNV é permitido para o tipo de veículo
        if (!empty($dados['gnv_instalado']) && $tipoVeiculo !== self::TIPO_COMBUSTAO) {
            $this->logger->error('GNV só pode ser instalado em veículos a combustão', [
                'tipo' => $tipoVeiculo,
            ]);
            return false;
        }

        // Gera hash_id único
        $hashId = $this->gerarHashUnico();
        $dados['hash_id'] = $hashId;

        // Define valores padrão
        $dados['gnv_instalado'] = $dados['gnv_instalado'] ?? 0;
        $dados['status_estoque'] = $dados['status_estoque'] ?? 'disponivel';
        $dados['status_vitrine'] = $dados['status_vitrine'] ?? 'inativo';

        $this->pdo->beginTransaction();

        try {
            // 1. Salva veículo principal
            $veiculoId = $this->veiculoRepo->save($dados);
            if ($veiculoId === false) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao salvar veículo principal');
                return false;
            }

            // 2. Salva complemento específico
            $complementoSalvo = $this->salvarComplemento($veiculoId, $tipoVeiculo, $dados);
            if (!$complementoSalvo) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao salvar complemento do veículo', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 3. Salva GNV se aplicável
            if (!empty($dados['gnv_instalado'])) {
                $gnvSalvo = $this->salvarGNV($veiculoId, $dados);
                if (!$gnvSalvo) {
                    $this->pdo->rollBack();
                    $this->logger->error('Falha ao salvar dados GNV', ['veiculo_id' => $veiculoId]);
                    return false;
                }
            }

            // 4. Sincroniza opcionais
            $syncOk = $this->opcionalRelRepo->sync($veiculoId, $opcionaisIds);
            if (!$syncOk) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao sincronizar opcionais', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 5. Processa imagens do veículo
            if (!$this->processarImagensCadastro($veiculoId, $hashId, $dados)) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao processar imagens do veículo', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 6. Commit final
            $this->pdo->commit();

            $this->logger->info('Veículo criado com sucesso', [
                'veiculo_id' => $veiculoId,
                'tipo'       => $tipoVeiculo,
                'hash_id'    => $hashId,
            ]);

            return $veiculoId;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error('Erro ao salvar veículo', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza um veículo existente.
     *
     * @param int $veiculoId
     * @param array $dados Dados atualizados.
     * @param array $opcionaisIds Lista de IDs de opcionais selecionados.
     * @param string $tipoVeiculo 'combustao', 'eletrico' ou 'hibrido'.
     * @return bool
     */
    public function atualizar(int $veiculoId, array $dados, array $opcionaisIds, string $tipoVeiculo): bool
    {
        // Valida tipo de veículo
        if (!in_array($tipoVeiculo, [self::TIPO_COMBUSTAO, self::TIPO_ELETRICO, self::TIPO_HIBRIDO])) {
            $this->logger->error('Tipo de veículo inválido na atualização', ['tipo' => $tipoVeiculo]);
            return false;
        }

        // Valida se GNV é permitido para o tipo de veículo
        if (!empty($dados['gnv_instalado']) && $tipoVeiculo !== self::TIPO_COMBUSTAO) {
            $this->logger->error('GNV só pode ser instalado em veículos a combustão', [
                'tipo' => $tipoVeiculo,
            ]);
            return false;
        }

        // Verifica se o veículo existe e não está deletado
        $veiculo = $this->veiculoRepo->findById($veiculoId);
        if (!$veiculo) {
            $this->logger->warning('Tentativa de atualizar veículo inexistente ou deletado', ['veiculo_id' => $veiculoId]);
            return false;
        }

        // Busca os nomes da marca e modelo (se os IDs estiverem presentes)
        $marcaId = $dados['marca_id'] ?? $veiculo['marca_id'] ?? null;
        $modeloId = $dados['modelo_id'] ?? $veiculo['modelo_id'] ?? null;

        if ($marcaId && $modeloId) {
            $marca = $this->marcaRepo->findById($marcaId);
            $modelo = $this->modeloRepo->findById($modeloId);

            if (!$marca || !$modelo) {
                $this->logger->error('Marca ou modelo não encontrados', [
                    'marca_id'  => $marcaId,
                    'modelo_id' => $modeloId,
                ]);
                return false;
            }

            // Gera o slug com base nos nomes da marca/modelo e ano (se disponível)
            $ano = (int) ($dados['ano_modelo'] ?? $veiculo['ano_modelo'] ?? 0);
            $dados['slug'] = SlugGenerator::generate($marca['nome'], $modelo['nome'], $ano);
        } else {
            // Fallback: manter o slug existente (caso os IDs não sejam fornecidos)
            $dados['slug'] = $veiculo['slug'] ?? null;
        }

        // Se o tipo mudou, precisamos deletar o complemento antigo
        $tipoAtual = $this->detectarTipoAtual($veiculoId);
        $deveDeletarComplementoAntigo = ($tipoAtual !== null && $tipoAtual !== $tipoVeiculo);

        $this->pdo->beginTransaction();

        try {
            // 1. Atualiza veículo principal
            $dadosParaUpdate = $dados;
            $updateOk = $this->veiculoRepo->update($veiculoId, $this->filtrarDadosPrincipais($dadosParaUpdate));
            if (!$updateOk) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao atualizar veículo principal', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 2. Se o tipo mudou, deleta o complemento antigo (verifica retorno)
            if ($deveDeletarComplementoAntigo) {
                $deletou = $this->deletarComplemento($veiculoId, $tipoAtual);
                if (!$deletou) {
                    $this->pdo->rollBack();
                    $this->logger->error('Falha ao deletar complemento antigo', ['veiculo_id' => $veiculoId, 'tipo' => $tipoAtual]);
                    return false;
                }
            }

            // 3. Salva complemento específico
            $complementoSalvo = $this->salvarComplemento($veiculoId, $tipoVeiculo, $dados);
            if (!$complementoSalvo) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao salvar complemento do veículo', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 4. Gerencia GNV
            $gnvGerenciado = $this->gerenciarGNV($veiculoId, $dados);
            if (!$gnvGerenciado) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao gerenciar GNV', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 5. Sincroniza opcionais
            $syncOk = $this->opcionalRelRepo->sync($veiculoId, $opcionaisIds);
            if (!$syncOk) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao sincronizar opcionais na atualização', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 6. Processa imagens do veículo
            if (!$this->processarImagensAtualizacao($veiculoId, $veiculo['hash_id'], $dados)) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao processar imagens na atualização', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 7. Commit final
            $this->pdo->commit();

            $this->logger->info('Veículo atualizado com sucesso', ['veiculo_id' => $veiculoId]);
            return true;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error('Erro ao atualizar veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Gerencia a sincronização do kit GNV de um veículo.
     * Adiciona, remove ou atualiza conforme a flag gnv_instalado.
     *
     * @param int $veiculoId
     * @param array $dados
     * @return bool
     */
    private function gerenciarGNV(int $veiculoId, array $dados): bool
    {
        $gnvAtual = $this->gnvRepo->findByVeiculoId($veiculoId);
        $temGNV = ($gnvAtual !== null);
        $gnvSolicitado = isset($dados['gnv_instalado']) ? (int) $dados['gnv_instalado'] : ($temGNV ? 1 : 0);

        if ($gnvSolicitado && !$temGNV) {
            // Adicionar GNV
            $gnvSalvo = $this->salvarGNV($veiculoId, $dados);
            if (!$gnvSalvo) {
                $this->logger->error('Falha ao salvar dados GNV (novo)', ['veiculo_id' => $veiculoId]);
                return false;
            }

            $flagAtualizada = $this->veiculoRepo->update($veiculoId, ['gnv_instalado' => 1]);
            if (!$flagAtualizada) {
                $this->logger->error('Falha ao atualizar flag gnv_instalado (adição)', ['veiculo_id' => $veiculoId]);
                return false;
            }

        } elseif (!$gnvSolicitado && $temGNV) {
            // Remover GNV
            $gnvRemovido = $this->gnvRepo->delete($veiculoId);
            if (!$gnvRemovido) {
                $this->logger->error('Falha ao remover dados GNV', ['veiculo_id' => $veiculoId]);
                return false;
            }

            $flagAtualizada = $this->veiculoRepo->update($veiculoId, ['gnv_instalado' => 0]);
            if (!$flagAtualizada) {
                $this->logger->error('Falha ao atualizar flag gnv_instalado (remoção)', ['veiculo_id' => $veiculoId]);
                return false;
            }

        } elseif ($gnvSolicitado && $temGNV) {
            // Atualizar GNV
            $gnvSalvo = $this->salvarGNV($veiculoId, $dados);
            if (!$gnvSalvo) {
                $this->logger->error('Falha ao atualizar dados GNV', ['veiculo_id' => $veiculoId]);
                return false;
            }
            // Flag já está como 1, não precisa atualizar
        }

        return true;
    }

    /**
     * Remove um veículo (soft delete).
     *
     * @param int $veiculoId
     * @return bool
     */
    public function deletar(int $veiculoId): bool
    {
        // Verifica se existe e não está deletado
        $veiculo = $this->veiculoRepo->findById($veiculoId);
        if (!$veiculo) {
            $this->logger->warning('Tentativa de deletar veículo inexistente ou já deletado', ['veiculo_id' => $veiculoId]);
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            // Antes de deletar, desativa a vitrine
            $updateOk = $this->veiculoRepo->update($veiculoId, ['status_vitrine' => 'inativo']);
            if (!$updateOk) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao desativar vitrine antes de deletar', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // Soft delete
            $deleted = $this->veiculoRepo->delete($veiculoId);
            if (!$deleted) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao deletar veículo', ['veiculo_id' => $veiculoId]);
                return false;
            }

            $this->pdo->commit();
            $this->logger->info('Veículo deletado com sucesso', ['veiculo_id' => $veiculoId]);
            return true;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error('Erro ao deletar veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Restaura um veículo deletado.
     *
     * @param int $veiculoId
     * @param int $lojistaId ID do lojista logado (para verificação de pertencimento)
     * @return bool
     */
    public function restaurar(int $veiculoId, int $lojistaId): bool
    {
        $veiculo = $this->veiculoRepo->findByIdIncludingDeleted($veiculoId);
        if (!$veiculo || $veiculo['deleted_at'] === null) {
            $this->logger->warning('Tentativa de restaurar veículo não deletado ou inexistente', ['veiculo_id' => $veiculoId]);
            return false;
        }

        // Verifica pertencimento
        if ($veiculo['lojista_id'] != $lojistaId) {
            $this->logger->warning('Tentativa de restaurar veículo de outro lojista', [
                'veiculo_id'   => $veiculoId,
                'lojista_id'   => $lojistaId,
                'veiculo_lojista_id' => $veiculo['lojista_id'],
            ]);
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            // Restaura o veículo (remove deleted_at)
            $restaurado = $this->veiculoRepo->restore($veiculoId);
            if (!$restaurado) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao restaurar veículo', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // Restaura a vitrine para ativo (opcional)
            $updateOk = $this->veiculoRepo->update($veiculoId, ['status_vitrine' => 'ativo']);
            if (!$updateOk) {
                $this->pdo->rollBack();
                $this->logger->warning('Falha ao reativar vitrine após restauração', ['veiculo_id' => $veiculoId]);
                return false;
            }

            $this->pdo->commit();
            $this->logger->info('Veículo restaurado com sucesso', ['veiculo_id' => $veiculoId]);
            return true;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error('Erro ao restaurar veículo', [
                'veiculo_id' => $veiculoId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Ativa/desativa a exibição na vitrine.
     *
     * @param int $veiculoId
     * @return bool
     */
    public function toggleVitrine(int $veiculoId): bool
    {
        $veiculo = $this->veiculoRepo->findById($veiculoId);
        if (!$veiculo) {
            $this->logger->warning('Veículo não encontrado para toggle vitrine', ['veiculo_id' => $veiculoId]);
            return false;
        }

        $novoStatus = $veiculo['status_vitrine'] === 'ativo' ? 'inativo' : 'ativo';
        return $this->veiculoRepo->update($veiculoId, ['status_vitrine' => $novoStatus]);
    }

    /**
     * Lista veículos de um lojista (painel).
     *
     * @param int $lojistaId
     * @param int $pagina
     * @param int $porPagina
     * @return array{veiculos: array, total: int, pagina: int, totalPaginas: int}
     */
    public function listarDoLojista(int $lojistaId, int $pagina = 1, int $porPagina = 20): array
    {
        $offset = ($pagina - 1) * $porPagina;
        $veiculos = $this->veiculoRepo->findByLojista($lojistaId, $porPagina, $offset);
        $total = $this->veiculoRepo->countByLojista($lojistaId);

        return [
            'veiculos'      => $veiculos,
            'total'         => $total,
            'pagina'        => $pagina,
            'totalPaginas'  => ceil($total / $porPagina),
        ];
    }

    /**
     * Lista veículos deletados de um lojista (lixeira).
     *
     * @param int $lojistaId
     * @param int $pagina
     * @param int $porPagina
     * @return array{veiculos: array, total: int, pagina: int, totalPaginas: int}
     */
    public function listarLixeira(int $lojistaId, int $pagina = 1, int $porPagina = 20): array
    {
        $offset = ($pagina - 1) * $porPagina;
        $veiculos = $this->veiculoRepo->findOnlyDeletedByLojista($lojistaId, $porPagina, $offset);
        $total = $this->veiculoRepo->countOnlyDeletedByLojista($lojistaId);

        return [
            'veiculos'      => $veiculos,
            'total'         => $total,
            'pagina'        => $pagina,
            'totalPaginas'  => ceil($total / $porPagina),
        ];
    }

    /**
     * Busca um veículo para a vitrine pública (por hash_id).
     *
     * @param string $hashId
     * @return array|null
     */
    public function buscarPorHash(string $hashId): ?array
    {
        return $this->veiculoRepo->findByHashId($hashId);
    }

    /**
     * Busca todos os dados de um veículo para edição (inclui complementos e opcionais).
     *
     * @param int $veiculoId
     * @return array|null
     */
    public function buscarParaEdicao(int $veiculoId): ?array
    {
        $veiculo = $this->veiculoRepo->findById($veiculoId);
        if (!$veiculo) {
            return null;
        }

        // Busca os nomes da marca e modelo
        $marca = $this->marcaRepo->findById($veiculo['marca_id'] ?? 0);
        $modelo = $this->modeloRepo->findById($veiculo['modelo_id'] ?? 0);

        $marcaNome = $marca['nome'] ?? null;
        $modeloNome = $modelo['nome'] ?? null;

        // Busca complemento
        $complemento = null;
        $tipo = $this->detectarTipoAtual($veiculoId);
        if ($tipo === self::TIPO_COMBUSTAO) {
            $complemento = $this->combustaoRepo->findByVeiculoId($veiculoId);
        } elseif ($tipo === self::TIPO_ELETRICO) {
            $complemento = $this->eletricoRepo->findByVeiculoId($veiculoId);
        } elseif ($tipo === self::TIPO_HIBRIDO) {
            $complemento = $this->hibridoRepo->findByVeiculoId($veiculoId);
        }

        // Busca GNV
        $gnv = $this->gnvRepo->findByVeiculoId($veiculoId);

        // Busca opcionais selecionados
        $opcionaisIds = $this->opcionalRelRepo->findByVeiculo($veiculoId);

        // Busca todos os opcionais agrupados
        $todosOpcionais = $this->opcionalRepo->findAllGrouped();

        // Busca imagens do veículo
        $imagens = $this->veiculoImagemRepo->findByVeiculo($veiculoId);

        return [
            'veiculo'                => $veiculo,
            'tipo'                   => $tipo,
            'complemento'            => $complemento,
            'gnv'                    => $gnv,
            'opcionais_selecionados' => $opcionaisIds,
            'todos_opcionais'        => $todosOpcionais,
            'imagens'                => $imagens,
            'marca_nome'             => $marcaNome,
            'modelo_nome'            => $modeloNome,
        ];
    }

    /**
     * Gera um hash_id único (loop com verificação no banco).
     *
     * @param int $tentativasMax
     * @return string
     * @throws \RuntimeException Se não conseguir gerar após várias tentativas.
     */
    private function gerarHashUnico(int $tentativasMax = 10): string
    {
        $tentativas = 0;
        do {
            $hash = RandomGenerator::generate(16);
            $existe = $this->veiculoRepo->hashIdExists($hash);
            $tentativas++;
            if ($tentativas >= $tentativasMax) {
                throw new \RuntimeException('Não foi possível gerar um hash_id único após ' . $tentativasMax . ' tentativas.');
            }
        } while ($existe);

        return $hash;
    }

    /**
     * Salva o complemento específico (combustão, elétrico ou híbrido).
     *
     * @param int $veiculoId
     * @param string $tipo
     * @param array $dados
     * @return bool
     */
    private function salvarComplemento(int $veiculoId, string $tipo, array $dados): bool
    {
        $dados['veiculo_id'] = $veiculoId;
        switch ($tipo) {
            case self::TIPO_COMBUSTAO:
                return $this->combustaoRepo->save($dados);
            case self::TIPO_ELETRICO:
                return $this->eletricoRepo->save($dados);
            case self::TIPO_HIBRIDO:
                return $this->hibridoRepo->save($dados);
            default:
                $this->logger->error('Tipo de veículo inválido', ['tipo' => $tipo]);
                return false;
        }
    }

    /**
     * Deleta o complemento de um veículo (usado quando o tipo muda).
     *
     * @param int $veiculoId
     * @param string $tipo
     * @return bool
     */
    private function deletarComplemento(int $veiculoId, string $tipo): bool
    {
        switch ($tipo) {
            case self::TIPO_COMBUSTAO:
                return $this->combustaoRepo->delete($veiculoId);
            case self::TIPO_ELETRICO:
                return $this->eletricoRepo->delete($veiculoId);
            case self::TIPO_HIBRIDO:
                return $this->hibridoRepo->delete($veiculoId);
            default:
                return false;
        }
    }

    /**
     * Detecta o tipo atual do veículo com base na existência de registros nos complementos.
     *
     * @param int $veiculoId
     * @return string|null 'combustao', 'eletrico', 'hibrido' ou null se nenhum.
     */
    private function detectarTipoAtual(int $veiculoId): ?string
    {
        if ($this->combustaoRepo->findByVeiculoId($veiculoId)) {
            return self::TIPO_COMBUSTAO;
        }
        if ($this->eletricoRepo->findByVeiculoId($veiculoId)) {
            return self::TIPO_ELETRICO;
        }
        if ($this->hibridoRepo->findByVeiculoId($veiculoId)) {
            return self::TIPO_HIBRIDO;
        }
        return null;
    }

    /**
     * Salva os dados GNV de um veículo.
     *
     * @param int $veiculoId
     * @param array $dados
     * @return bool
     */
    private function salvarGNV(int $veiculoId, array $dados): bool
    {
        $dados['veiculo_id'] = $veiculoId;
        return $this->gnvRepo->save($dados);
    }

    /**
     * Filtra apenas os campos que pertencem à tabela veiculos.
     *
     * @param array $dados
     * @return array
     */
    private function filtrarDadosPrincipais(array $dados): array
    {
        $allowed = [
            'marca_id', 'modelo_id', 'versao', 'ano_fabricacao', 'ano_modelo',
            'cor', 'quilometragem', 'preco', 'numero_portas', 'numero_assentos',
            'comprimento_mm', 'largura_mm', 'altura_mm', 'distancia_entre_eixos_mm',
            'peso_ordem_marcha_kg', 'volume_porta_malas_l', 'volume_cacamba_l',
            'carga_util_kg', 'capacidade_reboque_kg', 'gnv_instalado',
            'status_estoque', 'status_vitrine'
        ];
        return array_intersect_key($dados, array_flip($allowed));
    }

    /**
     * Lista veículos para a vitrine pública (apenas ativos).
     *
     * @param int $lojistaId
     * @param int $limite
     * @param int $offset
     * @return array
     */
    public function listarParaVitrine(int $lojistaId, int $limite = 12, int $offset = 0): array
    {
        return $this->veiculoRepo->findAtivosParaVitrine($lojistaId, $limite, $offset);
    }

    /**
     * Conta veículos ativos de um lojista para a vitrine.
     *
     * @param int $lojistaId
     * @return int
     */
    public function countParaVitrine(int $lojistaId): int
    {
        return $this->veiculoRepo->countAtivosParaVitrine($lojistaId);
    }

    /**
     * Busca todos os opcionais agrupados por categoria.
     * Útil para renderizar o formulário de cadastro/edição.
     *
     * @return array
     */
    public function buscarOpcionaisAgrupados(): array
    {
        return $this->opcionalRepo->findAllGrouped();
    }

    /**
     * Busca um veículo pelo ID, incluindo registros com soft delete.
     * Útil para operações de restauração ou logs.
     *
     * @param int $id
     * @return array|null
     */
    public function buscarPorIdIncluindoDeletado(int $id): ?array
    {
        return $this->veiculoRepo->findByIdIncludingDeleted($id);
    }

    /**
     * Remove uma imagem específica de um veículo.
     * 
     * @param int $imagemId
     * @param int $veiculoId
     * @return bool
     */
    public function deletarImagem(int $imagemId, int $veiculoId): bool
    {
        // 1. Busca a imagem no banco
        $imagem = $this->veiculoImagemRepo->findById($imagemId);
        if (!$imagem) {
            $this->logger->warning('Tentativa de deletar imagem inexistente', [
                'imagem_id' => $imagemId,
                'veiculo_id' => $veiculoId,
            ]);
            return false;
        }

        // 2. Verifica pertencimento
        if ($imagem['veiculo_id'] != $veiculoId) {
            $this->logger->warning('Tentativa de deletar imagem de outro veículo', [
                'imagem_id'       => $imagemId,
                'veiculo_id'      => $veiculoId,
                'veiculo_imagem'  => $imagem['veiculo_id'],
            ]);
            return false;
        }

        // 3. Remove o arquivo físico do disco
        $caminhoAbsoluto = $this->getCaminhoAbsolutoImagem($imagem['caminho']);
        if (file_exists($caminhoAbsoluto)) {
            if (!unlink($caminhoAbsoluto)) {
                $this->logger->error('Falha ao deletar arquivo de imagem do disco', [
                    'imagem_id' => $imagemId,
                    'caminho'   => $caminhoAbsoluto,
                ]);
                // Continuamos mesmo se falhar, para remover o registro do banco?
                // Vamos optar por retornar false para manter consistência.
                return false;
            }
        } else {
            $this->logger->warning('Arquivo de imagem não encontrado no disco', [
                'imagem_id' => $imagemId,
                'caminho'   => $caminhoAbsoluto,
            ]);
            // Se o arquivo não existe, removemos apenas o registro do banco (pode ser um dado órfão).
            // Continuamos para deletar do banco.
        }

        // 4. Remove o registro do banco
        $deletado = $this->veiculoImagemRepo->deleteById($imagemId, $veiculoId);
        if (!$deletado) {
            $this->logger->error('Falha ao deletar registro de imagem', [
                'imagem_id' => $imagemId,
                'veiculo_id' => $veiculoId,
            ]);
            return false;
        }

        $this->logger->info('Imagem deletada com sucesso', [
            'imagem_id' => $imagemId,
            'veiculo_id' => $veiculoId,
            'caminho'   => $imagem['caminho'],
        ]);

        return true;
    }

    /**
     * Obtém o caminho absoluto de uma imagem a partir do caminho relativo.
     *
     * @param string $caminhoRelativo Caminho relativo à pasta storage/uploads/ (ex: 'veiculos/hash_id/imagem.jpg')
     * @return string
     */
    private function getCaminhoAbsolutoImagem(string $caminhoRelativo): string
    {
        return ROOT_DIR . '/storage/uploads/' . $caminhoRelativo;
    }

    /**
     * Processa o upload e sincronização de imagens no cadastro de um veículo.
     *
     * @param int $veiculoId
     * @param string $hashId
     * @param array $dados Deve conter 'imagens' (array de arquivos) e 'capa_index' (opcional)
     * @return bool
     */
    private function processarImagensCadastro(int $veiculoId, string $hashId, array $dados): bool
    {
        // Extrai dados do array
        $arquivos = $dados['imagens'] ?? [];
        $capaIndex = $dados['capa_index'] ?? null;

        // Se não houver imagens, retorna sucesso
        if (empty($arquivos)) {
            return true;
        }

        $idsInseridos = [];
        $ordem = 0;

        foreach ($arquivos as $arquivo) {
            // Upload do arquivo usando o helper
            $caminhoRelativo = UploadHelper::upload($arquivo, 'veiculos/' . $hashId);
            if ($caminhoRelativo === false) {
                $this->logger->error('Falha no upload do arquivo', [
                    'name' => $arquivo['name'] ?? 'unknown',
                ]);
                return false;
            }

            $caminhoAbsoluto = ROOT_DIR . '/storage/uploads/' . $caminhoRelativo;

            // Salva a imagem no banco e obtém o ID
            $imagemId = $this->veiculoImagemRepo->save([
                'veiculo_id'    => $veiculoId,
                'caminho'       => $caminhoRelativo,
                'nome_original' => $arquivo['name'],
                'mime_type'     => mime_content_type($caminhoAbsoluto) ?: $arquivo['type'] ?? 'image/jpeg',
                'tamanho_bytes' => filesize($caminhoAbsoluto) ?: 0,
                'capa'          => 0,
                'ordem'         => $ordem++,
            ]);

            if ($imagemId === false) {
                $this->logger->error('Falha ao salvar imagem no banco', [
                    'caminho' => $caminhoRelativo,
                ]);
                return false;
            }

            $idsInseridos[] = $imagemId;
        }

        // Determina o ID da capa com base no índice fornecido
        $capaId = null;
        if ($capaIndex !== null && $capaIndex >= 0 && $capaIndex < count($idsInseridos)) {
            $capaId = $idsInseridos[$capaIndex];
        }

        // Sincroniza imagens (apenas reordena e define capa)
        $imagensData = [
            'ids_manter' => $idsInseridos,
            'novas'      => [],
            'capa_id'    => $capaId, // Se null, o sync define a primeira imagem como capa
        ];

        if (!$this->veiculoImagemRepo->sync($veiculoId, $imagensData)) {
            $this->logger->error('Falha ao sincronizar imagens no cadastro', ['veiculo_id' => $veiculoId]);
            return false;
        }

        $this->logger->info('Imagens cadastradas com sucesso', [
            'veiculo_id' => $veiculoId,
            'total'      => count($idsInseridos),
            'capa_id'    => $capaId,
        ]);

        return true;
    }

    /**
     * Processa o upload, remoção e sincronização de imagens na atualização de um veículo.
     *
     * @param int $veiculoId
     * @param string $hashId
     * @param array $dadosImagens Deve conter: ids_manter (array), novas (array de arquivos), capa_id (int|null)
     * @return bool
     */
    private function processarImagensAtualizacao(int $veiculoId, string $hashId, array $dadosImagens): bool
    {
        $idsManter = $dadosImagens['ids_manter'] ?? [];
        $novas = $dadosImagens['novas'] ?? [];
        $capaId = $dadosImagens['capa_id'] ?? null;

        // Busca imagens atuais do veículo
        $atuais = $this->veiculoImagemRepo->findByVeiculo($veiculoId);
        $idsAtuais = array_column($atuais, 'id');

        // 1. Identifica imagens a remover (que não estão em ids_manter)
        $idsRemover = array_diff($idsAtuais, $idsManter);

        // 2. Processa upload de novas imagens (se houver)
        $dadosNovas = [];
        if (!empty($novas)) {
            foreach ($novas as $arquivo) {
                // Upload do arquivo usando o helper
                $caminhoRelativo = UploadHelper::upload($arquivo, 'veiculos/' . $hashId);
                if ($caminhoRelativo === false) {
                    $this->logger->error('Falha no upload do arquivo', [
                        'name' => $arquivo['name'] ?? 'unknown',
                    ]);
                    return false;
                }

                $caminhoAbsoluto = ROOT_DIR . '/storage/uploads/' . $caminhoRelativo;

                // Monta dados da nova imagem
                $dadosNovas[] = [
                    'caminho'       => $caminhoRelativo,
                    'nome_original' => $arquivo['name'],
                    'mime_type'     => mime_content_type($caminhoAbsoluto) ?: $arquivo['type'] ?? 'image/jpeg',
                    'tamanho_bytes' => filesize($caminhoAbsoluto) ?: 0,
                ];
            }
        }

        // 3. Prepara dados para sync
        $imagensData = [
            'ids_manter' => $idsManter,
            'novas'      => $dadosNovas,
            'capa_id'    => $capaId,
        ];

        // 4. Sincroniza imagens (registra alterações, atualiza capa e reordena)
        if (!$this->veiculoImagemRepo->sync($veiculoId, $imagensData)) {
            $this->logger->error('Falha ao sincronizar imagens na atualização', ['veiculo_id' => $veiculoId]);
            return false;
        }

        // 5. Remove arquivos físicos das imagens removidas (agora com consulta em lote)
        if (!empty($idsRemover)) {
            // Busca todos os caminhos de uma vez
            $caminhos = $this->veiculoImagemRepo->findCaminhosByIds($idsRemover);

            foreach ($caminhos as $id => $caminho) {
                $caminhoAbsoluto = $this->getCaminhoAbsolutoImagem($caminho);
                if (file_exists($caminhoAbsoluto)) {
                    if (!unlink($caminhoAbsoluto)) {
                        $this->logger->error('Falha ao deletar arquivo de imagem na atualização', [
                            'imagem_id' => $id,
                            'caminho'   => $caminhoAbsoluto,
                        ]);
                        // Não retornamos false, pois o sync já foi bem-sucedido.
                    }
                } else {
                    $this->logger->warning('Arquivo de imagem não encontrado para remoção', [
                        'imagem_id' => $id,
                        'caminho'   => $caminhoAbsoluto,
                    ]);
                }
            }
        }

        $this->logger->info('Imagens atualizadas com sucesso', [
            'veiculo_id'        => $veiculoId,
            'removidas'         => count($idsRemover),
            'novas_adicionadas' => count($dadosNovas),
            'capa_id'           => $capaId,
        ]);

        return true;
    }

    /**
     * Lista veículos de um lojista com os nomes da marca e modelo.
     *
     * @param int $lojistaId
     * @param int $pagina
     * @param int $porPagina
     * @return array{veiculos: array, total: int, pagina: int, totalPaginas: int}
     */
    public function listarDoLojistaComNomes(int $lojistaId, int $pagina = 1, int $porPagina = 20): array
    {
        // Obtém a lista base com os IDs
        $resultado = $this->listarDoLojista($lojistaId, $pagina, $porPagina);

        // Enriquece cada veículo com os nomes da marca e modelo
        foreach ($resultado['veiculos'] as &$veiculo) {
            $marca = $this->marcaRepo->findById($veiculo['marca_id'] ?? 0);
            $modelo = $this->modeloRepo->findById($veiculo['modelo_id'] ?? 0);

            $veiculo['marca_nome'] = $marca['nome'] ?? 'N/A';
            $veiculo['modelo_nome'] = $modelo['nome'] ?? 'N/A';
        }

        return $resultado;
    }

    /**
     * Lista veículos de um lojista com filtro, paginação e nomes de marca/modelo.
     *
     * @param int $lojistaId
     * @param string|null $filtro 'vitrine', 'vendidos', 'reservados', 'estoque' ou null para todos
     * @param int $pagina
     * @param int $porPagina
     * @return array{veiculos: array, total: int, pagina: int, totalPaginas: int}
     */
    public function listarDoLojistaComFiltro(int $lojistaId, ?string $filtro = null, int $pagina = 1, int $porPagina = 20): array
    {
        // Converte a string de filtro para o array de condições
        $filtros = $this->mapearFiltro($filtro);

        $offset = ($pagina - 1) * $porPagina;

        // Busca os veículos e o total já filtrados
        $veiculos = $this->veiculoRepo->findByLojistaComFiltro($lojistaId, $filtros, $porPagina, $offset);
        $total = $this->veiculoRepo->countByLojistaComFiltro($lojistaId, $filtros);

        // Enriquece cada veículo com os nomes da marca e modelo
        foreach ($veiculos as &$veiculo) {
            $marca = $this->marcaRepo->findById($veiculo['marca_id'] ?? 0);
            $modelo = $this->modeloRepo->findById($veiculo['modelo_id'] ?? 0);

            $veiculo['marca_nome'] = $marca['nome'] ?? 'N/A';
            $veiculo['modelo_nome'] = $modelo['nome'] ?? 'N/A';
        }
        unset($veiculo); // Remove a referência para evitar efeitos colaterais

        return [
            'veiculos'      => $veiculos,
            'total'         => $total,
            'pagina'        => $pagina,
            'totalPaginas'  => (int) ceil($total / $porPagina),
        ];
    }

    /**
     * Converte a string de filtro para o array de condições do Repository.
     *
     * @param string|null $filtro
     * @return array
     */
    private function mapearFiltro(?string $filtro): array
    {
        return match ($filtro) {
            'vitrine'   => ['status_vitrine' => 'ativo', 'status_estoque' => 'disponivel'],
            'vendidos'  => ['status_estoque' => 'vendido'],
            'reservados'=> ['status_estoque' => 'reservado'],
            'estoque'   => ['status_estoque' => 'disponivel', 'status_vitrine' => 'inativo'],
            default     => [],
        };
    }

}