<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Request;
use App\Service\MarcaModeloService;

/**
 * Controlador da API para gerenciamento de modelos.
 * Fornece endpoints para listagem e criação de modelos por marca via AJAX.
 */
class ModeloController
{
    public function __construct(
        private MarcaModeloService $marcaModeloService,
    ) {}

<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\Request;
use App\Service\MarcaModeloService;

/**
 * Controlador da API para gerenciamento de modelos.
 * Fornece endpoints para listagem e criação de modelos por marca via AJAX.
 */
class ModeloController
{
    public function __construct(
        private MarcaModeloService $marcaModeloService,
    ) {}

    /**
     * Lista todos os modelos de uma marca específica.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request): void
    {
        try {
            // 1. Obtém o parâmetro 'marca_id' da query string
            $marcaId = $request->getQuery('marca_id');

            // 2. Valida se foi enviado e é um inteiro positivo
            if (!is_numeric($marcaId) || (int) $marcaId <= 0) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'O parâmetro "marca_id" é obrigatório e deve ser um número inteiro positivo.',
                ]);
                exit;
            }

            $marcaId = (int) $marcaId;

            // 3. Verifica se a marca existe
            $marca = $this->marcaModeloService->buscarMarcaPorId($marcaId);
            if (!$marca) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'Marca não encontrada.',
                ]);
                exit;
            }

            // 4. Chama o service para listar os modelos
            $modelos = $this->marcaModeloService->listarModelosPorMarca($marcaId);

            // Retorna a resposta JSON com sucesso
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'dados'   => $modelos,
            ]);
            exit;
        } catch (\Throwable $e) {
            // Em caso de erro, retorna status 500 com mensagem genérica
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'erro'    => 'Erro interno ao listar modelos.',
            ]);
            exit;
        }
    }

    /**
     * Cria um novo modelo para uma marca específica.
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request): void
    {
        try {
            // 1. Obtém os campos do POST
            $marcaId = $request->getPost('marca_id');
            $nome = trim($request->getPost('nome') ?? '');

            // 2. Valida se ambos os campos foram enviados
            if (empty($marcaId) || empty($nome)) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'Os campos "marca_id" e "nome" são obrigatórios.',
                ]);
                exit;
            }

            // 3. Valida se 'marca_id' é um inteiro positivo
            if (!is_numeric($marcaId) || (int) $marcaId <= 0) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'O campo "marca_id" deve ser um número inteiro positivo.',
                ]);
                exit;
            }

            $marcaId = (int) $marcaId;

            // 4. Valida o comprimento do nome (banco: VARCHAR(50))
            if (strlen($nome) > 50) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'O nome do modelo deve ter no máximo 50 caracteres.',
                ]);
                exit;
            }

            // 5. Verifica se a marca existe
            $marca = $this->marcaModeloService->buscarMarcaPorId($marcaId);
            if (!$marca) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'Marca não encontrada.',
                ]);
                exit;
            }

            // 6. Chama o service para criar o modelo
            $id = $this->marcaModeloService->criarModelo($marcaId, $nome);

            // 7. Se retornar false, assume conflito de nome (ou outro erro)
            if ($id === false) {
                header('Content-Type: application/json');
                http_response_code(409);
                echo json_encode([
                    'sucesso' => false,
                    'erro'    => 'Já existe um modelo com este nome para esta marca.',
                ]);
                exit;
            }

            // 8. Retorna sucesso com status 201 (Created)
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'dados'   => [
                    'id'   => $id,
                    'nome' => $nome,
                ],
            ]);
            exit;
        } catch (\Throwable $e) {
            // 9. Em caso de exceção, retorna status 500
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'erro'    => 'Erro interno ao criar modelo.',
            ]);
            exit;
        }
    }
    
}