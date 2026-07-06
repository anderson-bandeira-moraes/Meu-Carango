<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Request;
use App\Service\MarcaModeloService;

/**
 * Controlador da API para gerenciamento de marcas.
 * Fornece endpoints para listagem e criação de marcas via AJAX.
 */
class MarcaController
{
    public function __construct(
        private MarcaModeloService $marcaModeloService,
    ) {}

    /**
     * Lista todas as marcas, com suporte a busca por nome.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        try {
            // Obtém o parâmetro de busca da query string
            $busca = $request->getQuery('busca');

            // Chama o service para listar as marcas
            $marcas = $this->marcaModeloService->listarMarcas($busca);

            // Retorna a resposta JSON com sucesso
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'dados'   => $marcas,
            ]);
            exit;
        } catch (\Throwable $e) {
            // Em caso de erro, retorna status 500 com mensagem genérica
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'erro'    => 'Erro interno ao listar marcas.',
            ]);
            exit;
        }
    }

    /**
     * Cria uma nova marca.
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request): void
    {
        try {
            // 1. Valida se o campo 'nome' foi enviado e não está vazio
            $nome = trim($request->getPost('nome') ?? '');
            if (empty($nome)) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'O campo "nome" é obrigatório.',
                ]);
                exit;
            }

            // 2. (Opcional) Receber a logo via $_FILES['logo']
            $logoPath = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                // 3. Fazer upload da logo
                $caminhoRelativo = UploadHelper::upload($_FILES['logo'], 'marcas');
                if ($caminhoRelativo === false) {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode([
                        'sucesso' => false,
                        'erro'    => 'Erro ao fazer upload da logo.',
                    ]);
                    exit;
                }
                $logoPath = $caminhoRelativo;
            }

            // 4. Chama o service para criar a marca
            $id = $this->marcaModeloService->criarMarca($nome, $logoPath);

            // 5. Se retornar false, assume conflito de nome (ou outro erro)
            if ($id === false) {
                header('Content-Type: application/json');
                http_response_code(409);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'Já existe uma marca com este nome.',
                ]);
                exit;
            }

            // 6. Busca a marca recém-criada para retornar os dados completos (incluindo logo_url)
            $marca = $this->marcaModeloService->listarMarcas(); // Busca todas
            $novaMarca = null;
            foreach ($marca as $item) {
                if ((int) $item['id'] === $id) {
                    $novaMarca = $item;
                    break;
                }
            }

            // Caso não encontre, retorna apenas o ID e nome (fallback)
            if (!$novaMarca) {
                $novaMarca = [
                    'id'       => $id,
                    'nome'     => $nome,
                    'slug'     => null,
                    'logo'     => $logoPath,
                    'logo_url' => logo_url($logoPath),
                ];
            }

            // Retorna sucesso com status 201 (Created)
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'dados'   => $novaMarca,
            ]);
            exit;
        } catch (\Throwable $e) {
            // 7. Em caso de exceção, retorna status 500
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'erro'    => 'Erro interno ao criar marca.',
            ]);
            exit;
        }
    }
    
}