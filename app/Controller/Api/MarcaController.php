<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Request;
use App\Service\MarcaModeloService;
use App\Helpers\UploadHelper;

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

            // Limita o comprimento da busca para evitar abusos
            if ($busca !== null && strlen($busca) > 100) {
                $busca = substr($busca, 0, 100);
            }

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

            // 1.1 Valida o comprimento máximo do nome (banco: VARCHAR(50))
            if (strlen($nome) > 50) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'O nome da marca deve ter no máximo 50 caracteres.',
                ]);
                exit;
            }

            // 2. (Opcional) Receber a logo via $request->getFile()
            $logoPath = null;
            $file = $request->getFile('logo');
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                // 3. Fazer upload da logo
                $caminhoRelativo = UploadHelper::upload($file, 'marcas');
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
            // Nota: O service retorna false para nome duplicado, slug duplicado ou erro de banco.
            // Para simplificar, tratamos como conflito de nome. Se desejar maior precisão,
            // o service pode ser modificado para lançar exceções específicas ou retornar
            // um array com o motivo do erro.
            if ($id === false) {
                header('Content-Type: application/json');
                http_response_code(409);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'Já existe uma marca com este nome.',
                ]);
                exit;
            }

            // 6. Busca a marca recém-criada via método específico (mais eficiente)
            $novaMarca = $this->marcaModeloService->buscarMarcaPorId($id);

            // Fallback caso não encontre
            if (!$novaMarca) {
                $novaMarca = [
                    'id'       => $id,
                    'nome'     => $nome,
                    'slug'     => null,
                    'logo'     => $logoPath,
                    'logo_url' => \logo_url($logoPath), // Chamada global explícita
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