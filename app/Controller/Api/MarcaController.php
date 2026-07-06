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
}