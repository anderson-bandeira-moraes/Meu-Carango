<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Painel Administrativo</h4>
            </div>
            <div class="card-body">
                <p>Bem-vindo, <strong><?= htmlspecialchars($admin['nome'] ?? 'Administrador') ?></strong>!</p>
                <p>Este é o painel de controle administrativo. Aqui você poderá gerenciar lojistas, anúncios, planos e configurações do sistema.</p>
                <hr>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <i class="bi bi-people fs-1 text-primary"></i>
                                <h5 class="card-title mt-2">Lojistas</h5>
                                <p class="card-text">Gerencie os lojistas cadastrados.</p>
                                <a href="#" class="btn btn-sm btn-outline-primary">Gerenciar</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <i class="bi bi-megaphone fs-1 text-success"></i>
                                <h5 class="card-title mt-2">Anúncios</h5>
                                <p class="card-text">Modere e visualize anúncios.</p>
                                <a href="#" class="btn btn-sm btn-outline-success">Ver</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <i class="bi bi-gear fs-1 text-warning"></i>
                                <h5 class="card-title mt-2">Configurações</h5>
                                <p class="card-text">Parâmetros do sistema.</p>
                                <a href="#" class="btn btn-sm btn-outline-warning">Ajustar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>