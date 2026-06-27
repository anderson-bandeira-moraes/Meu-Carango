<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\ViewRenderer;
use App\Core\Contracts\SessionInterface;
use App\Service\TwoFactorService;
use App\Requests\TwoFactorRequest;

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
        private TwoFactorRequest $twoFactorRequest, // FormRequest injetada
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

        // --- Se não há registro ativo (expirado ou removido) ---
        if (!$status['exists']) {
            if ($this->twoFactorService->isResendBlocked($email)) {
                $mensagem = 'Código expirado. O reenvio está bloqueado por 30 minutos.';
            } else {
                $mensagem = 'Código expirado. Clique em "Reenviar" para obter um novo código.';
            }
            $this->session->set('flash_2fa_error', $mensagem);
        }

        // Recupera a flag do modal (se existir)
        $showModal = $this->session->get('show_blocked_modal', false);
        $this->session->delete('show_blocked_modal'); // Limpa após recuperar

        // Recupera mensagens flash
        $erro = $this->getFlash('flash_2fa_error');
        $sucesso = $this->getFlash('flash_2fa_success');

        $expiryMinutes = (int) ($_ENV['TWO_FACTOR_EXPIRY_MINUTES'] ?? 5);

        // Verifica se o reenvio está bloqueado (para a view)
        $resendBlocked = $this->twoFactorService->isResendBlocked($email);

        return $this->view->renderWithLayout(
            'admin/2fa',
            [
                'email'         => $email,
                'erro'          => $erro,
                'sucesso'       => $sucesso,
                'status'        => $status,
                'expiryMinutes' => $expiryMinutes,
                'resendBlocked' => $resendBlocked,
                'showModal'     => $showModal,
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

        // Valida o código usando a TwoFactorRequest
        if (!$this->twoFactorRequest->validate()) {
            $errors = $this->twoFactorRequest->getErrors();
            $this->session->set('flash_2fa_error', implode('<br>', $errors['code'] ?? []) ?: 'Código inválido.');
            header('Location: /admin/2fa');
            exit;
        }

        // Obtém o código validado
        $code = $this->twoFactorRequest->validated()['code'] ?? '';

        try {
            // Primeiro, verifica se o código ainda existe e não expirou
            $record = $this->twoFactorService->getRecord($email);

            if (!$record) {
                // Registro não existe (já foi removido ou nunca foi criado)
                $this->session->set('flash_2fa_error', 'Código expirado. Clique em "Reenviar" para obter um novo.');
                header('Location: /admin/2fa');
                exit;
            }

            if (strtotime($record['expires_at']) < time()) {
                // Código expirado - não remove pendências
                $this->session->set('flash_2fa_error', 'Código expirado. Clique em "Reenviar" para obter um novo.');
                header('Location: /admin/2fa');
                exit;
            }

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

                $this->session->set('flash_2fa_success', 'Verificação concluída com sucesso!');
                header('Location: /admin');
                exit;
            }

            // --- Falha na verificação (código inválido) com tentativas restantes ---
            if (isset($result['attempts_left']) && $result['attempts_left'] > 0) {
                $this->session->set('flash_2fa_error', "Código inválido. Você tem {$result['attempts_left']} tentativa(s) restante(s).");
                header('Location: /admin/2fa');
                exit;
            }

            // --- Esgotou tentativas (exhausted) ---
            if (isset($result['exhausted']) && $result['exhausted'] === true) {
                // Verifica se o reenvio está bloqueado
                $resendBlocked = $this->twoFactorService->isResendBlocked($email);
                if ($resendBlocked) {
                    $mensagem = 'Você esgotou as tentativas de verificação e o reenvio está bloqueado. Aguarde 30 minutos para tentar novamente.';
                } else {
                    $mensagem = 'Você esgotou as tentativas. Clique em "Reenviar" para obter um novo código.';
                }
                // NÃO limpa pendências – mantém o usuário na página 2FA
                $this->session->set('flash_2fa_error', $mensagem);
                header('Location: /admin/2fa');
                exit;
            }

            // --- Outros erros (fallback) ---
            $this->session->set('flash_2fa_error', $result['error'] ?? 'Erro na verificação. Tente novamente.');
            // Se for erro de expiração, mantém pendências; caso contrário, limpa
            if (strpos($result['error'] ?? '', 'expirou') !== false) {
                // Mantém pendências
            } else {
                $this->clearPendingSession();
            }
            header('Location: /admin/2fa');
            exit;

        } catch (\Throwable $e) {
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
            } else {
                // Verifica se o erro é de bloqueio (blocked_until presente)
                if (isset($result['blocked_until'])) {
                    // Define a flag para exibir o modal de bloqueio
                    $this->session->set('show_blocked_modal', true);
                } else {
                    $this->session->set('flash_2fa_error', $result['error'] ?? 'Falha ao reenviar código.');
                }
            }

            header('Location: /admin/2fa');
            exit;

        } catch (\Throwable $e) {
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
}