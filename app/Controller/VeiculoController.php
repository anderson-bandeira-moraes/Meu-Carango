<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;
use App\Service\VeiculoService;
use App\Requests\VeiculoRequest;
use App\Requests\VeiculoCombustaoRequest;
use App\Requests\VeiculoEletricoRequest;
use App\Requests\VeiculoHibridoRequest;
use App\Requests\VeiculoGNVRequest;
use App\Requests\VeiculoOpcionalRequest;
use App\Requests\VeiculoImagemRequest;

/**
 * Controlador para gerenciamento de veículos do lojista.
 * Gerencia CRUD, soft delete, restauração, vitrine e listagens.
 */
class VeiculoController
{
    public function __construct(
        private VeiculoService $veiculoService,
        private ViewRenderer $view,
        private SessionInterface $session,
        private VeiculoRequest $veiculoRequest,
        private VeiculoCombustaoRequest $combustaoRequest,
        private VeiculoEletricoRequest $eletricoRequest,
        private VeiculoHibridoRequest $hibridoRequest,
        private VeiculoGNVRequest $gnvRequest,
        private VeiculoOpcionalRequest $opcionalRequest,
        private VeiculoImagemRequest $veiculoImagemRequest,
    ) {}

    /**
     * Lista veículos do lojista (painel).
     *
     * @param Request $request
     * @return string
     */
    public function index(Request $request): string
    {
        $lojistaId = $this->getLojistaId();
        $pagina = (int) ($request->getQuery('pagina') ?? 1);
        $porPagina = 20;

        $veiculos = $this->veiculoService->listarDoLojista($lojistaId, $pagina, $porPagina);

        // Recupera flash messages
        $success = $this->getFlash('flash_veiculo_success');
        $error = $this->getFlash('flash_veiculo_error');

        return $this->view->renderWithLayout(
            'logista/veiculos/index',
            [
                'veiculos'      => $veiculos['veiculos'],
                'total'         => $veiculos['total'],
                'pagina'        => $veiculos['pagina'],
                'totalPaginas'  => $veiculos['totalPaginas'],
                'success'       => $success,
                'error'         => $error,
            ],
            'layouts/main',
            ['title' => 'Meus Veículos']
        );
    }

    /**
     * Lista veículos deletados do lojista (lixeira).
     *
     * @param Request $request
     * @return string
     */
    public function trash(Request $request): string
    {
        $lojistaId = $this->getLojistaId();
        $pagina = (int) ($request->getQuery('pagina') ?? 1);
        $porPagina = 20;

        $veiculos = $this->veiculoService->listarLixeira($lojistaId, $pagina, $porPagina);

        $success = $this->getFlash('flash_veiculo_success');
        $error = $this->getFlash('flash_veiculo_error');

        return $this->view->renderWithLayout(
            'logista/veiculos/trash',
            [
                'veiculos'      => $veiculos['veiculos'],
                'total'         => $veiculos['total'],
                'pagina'        => $veiculos['pagina'],
                'totalPaginas'  => $veiculos['totalPaginas'],
                'success'       => $success,
                'error'         => $error,
            ],
            'layouts/main',
            ['title' => 'Lixeira de Veículos']
        );
    }

    /**
     * Exibe o formulário de criação de veículo.
     *
     * @param Request $request
     * @return string
     */
    public function create(Request $request): string
    {
        // Busca todos os opcionais agrupados para o formulário
        $opcionais = $this->veiculoService->buscarOpcionaisAgrupados();

        // Recupera old input e flash
        $old = $this->session->get('old_veiculo_input', []);
        $this->session->delete('old_veiculo_input');

        $error = $this->getFlash('flash_veiculo_error');

        return $this->view->renderWithLayout(
            'logista/veiculos/form',
            [
                'veiculo'          => null,
                'tipo'             => null,
                'complemento'      => null,
                'gnv'              => null,
                'opcionais_selecionados' => [],
                'todos_opcionais'  => $opcionais,
                'old'              => $old,
                'error'            => $error,
                'isEdit'           => false,
            ],
            'layouts/main',
            ['title' => 'Cadastrar Veículo']
        );
    }

