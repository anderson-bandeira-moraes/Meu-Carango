<!-- app/View/logista/veiculos/etapas/_basico.php -->

<!-- ============================================================ -->
<!-- ETAPA: BÁSICO                                                -->
<!-- ============================================================ -->
<div class="wizard-step is-active" data-step="basico" data-label="Informações Básicas">
    <div class="d-flex flex-wrap gap-3">

        <!-- ========================================================== -->
        <!-- 1. MARCA E MODELO (área de exibição + modal)               -->
        <!-- ========================================================== -->
        <div class="col-12">
            <!-- Área de exibição (badges + botão "Selecionar") -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 fw-bold"><span class="material-symbols-outlined text-primary">car_tag</span> Marca e Modelo</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <div class="brand-model-display">
                                    <span id="marcaDisplay" class="badge bg-secondary p-2" style="margin-bottom: 0.5rem;">Nenhuma marca selecionada</span>
                                    <span id="modeloDisplay" class="badge bg-secondary p-2">Nenhum modelo selecionado</span>
                                    <!-- Feedback de erro -->
                                    <div id="marcaModeloFeedback" class="invalid-feedback fw-bold" style="display: none;">
                                        Selecione uma marca e um modelo.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-primary fw-bold" id="selecionarMarcaModeloBtn">
                                Selecionar<i class="bi bi-hand-index-thumb ms-2"></i> 
                            </button>
                        </div>
                    </div>
                    <!-- Campos ocultos para armazenar os IDs -->
                    <input type="hidden" name="marca_id" id="marca_id" value="<?= $veiculo['marca_id'] ?? $old['marca_id'] ?? '' ?>">
                    <input type="hidden" name="modelo_id" id="modelo_id" value="<?= $veiculo['modelo_id'] ?? $old['modelo_id'] ?? '' ?>">
                </div>
            </div>
        </div>

        <!-- Versão -->
        <div class="d-flex flex-column gap-1">
            <label for="versao" class="form-label mb-0 fw-bold text-nowrap" style="width: 160px;">
                Versão do Modelo
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Versão de acabamento do modelo. Define o nível de equipamentos, motorização e diferenciação estética do veículo. Importante para que o comprador identifique corretamente o veículo anunciado.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 170px;">
                <input type="text" 
                       title="Versão do modelo (Ex: GL, EX, Sport, Turbo)" 
                       placeholder="ex: Sport, Turbo" 
                       name="versao" 
                       id="versao" 
                       class="form-control <?= isset($errors['versao']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['versao'] ?? $veiculo['versao'] ?? '') ?>">
                <div class="invalid-feedback fw-bold">
                    A versão do modelo é inválida.
                </div>
            </div>
        </div>

        <!-- Carroceria -->
        <div class="d-flex flex-column gap-1">
            <label for="carroceria" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Carroceria <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de carroceria do veículo (ex: hatch, sedan, SUV). Define a estrutura, o design e a finalidade do modelo. A carroceria influencia diretamente o espaço interno, a dirigibilidade, o consumo e o valor de revenda do veículo. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 170px;">
                <?php
                    $carroceria = gerarSelectOutro(
                        nome: 'carroceria',
                        lista: carrocerias_list(),
                        valorSalvo: $old['carroceria'] ?? $veiculo['carroceria'] ?? '',
                        classes: isset($errors['carroceria']) ? 'is-invalid' : '',
                        attrs: 'required'
                    );
                ?>

                <?= $carroceria['select_html'] ?>

                <input type="text" name="carroceria_outro" id="carroceria_outro" 
                       class="form-control mt-2 <?= isset($errors['carroceria']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($carroceria['valor_outro']) ?>" 
                       placeholder="Digite a carroceria personalizada" 
                       style="display: <?= $carroceria['is_outro'] ? 'block' : 'none' ?>;">

                <div class="invalid-feedback fw-bold">
                    A carroceria é obrigatória.
                </div>
            </div>
        </div>

        <!-- Ano Modelo -->
        <div class="d-flex flex-column gap-1">
            <label for="ano_modelo" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Ano do Modelo <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Ano do modelo do veículo (ex: 2024). Refere-se ao ano de lançamento da versão do veículo, que pode ser igual ou posterior ao ano de fabricação. É um dos principais fatores que influenciam o valor de mercado e a depreciação do veículo.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 170px;">
                <input type="text" 
                       title="Digite o ano com 4 dígitos" 
                       placeholder="Ex: 2026" 
                       inputmode="numeric" 
                       pattern="\d*" 
                       data-tipo="inteiro" 
                       maxlength="4" 
                       name="ano_modelo" 
                       id="ano_modelo" 
                       class="form-control <?= isset($errors['ano_modelo']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['ano_modelo'] ?? $veiculo['ano_modelo'] ?? '') ?>" 
                       required>
                <div class="invalid-feedback fw-bold">
                    O ano do modelo é obrigatório.
                </div>
                <div class="invalid-feedback feedback-pontovirgula fw-bold" style="display: none;">
                    Este campo não permite ponto (.) ou vírgula (,)
                </div>
            </div>
        </div>

        <!-- Quilometragem -->
        <div class="d-flex flex-column gap-1">
            <label for="quilometragem_visual" class="form-label mb-0 fw-bold text-nowrap" style="width: 160px;">
                Quilometragem <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Quilometragem total percorrida pelo veículo, medida em quilômetros (km). É um dos principais fatores que influenciam o valor de mercado e a depreciação do veículo. Quanto menor a quilometragem, maior tende a ser o valor de revenda.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 170px;">
                <!-- Campo HIDDEN (valor puro) -->
                <input type="hidden" 
                       name="quilometragem" 
                       id="quilometragem" 
                       value="<?= htmlspecialchars($old['quilometragem'] ?? $veiculo['quilometragem'] ?? '') ?>">

                <!-- Campo VISUAL (formatado) -->
                <input type="text" 
                       inputmode="numeric" 
                       id="quilometragem_visual" 
                       data-mascara-milhar
                       class="form-control input-border-correction <?= isset($errors['quilometragem']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 90.258" 
                       required
                       maxlength="10">
                <span class="input-group-text">km</span>

                <!-- Feedback de erro (visível quando hidden estiver vazio) -->
                <div class="invalid-feedback fw-bold">
                    A quilometragem é obrigatória.
                </div>
                <div class="invalid-feedback feedback-pontovirgula fw-bold" style="display: none;">
                    Este campo não permite ponto (.) ou vírgula (,)
                </div>
            </div>
        </div>

        <!-- Cor -->
        <div class="d-flex flex-column gap-1">
            <label for="corInput" class="form-label mb-0 fw-bold text-nowrap" style="width: 150px;">
                Cor <span class="text-danger">*</span>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Cor do veículo. A cor é um dos principais fatores de decisão de compra. Cores metálicas ou perolizadas geralmente são mais valorizadas e podem influenciar o preço de revenda. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="input-group has-validation" style="width: 170px;">
                <input type="text" id="corInput" class="form-control <?= isset($errors['cor']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['cor'] ?? $veiculo['cor'] ?? '') ?>" 
                       placeholder="Selecione" readonly>
                <!-- Swatch de cor -->
                <span id="corSwatch" class="input-group-text p-1" style="display: none; width: 38px; background: white; border-left: 0;">
                    <span id="corSwatchInner" style="display: block; width: 28px; height: 28px; border-radius: 4px; border: 1px solid #ccc;"></span>
                </span>
                <button class="btn btn-outline-secondary border" style="border-top-right-radius: 0.375rem !important; border-bottom-right-radius: 0.375rem !important;" type="button" id="btnAbrirCores">
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div id="corFeedback" class="invalid-feedback fw-bold">
                    O nome da cor é obrigatório.
                </div>
            </div>

            <!-- Campo oculto para armazenar a cor selecionada -->
            <input type="hidden" name="cor" id="corSelecionada" value="<?= htmlspecialchars($old['cor'] ?? $veiculo['cor'] ?? '') ?>">

            <!-- Dropdown com a lista de cores -->
            <div id="dropdownCores" class="border rounded shadow-sm mt-1" style="display: none; max-height: 200px; overflow-y: auto; position: relative; z-index: 1000; background: white; width: 170px;">
                <div class="p-1">
                    <?php
                    $cores = cores_list();
                    $valorSalvo = $old['cor'] ?? $veiculo['cor'] ?? '';
                    ?>
                    <?php foreach ($cores as $nome => $hex): ?>
                        <div class="cor-item d-flex justify-content-between align-items-center p-2 rounded" 
                             style="cursor: pointer; <?= ($valorSalvo === $nome) ? 'background-color: #e9ecef;' : '' ?>"
                             data-cor="<?= htmlspecialchars($nome) ?>" 
                             data-hex="<?= $hex ?>">
                            <span><?= htmlspecialchars($nome) ?></span>
                            <span style="display: inline-block; width: 30px; height: 30px; background-color: <?= $hex ?>; border-radius: 4px; border: 1px solid #ccc; flex-shrink: 0;"></span>
                        </div>
                    <?php endforeach; ?>
                    <!-- Opção "Outro" -->
                    <div class="cor-item d-flex justify-content-between align-items-center p-2 rounded" 
                         style="cursor: pointer; <?= ($valorSalvo === 'outro') ? 'background-color: #e9ecef;' : '' ?>"
                         data-cor="outro" data-hex="#cccccc">
                        <span>Outro (digitar)</span>
                        <span style="display: inline-block; width: 30px; height: 30px; background-color: #cccccc; border-radius: 4px; border: 1px solid #999; flex-shrink: 0;"></span>
                    </div>
                </div>
            </div>

            <!-- Campo extra para "Outro" -->
            <input type="text" name="cor_outro" id="cor_outro" class="form-control mt-2 <?= isset($errors['cor']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($old['cor_outro'] ?? '') ?>" 
                   placeholder="Digite a cor personalizada" 
                   style="display: <?= ($valorSalvo === 'outro') ? 'block' : 'none' ?>; width: 170px;">
            <div class="invalid-feedback fw-bold">
                A cor personalizada é obrigatória.
            </div>
        </div>

        <!-- Placa -->
        <div class="d-flex flex-column gap-1">
            <label for="placa" class="form-label mb-0 fw-bold text-nowrap" style="width: 65px;">
                Placa
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Placa do veículo (uso interno). Serve como identificador auxiliar para gestão da frota, busca e controle de documentos. Campo opcional e não exibido publicamente.">
                    <i class="bi bi-info-circle-fill ms-2"></i>
                </button>
            </label>
            <div class="has-validation" style="width: 170px;">
                <input type="text" 
                       title="Placa do veículo (opcional)" 
                       placeholder="Ex: ABC1D23" 
                       name="placa" 
                       id="placa" 
                       class="form-control <?= isset($errors['placa']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['placa'] ?? $veiculo['placa'] ?? '') ?>" 
                       maxlength="7" 
                       data-tipo="placa">
                <div class="invalid-feedback fw-bold">
                    A placa informada é inválida.
                </div>
            </div>
        </div>
    </div> 
</div> 
