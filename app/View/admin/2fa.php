<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-dark text-white text-center rounded-top-4">
                <h4 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Verificação em Duas Etapas</h4>
            </div>
            <div class="card-body p-4">

                <!-- Mensagens flash -->
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($erro) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($sucesso)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($sucesso) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <!-- Mensagem de aviso (amarela) – exibida apenas quando o reenvio é bloqueado -->
                <?php if (!empty($warning)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($warning) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <!-- Instruções -->
                <p class="text-muted text-center">
                    Um código de 6 dígitos foi enviado para o e-mail:
                    <strong><?= htmlspecialchars($email) ?></strong>
                </p>

                <!-- Status do código -->
                <?php if (isset($status) && $status['exists']): ?>
                    <div class="mb-3 text-center small">
                        <p class="text-muted small">
                            <i class="bi bi-clock me-1"></i>
                            O código é válido por <strong><?= htmlspecialchars($expiryMinutes ?? 5) ?> minutos</strong>.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Formulário de verificação -->
                <form method="POST" action="/admin/2fa/verify" id="verifyForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label for="code" class="form-label">Código de verificação</label>
                        <input type="text"
                               class="form-control form-control-lg text-center"
                               id="code"
                               name="code"
                               placeholder="123456"
                               maxlength="6"
                               pattern="[0-9]{6}"
                               inputmode="numeric"
                               autocomplete="off"
                               autofocus
                               <?php
                               $codeExists = isset($status['exists']) && $status['exists'] === true;
                               $codeExpired = isset($status['expires_at']) && strtotime($status['expires_at']) < time();
                               $formDisabled = !$codeExists || $codeExpired;
                               echo $formDisabled ? 'disabled' : '';
                               ?>
                               required>
                        <div class="form-text text-center">
                            Digite os 6 dígitos recebidos por e-mail.
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="verifyBtn"
                            <?= $formDisabled ? 'disabled' : '' ?>>
                            <i class="bi bi-check-lg me-2"></i>Verificar
                        </button>
                    </div>
                </form>

                <hr>

                <!-- Reenvio e ações secundárias -->
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <form method="POST" action="/admin/2fa/resend" id="resendForm" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <!-- Wrapper para o tooltip funcionar em botão desabilitado -->
                        <span class="d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="Reenvio bloqueado por 30 minutos">
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" id="resendBtn" style="pointer-events: auto;">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reenviar código
                            </button>
                        </span>
                    </form>

                    <a href="/admin/logout" class="btn btn-primary" onclick="return confirm('Deseja voltar à página de login?');">
                        <i class="bi bi-box-arrow-left me-1"></i>Login
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        // ========== PASSO 1: Expor estado do bloqueio ao JavaScript ==========
        window.__2fa_state = {
            resendBlocked: <?= json_encode($resendBlocked) ?>
        };

        // ========== PASSO 2: Lógica de comparação e redirecionamento ==========
        document.addEventListener('DOMContentLoaded', function() {
            // Estado atual do bloqueio (vindo do servidor)
            const currentBlocked = window.__2fa_state?.resendBlocked ?? false;

            // Estado anterior (armazenado no sessionStorage)
            const previousBlocked = sessionStorage.getItem('2fa_resend_blocked');

            // Se o estado mudou de bloqueado (true) para desbloqueado (false),
            // significa que o bloqueio expirou → redireciona para login com mensagem
            if (previousBlocked === 'true' && currentBlocked === false) {
                sessionStorage.removeItem('2fa_resend_blocked');
                window.location.href = '/admin/login?blockage_expired=1';
                return; // Interrompe a execução para evitar outras ações
            }

            // Atualiza o sessionStorage com o estado atual (sempre)
            sessionStorage.setItem('2fa_resend_blocked', String(currentBlocked));

            // ========== Lógica existente (tooltip, foco, etc.) ==========
            const warningElement = document.querySelector('.alert-warning');
            const resendBtn = document.getElementById('resendBtn');

            if (warningElement) {
                const warningText = warningElement.textContent.trim();
                if (warningText.includes('bloqueado') || warningText.includes('Reenvio bloqueado')) {
                    if (resendBtn) {
                        resendBtn.disabled = true;
                        const wrapper = resendBtn.closest('span');
                        if (wrapper) {
                            const tooltip = new bootstrap.Tooltip(wrapper, {
                                trigger: 'hover focus',
                                title: 'Reenvio bloqueado por 30 minutos',
                                container: 'body'
                            });
                        }
                    }
                }
            }

            // Foco automático no campo de código (se habilitado)
            const codeInput = document.getElementById('code');
            if (codeInput && !codeInput.disabled) {
                codeInput.focus();
            }
        });

        // Prevenir envio duplicado do formulário de verificação
        document.getElementById('verifyForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('verifyBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Verificando...';
        });

        // Desabilitar temporariamente o botão de reenvio para evitar múltiplos cliques
        document.getElementById('resendForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('resendBtn');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando...';
            }
        });

    })();
</script>