    /**
     * Processa o cadastro de um novo veículo.
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request): void
    {
        // Captura TODOS os dados enviados (principais + complementos + GNV + opcionais)
        $allData = $request->all();

        // 1. Valida dados comuns
        if (!$this->veiculoRequest->validate()) {
            $this->handleValidationErrors(
                $this->veiculoRequest->getErrors(),
                $allData
            );
        }

        $dados = $this->veiculoRequest->getDadosPrincipais();
        $tipoVeiculo = $this->veiculoRequest->getTipoVeiculo();
        $opcionaisIds = $this->veiculoRequest->getOpcionaisIds();

        // 2. Adiciona lojista_id da sessão
        $dados['lojista_id'] = $this->getLojistaId();

        // 3. Valida complemento específico
        switch ($tipoVeiculo) {
            case 'combustao':
                if (!$this->combustaoRequest->validate()) {
                    $this->handleValidationErrors(
                        $this->combustaoRequest->getErrors(),
                        $allData
                    );
                }
                $dados = array_merge($dados, $this->combustaoRequest->getDadosCombustao());
                break;
            case 'eletrico':
                if (!$this->eletricoRequest->validate()) {
                    $this->handleValidationErrors(
                        $this->eletricoRequest->getErrors(),
                        $allData
                    );
                }
                $dados = array_merge($dados, $this->eletricoRequest->getDadosEletricos());
                break;
            case 'hibrido':
                if (!$this->hibridoRequest->validate()) {
                    $this->handleValidationErrors(
                        $this->hibridoRequest->getErrors(),
                        $allData
                    );
                }
                $dados = array_merge($dados, $this->hibridoRequest->getDadosHibridos());
                break;
            default:
                $this->redirectWithError('Tipo de veículo inválido.');
        }

        // 4. Valida GNV se aplicável
        if ($this->veiculoRequest->hasGNV()) {
            if (!$this->gnvRequest->validate()) {
                $this->handleValidationErrors(
                    $this->gnvRequest->getErrors(),
                    $allData
                );
            }
            $dados = array_merge($dados, $this->gnvRequest->getDadosGNV());
            $dados['gnv_instalado'] = 1;
        } else {
            $dados['gnv_instalado'] = 0;
        }

        // 5. Valida opcionais (redundante, mas seguro)
        if (!$this->opcionalRequest->validate()) {
            $this->handleValidationErrors(
                $this->opcionalRequest->getErrors(),
                $allData
            );
        }
        // opcionaisIds já está disponível

        // 6. Valida e processa imagens (se houver upload)
        if (isset($_FILES['imagens']) && !empty($_FILES['imagens']['tmp_name'][0])) {
            if (!$this->veiculoImagemRequest->validate()) {
                $this->handleValidationErrors(
                    $this->veiculoImagemRequest->getErrors(),
                    $allData
                );
            }
            $dadosImagem = $this->veiculoImagemRequest->validated();
            $dados['imagens'] = $_FILES['imagens'];
            $dados['capa_index'] = $dadosImagem['capa_index'] ?? null;
        }

        // 7. Chama o Service
        $result = $this->veiculoService->salvar($dados, $opcionaisIds, $tipoVeiculo);
        if ($result === false) {
            $this->redirectWithError('Falha ao criar veículo. Tente novamente.');
        }

        $this->session->set('flash_veiculo_success', 'Veículo criado com sucesso!');
        header('Location: /logista/veiculos');
        exit;
    }

    /**
     * Exibe o formulário de edição de veículo.
     *
     * @param Request $request
     * @param int $id
     * @return string
     */
    public function edit(Request $request, int $id): string
    {
        $dadosEdicao = $this->veiculoService->buscarParaEdicao($id);
        if (!$dadosEdicao) {
            $this->redirectWithError('Veículo não encontrado.');
        }

        // Verifica se o veículo pertence ao lojista logado
        if ($dadosEdicao['veiculo']['lojista_id'] != $this->getLojistaId()) {
            $this->redirectWithError('Acesso negado.');
        }

        // Recupera old input e flash
        $old = $this->session->get('old_veiculo_input', []);
        $this->session->delete('old_veiculo_input');

        $error = $this->getFlash('flash_veiculo_error');

        // Mescla old input com dados existentes (se houver)
        if (!empty($old)) {
            $dadosEdicao['veiculo'] = array_merge($dadosEdicao['veiculo'], $old);
        }

        return $this->view->renderWithLayout(
            'logista/veiculos/form',
            [
                'veiculo'          => $dadosEdicao['veiculo'],
                'tipo'             => $dadosEdicao['tipo'],
                'complemento'      => $dadosEdicao['complemento'],
                'gnv'              => $dadosEdicao['gnv'],
                'opcionais_selecionados' => $dadosEdicao['opcionais_selecionados'],
                'todos_opcionais'  => $dadosEdicao['todos_opcionais'],
                'old'              => [],
                'error'            => $error,
                'isEdit'           => true,
            ],
            'layouts/main',
            ['title' => 'Editar Veículo']
        );
    }

