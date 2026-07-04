<!-- views/logista/veiculos/form.php -->
<?php
/**
 * @var bool $isEdit
 * @var array|null $veiculo
 * @var string|null $tipo (combustao, eletrico, hibrido)
 * @var array|null $complemento (dados específicos do tipo)
 * @var array|null $gnv (dados do GNV)
 * @var array $opcionais_selecionados (IDs dos opcionais já marcados)
 * @var array $todos_opcionais (opcionais agrupados por categoria)
 * @var array $marcas
 * @var array $modelos
 * @var array $old (dados submetidos anteriormente em caso de erro)
 * @var string|null $error (mensagem de erro flash)
 */

// Define o título da página
$titulo = $isEdit ? 'Editar Veículo' : 'Cadastrar Veículo';

// Determina o tipo atual para edição
$tipoAtual = $tipo ?? ($old['tipo_veiculo'] ?? null);

// Para criação, define um tipo padrão (será sobrescrito pelo modal)
$tipoSelecionado = $isEdit ? $tipoAtual : null;
?>

<div class="container-fluid px-4">
    <!-- Título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= htmlspecialchars($titulo) ?></h1>
        <a href="/logista/veiculos" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Flash error -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <!-- ========== MODAL DE SELEÇÃO DE CATEGORIA (apenas criação) ========== -->
    <?php if (!$isEdit): ?>
    <div class="modal fade" id="categoriaModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-car-front me-2"></i>Selecione a categoria do veículo</h5>
                    <button type="button" class="btn-close btn-close-white" disabled></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-4">Escolha o tipo de veículo que deseja cadastrar. Esta ação é obrigatória.</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <button type="button" class="btn btn-outline-dark w-100 py-3 categoria-btn" data-tipo="combustao">
                                <i class="bi bi-fuel-pump fs-1 d-block mb-2"></i>
                                <strong>Combustão</strong>
                                <small class="d-block text-muted">Flex, Diesel, Gasolina</small>
                            </button>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="button" class="btn btn-outline-dark w-100 py-3 categoria-btn" data-tipo="eletrico">
                                <i class="bi bi-battery-charging fs-1 d-block mb-2"></i>
                                <strong>Elétrico</strong>
                                <small class="d-block text-muted">100% BEV</small>
                            </button>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="button" class="btn btn-outline-dark w-100 py-3 categoria-btn" data-tipo="hibrido">
                                <i class="bi bi-ev-front fs-1 d-block mb-2"></i>
                                <strong>Híbrido</strong>
                                <small class="d-block text-muted">HEV, MHEV, PHEV</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========== FORMULÁRIO ========== -->
    <form id="veiculoForm" action="<?= $isEdit ? "/logista/veiculos/atualizar/{$veiculo['id']}" : '/logista/veiculos/salvar' ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- Tipo de veículo (hidden) -->
        <input type="hidden" name="tipo_veiculo" id="tipo_veiculo" value="<?= htmlspecialchars($tipoSelecionado ?? '') ?>">

        <!-- ========== DADOS PRINCIPAIS (comuns a todos) ========== -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informações Básicas</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Marca -->
                    <div class="col-md-6">
                        <label for="marca_id" class="form-label">Marca <span class="text-danger">*</span></label>
                        <select name="marca_id" id="marca_id" class="form-select <?= isset($errors['marca_id']) ? 'is-invalid' : '' ?>">
                            <option value="">Selecione uma marca</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= $marca['id'] ?>" <?= selected($old['marca_id'] ?? $veiculo['marca_id'] ?? '', $marca['id']) ?>>
                                    <?= htmlspecialchars($marca['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['marca_id'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['marca_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Modelo -->
                    <div class="col-md-6">
                        <label for="modelo_id" class="form-label">Modelo <span class="text-danger">*</span></label>
                        <select name="modelo_id" id="modelo_id" class="form-select <?= isset($errors['modelo_id']) ? 'is-invalid' : '' ?>">
                            <option value="">Selecione um modelo</option>
                            <?php foreach ($modelos as $modelo): ?>
                                <option value="<?= $modelo['id'] ?>" <?= selected($old['modelo_id'] ?? $veiculo['modelo_id'] ?? '', $modelo['id']) ?>>
                                    <?= htmlspecialchars($modelo['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['modelo_id'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['modelo_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Versão -->
                    <div class="col-md-4">
                        <label for="versao" class="form-label">Versão</label>
                        <input type="text" name="versao" id="versao" class="form-control <?= isset($errors['versao']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['versao'] ?? $veiculo['versao'] ?? '') ?>">
                        <?php if (isset($errors['versao'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['versao']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Ano Fabricação -->
                    <div class="col-md-4">
                        <label for="ano_fabricacao" class="form-label">Ano de Fabricação <span class="text-danger">*</span></label>
                        <input type="number" name="ano_fabricacao" id="ano_fabricacao" class="form-control <?= isset($errors['ano_fabricacao']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['ano_fabricacao'] ?? $veiculo['ano_fabricacao'] ?? '') ?>" min="1900" max="<?= date('Y') ?>">
                        <?php if (isset($errors['ano_fabricacao'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['ano_fabricacao']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Ano Modelo -->
                    <div class="col-md-4">
                        <label for="ano_modelo" class="form-label">Ano do Modelo <span class="text-danger">*</span></label>
                        <input type="number" name="ano_modelo" id="ano_modelo" class="form-control <?= isset($errors['ano_modelo']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['ano_modelo'] ?? $veiculo['ano_modelo'] ?? '') ?>" min="1900" max="<?= date('Y') + 1 ?>">
                        <?php if (isset($errors['ano_modelo'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['ano_modelo']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Cor -->
                    <div class="col-md-4">
                        <label for="cor" class="form-label">Cor <span class="text-danger">*</span></label>
                        <input type="text" name="cor" id="cor" class="form-control <?= isset($errors['cor']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['cor'] ?? $veiculo['cor'] ?? '') ?>">
                        <?php if (isset($errors['cor'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['cor']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Quilometragem -->
                    <div class="col-md-4">
                        <label for="quilometragem" class="form-label">Quilometragem <span class="text-danger">*</span></label>
                        <input type="number" name="quilometragem" id="quilometragem" class="form-control <?= isset($errors['quilometragem']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['quilometragem'] ?? $veiculo['quilometragem'] ?? '') ?>" min="0">
                        <?php if (isset($errors['quilometragem'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['quilometragem']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Preço -->
                    <div class="col-md-4">
                        <label for="preco" class="form-label">Preço (R$) <span class="text-danger">*</span></label>
                        <input type="text" name="preco" id="preco" class="form-control <?= isset($errors['preco']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['preco'] ?? (isset($veiculo['preco']) ? number_format($veiculo['preco'], 2, ',', '.') : '')) ?>" 
                               placeholder="Ex: 45.900,00">
                        <?php if (isset($errors['preco'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['preco']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Número de Portas -->
                    <div class="col-md-3">
                        <label for="numero_portas" class="form-label">Portas</label>
                        <input type="number" name="numero_portas" id="numero_portas" class="form-control <?= isset($errors['numero_portas']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['numero_portas'] ?? $veiculo['numero_portas'] ?? '') ?>" min="2" max="6">
                        <?php if (isset($errors['numero_portas'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['numero_portas']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Número de Assentos -->
                    <div class="col-md-3">
                        <label for="numero_assentos" class="form-label">Assentos</label>
                        <input type="number" name="numero_assentos" id="numero_assentos" class="form-control <?= isset($errors['numero_assentos']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '') ?>" min="2" max="9">
                        <?php if (isset($errors['numero_assentos'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['numero_assentos']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- GNV Instalado (apenas para combustão) -->
                    <div class="col-md-6 gnv-field" style="display: none;">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="gnv_instalado" id="gnv_instalado" class="form-check-input" value="1"
                                   <?= ($old['gnv_instalado'] ?? $veiculo['gnv_instalado'] ?? 0) ? 'checked' : '' ?>>
                            <label for="gnv_instalado" class="form-check-label">Veículo possui kit GNV instalado</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== DADOS ESPECÍFICOS POR TIPO ========== -->
        <div id="campos-especificos">
            <!-- Combustão -->
            <div id="campos-combustao" class="card shadow-sm mb-4" style="display: none;">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-fuel-pump me-2"></i>Dados do Motor a Combustão</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Campos de combustão (serão carregados via AJAX ou já estarão preenchidos na edição) -->
                        <div class="col-md-6">
                            <label for="combustivel" class="form-label">Combustível <span class="text-danger">*</span></label>
                            <select name="combustivel" id="combustivel" class="form-select">
                                <option value="">Selecione</option>
                                <option value="alcool" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'alcool') ?>>Álcool</option>
                                <option value="diesel" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'diesel') ?>>Diesel</option>
                                <option value="flex" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'flex') ?>>Flex</option>
                                <option value="gasolina" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'gasolina') ?>>Gasolina</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="motor_tipo" class="form-label">Tipo do Motor <span class="text-danger">*</span></label>
                            <input type="text" name="motor_tipo" id="motor_tipo" class="form-control" 
                                   value="<?= htmlspecialchars($old['motor_tipo'] ?? $complemento['motor_tipo'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="potencia_cv" class="form-label">Potência (cv) <span class="text-danger">*</span></label>
                            <input type="number" name="potencia_cv" id="potencia_cv" class="form-control" 
                                   value="<?= htmlspecialchars($old['potencia_cv'] ?? $complemento['potencia_cv'] ?? '') ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="potencia_etanol_cv" class="form-label">Potência Etanol (cv) <span class="text-danger" id="etanol-required">*</span></label>
                            <input type="number" name="potencia_etanol_cv" id="potencia_etanol_cv" class="form-control" 
                                   value="<?= htmlspecialchars($old['potencia_etanol_cv'] ?? $complemento['potencia_etanol_cv'] ?? '') ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="torque_kgfm" class="form-label">Torque (kgfm)</label>
                            <input type="text" name="torque_kgfm" id="torque_kgfm" class="form-control" 
                                   value="<?= htmlspecialchars($old['torque_kgfm'] ?? $complemento['torque_kgfm'] ?? '') ?>">
                        </div>
                        <!-- Outros campos... (inserir todos os campos de combustão) -->
                    </div>
                </div>
            </div>

            <!-- Elétrico -->
            <div id="campos-eletrico" class="card shadow-sm mb-4" style="display: none;">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-battery-charging me-2"></i>Dados do Veículo Elétrico</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Campos elétricos -->
                        <div class="col-md-6">
                            <label for="tracao_tipo" class="form-label">Tipo de Tração <span class="text-danger">*</span></label>
                            <select name="tracao_tipo" id="tracao_tipo" class="form-select">
                                <option value="">Selecione</option>
                                <option value="dianteira" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'dianteira') ?>>Dianteira</option>
                                <option value="traseira" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'traseira') ?>>Traseira</option>
                                <option value="integral" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'integral') ?>>Integral</option>
                            </select>
                        </div>
                        <!-- Outros campos... -->
                    </div>
                </div>
            </div>

            <!-- Híbrido -->
            <div id="campos-hibrido" class="card shadow-sm mb-4" style="display: none;">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-ev-front me-2"></i>Dados do Veículo Híbrido</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tipo_hibrido" class="form-label">Tipo de Híbrido <span class="text-danger">*</span></label>
                            <select name="tipo" id="tipo_hibrido" class="form-select">
                                <option value="">Selecione</option>
                                <option value="hev" <?= selected($old['tipo'] ?? $complemento['tipo'] ?? '', 'hev') ?>>HEV</option>
                                <option value="mhev" <?= selected($old['tipo'] ?? $complemento['tipo'] ?? '', 'mhev') ?>>MHEV</option>
                                <option value="phev" <?= selected($old['tipo'] ?? $complemento['tipo'] ?? '', 'phev') ?>>PHEV</option>
                            </select>
                        </div>
                        <!-- Outros campos... -->
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== OPÇÕES E BOTÕES ========== -->
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Atualizar' : 'Salvar' ?>
            </button>
            <a href="/logista/veiculos" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>

<!-- ========== SCRIPTS ========== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // 1. MODAL DE CATEGORIA (criação)
    // =============================================
    <?php if (!$isEdit): ?>
        const modal = new bootstrap.Modal(document.getElementById('categoriaModal'), {
            backdrop: 'static',
            keyboard: false
        });
        modal.show();

        // Botões de categoria
        document.querySelectorAll('.categoria-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tipo = this.dataset.tipo;
                document.getElementById('tipo_veiculo').value = tipo;
                mostrarCampos(tipo);
                modal.hide();
            });
        });

        // Impedir fechamento com ESC ou clique fora
        document.getElementById('categoriaModal').addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    <?php else: ?>
        // Em edição, mostra os campos do tipo atual
        const tipoAtual = '<?= addslashes($tipoAtual) ?>';
        if (tipoAtual) {
            mostrarCampos(tipoAtual);
            // Marca o checkbox GNV se aplicável
            if (tipoAtual === 'combustao') {
                document.querySelector('.gnv-field').style.display = 'block';
            }
        }
    <?php endif; ?>

    // =============================================
    // 2. FUNÇÃO PARA MOSTRAR/OCULTAR CAMPOS
    // =============================================
    function mostrarCampos(tipo) {
        // Oculta todos
        document.getElementById('campos-combustao').style.display = 'none';
        document.getElementById('campos-eletrico').style.display = 'none';
        document.getElementById('campos-hibrido').style.display = 'none';
        document.querySelector('.gnv-field').style.display = 'none';

        // Mostra o específico
        if (tipo === 'combustao') {
            document.getElementById('campos-combustao').style.display = 'block';
            document.querySelector('.gnv-field').style.display = 'block';
            // Torna campos de etanol obrigatórios se combustível for flex
            // (isso pode ser feito com JS ou deixar a validação do backend)
        } else if (tipo === 'eletrico') {
            document.getElementById('campos-eletrico').style.display = 'block';
        } else if (tipo === 'hibrido') {
            document.getElementById('campos-hibrido').style.display = 'block';
        }
    }

    // =============================================
    // 3. VALIDAÇÃO DE GNV (exibe campos extras)
    // =============================================
    document.getElementById('gnv_instalado').addEventListener('change', function() {
        // Se GNV for marcado, mostrar campos adicionais de GNV (serão implementados depois)
        // Por enquanto apenas log
        console.log('GNV marcado:', this.checked);
    });

    // =============================================
    // 4. CARREGAMENTO DE MODELOS POR MARCA (opcional)
    // =============================================
    // Aqui você pode implementar AJAX para carregar modelos conforme a marca selecionada
    // Exemplo: quando mudar marca_id, fazer fetch('/modelos?marca_id=' + valor)
    // e popular o select modelo_id
});
</script>

<!-- ========== HELPER FUNCTION ========== -->
<?php
// Função auxiliar para marcar selected em selects
function selected($valorSalvo, $valorAtual): string {
    return ((string) $valorSalvo === (string) $valorAtual) ? 'selected' : '';
}
?>