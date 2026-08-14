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
                                            <input type="text" class="form-control" id="novaMarcaNome" placeholder="Ex: Fiat" required>
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
                                            <input type="text" class="form-control" id="novoModeloNome" placeholder="Ex: Palio" required>
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

        <!-- ========================================================== -->
        <!-- 4. ANO MODELO                                              -->
        <!-- ========================================================== -->
        <!-- (placeholder - será preenchido depois) -->

        <!-- ========================================================== -->
        <!-- 5. QUILOMETRAGEM                                           -->
        <!-- ========================================================== -->
        <!-- (placeholder - será preenchido depois) -->

        <!-- ========================================================== -->
        <!-- 6. COR (dropdown customizado com "Outro")                  -->
        <!-- ========================================================== -->
        <!-- (placeholder - será preenchido depois) -->

        <!-- ========================================================== -->
        <!-- 7. PLACA                                                   -->
        <!-- ========================================================== -->
        <!-- (placeholder - será preenchido depois) -->

    </div> 
</div> 