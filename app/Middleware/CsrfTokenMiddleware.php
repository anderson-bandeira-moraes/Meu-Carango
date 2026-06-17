<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Contracts\SessionInterface;
use App\Core\Contracts\CsrfTokenGeneratorInterface;

/**
 * Middleware responsável por garantir que um token CSRF exista na sessão.
 * Gera um novo token apenas se ele ainda não existir, utilizando um gerador injetado.
 */
class CsrfTokenMiddleware
{
    public function __construct(
        private SessionInterface $session,
        private CsrfTokenGeneratorInterface $generator,
    ) {}

    /**
     * Garante que um token CSRF exista na sessão.
     *
     * @param Request $request
     * @return void
     * @throws \RuntimeException Se a geração do token falhar (ex: falta de entropia).
     */
    public function handle(Request $request): void
    {
        if (!$this->session->has('csrf_token')) {
            $token = $this->generator->generate();
            $this->session->set('csrf_token', $token);
        }
    }
}