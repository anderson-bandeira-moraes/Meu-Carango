<div class="row">
    <div class="col-12">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center rounded-top-4">
                <h4 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard - Lojista</h4>
                <a href="/logista/logout" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Sair
                </a>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-person-circle fs-1 text-primary"></i>
                    <h2 class="mt-2">Bem-vindo, <strong><?= htmlspecialchars($user['nome'] ?? 'Lojista') ?></strong>!</h2>
                    <p class="text-muted">Este é o seu painel de controle. Aqui você poderá gerenciar seus anúncios, veículos e informações da sua loja.</p>
                </div>

                <hr>

                <!-- Cards de resumo (exemplo) -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card text-center border-primary h-100">
                            <div class="card-body">
                                <i class="bi bi-car-front fs-1 text-primary"></i>
                                <h5 class="card-title mt-2">Meus Veículos</h5>
                                <p class="card-text">Gerencie os veículos anunciados.</p>
                                <a href="#" class="btn btn-sm btn-outline-primary">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center border-success h-100">
                            <div class="card-body">
                                <i class="bi bi-megaphone fs-1 text-success"></i>
                                <h5 class="card-title mt-2">Meus Anúncios</h5>
                                <p class="card-text">Visualize e gerencie seus anúncios.</p>
                                <a href="#" class="btn btn-sm btn-outline-success">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center border-warning h-100">
                            <div class="card-body">
                                <i class="bi bi-gear fs-1 text-warning"></i>
                                <h5 class="card-title mt-2">Configurações</h5>
                                <p class="card-text">Ajuste os dados da sua loja.</p>
                                <a href="#" class="btn btn-sm btn-outline-warning">Ajustar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações adicionais (opcional) -->
                <div class="mt-4 p-3 rounded-3">
                    <h6><i class="bi bi-info-circle me-2"></i>Informações da conta</h6>
                    <p class="mb-1"><strong>E-mail:</strong> <?= htmlspecialchars($user['email'] ?? 'Não informado') ?></p>
                    <p class="mb-1"><strong>Loja:</strong> <?= htmlspecialchars($user['nome_loja'] ?? 'Não informado') ?></p>
                    <p class="mb-0"><strong>Slug:</strong> <?= htmlspecialchars($user['slug'] ?? 'Não informado') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>