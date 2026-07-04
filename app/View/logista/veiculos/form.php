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

<script>
    const CONFIG = {
        motorizacoes: <?= json_encode(motorizacoes_list()) ?>
    };
</script>

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

                <!-- ===== DIMENSÕES (opcionais) ===== -->
                <hr class="my-4">
                <h6 class="mb-3"><i class="bi bi-rulers me-2"></i>Dimensões e Capacidades</h6>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="comprimento_mm" class="form-label">Comprimento (mm)</label>
                        <input type="number" name="comprimento_mm" id="comprimento_mm" class="form-control <?= isset($errors['comprimento_mm']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['comprimento_mm'] ?? $veiculo['comprimento_mm'] ?? '') ?>" min="0">
                        <?php if (isset($errors['comprimento_mm'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['comprimento_mm']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="largura_mm" class="form-label">Largura (mm)</label>
                        <input type="number" name="largura_mm" id="largura_mm" class="form-control <?= isset($errors['largura_mm']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['largura_mm'] ?? $veiculo['largura_mm'] ?? '') ?>" min="0">
                        <?php if (isset($errors['largura_mm'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['largura_mm']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="altura_mm" class="form-label">Altura (mm)</label>
                        <input type="number" name="altura_mm" id="altura_mm" class="form-control <?= isset($errors['altura_mm']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['altura_mm'] ?? $veiculo['altura_mm'] ?? '') ?>" min="0">
                        <?php if (isset($errors['altura_mm'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['altura_mm']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="distancia_entre_eixos_mm" class="form-label">Dist. entre eixos (mm)</label>
                        <input type="number" name="distancia_entre_eixos_mm" id="distancia_entre_eixos_mm" class="form-control <?= isset($errors['distancia_entre_eixos_mm']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['distancia_entre_eixos_mm'] ?? $veiculo['distancia_entre_eixos_mm'] ?? '') ?>" min="0">
                        <?php if (isset($errors['distancia_entre_eixos_mm'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['distancia_entre_eixos_mm']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="peso_ordem_marcha_kg" class="form-label">Peso (kg)</label>
                        <input type="number" name="peso_ordem_marcha_kg" id="peso_ordem_marcha_kg" class="form-control <?= isset($errors['peso_ordem_marcha_kg']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['peso_ordem_marcha_kg'] ?? $veiculo['peso_ordem_marcha_kg'] ?? '') ?>" min="0">
                        <?php if (isset($errors['peso_ordem_marcha_kg'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['peso_ordem_marcha_kg']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="volume_porta_malas_l" class="form-label">Porta-malas (L)</label>
                        <input type="number" name="volume_porta_malas_l" id="volume_porta_malas_l" class="form-control <?= isset($errors['volume_porta_malas_l']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['volume_porta_malas_l'] ?? $veiculo['volume_porta_malas_l'] ?? '') ?>" min="0">
                        <?php if (isset($errors['volume_porta_malas_l'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['volume_porta_malas_l']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="volume_cacamba_l" class="form-label">Caçamba (L)</label>
                        <input type="number" name="volume_cacamba_l" id="volume_cacamba_l" class="form-control <?= isset($errors['volume_cacamba_l']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['volume_cacamba_l'] ?? $veiculo['volume_cacamba_l'] ?? '') ?>" min="0">
                        <?php if (isset($errors['volume_cacamba_l'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['volume_cacamba_l']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="carga_util_kg" class="form-label">Carga útil (kg)</label>
                        <input type="number" name="carga_util_kg" id="carga_util_kg" class="form-control <?= isset($errors['carga_util_kg']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['carga_util_kg'] ?? $veiculo['carga_util_kg'] ?? '') ?>" min="0">
                        <?php if (isset($errors['carga_util_kg'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['carga_util_kg']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <label for="capacidade_reboque_kg" class="form-label">Cap. reboque (kg)</label>
                        <input type="number" name="capacidade_reboque_kg" id="capacidade_reboque_kg" class="form-control <?= isset($errors['capacidade_reboque_kg']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['capacidade_reboque_kg'] ?? $veiculo['capacidade_reboque_kg'] ?? '') ?>" min="0">
                        <?php if (isset($errors['capacidade_reboque_kg'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['capacidade_reboque_kg']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ===== STATUS ===== -->
                <hr class="my-4">
                <h6 class="mb-3"><i class="bi bi-toggles me-2"></i>Status</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="status_estoque" class="form-label">Status de Estoque</label>
                        <select name="status_estoque" id="status_estoque" class="form-select <?= isset($errors['status_estoque']) ? 'is-invalid' : '' ?>">
                            <option value="disponivel" <?= selected($old['status_estoque'] ?? $veiculo['status_estoque'] ?? 'disponivel', 'disponivel') ?>>Disponível</option>
                            <option value="vendido" <?= selected($old['status_estoque'] ?? $veiculo['status_estoque'] ?? 'disponivel', 'vendido') ?>>Vendido</option>
                            <option value="reservado" <?= selected($old['status_estoque'] ?? $veiculo['status_estoque'] ?? 'disponivel', 'reservado') ?>>Reservado</option>
                        </select>
                        <?php if (isset($errors['status_estoque'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['status_estoque']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="status_vitrine" class="form-label">Vitrine</label>
                        <select name="status_vitrine" id="status_vitrine" class="form-select <?= isset($errors['status_vitrine']) ? 'is-invalid' : '' ?>">
                            <option value="inativo" <?= selected($old['status_vitrine'] ?? $veiculo['status_vitrine'] ?? 'inativo', 'inativo') ?>>Inativo</option>
                            <option value="ativo" <?= selected($old['status_vitrine'] ?? $veiculo['status_vitrine'] ?? 'inativo', 'ativo') ?>>Ativo</option>
                        </select>
                        <?php if (isset($errors['status_vitrine'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['status_vitrine']) ?></div>
                        <?php endif; ?>
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
                        <!-- Combustível e Motor -->
                        <div class="col-md-6">
                            <label for="combustivel" class="form-label">Combustível <span class="text-danger">*</span></label>
                            <select name="combustivel" id="combustivel" class="form-select <?= isset($errors['combustivel']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <option value="alcool" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'alcool') ?>>Álcool</option>
                                <option value="diesel" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'diesel') ?>>Diesel</option>
                                <option value="flex" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'flex') ?>>Flex</option>
                                <option value="gasolina" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'gasolina') ?>>Gasolina</option>
                                <option value="gnv" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'gnv') ?>>GNV</option>
                            </select>
                            <?php if (isset($errors['combustivel'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['combustivel']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Motorização (cilindrada) -->
                        <div class="col-md-6">
                            <label for="motor_tipo" class="form-label">Motorização <span class="text-danger">*</span></label>
                            <select name="motor_tipo" id="motor_tipo" class="form-select <?= isset($errors['motor_tipo']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <?php foreach (motorizacoes_list() as $valor): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= selected($old['motor_tipo'] ?? $complemento['motor_tipo'] ?? '', $valor) ?>>
                                        <?= htmlspecialchars($valor) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="outro" <?= selected($old['motor_tipo'] ?? $complemento['motor_tipo'] ?? '', 'outro') ?>>Outro (digitar)</option>
                            </select>
                            <?php if (isset($errors['motor_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['motor_tipo']) ?></div>
                            <?php endif; ?>
                            
                            <!-- Campo extra para "Outro" -->
                            <input type="text" name="motor_tipo_outro" id="motor_tipo_outro" class="form-control mt-2 <?= isset($errors['motor_tipo']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['motor_tipo_outro'] ?? '') ?>" 
                                   placeholder="Digite a motorização (ex: 1.8, 2.2, 3.0)" 
                                   style="display: none;">
                            <small class="text-muted">Ex: 1.0, 1.6, 2.0, etc.</small>
                        </div>

                        <!-- Potência e Torque -->
                        <div class="col-md-4">
                            <label for="potencia_cv" class="form-label">Potência (cv) <span class="text-danger">*</span></label>
                            <input type="number" name="potencia_cv" id="potencia_cv" class="form-control <?= isset($errors['potencia_cv']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['potencia_cv'] ?? $complemento['potencia_cv'] ?? '') ?>" min="0">
                            <?php if (isset($errors['potencia_cv'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['potencia_cv']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="torque_kgfm" class="form-label">Torque (kgfm)</label>
                            <input type="number" step="0.1" name="torque_kgfm" id="torque_kgfm" class="form-control <?= isset($errors['torque_kgfm']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['torque_kgfm'] ?? $complemento['torque_kgfm'] ?? '') ?>" min="0">
                            <?php if (isset($errors['torque_kgfm'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['torque_kgfm']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Tração e Transmissão -->
                        <div class="col-md-4">
                            <label for="tracao_tipo" class="form-label">Tipo de Tração <span class="text-danger">*</span></label>
                            <select name="tracao_tipo" id="tracao_tipo" class="form-select <?= isset($errors['tracao_tipo']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <option value="dianteira" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'dianteira') ?>>Dianteira</option>
                                <option value="traseira" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'traseira') ?>>Traseira</option>
                                <option value="integral" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'integral') ?>>Integral</option>
                                <option value="4x4" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', '4x4') ?>>4x4</option>
                            </select>
                            <?php if (isset($errors['tracao_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['tracao_tipo']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="transmissao_tipo" class="form-label">Tipo de Transmissão <span class="text-danger">*</span></label>
                            <input type="text" name="transmissao_tipo" id="transmissao_tipo" class="form-control <?= isset($errors['transmissao_tipo']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['transmissao_tipo'] ?? $complemento['transmissao_tipo'] ?? '') ?>" maxlength="30">
                            <?php if (isset($errors['transmissao_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['transmissao_tipo']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="numero_marchas" class="form-label">Número de Marchas</label>
                            <input type="number" name="numero_marchas" id="numero_marchas" class="form-control <?= isset($errors['numero_marchas']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['numero_marchas'] ?? $complemento['numero_marchas'] ?? '') ?>" min="0">
                            <?php if (isset($errors['numero_marchas'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['numero_marchas']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Consumo e Tanque -->
                        <div class="col-md-3">
                            <label for="consumo_cidade_kml" class="form-label">Consumo Cidade (km/l) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="consumo_cidade_kml" id="consumo_cidade_kml" class="form-control <?= isset($errors['consumo_cidade_kml']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['consumo_cidade_kml'] ?? $complemento['consumo_cidade_kml'] ?? '') ?>" min="0">
                            <?php if (isset($errors['consumo_cidade_kml'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['consumo_cidade_kml']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="consumo_estrada_kml" class="form-label">Consumo Estrada (km/l)</label>
                            <input type="number" step="0.1" name="consumo_estrada_kml" id="consumo_estrada_kml" class="form-control <?= isset($errors['consumo_estrada_kml']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['consumo_estrada_kml'] ?? $complemento['consumo_estrada_kml'] ?? '') ?>" min="0">
                            <?php if (isset($errors['consumo_estrada_kml'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['consumo_estrada_kml']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="capacidade_tanque_l" class="form-label">Capacidade Tanque (L) <span class="text-danger">*</span></label>
                            <input type="number" name="capacidade_tanque_l" id="capacidade_tanque_l" class="form-control <?= isset($errors['capacidade_tanque_l']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['capacidade_tanque_l'] ?? $complemento['capacidade_tanque_l'] ?? '') ?>" min="0">
                            <?php if (isset($errors['capacidade_tanque_l'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['capacidade_tanque_l']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Campos Flex (condicionais) -->
                        <div class="col-12 flex-fields" style="display: none;">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-arrow-repeat me-2"></i>Dados para Etanol (obrigatórios para Flex)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="potencia_etanol_cv" class="form-label">Potência Etanol (cv) <span class="text-danger flex-required">*</span></label>
                                    <input type="number" name="potencia_etanol_cv" id="potencia_etanol_cv" class="form-control <?= isset($errors['potencia_etanol_cv']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['potencia_etanol_cv'] ?? $complemento['potencia_etanol_cv'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['potencia_etanol_cv'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['potencia_etanol_cv']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="torque_etanol_kgfm" class="form-label">Torque Etanol (kgfm) <span class="text-danger flex-required">*</span></label>
                                    <input type="number" step="0.1" name="torque_etanol_kgfm" id="torque_etanol_kgfm" class="form-control <?= isset($errors['torque_etanol_kgfm']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['torque_etanol_kgfm'] ?? $complemento['torque_etanol_kgfm'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['torque_etanol_kgfm'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['torque_etanol_kgfm']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="consumo_cidade_etanol_kml" class="form-label">Consumo Cidade Etanol (km/l) <span class="text-danger flex-required">*</span></label>
                                    <input type="number" step="0.1" name="consumo_cidade_etanol_kml" id="consumo_cidade_etanol_kml" class="form-control <?= isset($errors['consumo_cidade_etanol_kml']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['consumo_cidade_etanol_kml'] ?? $complemento['consumo_cidade_etanol_kml'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['consumo_cidade_etanol_kml'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['consumo_cidade_etanol_kml']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label for="consumo_estrada_etanol_kml" class="form-label">Consumo Estrada Etanol (km/l) <span class="text-danger flex-required">*</span></label>
                                    <input type="number" step="0.1" name="consumo_estrada_etanol_kml" id="consumo_estrada_etanol_kml" class="form-control <?= isset($errors['consumo_estrada_etanol_kml']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['consumo_estrada_etanol_kml'] ?? $complemento['consumo_estrada_etanol_kml'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['consumo_estrada_etanol_kml'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['consumo_estrada_etanol_kml']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Desempenho (opcionais) -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-speedometer2 me-2"></i>Desempenho (opcional)</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="regime_potencia_rpm" class="form-label">Regime Potência (RPM)</label>
                                    <input type="number" name="regime_potencia_rpm" id="regime_potencia_rpm" class="form-control <?= isset($errors['regime_potencia_rpm']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['regime_potencia_rpm'] ?? $complemento['regime_potencia_rpm'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['regime_potencia_rpm'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['regime_potencia_rpm']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <label for="regime_torque_rpm" class="form-label">Regime Torque (RPM)</label>
                                    <input type="number" name="regime_torque_rpm" id="regime_torque_rpm" class="form-control <?= isset($errors['regime_torque_rpm']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['regime_torque_rpm'] ?? $complemento['regime_torque_rpm'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['regime_torque_rpm'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['regime_torque_rpm']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <label for="aceleracao_0_100_seg" class="form-label">Aceleração 0-100 (s)</label>
                                    <input type="number" step="0.1" name="aceleracao_0_100_seg" id="aceleracao_0_100_seg" class="form-control <?= isset($errors['aceleracao_0_100_seg']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['aceleracao_0_100_seg'] ?? $complemento['aceleracao_0_100_seg'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['aceleracao_0_100_seg'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['aceleracao_0_100_seg']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <label for="velocidade_max_kmh" class="form-label">Velocidade Máxima (km/h)</label>
                                    <input type="number" name="velocidade_max_kmh" id="velocidade_max_kmh" class="form-control <?= isset($errors['velocidade_max_kmh']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['velocidade_max_kmh'] ?? $complemento['velocidade_max_kmh'] ?? '') ?>" min="0">
                                    <?php if (isset($errors['velocidade_max_kmh'])): ?>
                                        <div class="invalid-feedback"><?= implode(', ', $errors['velocidade_max_kmh']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
                toggleFlexFields(); // para sincronizar flex
            } else if (tipo === 'eletrico') {
                document.getElementById('campos-eletrico').style.display = 'block';
            } else if (tipo === 'hibrido') {
                document.getElementById('campos-hibrido').style.display = 'block';
            }
        }

        // =============================================
        // 3. CONTROLE DE CAMPOS FLEX (etanol)
        // =============================================
        const combustivelSelect = document.getElementById('combustivel');
        const flexFields = document.querySelector('.flex-fields');
        const flexRequired = document.querySelectorAll('.flex-required');

        function toggleFlexFields() {
            if (!flexFields) return;
            const isFlex = combustivelSelect && combustivelSelect.value === 'flex';
            flexFields.style.display = isFlex ? 'block' : 'none';
            flexRequired.forEach(el => {
                el.style.display = isFlex ? 'inline' : 'none';
            });
        }

        if (combustivelSelect) {
            combustivelSelect.addEventListener('change', toggleFlexFields);
            // Executa ao carregar para sincronizar (edição)
            toggleFlexFields();
        }

        // =============================================
        // 4. CONTROLE DE "OUTRO" (motorização)
        // =============================================
        const motorTipoSelect = document.getElementById('motor_tipo');
        const motorTipoOutro = document.getElementById('motor_tipo_outro');

        function toggleMotorOutro() {
            if (motorTipoSelect && motorTipoOutro) {
                const isOutro = motorTipoSelect.value === 'outro';
                motorTipoOutro.style.display = isOutro ? 'block' : 'none';
                // Se não for outro, limpa o campo extra para não enviar dados inconsistentes
                if (!isOutro) {
                    motorTipoOutro.value = '';
                }
            }
        }

        if (motorTipoSelect) {
            // Ao mudar a seleção, mostra/oculta o campo extra
            motorTipoSelect.addEventListener('change', toggleMotorOutro);

            // Na edição, se o valor salvo não estiver na lista, seleciona "outro" e preenche o campo extra
            const valorAtual = motorTipoSelect.value;
            if (valorAtual && valorAtual !== 'outro') {
                // Verifica se o valor está na lista de motorizações (via CONFIG)
                if (CONFIG.motorizacoes && !CONFIG.motorizacoes.includes(valorAtual)) {
                    // Valor personalizado não está na lista: seleciona "outro" e coloca o valor no campo extra
                    motorTipoSelect.value = 'outro';
                    motorTipoOutro.value = valorAtual;
                    // Dispara o evento para exibir o campo
                    toggleMotorOutro();
                }
            }

            // Executa ao carregar para sincronizar (edição)
            toggleMotorOutro();
        }

        // =============================================
        // 5. VALIDAÇÃO DE GNV (exibe campos extras)
        // =============================================
        const gnvCheckbox = document.getElementById('gnv_instalado');
        if (gnvCheckbox) {
            gnvCheckbox.addEventListener('change', function() {
                // Se GNV for marcado, mostrar campos adicionais de GNV (serão implementados depois)
                console.log('GNV marcado:', this.checked);
            });
        }

        // =============================================
        // 6. PREPARAR SUBMISSÃO (garantir que o valor certo seja enviado)
        // =============================================
        const form = document.getElementById('veiculoForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Se "outro" estiver selecionado, copia o valor do campo extra para o select (ou para um hidden)
                if (motorTipoSelect && motorTipoSelect.value === 'outro') {
                    const valorPersonalizado = motorTipoOutro.value.trim();
                    if (valorPersonalizado === '') {
                        // Impede envio se campo extra estiver vazio
                        e.preventDefault();
                        alert('Por favor, digite a motorização personalizada.');
                        motorTipoOutro.focus();
                        return;
                    }
                    // Seta o valor personalizado no select para ser enviado
                    motorTipoSelect.value = valorPersonalizado;
                }
            });
        }
    });
</script>

<!-- ========== HELPER FUNCTION ========== -->
<?php
// Função auxiliar para marcar selected em selects
function selected($valorSalvo, $valorAtual): string {
    return ((string) $valorSalvo === (string) $valorAtual) ? 'selected' : '';
}
?>