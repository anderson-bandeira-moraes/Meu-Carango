<!-- ============================================================ -->
<!-- STEPPER: Navegação direta entre etapas (Combustão - 6 etapas) -->
<!-- ============================================================ -->
<div id="stepperContainer" class="stepper-wrapper d-flex align-items-center justify-content-between mb-4">
    <!-- 1. Informações Básicas -->
    <div class="stepper-item d-flex flex-column align-items-center">
        <div class="stepper-circle" data-index="0" data-bs-toggle="tooltip" title="Informações Básicas">1</div>
    </div>
    <div class="stepper-connector"></div>

    <!-- 2. Motor -->
    <div class="stepper-item d-flex flex-column align-items-center">
        <div class="stepper-circle" data-index="1" data-bs-toggle="tooltip" title="Motorização, Desempenho e Consumo">2</div>
    </div>
    <div class="stepper-connector"></div>

    <!-- 3. Chassi -->
    <div class="stepper-item d-flex flex-column align-items-center">
        <div class="stepper-circle" data-index="2" data-bs-toggle="tooltip" title="Tração, Suspensão e Rodas">3</div>
    </div>
    <div class="stepper-connector"></div>

    <!-- 4. Dimensões -->
    <div class="stepper-item d-flex flex-column align-items-center">
        <div class="stepper-circle" data-index="3" data-bs-toggle="tooltip" title="Dimensões, Peso e Portas">4</div>
    </div>
    <div class="stepper-connector"></div>

    <!-- 5. Opcionais -->
    <div class="stepper-item d-flex flex-column align-items-center">
        <div class="stepper-circle" data-index="4" data-bs-toggle="tooltip" title="Opcionais">5</div>
    </div>
    <div class="stepper-connector"></div>

    <!-- 6. GNV -->
    <div class="stepper-item d-flex flex-column align-items-center">
        <div class="stepper-circle" data-index="5" data-bs-toggle="tooltip" title="GNV">6</div>
    </div>
</div>
<!-- FIM STEPPER -->

<!-- ============================================================ -->
<!-- ETAPAS PARA COMBUSTÃO                                        -->
<!-- ============================================================ -->

<!-- Etapa 1: Básico (comum) -->
<?php include __DIR__ . '/_basico.php'; ?>

