<!-- app/View/logista/veiculos/etapas/_basico.php -->

<!-- ============================================================ -->
<!-- ETAPA: BÁSICO                                                -->
<!-- ============================================================ -->
<div class="wizard-step" data-step="basico" data-label="Informações Básicas">
    <div class="row g-3">

        <!-- ========================================================== -->
        <!-- 1. MARCA E MODELO (área de exibição + modal)               -->
        <!-- ========================================================== -->
        <div class="col-12">
            <!-- Área de exibição (badges + botão "Selecionar") -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Marca e Modelo</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <div class="brand-model-display">
                                    <span id="marcaDisplay" class="badge bg-secondary p-2">Nenhuma marca selecionada</span>
                                    <span id="modeloDisplay" class="badge bg-secondary p-2">Nenhum modelo selecionado</span>
                                    <!-- Feedback de erro -->
                                    <div id="marcaModeloFeedback" class="invalid-feedback" style="display: none;">
                                        Selecione uma marca e um modelo.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-primary" id="selecionarMarcaModeloBtn">
                                <i class="bi bi-search me-1"></i> Selecionar
                            </button>
                        </div>
                    </div>
                    <!-- Campos ocultos para armazenar os IDs -->
                    <input type="hidden" name="marca_id" id="marca_id" value="<?= $veiculo['marca_id'] ?? $old['marca_id'] ?? '' ?>">
                    <input type="hidden" name="modelo_id" id="modelo_id" value="<?= $veiculo['modelo_id'] ?? $old['modelo_id'] ?? '' ?>">
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- MODAL MARCA E MODELO (HTML completo)                       -->
            <!-- ========================================================== -->
            <div class="modal fade" id="marcaModeloModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="bi bi-car-front me-2"></i>Selecionar Marca e Modelo</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Etapa 1: Selecionar Marca -->
                            <div id="etapa-marca" class="etapa">
                                <div id="conteudo-marca-lista">
                                    <h6 class="mb-3">1. Selecione a Marca</h6>
                                    <div class="mb-3">
                                        <input type="text" id="buscaMarca" class="form-control" placeholder="Pesquisar marca...">
                                    </div>
                                    <div id="lista-marcas" class="lista-items" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Itens serão carregados via JS -->
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="adicionarMarcaBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Adicionar Nova Marca
                                        </button>
                                        <button type="button" class="btn btn-primary" id="btnProximoMarca" disabled>
                                            Próximo <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="conteudo-marca-form" style="display: none;">
                                    <h6 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Nova Marca</h6>
                                    <form id="formNovaMarca">
                                        <div class="mb-3">
                                            <label for="novaMarcaNome" class="form-label">Nome da Marca <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="novaMarcaNome" placeholder="Ex: Fiat">
                                        </div>
                                        <div class="mb-3">
                                            <label for="novaMarcaLogo" class="form-label">Logo (opcional)</label>
                                            <input type="file" class="form-control" id="novaMarcaLogo" accept="image/*">
                                            <div id="previewMarcaLogo" class="mt-2" style="display: none;">
                                                <img id="previewMarcaImg" src="#" alt="Preview" width="64" height="64" class="rounded border">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-outline-secondary" id="cancelarNovaMarcaBtn">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" id="salvarNovaMarcaBtn">
                                                <span id="spinnerMarca" class="spinner-border spinner-border-sm" role="status" style="display: none;"></span>
                                                <span id="textoMarcaBtn">Cadastrar</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Etapa 2: Selecionar Modelo -->
                            <div id="etapa-modelo" class="etapa" style="display: none;">
                                <div id="conteudo-modelo-lista">
                                    <h6 class="mb-3">2. Selecione o Modelo</h6>
                                    <div class="mb-3">
                                        <input type="text" id="buscaModelo" class="form-control" placeholder="Pesquisar modelo...">
                                    </div>
                                    <div id="lista-modelos" class="lista-items" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Itens carregados via AJAX -->
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="adicionarModeloBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Adicionar Novo Modelo
                                        </button>
                                        <div>
                                            <button type="button" class="btn btn-outline-secondary" id="voltarMarcaBtn">Voltar</button>
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="conteudo-modelo-form" style="display: none;">
                                    <h6 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Novo Modelo</h6>
                                    <form id="formNovoModelo">
                                        <div class="mb-3">
                                            <label for="novoModeloNome" class="form-label">Nome do Modelo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="novoModeloNome" placeholder="Ex: Palio">
                                        </div>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-outline-secondary" id="cancelarNovoModeloBtn">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" id="salvarNovoModeloBtn">
                                                <span id="spinnerModelo" class="spinner-border spinner-border-sm" role="status" style="display: none;"></span>
                                                <span id="textoModeloBtn">Cadastrar</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Etapa 3: Resumo e Edição -->
                            <div id="etapa-resumo" class="etapa" style="display: none;">
                                <h6 class="mb-3">3. Confirme a seleção</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card resumo-card" id="resumo-marca" style="cursor: pointer;">
                                            <div class="card-body text-center position-relative">
                                                <div class="editar-overlay">
                                                    <i class="bi bi-pencil-fill text-primary"></i>
                                                </div>
                                                <div id="resumo-marca-logo" class="mb-2">
                                                    <img src="/assets/images/default-brand.png" alt="Marca" width="64" height="64" class="rounded">
                                                </div>
                                                <h6 id="resumo-marca-nome">Nenhuma</h6>
                                                <small class="text-muted">Clique para editar</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card resumo-card" id="resumo-modelo" style="cursor: pointer;">
                                            <div class="card-body text-center position-relative">
                                                <div class="editar-overlay">
                                                    <i class="bi bi-pencil-fill text-primary"></i>
                                                </div>
                                                <h6 id="resumo-modelo-nome">Nenhum</h6>
                                                <small class="text-muted">Clique para editar</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-success" id="confirmarSelecaoBtn">
                                        <i class="bi bi-check-lg me-1"></i> Confirmar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIM DO MODAL MARCA E MODELO -->
        </div>

        <!-- Versão -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="versao" class="form-label mb-0">Versão do Modelo</label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Versão de acabamento do modelo. Define o nível de equipamentos, motorização e diferenciação estética do veículo. Importante para que o comprador identifique corretamente o veículo anunciado.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <input title="Versão do modelo (Ex: GL, EX, Sport, Turbo)" placeholder="ex: GL, EX, Sport, Turbo" type="text" name="versao" id="versao" class="form-control <?= isset($errors['versao']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($old['versao'] ?? $veiculo['versao'] ?? '') ?>">
        </div>

        <!-- Carroceria -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="carroceria" class="form-label mb-0">Carroceria</label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Tipo de carroceria do veículo (ex: hatch, sedan, SUV). Define a estrutura, o design e a finalidade do modelo. A carroceria influencia diretamente o espaço interno, a dirigibilidade, o consumo e o valor de revenda do veículo. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>

            <?php
                $carroceria = gerarSelectOutro(
                    nome: 'carroceria',
                    lista: carrocerias_list(),
                    valorSalvo: $old['carroceria'] ?? $veiculo['carroceria'] ?? '',
                    classes: isset($errors['carroceria']) ? 'is-invalid' : ''
                );
            ?>

            <!-- Select gerado pela função -->
            <?= $carroceria['select_html'] ?>

            <!-- Campo extra para "Outro" -->
            <input type="text" name="carroceria_outro" id="carroceria_outro" 
                   class="form-control mt-2 <?= isset($errors['carroceria']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($carroceria['valor_outro']) ?>" 
                   placeholder="Digite a carroceria personalizada" 
                   style="display: <?= $carroceria['is_outro'] ? 'block' : 'none' ?>;">

            <div class="invalid-feedback">
                A carroceria personalizada é obrigatória.
            </div>
        </div>

        <!-- Ano Modelo -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="ano_modelo" class="form-label mb-0">Ano do Modelo <span class="text-danger">*</span></label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Ano do modelo do veículo (ex: 2024). Refere-se ao ano de lançamento da versão do veículo, que pode ser igual ou posterior ao ano de fabricação. É um dos principais fatores que influenciam o valor de mercado e a depreciação do veículo.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <input title="Digite o ano com 4 dígitos" placeholder="Ex: 2026" type="text" inputmode="numeric" pattern="\d*" data-tipo="inteiro" maxlength="4" name="ano_modelo" id="ano_modelo" class="form-control <?= isset($errors['ano_modelo']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($old['ano_modelo'] ?? $veiculo['ano_modelo'] ?? '') ?>" required>

            <div class="invalid-feedback">
                O ano do modelo é obrigatório.
            </div>

            <div class="invalid-feedback feedback-pontovirgula" style="display: none;">
                Este campo não aceita ponto (.) ou vírgula (,)
            </div>
        </div>

        <!-- Quilometragem -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="quilometragem_visual" class="form-label mb-0">Quilometragem <span class="text-danger">*</span></label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Quilometragem total percorrida pelo veículo, medida em quilômetros (km). É um dos principais fatores que influenciam o valor de mercado e a depreciação do veículo. Quanto menor a quilometragem, maior tende a ser o valor de revenda.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <div class="input-group has-validation">
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
                       class="form-control <?= isset($errors['quilometragem']) ? 'is-invalid' : '' ?>" 
                       placeholder="Ex: 90.258" 
                       required
                       maxlength="10">
                <span class="input-group-text">km</span>

                <!-- Feedback de erro (visível quando hidden estiver vazio) -->
                <div class="invalid-feedback">
                    A quilometragem é obrigatória.
                </div>
                <div class="invalid-feedback feedback-pontovirgula" style="display: none;">
                    Este campo não aceita ponto (.) ou vírgula (,)
                </div>
            </div>
        </div>


        <!-- Cor -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="corInput" class="form-label mb-0">Cor <span class="text-danger">*</span></label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Cor do veículo. A cor é um dos principais fatores de decisão de compra. Cores metálicas ou perolizadas geralmente são mais valorizadas e podem influenciar o preço de revenda. A opção 'Outro' permite valores personalizados.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            
            <!-- Input + botão para abrir a lista -->
            <div class="input-group">
                <input type="text" id="corInput" class="form-control <?= isset($errors['cor']) ? 'is-invalid' : '' ?>" 
                       value="<?= htmlspecialchars($old['cor'] ?? $veiculo['cor'] ?? '') ?>" 
                       placeholder="Selecione uma cor" readonly>
                                            <!-- Swatch de cor -->
                <span id="corSwatch" class="input-group-text p-1" style="display: none; width: 38px; background: white; border-left: 0;">
                    <span id="corSwatchInner" style="display: block; width: 28px; height: 28px; border-radius: 4px; border: 1px solid #ccc;"></span>
                </span>
                <button class="btn btn-outline-secondary" type="button" id="btnAbrirCores">
                    <i class="bi bi-chevron-down"></i>
                </button>

                <div id="corFeedback" class="invalid-feedback">
                    O nome da cor é obrigatório. 
                </div>

            </div>
            
            <!-- Campo oculto para armazenar a cor selecionada -->
            <input type="hidden" name="cor" id="corSelecionada" value="<?= htmlspecialchars($old['cor'] ?? $veiculo['cor'] ?? '') ?>">

            <!-- Dropdown com a lista de cores -->
            <div id="dropdownCores" class="border rounded shadow-sm mt-1" style="display: none; max-height: 200px; overflow-y: auto; position: relative; z-index: 1000; background: white;">
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
                   style="display: <?= ($valorSalvo === 'outro') ? 'block' : 'none' ?>;">
            <div class="invalid-feedback">
                A cor personalizada é obrigatória.
            </div>
        </div>

        <!-- Placa -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="placa" class="form-label mb-0">Placa</label>
                <button type="button" 
                        class="btn btn-link btn-sm p-0 text-secondary" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Placa do veículo (uso interno). Serve como identificador auxiliar para gestão da frota, busca e controle de documentos. Campo opcional e não exibido publicamente.">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <input title="Placa do veículo (opcional)" 
                   placeholder="Ex: ABC1D23" 
                   type="text" 
                   name="placa" 
                   id="placa" 
                   class="form-control <?= isset($errors['placa']) ? 'is-invalid' : '' ?>" 
                   value="<?= htmlspecialchars($old['placa'] ?? $veiculo['placa'] ?? '') ?>" 
                   maxlength="7" data-tipo="placa">
        </div>
    </div> 
</div> 
