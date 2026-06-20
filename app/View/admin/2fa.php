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

                <!-- Status do código -->
                <?php if (isset($status) && $status['exists']): ?>
                    <div class="mb-3 text-center small">
                        <?php if (isset($status['expires_at'])): ?>
                            <span class="badge bg-warning text-dark me-2">
                                <i class="bi bi-clock me-1"></i>
                                Expira em: <span id="expiryTimer"><?= $this->getTimeRemaining($status['expires_at']) ?></span>
                            </span>
                        <?php endif; ?>

                        <?php if (isset($status['attempts_left']) && $status['attempts_left'] > 0): ?>
                            <span class="badge bg-info text-dark">
                                <i class="bi bi-repeat me-1"></i>
                                Tentativas restantes: <?= $status['attempts_left'] ?>
                            </span>
                        <?php endif; ?>

                        <?php if (isset($status['is_blocked']) && $status['is_blocked'] === true): ?>
                            <span class="badge bg-danger">
                                <i class="bi bi-ban me-1"></i>
                                Reenvio bloqueado até <?= date('H:i', strtotime($status['blocked_until'])) ?>
                            </span>
                        <?php endif; ?>
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
                               required>
                        <div class="form-text text-center">
                            Digite os 6 dígitos recebidos por e-mail.
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="verifyBtn">
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
                            <?= (isset($status['is_blocked']) && $status['is_blocked'] === true) ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            <?php if (isset($status['is_blocked']) && $status['is_blocked'] === true): ?>
                                Reenvio bloqueado
                            <?php else: ?>
                                Reenviar código
                            <?php endif; ?>
                        </button>
                    </form>

                    <a href="/admin/logout" class="btn btn-link text-danger text-decoration-none" onclick="return confirm('Cancelar verificação e voltar ao login?');">
                        <i class="bi bi-box-arrow-left me-1"></i>Cancelar
                    </a>
                </div>

                <?php if (isset($status['is_blocked']) && $status['is_blocked'] === true): ?>
                    <div class="mt-2 text-center small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        O reenvio está bloqueado até <?= date('H:i:s', strtotime($status['blocked_until'])) ?>.
                        Tente novamente após esse horário.
                    </div>
                <?php endif; ?>
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

        // Contagem regressiva para expiração (se existir)
        const expiryElement = document.getElementById('expiryTimer');
        if (expiryElement) {
            const expiryText = expiryElement.textContent.trim();
            const match = expiryText.match(/(\d+):(\d+):(\d+)/);
            if (match) {
                let seconds = parseInt(match[1]) * 3600 + parseInt(match[2]) * 60 + parseInt(match[3]);
                const timer = setInterval(function() {
                    if (seconds <= 0) {
                        clearInterval(timer);
                        expiryElement.textContent = 'Expirado';
                        expiryElement.closest('.badge')?.classList.remove('bg-warning');
                        expiryElement.closest('.badge')?.classList.add('bg-danger');
                        // Recarregar página para forçar novo estado (opcional)
                        // window.location.reload();
                        return;
                    }
                    const hrs = String(Math.floor(seconds / 3600)).padStart(2, '0');
                    const mins = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
                    const secs = String(seconds % 60).padStart(2, '0');
                    expiryElement.textContent = `${hrs}:${mins}:${secs}`;
                    seconds--;
                }, 1000);
            }
        }

    })();
</script>