<!-- ============================================================ -->
<!-- ETAPA: MOTOR (Combustível, Desempenho e Consumo)             -->
<!-- ============================================================ -->
<div class="wizard-step" data-step="motor" data-label="Motor">
    
    <!-- ===== SEÇÃO 1: MOTORIZAÇÃO ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">car_gear</span> Motorização</h5>
    <div class="row g-3">
        <!-- Combustível -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="combustivel" class="form-label mb-0 fw-bold">Combustível <span class="text-danger">*</span></label>
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
            <div class="invalid-feedback fw-bold">
                O combustível é obrigatório.
            </div>
        </div>

        <!-- Motorização (cilindrada) -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="motor_tipo" class="form-label mb-0 fw-bold">Motorização <span class="text-danger">*</span></label>
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

            <?= $motorTipo['select_html'] ?>

            <input type="text" name="motor_tipo_outro" id="motor_tipo_outro" 
                   class="form-control mt-2 <?= isset($errors['motor_tipo']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($motorTipo['valor_outro']) ?>" 
                   placeholder="Digite a motorização (ex: 1.8, 2.2, 3.0)" 
                   style="display: <?= $motorTipo['is_outro'] ? 'block' : 'none' ?>;">

            <div class="invalid-feedback fw-bold">
                A motorização é obrigatória.
            </div>
        </div>

        <!-- Aspiração -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="aspiracao_combustao" class="form-label mb-0 fw-bold">Tipo de Aspiração</label>
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
        </div>
    </div>

    <hr class="my-4">

    <!-- ===== SEÇÃO 2: DESEMPENHO ===== -->
    <h5 class="fw-bold mb-3"><span class="material-symbols-outlined text-primary">readiness_score</span> Desempenho</h5>
    <div class="row g-3">
        <!-- Aceleração 0-100 -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="aceleracao_0_100_seg" class="form-label mb-0 fw-bold">Aceleração 0-100</label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tempo necessário para o veículo acelerar de 0 a 100 km/h, medido em segundos (s). Valores comuns: 6 a 12 segundos para veículos de passeio. Quanto menor o tempo, melhor o desempenho.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <div class="input-group">
                <input type="number" step="any" inputmode="decimal" name="aceleracao_0_100_seg" id="aceleracao_0_100_seg" 
                       class="form-control <?= isset($errors['aceleracao_0_100_seg']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['aceleracao_0_100_seg'] ?? $complemento['aceleracao_0_100_seg'] ?? '') ?>" 
                       placeholder="Ex: 8.5" min="0">
                <span class="input-group-text">s</span>
            </div>
        </div>

        <!-- Velocidade Máxima -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="velocidade_max_kmh" class="form-label mb-0 fw-bold">Velocidade Máxima</label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Velocidade máxima que o veículo pode atingir, medida em km/h. Valores comuns: 150 a 300 km/h. Importante para viagens longas em rodovias.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <div class="input-group has-validation">
                <input type="text" inputmode="numeric" pattern="\d*" data-tipo="inteiro" name="velocidade_max_kmh" id="velocidade_max_kmh" 
                       class="form-control <?= isset($errors['velocidade_max_kmh']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['velocidade_max_kmh'] ?? $complemento['velocidade_max_kmh'] ?? '') ?>" 
                       placeholder="Ex: 220">
                <span class="input-group-text">km/h</span>
                <div class="invalid-feedback feedback-pontovirgula fw-bold" style="display: none;">
                    Este campo não aceita ponto (.) ou vírgula (,)
                </div>
            </div>
        </div>

        <!-- Potência Máxima -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="potencia_cv" class="form-label mb-0 fw-bold">Potência Máxima<span class="text-danger">*</span></label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Potência máxima do motor em cavalos-vapor (cv). Valores comuns: 60 a 600 cv. Quanto maior a potência, melhor o desempenho em aceleração e retomadas, mas geralmente com maior consumo de combustível.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <div class="input-group has-validation">
                <input type="text" inputmode="numeric" pattern="\d*" data-tipo="inteiro" name="potencia_cv" id="potencia_cv" 
                       class="form-control <?= isset($errors['potencia_cv']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['potencia_cv'] ?? $complemento['potencia_cv'] ?? '') ?>" 
                       placeholder="Ex: 120" required>
                <span class="input-group-text">cv</span>
                <div class="invalid-feedback fw-bold">
                    A potência máxima é obrigatória.
                </div>
                <div class="invalid-feedback feedback-pontovirgula fw-bold" style="display: none;">
                    Este campo não aceita ponto (.) ou vírgula (,)
                </div>
            </div>
        </div>

        <!-- Torque Máximo -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="torque_kgfm" class="form-label mb-0 fw-bold">Torque Máximo</label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Torque máximo do motor em quilograma-força-metro (kgfm). Valores comuns: 10 a 80 kgfm. Indica a força de giro do motor, influenciando a capacidade de arrancada, retomada e capacidade de reboque.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <div class="input-group">
                <input type="number" step="any" inputmode="decimal" name="torque_kgfm" id="torque_kgfm" 
                       class="form-control <?= isset($errors['torque_kgfm']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['torque_kgfm'] ?? $complemento['torque_kgfm'] ?? '') ?>" 
                       placeholder="Ex: 18.5" min="0">
                <span class="input-group-text">kgfm</span>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- ===== SEÇÃO 3: CONSUMO ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">local_gas_station</span> Consumo</h5>
    <div class="row g-3">
        <!-- Consumo Cidade -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="consumo_cidade_kml" class="form-label mb-0 fw-bold">
                    Consumo Cidade 
                    <span class="sufixo-gasolina" style="display: none;"> (Gasolina)</span>
                    <span class="text-danger">*</span>
                </label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Consumo de combustível em condições urbanas (com trânsito, semáforos e paradas), medido em quilômetros por litro (km/l). Valores comuns: 8 a 15 km/l. Essencial para o comprador avaliar o custo de uso diário do veículo.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <div class="input-group">
                <input type="number" step="any" inputmode="decimal" name="consumo_cidade_kml" id="consumo_cidade_kml" 
                       class="form-control <?= isset($errors['consumo_cidade_kml']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['consumo_cidade_kml'] ?? $complemento['consumo_cidade_kml'] ?? '') ?>" 
                       placeholder="Ex: 12.5" min="0" required>
                <span class="input-group-text">km/l</span>
                <div class="invalid-feedback fw-bold">
                    O consumo na cidade é obrigatório.
                </div>
            </div>  
        </div>

        <!-- Consumo Estrada -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="consumo_estrada_kml" class="form-label mb-0 fw-bold">
                    Consumo Estrada 
                    <span class="sufixo-gasolina" style="display: none;"> (Gasolina)</span>
                    <span class="text-danger">*</span>
                </label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Consumo de combustível em condições de estrada/rodovia, medido em quilômetros por litro (km/l). Valores comuns: 12 a 20 km/l. Essencial para avaliar o custo em viagens e percursos de longa distância.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <div class="input-group">
                <input type="number" step="any" inputmode="decimal" name="consumo_estrada_kml" id="consumo_estrada_kml" 
                       class="form-control <?= isset($errors['consumo_estrada_kml']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['consumo_estrada_kml'] ?? $complemento['consumo_estrada_kml'] ?? '') ?>" 
                       placeholder="Ex: 15.0" min="0" required>
                <span class="input-group-text">km/l</span>
                <div class="invalid-feedback fw-bold">
                    O consumo na estrada é obrigatório.
                </div>
            </div>
        </div>
    </div>

    <!-- Bloco de campos para Etanol (Combustão) -->
    <div id="flex-fields" class="flex-fields mt-3" style="display: none;">
        <hr>
        <h6 class="fw-bold text-success mb-2"><span class="material-symbols-outlined fs-5">nest_eco_leaf</span> Dados para Etanol <span class="fw-normal"> (obrigatórios para Flex)</span></h6>
        <div class="row g-3">
            <!-- Consumo Cidade Etanol -->
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="consumo_cidade_etanol_kml" class="form-label mb-0 fw-bold">Consumo Cidade (Etanol) <span class="text-danger flex-required">*</span></label>
                    <button type="button" 
                            class="btn btn-link btn-sm p-0 text-secondary" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Consumo de etanol em ciclo urbano, medido em km/l. Valores comuns: 5 a 12 km/l. Obrigatório para veículos flex, pois o consumo com etanol é geralmente 20-30% maior que com gasolina, impactando diretamente o custo de abastecimento para o comprador.">
                        <i class="bi bi-info-circle-fill"></i>
                    </button>
                </div>
                <div class="input-group">
                    <input type="number" step="any" inputmode="decimal" name="consumo_cidade_etanol_kml" id="consumo_cidade_etanol_kml" 
                           class="form-control <?= isset($errors['consumo_cidade_etanol_kml']) ? 'is-invalid' : '' ?>" 
                           value="<?= htmlspecialchars($old['consumo_cidade_etanol_kml'] ?? $complemento['consumo_cidade_etanol_kml'] ?? '') ?>" 
                           placeholder="Ex: 8.5" min="0" required>
                    <span class="input-group-text">km/l</span>
                    <div class="invalid-feedback fw-bold">
                        O consumo na cidade para etanol é obrigatório.
                    </div>
                </div>
            </div>

            <!-- Consumo Estrada Etanol -->
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="consumo_estrada_etanol_kml" class="form-label mb-0 fw-bold">Consumo Estrada (Etanol) <span class="text-danger flex-required">*</span></label>
                    <button type="button" 
                            class="btn btn-link btn-sm p-0 text-secondary" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Consumo de etanol em ciclo rodoviário, medido em km/l. Valores comuns: 7 a 14 km/l. Obrigatório para veículos flex, pois o consumo com etanol em estrada é geralmente 20-30% maior que com gasolina, impactando o custo em viagens longas.">
                        <i class="bi bi-info-circle-fill"></i>
                    </button>
                </div>
                <div class="input-group">
                    <input type="number" step="any" inputmode="decimal" name="consumo_estrada_etanol_kml" id="consumo_estrada_etanol_kml" 
                           class="form-control <?= isset($errors['consumo_estrada_etanol_kml']) ? 'is-invalid' : '' ?>" 
                           value="<?= htmlspecialchars($old['consumo_estrada_etanol_kml'] ?? $complemento['consumo_estrada_etanol_kml'] ?? '') ?>" 
                           placeholder="Ex: 10.2" min="0" required>
                    <span class="input-group-text">km/l</span>
                    <div class="invalid-feedback fw-bold">
                        O consumo na estrada para etanol é obrigatório.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ETAPA: CHASSI (Direção, Tração, Suspensão, Freios e Rodas)    -->
