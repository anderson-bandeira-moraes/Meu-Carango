<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Contracts\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware que protege as rotas administrativas, garantindo que o 2FA foi concluído.
 * Redireciona para login ou para a página de verificação 2FA conforme o estado da sessão.
 */
class TwoFactorMiddleware
{
    private array $ignorePaths = [
        '/admin/login',
        '/admin/logout',
        '/admin/2fa',
    ];

    public function __construct(
        private SessionInterface $session,
        private LoggerInterface $logger,
    ) {}

    /**
     * Executa o middleware, verificando autenticação e conclusão do 2FA.
     *
     * @param Request $request
     * @return void
     */
    public function handle(Request $request): void
    {
        $uri = $request->getPath();

        // Ignora rotas públicas de autenticação e 2FA
        if ($this->shouldIgnore($uri)) {
            $this->logger->debug('TwoFactorMiddleware ignorado', ['uri' => $uri]);
            return;
        }

        // Verifica se o admin está logado
        if (!$this->session->has('admin_id')) {
            $this->logger->warning('Acesso negado: admin não logado', ['uri' => $uri]);
            header('Location: /admin/login');
            exit;
        }

        // Verifica se o 2FA já foi concluído
        $adminId = $this->session->get('admin_id');
        $twoFactorVerified = $this->session->get('2fa_verified', false);

        if (!$twoFactorVerified) {
            $this->logger->warning('Acesso negado: 2FA pendente', [
                'uri' => $uri,
                'admin_id' => $adminId,
            ]);
            header('Location: /admin/2fa');
            exit;
        }

        // Tudo ok
        $this->logger->debug('Acesso permitido via 2FA', [
            'uri' => $uri,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * Verifica se a URI atual deve ser ignorada pelo middleware.
     *
     * @param string $uri
     * @return bool
     */
    private function shouldIgnore(string $uri): bool
    {
        foreach ($this->ignorePaths as $path) {
            if (str_starts_with($uri, $path)) {
                return true;
            }
        }
        return false;
    }
}