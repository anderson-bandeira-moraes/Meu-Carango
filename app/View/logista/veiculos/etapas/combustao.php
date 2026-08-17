<!-- ============================================================ -->
<!-- ETAPAS PARA COMBUSTÃO                                         -->
<!-- ============================================================ -->

<!-- Etapa 1: Básico (comum) -->
<?php include __DIR__ . '/_basico.php'; ?>

<!-- Etapa 2: Motorização -->
<div class="wizard-step" data-step="motorizacao" data-label="Motorização" style="display: none;">
    <!-- @todo: Adicionar campos de motorização (combustível, cilindrada, aspiração) -->
    <div class="row g-3">
        <!-- Combustível -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="combustivel" class="form-label mb-0">Combustível <span class="text-danger">*</span></label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de combustível utilizado pelo veículo. Opções: Álcool, Diesel, Flex (Álcool/Gasolina) ou Gasolina. Essencial para o comprador saber o custo de abastecimento e a disponibilidade do combustível em sua região.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <select name="combustivel" id="combustivel" class="form-select <?= isset($errors['combustivel']) ? 'is-invalid' : '' ?>" required>
                <option value="">Selecione</option>
                <?php foreach (combustiveis_list() as $value => $label): ?>
                    <option value="<?= $value ?>" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', $value) ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
                O combustível é obrigatório.
            </div>
        </div>

        <!-- Motorização (cilindrada) -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="motor_tipo" class="form-label mb-0">Motorização <span class="text-danger">*</span></label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Cilindrada do motor, que determina sua capacidade volumétrica. Valores comuns: 1.0, 1.6, 2.0, etc. Quanto maior a cilindrada, maior a potência e o consumo de combustível. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>

            <?php
                $motorTipo = gerarSelectOutro(
                    nome: 'motor_tipo',
                    lista: motorizacoes_list(),
                    valorSalvo: $old['motor_tipo'] ?? $complemento['motor_tipo'] ?? '',
                    classes: isset($errors['motor_tipo']) ? 'is-invalid' : '',
                    id: '',
                    attrs: 'required'
                );
            ?>

            <!-- Select gerado pela função -->
            <?= $motorTipo['select_html'] ?>

            <!-- Campo extra para "Outro" -->
            <input type="text" name="motor_tipo_outro" id="motor_tipo_outro" 
                   class="form-control mt-2 <?= isset($errors['motor_tipo']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($motorTipo['valor_outro']) ?>" 
                   placeholder="Digite a motorização (ex: 1.8, 2.2, 3.0)" 
                   style="display: <?= $motorTipo['is_outro'] ? 'block' : 'none' ?>;">

            <div class="invalid-feedback">
                A motorização é obrigatória.
            </div>
        </div>

        <!-- Aspiração -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="aspiracao_combustao" class="form-label mb-0">Tipo de Aspiração</label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Como o ar é admitido no motor: Aspirado (sem turbina), Turbo (turbocompressor acionado pelos gases de escape) ou Supercharger (compressor mecânico acionado pelo motor). Afeta a potência, o consumo e a resposta do acelerador.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <select name="aspiracao" id="aspiracao_combustao" class="form-select <?= isset($errors['aspiracao']) ? 'is-invalid' : '' ?>">
                <option value="">Selecione</option>
                <?php foreach (aspiracao_list() as $value => $label): ?>
                    <option value="<?= $value ?>" <?= selected($old['aspiracao'] ?? $complemento['aspiracao'] ?? '', $value) ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
                Selecione um tipo de aspiração válido.
            </div>
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