    /**
     * Processa a atualização de um veículo.
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, int $id): void
    {
        // Captura TODOS os dados enviados (principais + complementos + GNV + opcionais)
        $allData = $request->all();

        // Verifica se o veículo existe e pertence ao lojista (opcional, mas seguro)
        $dadosEdicao = $this->veiculoService->buscarParaEdicao($id);
        if (!$dadosEdicao || $dadosEdicao['veiculo']['lojista_id'] != $this->getLojistaId()) {
            $this->redirectWithError('Veículo não encontrado ou acesso negado.');
        }

        // 1. Valida dados comuns
        if (!$this->veiculoRequest->validate()) {
            $this->handleValidationErrors(
                $this->veiculoRequest->getErrors(),
                $allData
            );
        }

        $dados = $this->veiculoRequest->getDadosPrincipais();
        $tipoVeiculo = $this->veiculoRequest->getTipoVeiculo();
        $opcionaisIds = $this->veiculoRequest->getOpcionaisIds();

        // 2. Valida complemento específico
        switch ($tipoVeiculo) {
            case 'combustao':
                if (!$this->combustaoRequest->validate()) {
                    $this->handleValidationErrors(
                        $this->combustaoRequest->getErrors(),
                        $allData
                    );
                }
                $dados = array_merge($dados, $this->combustaoRequest->getDadosCombustao());
                break;
            case 'eletrico':
                if (!$this->eletricoRequest->validate()) {
                    $this->handleValidationErrors(
                        $this->eletricoRequest->getErrors(),
                        $allData
                    );
                }
                $dados = array_merge($dados, $this->eletricoRequest->getDadosEletricos());
                break;
            case 'hibrido':
                if (!$this->hibridoRequest->validate()) {
                    $this->handleValidationErrors(
                        $this->hibridoRequest->getErrors(),
                        $allData
                    );
                }
                $dados = array_merge($dados, $this->hibridoRequest->getDadosHibridos());
                break;
            default:
                $this->redirectWithError('Tipo de veículo inválido.');
        }

        // 3. Valida GNV se aplicável
        if ($this->veiculoRequest->hasGNV()) {
            if (!$this->gnvRequest->validate()) {
                $this->handleValidationErrors(
                    $this->gnvRequest->getErrors(),
                    $allData
                );
            }
            $dados = array_merge($dados, $this->gnvRequest->getDadosGNV());
            $dados['gnv_instalado'] = 1;
        } else {
            $dados['gnv_instalado'] = 0;
        }

        // 4. Valida opcionais
        if (!$this->opcionalRequest->validate()) {
            $this->handleValidationErrors(
                $this->opcionalRequest->getErrors(),
                $allData
            );
        }

        // 5. Valida e processa imagens (edição)
        if ($this->veiculoImagemRequest->hasImagensAlteracao()) {
            if (!$this->veiculoImagemRequest->validate()) {
                $this->handleValidationErrors(
                    $this->veiculoImagemRequest->getErrors(),
                    $allData
                );
            }
            $dadosImagem = $this->veiculoImagemRequest->validated();
            $dados['ids_manter'] = $dadosImagem['ids_manter'] ?? [];
            $dados['novas'] = $_FILES['novas'] ?? [];
            $dados['capa_id'] = $dadosImagem['capa_id'] ?? null;
        }

        // 6. Chama o Service
        $result = $this->veiculoService->atualizar($id, $dados, $opcionaisIds, $tipoVeiculo);
        if (!$result) {
            $this->redirectWithError('Falha ao atualizar veículo. Tente novamente.');
        }

        $this->session->set('flash_veiculo_success', 'Veículo atualizado com sucesso!');
        header('Location: /logista/veiculos');
        exit;
    }

    /**
     * Remove um veículo (soft delete).
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function destroy(Request $request, int $id): void
    {
        // Verifica pertencimento
        $veiculo = $this->veiculoService->buscarParaEdicao($id);
        if (!$veiculo || $veiculo['veiculo']['lojista_id'] != $this->getLojistaId()) {
            $this->redirectWithError('Veículo não encontrado ou acesso negado.');
        }

        $result = $this->veiculoService->deletar($id);
        if (!$result) {
            $this->redirectWithError('Falha ao deletar veículo.');
        }

        $this->session->set('flash_veiculo_success', 'Veículo movido para a lixeira.');
        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true]);
            exit;
        }
        header('Location: /logista/veiculos');
        exit;
    }

    /**
     * Restaura um veículo deletado.
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function restore(Request $request, int $id): void
    {
        $result = $this->veiculoService->restaurar($id, $this->getLojistaId());
        if (!$result) {
            $this->redirectWithError('Falha ao restaurar veículo.');
        }

        $this->session->set('flash_veiculo_success', 'Veículo restaurado com sucesso!');
        header('Location: /logista/veiculos/lixeira');
        exit;
    }

    /**
     * Ativa/desativa a exibição na vitrine.
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function toggleVitrine(Request $request, int $id): void
    {
        // Verifica pertencimento
        $veiculo = $this->veiculoService->buscarParaEdicao($id);
        if (!$veiculo || $veiculo['veiculo']['lojista_id'] != $this->getLojistaId()) {
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado.']);
                exit;
            }
            $this->redirectWithError('Veículo não encontrado ou acesso negado.');
        }

        $result = $this->veiculoService->toggleVitrine($id);
        if (!$result) {
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Falha ao alterar status.']);
                exit;
            }
            $this->redirectWithError('Falha ao alterar status da vitrine.');
        }

        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true]);
            exit;
        }

        $this->session->set('flash_veiculo_success', 'Status da vitrine alterado.');
        header('Location: /logista/veiculos');
        exit;
    }

    // ============================================================
    // Métodos auxiliares privados
    // ============================================================

    /**
     * Retorna o ID do lojista logado (da sessão).
     *
     * @return int
     */
    private function getLojistaId(): int
    {
        return (int) $this->session->get('user_id');
    }

