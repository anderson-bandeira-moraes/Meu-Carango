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

        // Gera slug descritivo
        $dados['slug'] = SlugGenerator::generate(
            $dados['marca'] ?? '',
            $dados['modelo'] ?? '',
            (int) ($dados['ano_modelo'] ?? 0)
        );

        // Define valores padrão
        // A flag gnv_instalado é definida como 0 por padrão caso o formulário não envie o campo.
        // Isso garante que o campo esteja sempre presente no array, evitando erros de índice
        // e garantindo que veículos sem GNV sejam salvos corretamente com o valor 0.
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

            // 4. Sincroniza opcionais (agora dentro da transação)
            $syncOk = $this->opcionalRelRepo->sync($veiculoId, $opcionaisIds);
            if (!$syncOk) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao sincronizar opcionais', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 5. Commit final
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

            // 5. Sincroniza opcionais (agora dentro da transação)
            $syncOk = $this->opcionalRelRepo->sync($veiculoId, $opcionaisIds);
            if (!$syncOk) {
                $this->pdo->rollBack();
                $this->logger->error('Falha ao sincronizar opcionais na atualização', ['veiculo_id' => $veiculoId]);
                return false;
            }

            // 6. Commit final
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
     * @return bool
     */
    public function restaurar(int $veiculoId): bool
    {
        $veiculo = $this->veiculoRepo->findByIdIncludingDeleted($veiculoId);
        if (!$veiculo || $veiculo['deleted_at'] === null) {
            $this->logger->warning('Tentativa de restaurar veículo não deletado ou inexistente', ['veiculo_id' => $veiculoId]);
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
                // Não é crítico, mas vamos manter consistência: se o update falhar, ainda assim consideramos restaurado?
                // Optamos por rollback para manter consistência total.
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

        return [
            'veiculo'          => $veiculo,
            'tipo'             => $tipo,
            'complemento'      => $complemento,
            'gnv'              => $gnv,
            'opcionais_selecionados' => $opcionaisIds,
            'todos_opcionais'  => $todosOpcionais,
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
            'marca', 'modelo', 'versao', 'ano_fabricacao', 'ano_modelo',
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

}