<!-- ============================================================ -->
<div class="wizard-step" data-step="chassi" data-label="Chassi">

    <!-- ===== SEÇÃO 1: DIREÇÃO ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">search_hands_free</span> Direção</h5>
    <div class="d-flex flex-wrap gap-3">
        <!-- Tipo de Direção -->
        <div class="d-flex flex-column gap-1">
            <label for="tipo_direcao" class="form-label mb-0 fw-bold text-nowrap" style="width: 145px;">
                Tipo de Direção
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de sistema de direção do veículo: Mecânica, Hidráulica, Elétrica ou Eletro-Hidráulica.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 145px;">
                <select name="tipo_direcao" id="tipo_direcao" class="form-select <?= isset($errors['tipo_direcao']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (tipos_direcao_list() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= selected($old['tipo_direcao'] ?? $veiculo['tipo_direcao'] ?? '', $value) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">
                    O tipo de direção é obrigatório.
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- ===== SEÇÃO 2: TRAÇÃO E TRANSMISSÃO ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">auto_transmission</span> Tração e Transmissão</h5>
    <div class="d-flex flex-wrap gap-3">
        <!-- Tipo de Tração -->
        <div class="d-flex flex-column gap-1">
            <label for="tracao_tipo" class="form-label mb-0 fw-bold text-nowrap" style="width: 145px;">
                Tipo de Tração <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de tração do veículo: Dianteira, Traseira, Integral ou 4x4.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 145px;">
                <select name="tracao_tipo" id="tracao_tipo" class="form-select <?= isset($errors['tracao_tipo']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (tracao_list() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', $value) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">
                    O tipo de tração é obrigatório.
                </div>
            </div>
        </div>

        <!-- Tipo de Transmissão -->
        <div class="d-flex flex-column gap-1">
            <label for="transmissao_tipo" class="form-label mb-0 fw-bold text-nowrap" style="width: 265px;">
                Tipo de Transmissão <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de transmissão do veículo: Manual, Automática, CVT, Automatizada ou Dupla Embreagem.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 265px;">
                <select name="transmissao_tipo" id="transmissao_tipo" class="form-select <?= isset($errors['transmissao_tipo']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (transmissoes_list()['combustao'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= selected($old['transmissao_tipo'] ?? $complemento['transmissao_tipo'] ?? '', $value) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">
                    O tipo de transmissão é obrigatório.
                </div>
            </div>
        </div>

        <!-- Número de Marchas (condicional) -->
        <div id="container-marchas" class="d-flex flex-column gap-1 marchas-hidden">
            <label for="numero_marchas" class="form-label mb-0 fw-bold text-nowrap" style="width: 185px;">
                Número de Marchas <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Quantidade de marchas da transmissão. Para transmissões CVT, este campo não se aplica.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 185px;">
                <select name="numero_marchas" id="numero_marchas" class="form-select <?= isset($errors['numero_marchas']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (marchas_list() as $valor): ?>
                        <option value="<?= $valor ?>" <?= selected($old['numero_marchas'] ?? $complemento['numero_marchas'] ?? '', $valor) ?>>
                            <?= $valor ?> marchas
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">
                    O número de marchas é obrigatório.
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- ===== SEÇÃO 3: SUSPENSÃO E FREIO ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">emoji_transportation</span> Suspensão e Freios</h5>
    <div class="d-flex flex-wrap gap-3">
        <!-- Suspensão Dianteira -->
        <div class="d-flex flex-column gap-1">
            <label for="suspensao_dianteira" class="form-label mb-0 fw-bold text-nowrap" style="width: 255px;">
                Suspensão Dianteira
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de suspensão utilizada no eixo dianteiro. Define o comportamento dinâmico, o conforto e a estabilidade do veículo. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 255px;">
                <?php
                $suspensaoDianteira = gerarSelectOutro(
                    nome: 'suspensao_dianteira',
                    lista: suspensao_dianteira_list(),
                    valorSalvo: $old['suspensao_dianteira'] ?? $veiculo['suspensao_dianteira'] ?? '',
                    classes: isset($errors['suspensao_dianteira']) ? 'is-invalid' : ''
                );
                ?>

                <?= $suspensaoDianteira['select_html'] ?>

                <input type="text" name="suspensao_dianteira_outro" id="suspensao_dianteira_outro" 
                       class="form-control mt-2 <?= isset($errors['suspensao_dianteira']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($suspensaoDianteira['valor_outro']) ?>" 
                       placeholder="Digite a suspensão personalizada" 
                       style="display: <?= $suspensaoDianteira['is_outro'] ? 'block' : 'none' ?>;">

                <div class="invalid-feedback fw-bold">
                    A suspensão dianteira personalizada é obrigatória.
                </div>
            </div>
        </div>

        <!-- Suspensão Traseira -->
        <div class="d-flex flex-column gap-1">
            <label for="suspensao_traseira" class="form-label mb-0 fw-bold text-nowrap" style="width: 375px;">
                Suspensão Traseira
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de suspensão utilizada no eixo traseiro. Influencia o conforto, a capacidade de carga e a estabilidade do veículo. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 375px;">
                <?php
                $suspensaoTraseira = gerarSelectOutro(
                    nome: 'suspensao_traseira',
                    lista: suspensao_traseira_list(),
                    valorSalvo: $old['suspensao_traseira'] ?? $veiculo['suspensao_traseira'] ?? '',
                    classes: isset($errors['suspensao_traseira']) ? 'is-invalid' : ''
                );
                ?>

                <?= $suspensaoTraseira['select_html'] ?>

                <input type="text" name="suspensao_traseira_outro" id="suspensao_traseira_outro" 
                       class="form-control mt-2 <?= isset($errors['suspensao_traseira']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($suspensaoTraseira['valor_outro']) ?>" 
                       placeholder="Digite a suspensão personalizada" 
                       style="display: <?= $suspensaoTraseira['is_outro'] ? 'block' : 'none' ?>;">

                <div class="invalid-feedback fw-bold">
                    A suspensão traseira personalizada é obrigatória.
                </div>
            </div>
        </div>

        <!-- Freio Dianteiro -->
        <div class="d-flex flex-column gap-1">
            <label for="freio_dianteiro" class="form-label mb-0 fw-bold text-nowrap" style="width: 250px;">
                Freio Dianteiro
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de freio nas rodas dianteiras: Disco (melhor dissipação de calor e desempenho em frenagens) ou Tambor (mais simples e econômico). Veículos modernos geralmente utilizam freios a disco nas quatro rodas ou disco na dianteira e tambor na traseira.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 250px;">
                <select name="freio_dianteiro" id="freio_dianteiro" class="form-select <?= isset($errors['freio_dianteiro']) ? 'is-invalid' : '' ?>">
                    <option value="">Selecione</option>
                    <?php foreach (freio_dianteiro_list() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= selected($old['freio_dianteiro'] ?? $veiculo['freio_dianteiro'] ?? '', $value) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Freio Traseiro -->
        <div class="d-flex flex-column gap-1">
            <label for="freio_traseiro" class="form-label mb-0 fw-bold text-nowrap" style="width: 250px;">
                Freio Traseiro
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de freio nas rodas traseiras: Disco (melhor dissipação de calor e desempenho em frenagens) ou Tambor (mais simples e econômico). Muitos veículos utilizam disco na dianteira e tambor na traseira para equilibrar custo e desempenho.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 250px;">
                <select name="freio_traseiro" id="freio_traseiro" class="form-select <?= isset($errors['freio_traseiro']) ? 'is-invalid' : '' ?>">
                    <option value="">Selecione</option>
                    <?php foreach (freio_traseiro_list() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= selected($old['freio_traseiro'] ?? $veiculo['freio_traseiro'] ?? '', $value) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

    </div>

    <hr class="my-4">

    <!-- ===== SEÇÃO 4: RODAS ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">tire_repair</span> Rodas</h5>
    <div class="d-flex flex-wrap gap-3">
        <!-- Aro do Pneu -->
        <div class="d-flex flex-column gap-1">
            <label for="pneu_aro" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Aro do Pneu <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Diâmetro interno do pneu em polegadas (ex: 15, 16, 17). É uma das principais informações para compradores, pois influencia a estética, o conforto de rodagem, a disponibilidade de pneus e o custo de substituição. Aros comuns variam de 12 a 22 polegadas. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 150px;">
                <?php
                $pneuAro = gerarSelectOutro(
                    nome: 'pneu_aro',
                    lista: aros_pneu_list(),
                    valorSalvo: $old['pneu_aro'] ?? $veiculo['pneu_aro'] ?? '',
                    classes: isset($errors['pneu_aro']) ? 'is-invalid' : '',
                    attrs: 'required'
                );
                ?>

                <?= $pneuAro['select_html'] ?>

                <input type="text" 
                       inputmode="numeric" 
                       pattern="\d*" 
                       data-tipo="inteiro" 
                       name="pneu_aro_outro" 
                       id="pneu_aro_outro" 
                       class="form-control mt-2 <?= isset($errors['pneu_aro']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($pneuAro['valor_outro']) ?>" 
                       placeholder="Digite o aro personalizado" 
                       style="display: <?= $pneuAro['is_outro'] ? 'block' : 'none' ?>;">

                <div class="invalid-feedback fw-bold">
                    O aro do pneu é obrigatório.
                </div>

                <div class="invalid-feedback feedback-pontovirgula fw-bold" style="display: none;">
                    Este campo não aceita ponto (.) ou vírgula (,)
                </div>
            </div>
        </div>

        <!-- Tipo de Roda -->
        <div class="d-flex flex-column gap-1">
            <label for="tipo_roda" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Tipo de Roda
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Material da roda: liga leve (alumínio, magnésio) ou aço com calota.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 150px;">
                <select name="tipo_roda" id="tipo_roda" class="form-select <?= isset($errors['tipo_roda']) ? 'is-invalid' : '' ?>">
                    <option value="">Selecione</option>
                    <?php foreach (tipos_roda_list() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= selected($old['tipo_roda'] ?? $veiculo['tipo_roda'] ?? '', $value) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ETAPA: DIMENSÕES, PESO, VOLUME, PORTAS E ASSENTOS            -->
<!-- ============================================================ -->
<div class="wizard-step" data-step="dimensoes" data-label="Dimensões">

    <!-- ===== SEÇÃO 1: DIMENSÕES EXTERNAS ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">border_style</span> Dimensões Externas</h5>
    <div class="d-flex flex-wrap gap-3">
        <!-- Comprimento -->
        <div class="d-flex flex-column gap-1">
            <label for="comprimento_mm_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Comprimento
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Comprimento total do veículo medido em milímetros (mm). Valores comuns: 3800 a 5200 mm. Afeta a manobrabilidade e o espaço interno disponível.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 150px;">
                <input type="hidden" 
                       name="comprimento_mm" 
                       id="comprimento_mm" 
                       value="<?= htmlspecialchars($old['comprimento_mm'] ?? $veiculo['comprimento_mm'] ?? '') ?>">
                <input type="text" 
                       maxlength="6"
                       inputmode="numeric" 
                       id="comprimento_mm_visual" 
                       data-mascara-milhar 
                       class="form-control input-border-correction <?= isset($errors['comprimento_mm']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 4.200">
                <span class="input-group-text">mm</span>
            </div>
        </div>

        <!-- Largura -->
        <div class="d-flex flex-column gap-1">
            <label for="largura_mm_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Largura
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Largura total do veículo medida em milímetros (mm), geralmente sem os retrovisores. Valores comuns: 1700 a 2000 mm. Influencia a estabilidade, o espaço interno e a facilidade em vagas estreitas.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 150px;">
                <input type="hidden" 
                       name="largura_mm" 
                       id="largura_mm" 
                       value="<?= htmlspecialchars($old['largura_mm'] ?? $veiculo['largura_mm'] ?? '') ?>">
                <input type="text" 
                       maxlength="6"
                       inputmode="numeric" 
                       id="largura_mm_visual" 
                       data-mascara-milhar 
                       class="form-control input-border-correction <?= isset($errors['largura_mm']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 1.800">
                <span class="input-group-text">mm</span>
            </div>
        </div>

        <!-- Altura -->
        <div class="d-flex flex-column gap-1">
            <label for="altura_mm_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 155px;">
                Altura do Veículo
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Altura total do veículo medida em milímetros (mm). Valores comuns: 1400 a 1900 mm. Influencia o centro de gravidade, a estabilidade e a aerodinâmica do veículo.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 155px;">
                <input type="hidden" 
                       name="altura_mm" 
                       id="altura_mm" 
                       value="<?= htmlspecialchars($old['altura_mm'] ?? $veiculo['altura_mm'] ?? '') ?>">
                <input type="text" 
                       maxlength="6"
                       inputmode="numeric" 
                       id="altura_mm_visual" 
                       data-mascara-milhar 
                       class="form-control input-border-correction <?= isset($errors['altura_mm']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 1.500">
                <span class="input-group-text">mm</span>
            </div>
        </div>

        <!-- Altura Solo -->
        <div class="d-flex flex-column gap-1">
            <label for="altura_solo_mm_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Altura do Solo
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Distância do ponto mais baixo do veículo (geralmente o cárter) ao solo, medida em milímetros. Valores comuns: 120 a 300 mm. Veículos com maior altura do solo têm melhor capacidade para enfrentar ruas irregulares, rampas e off-road.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 150px;">
                <input type="hidden" 
                       name="altura_solo_mm" 
                       id="altura_solo_mm" 
                       value="<?= htmlspecialchars($old['altura_solo_mm'] ?? $veiculo['altura_solo_mm'] ?? '') ?>">
                <input type="text" 
                       maxlength="4"
                       inputmode="numeric" 
                       id="altura_solo_mm_visual" 
                       data-mascara-milhar 
                       class="form-control input-border-correction <?= isset($errors['altura_solo_mm']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 180">
                <span class="input-group-text">mm</span>
            </div>
        </div>

        <!-- Distância entre eixos -->
        <div class="d-flex flex-column gap-1">
            <label for="distancia_entre_eixos_mm_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 180px;">
                Distância entre eixos
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Distância entre os eixos dianteiro e traseiro do veículo, medida em milímetros (mm). Valores comuns: 2400 a 3000 mm. Quanto maior a distância entre eixos, maior tende a ser o espaço interno e a estabilidade em linha reta, influenciando também o raio de giro.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 180px;">
                <input type="hidden" 
                       name="distancia_entre_eixos_mm" 
                       id="distancia_entre_eixos_mm" 
                       value="<?= htmlspecialchars($old['distancia_entre_eixos_mm'] ?? $veiculo['distancia_entre_eixos_mm'] ?? '') ?>">
                <input type="text" 
                       maxlength="6"
                       inputmode="numeric" 
                       id="distancia_entre_eixos_mm_visual" 
                       data-mascara-milhar 
                       class="form-control input-border-correction <?= isset($errors['distancia_entre_eixos_mm']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 2.600">
                <span class="input-group-text">mm</span>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- ===== SEÇÃO 2: PESO E VOLUMES ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">square_foot</span> Peso e Volumes</h5>
    <div class="d-flex flex-wrap gap-3">
        <!-- Peso -->
        <div class="d-flex flex-column gap-1">
            <label for="peso_ordem_marcha_kg" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Peso do Veículo
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Peso do veículo com todos os fluidos (óleo, água, combustível), ferramentas e acessórios de série, sem carga e sem ocupantes. Medido em quilogramas (kg). Valores comuns: 1000 a 2500 kg. Afeta o consumo de combustível e o desempenho.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 150px;">
                <input type="number" step="any" inputmode="decimal" name="peso_ordem_marcha_kg" id="peso_ordem_marcha_kg" 
                       class="form-control input-border-correction <?= isset($errors['peso_ordem_marcha_kg']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['peso_ordem_marcha_kg'] ?? $veiculo['peso_ordem_marcha_kg'] ?? '') ?>" 
                       min="0" placeholder="Ex: 1200">
                <span class="input-group-text">kg</span>
            </div>
        </div>

        <!-- Volume do porta-malas -->
        <div class="d-flex flex-column gap-1">
            <label for="volume_porta_malas_l_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 210px;">
                Volume do porta-malas <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Capacidade de carga do porta-malas, medida em litros (L). Valores comuns: 200 a 600 L para hatches e sedans; 400 a 800 L para SUVs e peruas. Afeta a praticidade para viagens e transporte de bagagens.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 210px;">
                <input type="hidden" 
                       name="volume_porta_malas_l" 
                       id="volume_porta_malas_l" 
                       value="<?= htmlspecialchars($old['volume_porta_malas_l'] ?? $veiculo['volume_porta_malas_l'] ?? '') ?>">
                <input type="text" 
                       maxlength="4"
                       inputmode="numeric" 
                       id="volume_porta_malas_l_visual" 
                       data-mascara-milhar 
                       class="form-control input-border-correction <?= isset($errors['volume_porta_malas_l']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 450"
                       required>
                <span class="input-group-text">L</span>
                <div class="invalid-feedback fw-bold">
                    O volume do porta-malas é obrigatório.
                </div>
            </div>
        </div>

        <!-- Capacidade Tanque -->
        <div class="d-flex flex-column gap-1">
            <label for="capacidade_tanque_l" class="form-label mb-0 fw-bold text-nowrap" style="width: 175px;">
                Volume do Tanque <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Capacidade total do tanque de combustível, medida em litros (L). Valores comuns: 30 a 80 L. A capacidade do tanque influencia diretamente a autonomia do veículo e a frequência de abastecimento.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 175px;">
                <input type="text" inputmode="numeric" pattern="\d*" data-tipo="inteiro" name="capacidade_tanque_l" id="capacidade_tanque_l" 
                       class="form-control <?= isset($errors['capacidade_tanque_l']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['capacidade_tanque_l'] ?? $complemento['capacidade_tanque_l'] ?? '') ?>" 
                       placeholder="Ex: 50" required>
                <span class="input-group-text">L</span>
                <div class="invalid-feedback fw-bold">
                    O volume do tanque é obrigatório.
                </div>
                <div class="invalid-feedback feedback-pontovirgula fw-bold" style="display: none;">
                    Este campo não aceita ponto (.) ou vírgula (,)
                </div>
            </div>
        </div>

        <!-- Volume da caçamba (condicional) -->
        <div id="cacamba-container" class="d-flex flex-column gap-1 cacamba-hidden">
            <label for="volume_cacamba_l_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 175px;">
                Volume da caçamba
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Capacidade de carga da caçamba, medida em litros (L). Valores comuns: 300 a 1500 L, dependendo do tamanho do veículo. Essencial para quem utiliza o veículo para transporte de cargas, mudanças ou atividades profissionais.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 175px;">
                <input type="hidden" 
                       name="volume_cacamba_l" 
                       id="volume_cacamba_l" 
                       value="<?= htmlspecialchars($old['volume_cacamba_l'] ?? $veiculo['volume_cacamba_l'] ?? '') ?>">
                <input type="text" 
                       maxlength="4"
                       inputmode="numeric" 
                       id="volume_cacamba_l_visual" 
                       data-mascara-milhar 
                       class="form-control input-border-correction <?= isset($errors['volume_cacamba_l']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 800">
                <span class="input-group-text">L</span>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- ===== SEÇÃO 3: PORTAS E ASSENTOS ===== -->
    <h5 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">airline_seat_recline_extra</span> Portas e Assentos</h5>
    <div class="d-flex flex-wrap gap-3">
        <!-- Número de Portas -->
        <div class="d-flex flex-column gap-1">
            <label for="numero_portas" class="form-label mb-0 fw-bold text-nowrap" style="width: 170px;">
                Número de Portas <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Quantidade de portas do veículo (ex: 2, 3, 4). Veículos com 2 ou 3 portas geralmente são mais esportivos ou compactos, enquanto 4 portas oferecem maior acessibilidade para passageiros. É um fator importante para famílias e uso diário.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 170px;">
                <select name="numero_portas" id="numero_portas" class="form-select <?= isset($errors['numero_portas']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (portas_list() as $valor => $label): ?>
                        <option value="<?= $valor ?>" <?= selected($old['numero_portas'] ?? $veiculo['numero_portas'] ?? '', $valor) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">
                    O número de portas é obrigatório.
                </div>
            </div>
        </div>

        <!-- Número de Assentos -->
        <div class="d-flex flex-column gap-1">
            <label for="numero_assentos" class="form-label mb-0 fw-bold text-nowrap" style="width: 205px;">
                Quantidade de Lugares <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Quantidade de assentos do veículo. Valores comuns: 2 a 15 assentos. Veículos com 2 ou 3 assentos são geralmente esportivos ou utilitários; 4 a 5 assentos é o padrão para veículos de passeio; 6 a 8 assentos são comuns em SUVs grandes, minivans e picapes; 9 a 15 assentos são típicos de vans e micro-ônibus.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 205px;">
                <select name="numero_assentos" id="numero_assentos" class="form-select <?= isset($errors['numero_assentos']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (assentos_list() as $valor): ?>
                        <option value="<?= $valor ?>" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', $valor) ?>>
                            <?= $valor ?> assentos
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">
                    O número de assentos é obrigatório.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Etapa 10: Opcionais (comum) -->
<div class="wizard-step" data-step="opcionais" data-label="Opcionais" style="display: none;">
    <div class="card shadow-sm my-4">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-bold"><span class="material-symbols-outlined text-primary">traffic_jam</span> Opcionais</h5>
        </div>
        <div class="card-body">
            <?php if (empty($todos_opcionais)): ?>
                <p class="text-muted">Nenhum opcional cadastrado.</p>
            <?php else: ?>
                <div class="accordion" id="accordionOpcionais">
                    <?php $i = 0; ?>
                    <?php foreach ($todos_opcionais as $categoria => $opcionais): ?>
                        <?php $id = 'collapse_' . $i; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading_<?= $i ?>">
                                <button class="accordion-button collapsed fw-bold" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#<?= $id ?>" 
                                        aria-expanded="false" 
                                        aria-controls="<?= $id ?>">
                                    <?= htmlspecialchars($categoria) ?>
                                </button>
                            </h2>
                            <div id="<?= $id ?>" 
                                 class="accordion-collapse collapse" 
                                 aria-labelledby="heading_<?= $i ?>" 
                                 data-bs-parent="#accordionOpcionais">
                                <div class="accordion-body">
                                    <div class="row g-2">
                                        <?php foreach ($opcionais as $opcional): ?>
                                            <div class="col-md-4 col-lg-3">
                                                <div class="form-check">
                                                    <input type="checkbox" 
                                                           name="opcionaisIds[]" 
                                                           value="<?= $opcional['id'] ?>" 
                                                           class="form-check-input" 
                                                           id="opcional_<?= $opcional['id'] ?>"
                                                           <?= (in_array($opcional['id'], $opcionais_selecionados ?? [])) ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold" for="opcional_<?= $opcional['id'] ?>">
                                                        <?= htmlspecialchars($opcional['nome']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Etapa 11: GNV (condicional) -->
<div class="wizard-step" data-step="gnv" data-label="GNV" style="display: none;">
    <!-- ========================================================== -->
    <!-- GNV - Possui GNV?                                          -->
    <!-- ========================================================== -->
    <h4 class="mb-3 fw-bold"><span class="material-symbols-outlined text-primary">propane</span> GNV</h4>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="gnv_instalado" class="form-label mb-0 fw-bold">Veículo possui GNV? <span class="text-danger">*</span></label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Indique se o veículo possui kit GNV (Gás Natural Veicular) instalado. Se sim, serão solicitados dados adicionais sobre o sistema.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <select name="gnv_instalado" id="gnv_instalado" class="form-select <?= isset($errors['gnv_instalado']) ? 'is-invalid' : '' ?>" required>
                <option value="">Selecione</option>
                <option value="1" <?= selected($old['gnv_instalado'] ?? $veiculo['gnv_instalado'] ?? '', 1) ?>>Sim</option>
                <option value="0" <?= selected($old['gnv_instalado'] ?? $veiculo['gnv_instalado'] ?? '', 0) ?>>Não</option>
            </select>
            <div class="invalid-feedback fw-bold">
                Indique se o veículo possui GNV.
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- Bloco de campos GNV (condicional)                          -->
    <!-- ========================================================== -->
    <div id="gnv-fields" class="mt-3 p-3 border rounded bg-light" style="display: none;">
        <div class="row g-3">
            <!-- Geração do Kit -->
            <div class="col-md-3">
                <label for="geracao_kit" class="form-label fw-bold">Geração do Kit <span class="text-danger">*</span></label>
                <select name="geracao_kit" id="geracao_kit" class="form-select <?= isset($errors['geracao_kit']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (gnv_geracoes_list() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= selected($old['geracao_kit'] ?? $gnv['geracao_kit'] ?? '', $value) ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">A geração do kit é obrigatória.</div>
            </div>

            <!-- Capacidade (m³) -->
            <div class="col-md-3">
                <label for="capacidade_cilindro_m3" class="form-label fw-bold">Capacidade (m³) <span class="text-danger">*</span></label>
                <?php
                    $capacidade = gerarSelectOutro(
                        nome: 'capacidade_cilindro_m3',
                        lista: gnv_capacidades_list(),
                        valorSalvo: $old['capacidade_cilindro_m3'] ?? $gnv['capacidade_cilindro_m3'] ?? '',
                        classes: isset($errors['capacidade_cilindro_m3']) ? 'is-invalid' : '',
                        attrs: 'required'
                    );
                ?>
                <?= $capacidade['select_html'] ?>
                <input type="text" name="capacidade_cilindro_m3_outro" id="capacidade_cilindro_m3_outro" 
                       class="form-control mt-2 <?= isset($errors['capacidade_cilindro_m3']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($capacidade['valor_outro']) ?>" 
                       placeholder="Digite a capacidade em m³" 
                       style="display: <?= $capacidade['is_outro'] ? 'block' : 'none' ?>;">
                <div class="invalid-feedback fw-bold">A capacidade é obrigatória.</div>
            </div>

            <!-- Quantidade de Cilindros -->
            <div class="col-md-3">
                <label for="quantidade_cilindros" class="form-label fw-bold">Quantidade de Cilindros <span class="text-danger">*</span></label>
                <select name="quantidade_cilindros" id="quantidade_cilindros" class="form-select <?= isset($errors['quantidade_cilindros']) ? 'is-invalid' : '' ?>" required>
                    <option value="">Selecione</option>
                    <?php foreach (gnv_quantidades_list() as $valor): ?>
                        <option value="<?= $valor ?>" <?= selected($old['quantidade_cilindros'] ?? $gnv['quantidade_cilindros'] ?? '', $valor) ?>>
                            <?= $valor ?> cilindro<?= $valor > 1 ? 's' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback fw-bold">A quantidade é obrigatória.</div>
            </div>

            <!-- Localização do Cilindro -->
            <div class="col-md-3">
                <label for="localizacao_cilindro" class="form-label fw-bold">Localização <span class="text-danger">*</span></label>
                <?php
                    $localizacao = gerarSelectOutro(
                        nome: 'localizacao_cilindro',
                        lista: gnv_localizacoes_list(),
                        valorSalvo: $old['localizacao_cilindro'] ?? $gnv['localizacao_cilindro'] ?? '',
                        classes: isset($errors['localizacao_cilindro']) ? 'is-invalid' : '',
                        attrs: 'required'
                    );
                ?>
                <?= $localizacao['select_html'] ?>
                <input type="text" name="localizacao_cilindro_outro" id="localizacao_cilindro_outro" 
                       class="form-control mt-2 <?= isset($errors['localizacao_cilindro']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($localizacao['valor_outro']) ?>" 
                       placeholder="Digite a localização personalizada" 
                       style="display: <?= $localizacao['is_outro'] ? 'block' : 'none' ?>;">
                <div class="invalid-feedback fw-bold">A localização é obrigatória.</div>
            </div>
        </div>
    </div>
</div>