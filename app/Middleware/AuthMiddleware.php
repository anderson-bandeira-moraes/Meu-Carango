<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Contracts\SessionInterface;
use App\Core\Request;

/**
 * Middleware de autenticação.
 * Protege rotas que exigem usuário logado.
 */
class AuthMiddleware
{
    public function __construct(
        private SessionInterface $session,
    ) {}

    /**
     * Executa o middleware.
     *
     * @param Request $request A requisição atual.
     * @throws \App\Exception\ForbiddenException (opcional, se preferir exceção em vez de redirecionamento)
     */
    public function handle(Request $request): void
    {
        // Verifica se o usuário está autenticado
        if (!$this->session->has('user_id')) {
            // (Opcional) Guarda a URL original para redirecionar após login
            $this->session->set('intended_url', $request->getPath());

            // Redireciona para a página de login
            header('Location: /login');
            exit;
        }

        // Usuário autenticado: continua a requisição
    }
}