    /**
     * Obtém e remove uma mensagem flash da sessão.
     *
     * @param string $key
     * @return string|null
     */
    private function getFlash(string $key): ?string
    {
        $value = $this->session->get($key);
        if ($value !== null) {
            $this->session->delete($key);
        }
        return $value;
    }

    /**
     * Centraliza o tratamento de erros de validação.
     *
     * @param array $errors
     * @param array|null $old
     * @return void
     */
    private function handleValidationErrors(array $errors, ?array $old = null): void
    {
        $isAjax = $this->veiculoRequest->isAjax();

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erros' => $errors]);
            exit;
        }

        // Salva old input
        $this->session->set('old_veiculo_input', $old ?? $this->veiculoRequest->all());

        // Converte erros em string
        $errorMessages = [];
        foreach ($errors as $field => $msgs) {
            $errorMessages = array_merge($errorMessages, $msgs);
        }
        $this->session->set('flash_veiculo_error', implode('<br>', $errorMessages));

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/logista/veiculos'));
        exit;
    }

    /**
     * Redireciona com mensagem de erro (flash).
     *
     * @param string $message
     * @param string|null $redirectTo
     * @return void
     */
    private function redirectWithError(string $message, ?string $redirectTo = null): void
    {
        $this->session->set('flash_veiculo_error', $message);
        $url = $redirectTo ?? ($_SERVER['HTTP_REFERER'] ?? '/logista/veiculos');
        header('Location: ' . $url);
        exit;
    }

    /**
     * Remove uma imagem específica de um veículo (via AJAX ou POST).
     *
     * @param Request $request
     * @param int $veiculoId
     * @param int $imagemId
     * @return void
     */
    public function deleteImagem(Request $request, int $veiculoId, int $imagemId): void
    {
        // Verifica se o veículo existe e pertence ao lojista
        $veiculo = $this->veiculoService->buscarParaEdicao($veiculoId);
        if (!$veiculo || $veiculo['veiculo']['lojista_id'] != $this->getLojistaId()) {
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Veículo não encontrado ou acesso negado.']);
                exit;
            }
            $this->redirectWithError('Veículo não encontrado ou acesso negado.');
        }

        // Chama o Service para deletar a imagem
        $result = $this->veiculoService->deletarImagem($imagemId, $veiculoId);

        if (!$result) {
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Falha ao deletar a imagem.']);
                exit;
            }
            $this->redirectWithError('Falha ao deletar a imagem.');
        }

        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true]);
            exit;
        }

        $this->session->set('flash_veiculo_success', 'Imagem removida com sucesso.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/logista/veiculos/editar/' . $veiculoId));
        exit;
    }
    
}