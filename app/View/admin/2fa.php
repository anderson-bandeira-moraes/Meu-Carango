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

                <!-- Instruções -->
                <p class="text-muted text-center">
                    Um código de 6 dígitos foi enviado para o e-mail:
                    <strong><?= htmlspecialchars($email) ?></strong>
                </p>

                <!-- Status do código (sem contagem regressiva) -->
                <?php if (isset($status) && $status['exists']): ?>
                    <div class="mb-3 text-center small">
                        <p class="text-muted small">
                            <i class="bi bi-clock me-1"></i>
                            O código é válido por <strong><?= htmlspecialchars($expiryMinutes ?? 5) ?> minutos</strong>.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Alerta de bloqueio de reenvio (usando $resendBlocked) -->
                <?php if ($resendBlocked ?? false): ?>
                    <div class="alert alert-warning text-center" role="alert">
                        <i class="bi bi-ban me-2"></i>
                        Reenvio bloqueado por 30 minutos. Tente novamente mais tarde.
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
                               <?= (isset($formBlocked) && $formBlocked === true) ? 'disabled' : '' ?>
                               required>
                        <div class="form-text text-center">
                            Digite os 6 dígitos recebidos por e-mail.
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="verifyBtn"
                            <?= (isset($formBlocked) && $formBlocked === true) ? 'disabled' : '' ?>>
                            <i class="bi bi-check-lg me-2"></i>Verificar
                        </button>
                    </div>
                </form>

                <hr>

                <!-- Reenvio e ações secundárias -->
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <form method="POST" action="/admin/2fa/resend" id="resendForm" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-link p-0 text-decoration-none" id="resendBtn"
                            <?= ($resendBlocked ?? false) ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            <?= ($resendBlocked ?? false) ? 'Reenvio bloqueado' : 'Reenviar código' ?>
                        </button>
                    </form>

                    <a href="/admin/logout" class="btn btn-link text-danger text-decoration-none" onclick="return confirm('Cancelar verificação e voltar ao login?');">
                        <i class="bi bi-box-arrow-left me-1"></i>Cancelar
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        // Foco automático no campo de código
        document.getElementById('code')?.focus();

        // Prevenir envio duplicado
        document.getElementById('verifyForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('verifyBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Verificando...';
        });

        // Reenvio com confirmação (opcional)
        document.getElementById('resendForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('resendBtn');
            if (btn && !btn.disabled) {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando...';
                btn.disabled = true;
            }
        });

        // Contagem regressiva removida – exibimos apenas a mensagem estática.
    })();
</script>