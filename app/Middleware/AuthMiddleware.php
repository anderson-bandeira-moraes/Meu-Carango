<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Contracts\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware que protege as rotas do lojista, verificando autenticação e status da conta.
 * Redireciona para login se o lojista não estiver logado ou se a conta estiver inativa.
 */
class AuthMiddleware
{
    public function __construct(
        private SessionInterface $session,
        private LoggerInterface $logger,
    ) {}

    /**
     * Executa o middleware, verificando autenticação e status da conta.
     *
     * @param Request $request
     * @return void
     */
    public function handle(Request $request): void
    {
        // Verifica se o lojista está logado
        if (!$this->session->has('user_id')) {
            $this->logger->warning('Acesso negado: lojista não logado', [
                'uri' => $request->getPath(),
                'ip'  => $request->getClientIp(),
            ]);
            header('Location: /logista/login');
            exit;
        }

        // Verifica se a conta está ativa (fallback seguro: inativo)
        $userStatus = $this->session->get('user_status', 'inativo'); // <-- ALTERADO
        if ($userStatus !== 'ativo') {
            $userId = $this->session->get('user_id');
            $this->logger->warning('Acesso negado: conta inativa', [
                'user_id' => $userId,
                'status'  => $userStatus,
                'uri'     => $request->getPath(),
            ]);

            // Destroi a sessão para forçar novo login
            $this->session->destroy();
            header('Location: /logista/login?inativo=1');
            exit;
        }

        // Tudo ok, permite acesso
        $this->logger->debug('Acesso permitido (lojista)', [
            'user_id' => $this->session->get('user_id'),
            'uri'     => $request->getPath(),
        ]);
    }
}