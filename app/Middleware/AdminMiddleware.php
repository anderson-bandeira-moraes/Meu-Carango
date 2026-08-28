<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Contracts\SessionInterface;
use App\Core\Request;
use Psr\Log\LoggerInterface;

class AdminMiddleware
{
    public function __construct(
        private SessionInterface $session,
        private LoggerInterface $logger,
    ) {}

    /**
     * Executa o middleware de autenticação administrativa.
     *
     * @param Request $request
     */
    public function handle(Request $request): void
    {
        if (!$this->session->has('admin_id')) {
            $this->logger->warning('Acesso negado: admin não logado', [
                'uri' => $request->getPath(),
                'ip'  => $request->getClientIp(),
            ]);
            header('Location: /admin/login');
            exit;
        }
    }
}