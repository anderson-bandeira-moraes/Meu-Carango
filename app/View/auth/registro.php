<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Criar conta</h4>
            </div>
            <div class="card-body">
                <!-- Exibe mensagem de erro geral se houver -->
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($erro) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/registro">
                    <!-- Nome (lojista) -->
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" 
                               required autofocus>
                        <div class="form-text">Seu nome pessoal (como aparecerá na loja).</div>
                    </div>

                    <!-- E-mail -->
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($dados['email'] ?? '') ?>" 
                               required>
                        <div class="form-text">Usado para login e notificações.</div>
                    </div>

                    <!-- Senha -->
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="senha" name="senha" 
                               required minlength="6">
                        <div class="form-text">Mínimo de 6 caracteres.</div>
                    </div>

                    <!-- Nome da loja (fantasia) -->
                    <div class="mb-3">
                        <label for="nome_loja" class="form-label">Nome da loja <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome_loja" name="nome_loja" 
                               value="<?= htmlspecialchars($dados['nome_loja'] ?? '') ?>" 
                               required>
                        <div class="form-text">Nome fantasia que aparecerá na vitrine.</div>
                    </div>

                    <!-- Slug (identificador único) -->
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug da loja <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" 
                               value="<?= htmlspecialchars($dados['slug'] ?? '') ?>" 
                               required pattern="[a-z0-9-]+">
                        <div class="form-text">
                            Ex: <strong>auto-jose</strong> (apenas letras minúsculas, números e hífens). 
                            Será usado na URL: loja.seudominio.com/<strong>auto-jose</strong>
                        </div>
                    </div>

                    <!-- Telefone (WhatsApp) -->
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone (WhatsApp) <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="telefone" name="telefone" 
                               value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>" 
                               required placeholder="5511999999999">
                        <div class="form-text">Formato internacional sem máscara: DDD + número (ex: 5511999999999).</div>
                    </div>

                    <hr>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg me-1"></i> Registrar
                        </button>
                        <a href="/login" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Já tenho conta
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>