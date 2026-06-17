<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-dark text-white text-center rounded-top-4">
                <h4 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Acesso Administrativo</h4>
            </div>
            <div class="card-body p-4">
                <!-- Área de mensagens (para erro/sucesso) -->
                <div id="messageArea">
                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($erro) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Formulário único (usado para ambos os modos) -->
                <form id="loginForm" method="POST" action="/admin/login">
                    <!-- Token CSRF (sempre enviado) -->
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 py-2" id="submitBtn">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                    </button>
                </form>

                <!-- Indicador de carregamento (AJAX) -->
                <div id="loadingIndicator" class="text-center mt-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Autenticando...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE ESCOLHA (exibido ao carregar a página) -->
<div class="modal fade" id="loginChoiceModal" tabindex="-1" aria-labelledby="loginChoiceModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="loginChoiceModalLabel"><i class="bi bi-question-circle me-2"></i>Escolha o tipo de login</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Como deseja testar o login administrativo?</p>
                <div class="d-grid gap-3">
                    <button class="btn btn-outline-primary btn-lg" id="choiceTraditional">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Login Tradicional (POST)
                        <small class="d-block text-muted">Envio do formulário com recarregamento da página</small>
                    </button>
                    <button class="btn btn-outline-success btn-lg" id="choiceAjax">
                        <i class="bi bi-cloud-upload me-2"></i> Login via AJAX
                        <small class="d-block text-muted">Envio assíncrono sem recarregar a página</small>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript: controle do modal e envio AJAX -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        const modal = new bootstrap.Modal(document.getElementById('loginChoiceModal'));
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const messageArea = document.getElementById('messageArea');

        let loginMode = 'traditional'; // 'traditional' ou 'ajax'

        // Exibe o modal ao carregar a página (apenas se não houver erro prévio)
        window.addEventListener('load', function() {
            // Se já houver uma mensagem de erro (ex: flash), não exibe o modal
            const hasError = document.querySelector('.alert-danger') !== null;
            if (!hasError) {
                modal.show();
            }
        });

        // Escolha: Login Tradicional
        document.getElementById('choiceTraditional').addEventListener('click', function() {
            loginMode = 'traditional';
            modal.hide();
            submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Entrar (POST)';
            submitBtn.className = 'btn btn-dark w-100 py-2';
        });

        // Escolha: Login via AJAX
        document.getElementById('choiceAjax').addEventListener('click', function() {
            loginMode = 'ajax';
            modal.hide();
            submitBtn.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Entrar (AJAX)';
            submitBtn.className = 'btn btn-success w-100 py-2';
        });

        // Intercepta o envio do formulário
        form.addEventListener('submit', function(event) {
            if (loginMode === 'ajax') {
                event.preventDefault(); // Impede o envio tradicional
                handleAjaxLogin();
            }
            // Se for tradicional, o formulário é enviado normalmente
        });

        // Função para login via AJAX
        function handleAjaxLogin() {
            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value.trim();
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

            // Validação básica
            if (!email || !senha) {
                showMessage('Preencha e-mail e senha.', 'danger');
                return;
            }

            // Mostra indicador de carregamento e desabilita o botão
            loadingIndicator.style.display = 'block';
            submitBtn.disabled = true;

            // Envia requisição AJAX
            fetch('/admin/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ email: email, senha: senha })
            })
            .then(response => {
                return response.json().then(data => ({ status: response.status, data }));
            })
            .then(({ status, data }) => {
                loadingIndicator.style.display = 'none';
                submitBtn.disabled = false;

                if (status === 200 && data.sucesso) {
                    // Redireciona para o dashboard
                    window.location.href = data.redirect || '/admin';
                } else if (status === 403) {
                    // Token inválido (CSRF)
                    showMessage('Token CSRF inválido. Recarregue a página e tente novamente.', 'danger');
                    // Recarrega a página para obter um novo token
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    // Outros erros (ex: credenciais inválidas)
                    showMessage(data.erro || 'Erro ao fazer login. Tente novamente.', 'danger');
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                submitBtn.disabled = false;
                showMessage('Erro na comunicação com o servidor. Verifique sua conexão.', 'danger');
                console.error('AJAX error:', error);
            });
        }

        // Função para exibir mensagens (alerta)
        function showMessage(message, type = 'danger') {
            messageArea.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            `;
        }

        // Se houver erro na página, fecha o modal automaticamente
        if (document.querySelector('.alert-danger')) {
            modal.hide();
        }
    });
</script>