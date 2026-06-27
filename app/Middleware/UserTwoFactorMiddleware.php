<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Contracts\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware que protege as rotas do lojista, garantindo que o 2FA foi concluído.
 * Redireciona para login ou para a página de verificação 2FA conforme o estado da sessão.
 */
class UserTwoFactorMiddleware
{
    private array $ignorePaths = [
        '/logista/login',
        '/logista/logout',
        '/logista/2fa',
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
            $this->logger->debug('UserTwoFactorMiddleware ignorado', ['uri' => $uri]);
            return;
        }

        // Verifica se o lojista está logado
        if (!$this->session->has('user_id')) {
            $this->logger->warning('Acesso negado: lojista não logado', ['uri' => $uri]);
            header('Location: /logista/login');
            exit;
        }

        // Verifica se o 2FA já foi concluído
        $userId = $this->session->get('user_id');
        $twoFactorVerified = $this->session->get('2fa_verified_user', false);

        if (!$twoFactorVerified) {
            $this->logger->warning('Acesso negado: 2FA pendente (lojista)', [
                'uri' => $uri,
                'user_id' => $userId,
            ]);
            header('Location: /logista/2fa');
            exit;
        }

        // Tudo ok
        $this->logger->debug('Acesso permitido via 2FA (lojista)', [
            'uri' => $uri,
            'user_id' => $userId,
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