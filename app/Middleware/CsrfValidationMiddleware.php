<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Contracts\SessionInterface;
use App\Exception\CsrfException;
use Monolog\Logger;

/**
 * Middleware responsável por validar o token CSRF em requisições que alteram o estado.
 * Suporta envio via campo POST (csrf_token) e cabeçalho X-CSRF-TOKEN (AJAX).
 */
class CsrfValidationMiddleware
{
    public function __construct(
        private SessionInterface $session,
        private Logger $logger,
    ) {}

    /**
    * Valida o token CSRF para métodos que alteram estado (POST, PUT, DELETE, PATCH).
    *
    * @param Request $request
    * @return void
    * @throws CsrfException Se o token estiver ausente ou for inválido.
    */
    public function handle(Request $request): void
    {
        // Aplica apenas a métodos que alteram o estado do servidor
        $method = $request->getMethod();
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return;
        }

        // Obtém o token enviado (prioridade: POST > cabeçalho)
        $token = $request->getPost('csrf_token') ?? $request->getHeader('X-CSRF-TOKEN');

        // Obtém o token armazenado na sessão
        $sessionToken = $this->session->get('csrf_token');

        // Validação timing-safe: compara os tokens em tempo constante
        if (!$token || !$sessionToken || !hash_equals($token, $sessionToken)) {
            // Registra log específico para CSRF inválido
            $this->logger->warning('CSRF token inválido', [
                'ip'     => $request->getClientIp(),
                'uri'    => $request->getPath(),
                'method' => $request->getMethod(),
            ]);

            throw new CsrfException('A página expirou. Recarregue e tente novamente.');
        }
    }
}