<?php
/**
 * Layout específico para o wizard de cadastro/edição de veículos.
 * 
 * Variáveis esperadas:
 *   - $content     : string  HTML do conteúdo das etapas (gerado pela view específica)
 *   - $title       : string  Título da página
 *   - $action      : string  URL de submissão do formulário
 *   - $tipo        : string  Tipo do veículo (combustao, eletrico, hibrido)
 *   - $isEdit      : bool    Indica se é edição
 *   - $veiculoId   : int     ID do veículo (na edição)
 *   - $error       : string  Mensagem de erro (opcional)
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Cadastrar Veículo') ?> - Meu Carango</title>
    
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=car_gear,readiness_score,local_gas_station,search_hands_free,auto_transmission,emoji_transportation,tire_repair,border_style,square_foot,airline_seat_recline_extra,nest_eco_leaf,traffic_jam,car_tag,propane,eco,pan_tool_alt" />

    <!-- CSS do wizard (cache-busting via filemtime) -->
    <link rel="stylesheet" href="/assets/css/wizard.css?v=<?= filemtime(ROOT_DIR . '/public/assets/css/wizard.css') ?: time() ?>">

    <!-- Bootstrap JS -->
    <script src="/assets/js/bootstrap.bundle.min.js" defer></script>

    <!-- Config do wizard (injetada pelo PHP) -->
    <script>
        window.WIZARD_CONFIG = {
            marcas:            <?= json_encode($marcas) ?>,
            modelos:           <?= json_encode($modelos) ?>,
            isEdit:            <?= json_encode($isEdit ?? false) ?>,
            isReturnFromError: <?= json_encode($isReturnFromError ?? false) ?>
        };
    </script>

    <!-- Wizard JS (defer: depende do HTML parseado) -->
    <script src="/assets/js/wizard.js?v=<?= filemtime(ROOT_DIR . '/public/assets/js/wizard.js') ?: time() ?>" defer></script>
</head>
<body>
    <div class="container-fluid wizard-page">
        <!-- Formulário -->
        <form id="veiculoForm" action="<?= $action ?>" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="tipo_veiculo" value="<?= htmlspecialchars($tipo) ?>">

            <!-- Mensagem de erro (flash) -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i> <?= nl2br(htmlspecialchars($error)) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>

            <!-- Container das etapas (o conteúdo vem da view específica) -->
            <div id="wizard-container">
                <?= $content ?? '' ?>
            </div>

            <!-- Rodapé com botões de navegação -->
            <div class="wizard-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAnterior" disabled>
                    <i class="bi bi-arrow-left me-1"></i>Anterior
                </button>
                
                <button type="button" class="btn btn-primary btn-sm" id="btnProximo">
                    Próximo<i class="bi bi-arrow-right ms-1"></i>
                </button>

                <button type="button" class="btn btn-success btn-sm" id="btnSalvar" disabled>
                    <i class="bi bi-check-lg me-1"></i>Salvar
                </button>
            </div>
        </form>

        <?php include VIEW_DIR . '/logista/veiculos/etapas/_modal_marca_modelo.php'; ?>
    </div>
</body>
</html>