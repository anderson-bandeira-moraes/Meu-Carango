<?php

declare(strict_types=1);

use App\Core\Session;

/**
 * Retorna o token CSRF armazenado na sessão.
 * Se não existir, retorna string vazia.
 *
 * A geração do token é responsabilidade do CsrfTokenMiddleware.
 * Este helper apenas recupera o token existente para uso em views.
 *
 * @return string Token CSRF ou string vazia se não existir.
 */
function csrf_token(): string
{
    return Session::get('csrf_token', '');
}