<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;
use App\Service\TwoFactorService;
use Monolog\Logger;

/**
 * Controlador para autenticação em duas etapas (2FA).
 * Gerencia exibição do formulário, verificação do código e reenvio.
 */
class TwoFactorController
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private ViewRenderer $view,
        private SessionInterface $session,
        private Logger $logger,
    ) {}

    /**
     * Exibe o formulário para inserção do código 2FA.
     *
     * @param Request $request
     * @return string
     */
    public function form(Request $request): string
    {
        // Se já estiver logado e com 2FA verificado, redireciona para dashboard
        if ($this->session->has('admin_id') && $this->session->get('2fa_verified') === true) {
            header('Location: /admin');
            exit;
        }

        // Verifica se há um login pendente
        $email = $this->session->get('pending_admin_email');
        if (!$email) {
            $this->session->set('flash_2fa_error', 'Sessão expirada. Faça login novamente.');
            header('Location: /admin/login');
            exit;
        }

        // Recupera status atual do 2FA
        $status = $this->twoFactorService->getStatus($email);
        $this->logger->debug('Acesso ao formulário 2FA', [
            'email' => $email,
            'status' => $status,
        ]);

        // Se não há registro ativo (expirado ou removido), redireciona para login
        if (!$status['exists']) {
            $this->session->set('flash_2fa_error', 'Código expirado. Faça login novamente.');
            $this->clearPendingSession();
            header('Location: /admin/login');
            exit;
        }

        // Recupera mensagens flash
        $erro = $this->getFlash('flash_2fa_error');
        $sucesso = $this->getFlash('flash_2fa_success');

        // Renderiza view
        return $this->view->renderWithLayout(
            'admin/2fa',
            [
                'email'   => $email,
                'erro'    => $erro,
                'sucesso' => $sucesso,
                'status'  => $status,
                'getTimeRemaining' => [$this, 'getTimeRemaining'], // ou passe o valor calculado
            ],
            'layouts/main',
            ['title' => 'Verificação em Duas Etapas']
        );
    }

    /**
     * Processa a verificação do código 2FA informado.
     *
     * @param Request $request
     * @return void
     */
    public function verify(Request $request): void
    {
        $email = $this->session->get('pending_admin_email');
        if (!$email) {
            $this->session->set('flash_2fa_error', 'Sessão expirada. Faça login novamente.');
            header('Location: /admin/login');
            exit;
        }

        $code = trim($request->getPost('code') ?? '');

        // Validação: código deve ter 6 dígitos numéricos
        if (!preg_match('/^[0-9]{6}$/', $code)) {
            $this->session->set('flash_2fa_error', 'Código inválido. Digite os 6 dígitos numéricos.');
            header('Location: /admin/2fa');
            exit;
        }

        try {
            $result = $this->twoFactorService->verify($email, $code);

            if ($result['success']) {
                // --- Sucesso: conclui o login ---
                $adminId = (int) $this->session->get('pending_admin_id');
                $adminNome = $this->session->get('pending_admin_nome');
                $adminEmail = $this->session->get('pending_admin_email');

                // Remove pendências
                $this->clearPendingSession();

                // Seta dados do admin na sessão
                $this->session->regenerate();
                $this->session->set('admin_id', $adminId);
                $this->session->set('admin_nome', $adminNome);
                $this->session->set('admin_email', $adminEmail);
                $this->session->set('2fa_verified', true);

                $this->logger->info('Login concluído com 2FA', ['email' => $adminEmail]);

                $this->session->set('flash_2fa_success', 'Verificação concluída com sucesso!');
                header('Location: /admin');
                exit;
            }

            // --- Falha na verificação ---
            if (isset($result['attempts_left']) && $result['attempts_left'] > 0) {
                $this->session->set('flash_2fa_error', "Código inválido. Você tem {$result['attempts_left']} tentativa(s) restante(s).");
                header('Location: /admin/2fa');
                exit;
            }

            // --- Esgotou tentativas ou erro fatal ---
            $this->session->set('flash_2fa_error', $result['error'] ?? 'Erro na verificação. Faça login novamente.');
            $this->clearPendingSession();
            header('Location: /admin/login');
            exit;

        } catch (\Throwable $e) {
            $this->logger->error('Erro ao verificar código 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            $this->session->set('flash_2fa_error', 'Erro interno ao verificar código. Tente novamente.');
            header('Location: /admin/2fa');
            exit;
        }
    }

    /**
     * Reenvia um novo código 2FA para o e-mail do administrador.
     *
     * @param Request $request
     * @return void
     */
    public function resend(Request $request): void
    {
        $email = $this->session->get('pending_admin_email');
        if (!$email) {
            $this->session->set('flash_2fa_error', 'Sessão expirada. Faça login novamente.');
            header('Location: /admin/login');
            exit;
        }

        try {
            $result = $this->twoFactorService->resend($email);

            if ($result['success']) {
                $message = $result['message'] ?? 'Novo código enviado com sucesso.';
                $this->session->set('flash_2fa_success', $message);
                $this->logger->info('Código 2FA reenviado', ['email' => $email]);
            } else {
                $this->session->set('flash_2fa_error', $result['error'] ?? 'Falha ao reenviar código.');
                $this->logger->warning('Falha ao reenviar código 2FA', [
                    'email' => $email,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }

            header('Location: /admin/2fa');
            exit;

        } catch (\Throwable $e) {
            $this->logger->error('Erro ao reenviar código 2FA', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            $this->session->set('flash_2fa_error', 'Erro interno ao reenviar código. Tente novamente.');
            header('Location: /admin/2fa');
            exit;
        }
    }

    /**
     * Limpa os dados pendentes da sessão.
     *
     * @return void
     */
    private function clearPendingSession(): void
    {
        $this->session->delete('pending_admin_id');
        $this->session->delete('pending_admin_email');
        $this->session->delete('pending_admin_nome');
    }

    /**
     * Obtém e remove uma mensagem flash da sessão.
     *
     * @param string $key
     * @return string|null
     */
    private function getFlash(string $key): ?string
    {
        $value = $this->session->get($key);
        if ($value !== null) {
            $this->session->delete($key);
        }
        return $value;
    }

    /**
     * Calcula o tempo restante para expiração em formato HH:MM:SS.
     *
     * @param string $expiresAt
     * @return string
     */
    private function getTimeRemaining(string $expiresAt): string
    {
        $diff = strtotime($expiresAt) - time();
        if ($diff <= 0) {
            return 'Expirado';
        }
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $seconds = $diff % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}