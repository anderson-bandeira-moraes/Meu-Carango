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
}