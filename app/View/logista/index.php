<!-- views/logista/veiculos/index.php -->
<?php
// Define o título com base no filtro
$titulo = match ($filtro ?? null) {
    'vitrine'   => 'Veículos na Vitrine',
    'vendidos'  => 'Veículos Vendidos',
    'reservados'=> 'Veículos Reservados',
    'estoque'   => 'Veículos em Estoque',
    default     => 'Meus Veículos'
};

// Define a URL base para os links de paginação (mantendo o filtro)
$baseUrl = '/logista/veiculos';
if (!empty($filtro)) {
    $baseUrl .= '?filtro=' . urlencode($filtro);
}
?>

<div class="container-fluid px-4">
    <!-- Título e ações -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= htmlspecialchars($titulo) ?></h1>
            <p class="text-muted small"><?= $total ?> veículo(s) encontrado(s)</p>
        </div>
        <a href="/logista/veiculos/criar" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Novo Veículo
        </a>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <!-- Filtro ativo (opcional) -->
    <?php if (!empty($filtro)): ?>
        <div class="mb-3">
            <span class="badge bg-secondary rounded-pill px-3 py-2">
                <i class="bi bi-funnel me-1"></i> Filtro ativo: 
                <strong><?= htmlspecialchars($titulo) ?></strong>
            </span>
            <a href="/logista/veiculos" class="btn btn-outline-secondary btn-sm ms-2">
                <i class="bi bi-x-lg"></i> Limpar filtro
            </a>
        </div>
    <?php endif; ?>

    <!-- Tabela de veículos -->
    <?php if (empty($veiculos)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-car-front display-1 text-muted"></i>
                <h4 class="mt-3">Nenhum veículo encontrado</h4>
                <p class="text-muted">Comece adicionando seu primeiro veículo.</p>
                <a href="/logista/veiculos/criar" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Adicionar Veículo
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Ano</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th>Vitrine</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = ($pagina - 1) * 20 + 1; ?>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <tr>
                                    <td><?= $contador++ ?></td>
                                    <td>
                                        <?= htmlspecialchars($veiculo['marca_nome'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($veiculo['modelo_nome'] ?? 'N/A') ?>
                                    </td>
                                    <td><?= htmlspecialchars($veiculo['ano_modelo'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($veiculo['preco'])): ?>
                                            R$ <?= number_format((float) $veiculo['preco'], 2, ',', '.') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $veiculo['status_estoque'] ?? 'disponivel';
                                        $badgeClass = match($status) {
                                            'disponivel' => 'bg-success',
                                            'vendido'    => 'bg-danger',
                                            'reservado'  => 'bg-warning text-dark',
                                            default      => 'bg-secondary'
                                        };
                                        $label = match($status) {
                                            'disponivel' => 'Disponível',
                                            'vendido'    => 'Vendido',
                                            'reservado'  => 'Reservado',
                                            default      => ucfirst($status)
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1">
                                            <?= $label ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($veiculo['status_vitrine'] ?? 'inativo') === 'ativo'): ?>
                                            <span class="badge bg-primary rounded-pill px-3 py-1">
                                                <i class="bi bi-eye"></i> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-1">
                                                <i class="bi bi-eye-slash"></i> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Editar -->
                                            <a href="/logista/veiculos/editar/<?= $veiculo['id'] ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <!-- Toggle vitrine -->
                                            <a href="#" 
                                               class="btn btn-outline-<?= ($veiculo['status_vitrine'] ?? 'inativo') === 'ativo' ? 'warning' : 'success' ?>" 
                                               title="<?= ($veiculo['status_vitrine'] ?? 'inativo') === 'ativo' ? 'Remover da vitrine' : 'Adicionar à vitrine' ?>"
                                               onclick="event.preventDefault(); if(confirm('<?= ($veiculo['status_vitrine'] ?? 'inativo') === 'ativo' ? 'Remover este veículo da vitrine?' : 'Adicionar este veículo à vitrine?' ?>')) { document.getElementById('toggle-vitrine-<?= $veiculo['id'] ?>').submit(); }">
                                                <i class="bi bi-<?= ($veiculo['status_vitrine'] ?? 'inativo') === 'ativo' ? 'eye-slash' : 'eye' ?>"></i>
                                            </a>
                                            <form id="toggle-vitrine-<?= $veiculo['id'] ?>" 
                                                  action="/logista/veiculos/toggle-vitrine/<?= $veiculo['id'] ?>" 
                                                  method="POST" 
                                                  style="display: none;">
                                            </form>

                                            <!-- Deletar -->
                                            <a href="#" 
                                               class="btn btn-outline-danger" 
                                               title="Mover para lixeira"
                                               onclick="event.preventDefault(); if(confirm('Tem certeza que deseja mover este veículo para a lixeira?')) { document.getElementById('delete-<?= $veiculo['id'] ?>').submit(); }">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <form id="delete-<?= $veiculo['id'] ?>" 
                                                  action="/logista/veiculos/deletar/<?= $veiculo['id'] ?>" 
                                                  method="POST" 
                                                  style="display: none;">
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <?php if ($totalPaginas > 1): ?>
            <nav class="mt-4" aria-label="Navegação de páginas">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl ?>&pagina=<?= $pagina - 1 ?>" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>&pagina=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl ?>&pagina=<?= $pagina + 1 ?>" aria-label="Próxima">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>