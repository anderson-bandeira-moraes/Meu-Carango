<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Contracts\SessionInterface;
use App\Core\Request;

class AdminMiddleware
{
    public function __construct(private SessionInterface $session) {}

    /**
     * Executa o middleware de autenticação administrativa.
     *
     * @param Request $request
     */
    public function handle(Request $request): void
    {
        if (!$this->session->has('admin_id')) {
            header('Location: /admin/login');
            exit;
        }
    }
}