<!-- ============================================================ -->
<!-- ETAPAS PARA COMBUSTÃO                                         -->
<!-- ============================================================ -->

<!-- Etapa 1: Básico (comum) -->
<?php include __DIR__ . '/_basico.php'; ?>

<!-- Etapa 2: Motorização -->
<div class="wizard-step" data-step="motorizacao" data-label="Motorização" style="display: none;">
    <!-- @todo: Adicionar campos de motorização (combustível, cilindrada, aspiração) -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos de motorização serão adicionados aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 3: Desempenho -->
<div class="wizard-step" data-step="desempenho" data-label="Desempenho" style="display: none;">
    <!-- @todo: Adicionar campos de desempenho (aceleração, velocidade, potência, torque) -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos de desempenho serão adicionados aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 4: Consumo -->
<div class="wizard-step" data-step="consumo" data-label="Consumo" style="display: none;">
    <!-- @todo: Adicionar campos de consumo (cidade, estrada, e flex) -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos de consumo serão adicionados aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 5: Direção -->
<div class="wizard-step" data-step="direcao" data-label="Direção" style="display: none;">
    <!-- @todo: Adicionar campo tipo_direcao -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campo de direção será adicionado aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 6: Tração e Transmissão -->
<div class="wizard-step" data-step="tracao_transmissao" data-label="Tração e Transmissão" style="display: none;">
    <!-- @todo: Adicionar campos tracao_tipo, transmissao_tipo, numero_marchas (condicional) -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos de tração e transmissão serão adicionados aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 7: Suspensão e Freio -->
<div class="wizard-step" data-step="suspensao_freio" data-label="Suspensão e Freio" style="display: none;">
    <!-- @todo: Adicionar campos suspensao_dianteira, suspensao_traseira, freio_dianteiro, freio_traseiro -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos de suspensão e freio serão adicionados aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 8: Rodas -->
<div class="wizard-step" data-step="rodas" data-label="Rodas" style="display: none;">
    <!-- @todo: Adicionar campos tipo_roda, pneu_aro (com "Outro") -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos de rodas serão adicionados aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 9: Dimensões -->
<div class="wizard-step" data-step="dimensoes" data-label="Dimensões" style="display: none;">
    <!-- @todo: Adicionar campos de dimensões (comprimento, largura, altura, etc.) -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos de dimensões serão adicionados aqui.</p>
        </div>
    </div>
</div>

<!-- Etapa 10: Opcionais (comum) -->
<div class="wizard-step" data-step="opcionais" data-label="Opcionais" style="display: none;">
    <?php include __DIR__ . '/_opcionais.php'; ?>
</div>

<!-- Etapa 11: GNV (condicional) -->
<div class="wizard-step" data-step="gnv" data-label="GNV" style="display: none;">
    <!-- @todo: Adicionar campos GNV (geracao_kit, capacidade_cilindro_m3, quantidade_cilindros, localizacao_cilindro) -->
    <div class="row g-3">
        <div class="col-12">
            <p class="text-muted">Campos GNV serão adicionados aqui (exibidos apenas se checkbox marcado).</p>
        </div>
    </div>
</div>