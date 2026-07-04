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

                <div class="row g-4 mb-4">
                    <!-- Card 1: Total de Veículos -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <a href="/logista/veiculos" class="text-decoration-none text-reset d-block">
                            <div class="card border-2 shadow h-100 card-zoom">
                                <div class="card-body text-center">
                                    <div class="bg-primary shadow rounded-1 py-2 px-2 d-block w-100 mb-3">
                                        <i class="bi bi-car-front fs-2 text-white d-block text-center"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Total de Veículos</h6>
                                    <h3 class="mb-2 fw-bold">1.247</h3>
                                    <span class="badge shadow bg-primary bg-opacity-50 text-body rounded-pill px-3 py-1">
                                        <i class="bi bi-arrow-up"></i> +12%
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 2: Na Vitrine -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <a href="/logista/veiculos?filtro=vitrine" class="text-decoration-none text-reset d-block">
                            <div class="card border-2 shadow h-100 card-zoom">
                                <div class="card-body text-center">
                                    <div class="bg-warning shadow rounded-1 py-2 px-2 d-block w-100 mb-3">
                                        <i class="bi bi-megaphone fs-2 text-white d-block text-center"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Na Vitrine</h6>
                                    <h3 class="mb-2 fw-bold">384</h3>
                                    <span class="badge shadow bg-warning bg-opacity-50 text-body rounded-pill px-3 py-1">
                                        <i class="bi bi-arrow-up"></i> +5%
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 3: Vendidos -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <a href="/logista/veiculos?filtro=vendidos" class="text-decoration-none text-reset d-block">
                            <div class="card border-2 shadow h-100 card-zoom">
                                <div class="card-body text-center">
                                    <div class="bg-success shadow rounded-1 py-2 px-2 d-block w-100 mb-3">
                                        <i class="bi bi-cash fs-2 text-white d-block text-center"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Vendidos</h6>
                                    <h3 class="mb-2 fw-bold">312</h3>
                                    <span class="badge shadow bg-success bg-opacity-50 text-body rounded-pill px-3 py-1">
                                        <i class="bi bi-arrow-down"></i> -2%
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 4: Reservados -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <a href="/logista/veiculos?filtro=reservados" class="text-decoration-none text-reset d-block">
                            <div class="card border-2 shadow h-100 card-zoom">
                                <div class="card-body text-center">
                                    <div class="bg-danger shadow rounded-1 py-2 px-2 d-block w-100 mb-3">
                                        <i class="bi bi-clock-history fs-2 text-white d-block text-center"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Reservados</h6>
                                    <h3 class="mb-2 fw-bold">89</h3>
                                    <span class="badge shadow bg-danger bg-opacity-50 text-body rounded-pill px-3 py-1">
                                        <i class="bi bi-dash"></i> 0%
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Card 5: Em Estoque -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <a href="/logista/veiculos?filtro=estoque" class="text-decoration-none text-reset d-block">
                            <div class="card border-2 shadow h-100 card-zoom">
                                <div class="card-body text-center">
                                    <div class="bg-info shadow rounded-1 py-2 px-2 d-block w-100 mb-3">
                                        <i class="bi bi-box-seam fs-2 text-white d-block text-center"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Em Estoque</h6>
                                    <h3 class="mb-2 fw-bold">462</h3>
                                    <span class="badge shadow bg-info bg-opacity-50 text-body rounded-pill px-3 py-1">
                                        <i class="bi bi-arrow-up"></i> +8%
                                    </span>
                                </div>
                            </div>
                        </a>
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