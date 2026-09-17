
<!-- ========================================================== -->
<!-- MODAL MARCA E MODELO (HTML completo)                       -->
<!-- ========================================================== -->
<div class="modal fade" id="marcaModeloModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title text-center w-100 fw-bold">Selecionar Marca e Modelo</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <!-- Etapa 1: Selecionar Marca -->
                <div id="etapa-marca" class="etapa">
                    <div id="conteudo-marca-lista">
                        <div class="mb-5 mt-3 px-4">
                            <input type="text" id="buscaMarca" class="form-control form-control-lg shadow-sm" placeholder="Pesquisar...">
                        </div>
                        <div>
                            <h5 class="fw-bold text-center text-muted mb-3">Escolha uma marca abaixo:</h5>
                        </div>
                        <div id="lista-marcas" class="lista-items" style="max-height: 300px; overflow-y: auto;">
                            <!-- Itens serão carregados via JS -->
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <button type="button" class="btn btn-primary fw-bold" id="adicionarMarcaBtn">
                                <i class="bi bi-plus-lg me-1"></i> Adicionar Marca
                            </button>
                            <button type="button" class="btn btn-success fw-bold" id="btnProximoMarca" disabled>
                                Selecionar <i class="bi bi-check-lg ms-1"></i>
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
                        <div class="mb-5 mt-3 px-4">
                            <input type="text" id="buscaModelo" class="form-control form-control-lg shadow-sm" placeholder="Pesquisar...">
                        </div>
                        <div>
                            <h5 class="fw-bold text-center text-muted mb-3">Escolha um modelo abaixo:</h5>
                        </div>
                        <div id="lista-modelos" class="lista-items" style="max-height: 300px; overflow-y: auto;">
                            <!-- Itens carregados via AJAX -->
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <button type="button" class="btn btn-primary fw-bold" id="adicionarModeloBtn">
                                <i class="bi bi-plus-lg me-1"></i> Adicionar Modelo
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
                    <div class="row g-3" style="padding-left: 8rem; padding-right: 8rem;">
                        <div class="col-12">
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
                        <div class="col-12">
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