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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=car_gear,readiness_score,local_gas_station,search_hands_free,auto_transmission,emoji_transportation,tire_repair,border_style,square_foot,airline_seat_recline_extra,nest_eco_leaf,traffic_jam,car_tag,propane,eco,pan_tool_alt" />

    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* COLUNA CENTRALIZADA DO WIZARD */
        .wizard-page {
            max-width: min(90rem, 92vw);
            margin-inline: auto;
            padding-inline: clamp(1.5rem, 4vw, 4rem);
        }

        /* Efeito de zoom nos cards do dashboard */
        .card-zoom {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-zoom:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0,0,0,.15) !important;
        }

        /* Estilos para a lista de itens */
        .lista-items .item-lista {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .lista-items .item-lista:hover {
            background-color: #dfe0e1;
        }
        .lista-items .item-lista.selecionado {
            background-color: #cfdae5;
            border-left: 4px solid #0d6efd;
        }
        .lista-items .item-lista img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 4px;
            background: #f8f9fa;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        }
        .lista-items .item-lista .nome {
            font-weight: 600;
        }

        /* Overlay de edição nos cards de resumo */
        .resumo-card {
            position: relative;
            transition: transform 0.2s;
        }
        .resumo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        .resumo-card .editar-overlay {
            position: absolute;
            top: 8px;
            right: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .resumo-card:hover .editar-overlay {
            opacity: 1;
        }
        .resumo-card .editar-overlay i {
            font-size: 1.2rem;
            background: white;
            padding: 4px 6px;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Badge de exibição no formulário */
        .brand-model-display .badge {
            font-size: 1rem;
            padding: 0.6rem 1rem;
        }

        /* Remove setas do Chrome, Safari, Edge, Opera */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Remove setas do Firefox */
        input[type="number"] {
            -moz-appearance: textfield;
        }

        /* ============================================================ */
        /* STEPPER - Navegação direta entre etapas                      */
        /* ============================================================ */

        .stepper-wrapper {
            gap: 0; /* espaçamento gerenciado pelos conectores */
            padding: 0 10px; /* pequeno respiro nas bordas */
        }

        /* Cada item (círculo + possível label) */
        .stepper-item {
            flex-shrink: 0; /* impede encolhimento */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Círculo base (neutro) */
        .stepper-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef; /* cinza claro (neutro) */
            color: #6c757d; /* cinza escuro */
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, border 0.2s ease;
            border: 3px solid transparent; /* reserva espaço para a borda ativa */
            user-select: none;
        }

        /* Hover (melhora a experiência) */
        .stepper-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        /* ---------- Estados ---------- */

        /* Completo (verde) */
        .stepper-circle.complete {
            background-color: var(--bs-success, #28a745);
            color: white;
        }

        /* Erro (vermelho) */
        .stepper-circle.error {
            background-color: var(--bs-danger, #dc3545);
            color: white;
        }

        /* Ativo (etapa atual) – borda azul + leve escala */
        .stepper-circle.active {
            border-color: var(--bs-primary, #0d6efd);
            transform: scale(1.1);
        }

        /* ---------- Conectores (linhas entre círculos) ---------- */
        .stepper-connector {
            flex: 1;
            height: 2px;
            background-color: #d1d5db; /* cinza claro */
            margin: 0 8px;
            transition: background-color 0.3s ease;
            min-width: 10px; /* evita sumir em telas muito pequenas */
        }

        .wizard-step {
            display: none;
        }
        .wizard-step.is-active {
            display: block;
        }

        .material-symbols-outlined {
            vertical-align: text-bottom;
            line-height: 1;
        }

        .input-group-text {
            border-top-right-radius: 0.375rem !important; 
            border-bottom-right-radius: 0.375rem !important;
        }

        .input-border-correction {
            border-top-left-radius: 0.375rem !important; 
            border-bottom-left-radius: 0.375rem !important; 
            border-top-right-radius: 0 !important; 
            border-bottom-right-radius: 0 !important;
        }

        .cacamba-hidden {
            display: none !important;
        }

        .marchas-hidden {
            display: none !important;
        }

        .icon-hand-right {
            display: inline-block;
            transform: rotate(90deg);
        }

        /* ============================================================ */
        /* CABEÇALHO FIXO: Header + Stepper                             */
        /* ============================================================ */
        .sticky-wizard-top {
            position: sticky;
            top: 0;
            z-index: 1020;
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            padding-top: clamp(1.5rem, 3vh, 3rem);
            box-shadow: 0 5px 7px -4px rgba(0, 0, 0, 0);
            transition: box-shadow 0.4s ease;
        }

        /* Espaço entre o cabeçalho sticky e o primeiro campo do formulário */
        .sticky-wizard-top ~ .wizard-step {
            margin-top: 2rem;
        }

        .sticky-wizard-top.is-scrolled {
            box-shadow: 0 5px 7px -4px rgba(0, 0, 0, 0.26);
        }

        /* ============================================================ */
        /* RODAPÉ FIXO                                                   */
        /* ============================================================ */
        .wizard-footer {
            position: sticky;
            bottom: 0;
            z-index: 1020;
            background-color: #fff;
            border-top: 1px solid #e9ecef;
            padding-block: clamp(1rem, 2vh, 1.5rem);
            box-shadow: 0 -5px 7px -4px rgba(0, 0, 0, 0);
            transition: box-shadow 0.7s ease;
        }

        .wizard-footer.is-scrolled {
            box-shadow: 0 -5px 7px -4px rgba(0, 0, 0, 0.26);
        }

        /* ============================================================ */
        /* LAYOUT DO FORM (para o footer grudar no fundo em etapas curtas) */
        /* ============================================================ */
        #veiculoForm {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #wizard-container {
            flex-grow: 1;
            padding-bottom: 3rem;
        }
    </style>

    <!-- Bootstrap JS (defer: ordem garantida em relação ao wizard) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
                <button type="button" class="btn btn-warning btn-sm" id="btnAnterior" disabled>
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

        <?php include __DIR__ . '/_modal_marca_modelo.php'; ?>
    </div>
</body>
</html>