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
        motorizacoes: <?= json_encode(motorizacoes_list()) ?>,
        regrasHibrido: <?= json_encode(regras_hibrido()) ?>
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
                    <!-- ===== MARCA E MODELO ===== -->
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

                    <!-- Versão -->
                    <div class="col-md-4">
                        <label for="versao" class="form-label">Versão do Modelo</label>
                        <input title="Versão do modelo (ex: GL, EX, Sport, Turbo)" placeholder="ex: GL, EX, Sport, Turbo" type="text" name="versao" id="versao" class="form-control <?= isset($errors['versao']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['versao'] ?? $veiculo['versao'] ?? '') ?>">
                        <?php if (isset($errors['versao'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['versao']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Ano Fabricação -->
                    <div class="col-md-4">
                        <label for="ano_fabricacao" class="form-label">Ano de Fabricação <span class="text-danger">*</span></label>
                        <input title="Digite o ano com 4 dígitos" placeholder="ex: 2025" type="number" name="ano_fabricacao" id="ano_fabricacao" class="form-control <?= isset($errors['ano_fabricacao']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['ano_fabricacao'] ?? $veiculo['ano_fabricacao'] ?? '') ?>" min="1900" max="<?= date('Y') ?>">
                        <?php if (isset($errors['ano_fabricacao'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['ano_fabricacao']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Ano Modelo -->
                    <div class="col-md-4">
                        <label for="ano_modelo" class="form-label">Ano do Modelo <span class="text-danger">*</span></label>
                        <input title="Digite o ano com 4 dígitos" placeholder="ex: 2026" type="number" name="ano_modelo" id="ano_modelo" class="form-control <?= isset($errors['ano_modelo']) ? 'is-invalid' : '' ?>" 
                               value="<?= htmlspecialchars($old['ano_modelo'] ?? $veiculo['ano_modelo'] ?? '') ?>" min="1900" max="<?= date('Y') + 1 ?>">
                        <?php if (isset($errors['ano_modelo'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['ano_modelo']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Cor -->
                    <div class="col-md-4">
                        <label for="corInput" class="form-label">Cor <span class="text-danger">*</span></label>
                        
                        <!-- Input + botão para abrir a lista -->
                        <div class="input-group">
                            <input type="text" id="corInput" class="form-control <?= isset($errors['cor']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['cor'] ?? $veiculo['cor'] ?? '') ?>" 
                                   placeholder="Selecione uma cor" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="btnAbrirCores">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                        
                        <!-- Campo oculto para armazenar a cor selecionada -->
                        <input type="hidden" name="cor" id="corSelecionada" value="<?= htmlspecialchars($old['cor'] ?? $veiculo['cor'] ?? '') ?>">

                        <?php if (isset($errors['cor'])): ?>
                            <div class="invalid-feedback d-block"><?= implode(', ', $errors['cor']) ?></div>
                        <?php endif; ?>

                        <!-- Dropdown com a lista de cores -->
                        <div id="dropdownCores" class="border rounded shadow-sm mt-1" style="display: none; max-height: 200px; overflow-y: auto; position: relative; z-index: 1000; background: white;">
                            <div class="p-1">
                                <?php
                                $cores = [
                                    'Preto' => '#000000',
                                    'Branco' => '#FFFFFF',
                                    'Prata' => '#C0C0C0',
                                    'Cinza' => '#808080',
                                    'Vermelho' => '#FF0000',
                                    'Azul' => '#0000FF',
                                    'Verde' => '#008000',
                                    'Amarelo' => '#FFD700',
                                    'Laranja' => '#FFA500',
                                    'Marrom' => '#8B4513',
                                    'Bege' => '#F5F5DC',
                                    'Dourado' => '#FFD700',
                                    'Prata Metálico' => '#A8A9AD',
                                    'Azul Metálico' => '#1E3A5F',
                                    'Vermelho Metálico' => '#8B0000',
                                    'Verde Metálico' => '#2E8B57',
                                    'Cinza Metálico' => '#696969',
                                    'Preto Metálico' => '#1A1A1A',
                                    'Branco Pérola' => '#F8F8FF',
                                    'Azul Escuro' => '#191970',
                                    'Vinho' => '#722F37',
                                    'Bronze' => '#CD7F32',
                                ];
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
                    </div>

                    <!-- Quilometragem -->
                    <div class="col-md-4">
                        <label for="quilometragem" class="form-label">Quilometragem <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="quilometragem_display" id="quilometragem" class="form-control <?= isset($errors['quilometragem']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars(
                                       isset($old['quilometragem']) ? number_format($old['quilometragem'], 0, ',', '.') : 
                                       (isset($veiculo['quilometragem']) ? number_format($veiculo['quilometragem'], 0, ',', '.') : '')
                                   ) ?>" 
                                   placeholder="Ex: 90.000" inputmode="numeric">
                            <span class="input-group-text">km</span>
                        </div>
                        <!-- Campo oculto com o valor real (sem formatação) -->
                        <input type="hidden" name="quilometragem" id="quilometragem_real" value="<?= htmlspecialchars($old['quilometragem'] ?? $veiculo['quilometragem'] ?? '') ?>">
                        <?php if (isset($errors['quilometragem'])): ?>
                            <div class="invalid-feedback d-block"><?= implode(', ', $errors['quilometragem']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Preço -->
                    <div class="col-md-4">
                        <label for="preco" class="form-label">Preço <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" id="preco" class="form-control <?= isset($errors['preco']) ? 'is-invalid' : '' ?>" 
                                   placeholder="Ex: 45.900,00" inputmode="decimal">
                        </div>
                        <input type="hidden" name="preco" id="preco_real" value="<?= htmlspecialchars($old['preco'] ?? $veiculo['preco'] ?? '') ?>">
                        <?php if (isset($errors['preco'])): ?>
                            <div class="invalid-feedback d-block"><?= implode(', ', $errors['preco']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Número de Portas -->
                    <div class="col-md-3">
                        <label for="numero_portas" class="form-label">Portas <span class="text-danger">*</span></label>
                        <select name="numero_portas" id="numero_portas" class="form-select <?= isset($errors['numero_portas']) ? 'is-invalid' : '' ?>">
                            <option value="">Selecione</option>
                            <option value="2" <?= selected($old['numero_portas'] ?? $veiculo['numero_portas'] ?? '', '2') ?>>2 portas (cupê)</option>
                            <option value="3" <?= selected($old['numero_portas'] ?? $veiculo['numero_portas'] ?? '', '3') ?>>3 portas (hatch 2 portas)</option>
                            <option value="4" <?= selected($old['numero_portas'] ?? $veiculo['numero_portas'] ?? '', '4') ?>>4 portas (sedã)</option>
                            <option value="5" <?= selected($old['numero_portas'] ?? $veiculo['numero_portas'] ?? '', '5') ?>>5 portas (hatch 4 portas + mala)</option>
                        </select>
                        <?php if (isset($errors['numero_portas'])): ?>
                            <div class="invalid-feedback"><?= implode(', ', $errors['numero_portas']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Número de Assentos -->
                    <div class="col-md-3">
                        <label for="numero_assentos" class="form-label">Assentos <span class="text-danger">*</span></label>
                        <select name="numero_assentos" id="numero_assentos" class="form-select <?= isset($errors['numero_assentos']) ? 'is-invalid' : '' ?>">
                            <option value="">Selecione</option>
                            <option value="2" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '2') ?>>2 assentos</option>
                            <option value="3" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '3') ?>>3 assentos</option>
                            <option value="4" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '4') ?>>4 assentos</option>
                            <option value="5" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '5') ?>>5 assentos</option>
                            <option value="6" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '6') ?>>6 assentos</option>
                            <option value="7" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '7') ?>>7 assentos</option>
                            <option value="8" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '8') ?>>8 assentos</option>
                            <option value="9" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '9') ?>>9 assentos</option>
                            <option value="10" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '10') ?>>10 assentos</option>
                            <option value="11" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '11') ?>>11 assentos</option>
                            <option value="12" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '12') ?>>12 assentos</option>
                            <option value="13" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '13') ?>>13 assentos</option>
                            <option value="14" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '14') ?>>14 assentos</option>
                            <option value="15" <?= selected($old['numero_assentos'] ?? $veiculo['numero_assentos'] ?? '', '15') ?>>15 assentos</option>
                        </select>
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

                    <!-- Bloco GNV (exibido apenas se o checkbox estiver marcado) -->
                    <div id="bloco-gnv" style="display: <?= ($old['gnv_instalado'] ?? $veiculo['gnv_instalado'] ?? 0) ? 'block' : 'none' ?>;" class="mt-3 p-3 border rounded bg-light">
                        <h6 class="mb-3"><i class="bi bi-gas-pump me-2"></i>Dados do Kit GNV</h6>
                        <div class="row g-3">
                            <!-- Tipo de Sistema -->
                            <div class="col-md-4">
                                <label for="tipo_sistema" class="form-label">Tipo de Sistema <span class="text-danger">*</span></label>
                                <select name="tipo_sistema" id="tipo_sistema" class="form-select <?= isset($errors['tipo_sistema']) ? 'is-invalid' : '' ?>">
                                    <option value="">Selecione</option>
                                    <option value="GNC" <?= selected($old['tipo_sistema'] ?? $complemento['tipo_sistema'] ?? '', 'GNC') ?>>GNC (Gás Natural Comprimido)</option>
                                    <option value="GLP" <?= selected($old['tipo_sistema'] ?? $complemento['tipo_sistema'] ?? '', 'GLP') ?>>GLP (Gás Liquefeito de Petróleo)</option>
                                </select>
                                <?php if (isset($errors['tipo_sistema'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['tipo_sistema']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Geração do Kit -->
                            <div class="col-md-4">
                                <label for="geracao_kit" class="form-label">Geração do Kit <span class="text-danger">*</span></label>
                                <select name="geracao_kit" id="geracao_kit" class="form-select <?= isset($errors['geracao_kit']) ? 'is-invalid' : '' ?>">
                                    <option value="">Selecione</option>
                                    <option value="3ª" <?= selected($old['geracao_kit'] ?? $complemento['geracao_kit'] ?? '', '3ª') ?>>3ª Geração</option>
                                    <option value="4ª" <?= selected($old['geracao_kit'] ?? $complemento['geracao_kit'] ?? '', '4ª') ?>>4ª Geração</option>
                                    <option value="5ª" <?= selected($old['geracao_kit'] ?? $complemento['geracao_kit'] ?? '', '5ª') ?>>5ª Geração</option>
                                    <option value="6ª" <?= selected($old['geracao_kit'] ?? $complemento['geracao_kit'] ?? '', '6ª') ?>>6ª Geração</option>
                                </select>
                                <?php if (isset($errors['geracao_kit'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['geracao_kit']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Marca do Kit -->
                            <div class="col-md-4">
                                <label for="marca_kit" class="form-label">Marca do Kit</label>
                                <input type="text" name="marca_kit" id="marca_kit" class="form-control <?= isset($errors['marca_kit']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['marca_kit'] ?? $complemento['marca_kit'] ?? '') ?>" maxlength="40">
                                <?php if (isset($errors['marca_kit'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['marca_kit']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Data de Instalação -->
                            <div class="col-md-4">
                                <label for="data_instalacao" class="form-label">Data de Instalação</label>
                                <input type="date" name="data_instalacao" id="data_instalacao" class="form-control <?= isset($errors['data_instalacao']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['data_instalacao'] ?? $complemento['data_instalacao'] ?? '') ?>">
                                <?php if (isset($errors['data_instalacao'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['data_instalacao']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Data da Última Inspeção -->
                            <div class="col-md-4">
                                <label for="data_inspecao" class="form-label">Data da Última Inspeção <span class="text-danger">*</span></label>
                                <input type="date" name="data_inspecao" id="data_inspecao" class="form-control <?= isset($errors['data_inspecao']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['data_inspecao'] ?? $complemento['data_inspecao'] ?? '') ?>">
                                <?php if (isset($errors['data_inspecao'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['data_inspecao']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Validade do Cilindro -->
                            <div class="col-md-4">
                                <label for="data_validade_cilindro" class="form-label">Validade do Cilindro <span class="text-danger">*</span></label>
                                <input type="date" name="data_validade_cilindro" id="data_validade_cilindro" class="form-control <?= isset($errors['data_validade_cilindro']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['data_validade_cilindro'] ?? $complemento['data_validade_cilindro'] ?? '') ?>">
                                <?php if (isset($errors['data_validade_cilindro'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['data_validade_cilindro']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Capacidade -->
                            <div class="col-md-3">
                                <label for="capacidade_cilindro_m3" class="form-label">Capacidade <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="capacidade_cilindro_m3" id="capacidade_cilindro_m3" class="form-select <?= isset($errors['capacidade_cilindro_m3']) ? 'is-invalid' : '' ?>">
                                        <option value="">Selecione</option>
                                        <option value="7.5" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '7.5') ?>>7,5 m³</option>
                                        <option value="9.5" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '9.5') ?>>9,5 m³</option>
                                        <option value="10" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '10') ?>>10 m³</option>
                                        <option value="15" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '15') ?>>15 m³</option>
                                        <option value="17" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '17') ?>>17 m³</option>
                                        <option value="21" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '21') ?>>21 m³</option>
                                        <option value="24.5" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '24.5') ?>>24,5 m³</option>
                                        <option value="25" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', '25') ?>>25 m³</option>
                                        <option value="outro" <?= selected($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '', 'outro') ?>>Outro (digitar)</option>
                                    </select>
                                    
                                </div>
                                <?php if (isset($errors['capacidade_cilindro_m3'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['capacidade_cilindro_m3']) ?></div>
                                <?php endif; ?>

                                <!-- Campo extra para "Outro" -->
                                <input type="number" step="0.01" name="capacidade_cilindro_m3_outro" id="capacidade_cilindro_m3_outro" class="form-control mt-2 <?= isset($errors['capacidade_cilindro_m3']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['capacidade_cilindro_m3_outro'] ?? '') ?>" 
                                       placeholder="Digite a capacidade em m³" 
                                       style="display: <?= ($old['capacidade_cilindro_m3'] ?? $complemento['capacidade_cilindro_m3'] ?? '') === 'outro' ? 'block' : 'none' ?>;" 
                                       min="0">
                            </div>

                            <!-- Quantidade Cilindros -->
                            <div class="col-md-3">
                                <label for="quantidade_cilindros" class="form-label">Quantidade <span class="text-danger">*</span></label>
                                <select name="quantidade_cilindros" id="quantidade_cilindros" class="form-select <?= isset($errors['quantidade_cilindros']) ? 'is-invalid' : '' ?>">
                                    <option value="">Selecione</option>
                                    <option value="1" <?= selected($old['quantidade_cilindros'] ?? $complemento['quantidade_cilindros'] ?? '', '1') ?>>1 cilindro</option>
                                    <option value="2" <?= selected($old['quantidade_cilindros'] ?? $complemento['quantidade_cilindros'] ?? '', '2') ?>>2 cilindros</option>
                                    <option value="3" <?= selected($old['quantidade_cilindros'] ?? $complemento['quantidade_cilindros'] ?? '', '3') ?>>3 cilindros</option>
                                    <option value="4" <?= selected($old['quantidade_cilindros'] ?? $complemento['quantidade_cilindros'] ?? '', '4') ?>>4 cilindros</option>
                                    <option value="5" <?= selected($old['quantidade_cilindros'] ?? $complemento['quantidade_cilindros'] ?? '', '5') ?>>5 cilindros</option>
                                </select>
                                <?php if (isset($errors['quantidade_cilindros'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['quantidade_cilindros']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Material Cilindro -->
                            <div class="col-md-3">
                                <label for="material_cilindro" class="form-label">Material do Cilindro</label>
                                <select name="material_cilindro" id="material_cilindro" class="form-select <?= isset($errors['material_cilindro']) ? 'is-invalid' : '' ?>">
                                    <option value="">Selecione</option>
                                    <option value="Aço" <?= selected($old['material_cilindro'] ?? $complemento['material_cilindro'] ?? '', 'Aço') ?>>Aço</option>
                                    <option value="Alumínio" <?= selected($old['material_cilindro'] ?? $complemento['material_cilindro'] ?? '', 'Alumínio') ?>>Alumínio</option>
                                    <option value="Compósito (Fibra de Carbono)" <?= selected($old['material_cilindro'] ?? $complemento['material_cilindro'] ?? '', 'Compósito (Fibra de Carbono)') ?>>Compósito (Fibra de Carbono)</option>
                                    <option value="Compósito (Fibra de Vidro)" <?= selected($old['material_cilindro'] ?? $complemento['material_cilindro'] ?? '', 'Compósito (Fibra de Vidro)') ?>>Compósito (Fibra de Vidro)</option>
                                    <option value="outro" <?= selected($old['material_cilindro'] ?? $complemento['material_cilindro'] ?? '', 'outro') ?>>Outro (digitar)</option>
                                </select>
                                <?php if (isset($errors['material_cilindro'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['material_cilindro']) ?></div>
                                <?php endif; ?>

                                <!-- Campo extra para "Outro" -->
                                <input type="text" name="material_cilindro_outro" id="material_cilindro_outro" class="form-control mt-2 <?= isset($errors['material_cilindro']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['material_cilindro_outro'] ?? '') ?>" 
                                       placeholder="Digite o material personalizado" 
                                       style="display: <?= ($old['material_cilindro'] ?? $complemento['material_cilindro'] ?? '') === 'outro' ? 'block' : 'none' ?>;">
                            </div>

                            <!-- Localização Cilindro -->
                            <div class="col-md-3">
                                <label for="localizacao_cilindro" class="form-label">Localização</label>
                                <select name="localizacao_cilindro" id="localizacao_cilindro" class="form-select <?= isset($errors['localizacao_cilindro']) ? 'is-invalid' : '' ?>">
                                    <option value="">Selecione</option>
                                    <option value="Porta-malas" <?= selected($old['localizacao_cilindro'] ?? $complemento['localizacao_cilindro'] ?? '', 'Porta-malas') ?>>Porta-malas</option>
                                    <option value="Sob o assoalho (Por baixo do carro)" <?= selected($old['localizacao_cilindro'] ?? $complemento['localizacao_cilindro'] ?? '', 'Sob o assoalho (Por baixo do carro)') ?>>Sob o assoalho (Por baixo do carro)</option>
                                    <option value="Atrás dos bancos" <?= selected($old['localizacao_cilindro'] ?? $complemento['localizacao_cilindro'] ?? '', 'Atrás dos bancos') ?>>Atrás dos bancos</option>
                                    <option value="Sobre o assoalho (área de carga)" <?= selected($old['localizacao_cilindro'] ?? $complemento['localizacao_cilindro'] ?? '', 'Sobre o assoalho (área de carga)') ?>>Sobre o assoalho (área de carga)</option>
                                    <option value="outro" <?= selected($old['localizacao_cilindro'] ?? $complemento['localizacao_cilindro'] ?? '', 'outro') ?>>Outro (digitar)</option>
                                </select>
                                <?php if (isset($errors['localizacao_cilindro'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['localizacao_cilindro']) ?></div>
                                <?php endif; ?>

                                <!-- Campo extra para "Outro" -->
                                <input type="text" name="localizacao_cilindro_outro" id="localizacao_cilindro_outro" class="form-control mt-2 <?= isset($errors['localizacao_cilindro']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['localizacao_cilindro_outro'] ?? '') ?>" 
                                       placeholder="Digite a localização personalizada" 
                                       style="display: <?= ($old['localizacao_cilindro'] ?? $complemento['localizacao_cilindro'] ?? '') === 'outro' ? 'block' : 'none' ?>;">
                            </div>

                            <!-- Consumo Cidade -->
                            <div class="col-md-3">
                                <label for="consumo_cidade_m3km" class="form-label">Consumo Cidade</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="consumo_cidade_m3km" id="consumo_cidade_m3km" class="form-control <?= isset($errors['consumo_cidade_m3km']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['consumo_cidade_m3km'] ?? $complemento['consumo_cidade_m3km'] ?? '') ?>" min="0">
                                    <span class="input-group-text">m³/km</span>
                                </div>
                                <?php if (isset($errors['consumo_cidade_m3km'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['consumo_cidade_m3km']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Consumo Estrada -->
                            <div class="col-md-3">
                                <label for="consumo_estrada_m3km" class="form-label">Consumo Estrada</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="consumo_estrada_m3km" id="consumo_estrada_m3km" class="form-control <?= isset($errors['consumo_estrada_m3km']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['consumo_estrada_m3km'] ?? $complemento['consumo_estrada_m3km'] ?? '') ?>" min="0">
                                    <span class="input-group-text">m³/km</span>
                                </div>
                                <?php if (isset($errors['consumo_estrada_m3km'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['consumo_estrada_m3km']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Autonomia Média -->
                            <div class="col-md-2">
                                <label for="autonomia_media_km" class="form-label">Autonomia Média</label>
                                <div class="input-group">
                                    <input type="number" name="autonomia_media_km" id="autonomia_media_km" class="form-control <?= isset($errors['autonomia_media_km']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['autonomia_media_km'] ?? $complemento['autonomia_media_km'] ?? '') ?>" min="0">
                                    <span class="input-group-text">km</span>
                                </div>
                                <?php if (isset($errors['autonomia_media_km'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['autonomia_media_km']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Autonomia Cidade -->
                            <div class="col-md-2">
                                <label for="autonomia_cidade_km" class="form-label">Autonomia Cidade</label>
                                <div class="input-group">
                                    <input type="number" name="autonomia_cidade_km" id="autonomia_cidade_km" class="form-control <?= isset($errors['autonomia_cidade_km']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['autonomia_cidade_km'] ?? $complemento['autonomia_cidade_km'] ?? '') ?>" min="0">
                                    <span class="input-group-text">km</span>
                                </div>
                                <?php if (isset($errors['autonomia_cidade_km'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['autonomia_cidade_km']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Autonomia Estrada -->
                            <div class="col-md-2">
                                <label for="autonomia_estrada_km" class="form-label">Autonomia Estrada</label>
                                <div class="input-group">
                                    <input type="number" name="autonomia_estrada_km" id="autonomia_estrada_km" class="form-control <?= isset($errors['autonomia_estrada_km']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($old['autonomia_estrada_km'] ?? $complemento['autonomia_estrada_km'] ?? '') ?>" min="0">
                                    <span class="input-group-text">km</span>
                                </div>
                                <?php if (isset($errors['autonomia_estrada_km'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['autonomia_estrada_km']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Booleanos -->
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input type="hidden" name="selo_inmetro" value="0">
                                    <input type="checkbox" name="selo_inmetro" id="selo_inmetro" class="form-check-input" value="1"
                                           <?= ($old['selo_inmetro'] ?? $complemento['selo_inmetro'] ?? 0) ? 'checked' : '' ?>>
                                    <label for="selo_inmetro" class="form-check-label">Possui selo Inmetro?</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input type="hidden" name="certificado_csv" value="0">
                                    <input type="checkbox" name="certificado_csv" id="certificado_csv" class="form-check-input" value="1"
                                           <?= ($old['certificado_csv'] ?? $complemento['certificado_csv'] ?? 0) ? 'checked' : '' ?>>
                                    <label for="certificado_csv" class="form-check-label">Possui certificado CSV?</label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input type="hidden" name="registro_detran" value="0">
                                    <input type="checkbox" name="registro_detran" id="registro_detran" class="form-check-input" value="1"
                                           <?= ($old['registro_detran'] ?? $complemento['registro_detran'] ?? 0) ? 'checked' : '' ?>>
                                    <label for="registro_detran" class="form-check-label">Registrado no Detran?</label>
                                </div>
                            </div>

                            <!-- Instaladora e Observações -->
                            <div class="col-md-6">
                                <label for="instaladora_certificada" class="form-label">Instaladora Certificada</label>
                                <input type="text" name="instaladora_certificada" id="instaladora_certificada" class="form-control <?= isset($errors['instaladora_certificada']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($old['instaladora_certificada'] ?? $complemento['instaladora_certificada'] ?? '') ?>" maxlength="50">
                                <?php if (isset($errors['instaladora_certificada'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['instaladora_certificada']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea name="observacoes" id="observacoes" class="form-control <?= isset($errors['observacoes']) ? 'is-invalid' : '' ?>" rows="2"><?= htmlspecialchars($old['observacoes'] ?? $complemento['observacoes'] ?? '') ?></textarea>
                                <?php if (isset($errors['observacoes'])): ?>
                                    <div class="invalid-feedback d-block"><?= implode(', ', $errors['observacoes']) ?></div>
                                <?php endif; ?>
                            </div>
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
                        <!-- Tração e Transmissão -->
                        <div class="col-md-6">
                            <label for="tracao_tipo_eletrico" class="form-label">Tipo de Tração <span class="text-danger">*</span></label>
                            <select name="tracao_tipo" id="tracao_tipo_eletrico" class="form-select <?= isset($errors['tracao_tipo']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <option value="dianteira" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'dianteira') ?>>Dianteira</option>
                                <option value="traseira" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'traseira') ?>>Traseira</option>
                                <option value="integral" <?= selected($old['tracao_tipo'] ?? $complemento['tracao_tipo'] ?? '', 'integral') ?>>Integral</option>
                            </select>
                            <?php if (isset($errors['tracao_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['tracao_tipo']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="transmissao_tipo_eletrico" class="form-label">Tipo de Transmissão <span class="text-danger">*</span></label>
                            <input type="text" name="transmissao_tipo" id="transmissao_tipo_eletrico" class="form-control <?= isset($errors['transmissao_tipo']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['transmissao_tipo'] ?? $complemento['transmissao_tipo'] ?? '') ?>" maxlength="30">
                            <?php if (isset($errors['transmissao_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['transmissao_tipo']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Motorização -->
                        <div class="col-md-4">
                            <label for="potencia_max_cv" class="form-label">Potência Máxima (cv) <span class="text-danger">*</span></label>
                            <input type="number" name="potencia_max_cv" id="potencia_max_cv" class="form-control <?= isset($errors['potencia_max_cv']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['potencia_max_cv'] ?? $complemento['potencia_max_cv'] ?? '') ?>" min="0">
                            <?php if (isset($errors['potencia_max_cv'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['potencia_max_cv']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="torque_max_nm" class="form-label">Torque Máximo (Nm)</label>
                            <input type="number" name="torque_max_nm" id="torque_max_nm" class="form-control <?= isset($errors['torque_max_nm']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['torque_max_nm'] ?? $complemento['torque_max_nm'] ?? '') ?>" min="0">
                            <?php if (isset($errors['torque_max_nm'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['torque_max_nm']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="torque_max_kgfm" class="form-label">Torque Máximo (kgfm)</label>
                            <input type="number" step="0.1" name="torque_max_kgfm" id="torque_max_kgfm" class="form-control <?= isset($errors['torque_max_kgfm']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['torque_max_kgfm'] ?? $complemento['torque_max_kgfm'] ?? '') ?>" min="0">
                            <?php if (isset($errors['torque_max_kgfm'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['torque_max_kgfm']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Desempenho (opcionais) -->
                        <div class="col-md-4">
                            <label for="aceleracao_0_100_seg_eletrico" class="form-label">Aceleração 0-100 (s)</label>
                            <input type="number" step="0.1" name="aceleracao_0_100_seg" id="aceleracao_0_100_seg_eletrico" class="form-control <?= isset($errors['aceleracao_0_100_seg']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['aceleracao_0_100_seg'] ?? $complemento['aceleracao_0_100_seg'] ?? '') ?>" min="0">
                            <?php if (isset($errors['aceleracao_0_100_seg'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['aceleracao_0_100_seg']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="velocidade_max_kmh_eletrico" class="form-label">Velocidade Máxima (km/h)</label>
                            <input type="number" name="velocidade_max_kmh" id="velocidade_max_kmh_eletrico" class="form-control <?= isset($errors['velocidade_max_kmh']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['velocidade_max_kmh'] ?? $complemento['velocidade_max_kmh'] ?? '') ?>" min="0">
                            <?php if (isset($errors['velocidade_max_kmh'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['velocidade_max_kmh']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Bateria -->
                        <div class="col-md-4">
                            <label for="capacidade_liquida_kwh" class="form-label">Capacidade Líquida (kWh) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="capacidade_liquida_kwh" id="capacidade_liquida_kwh" class="form-control <?= isset($errors['capacidade_liquida_kwh']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['capacidade_liquida_kwh'] ?? $complemento['capacidade_liquida_kwh'] ?? '') ?>" min="0">
                            <?php if (isset($errors['capacidade_liquida_kwh'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['capacidade_liquida_kwh']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="saude_bateria_soh" class="form-label">Saúde da Bateria (SoH %) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="saude_bateria_soh" id="saude_bateria_soh" class="form-control <?= isset($errors['saude_bateria_soh']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['saude_bateria_soh'] ?? $complemento['saude_bateria_soh'] ?? '') ?>" min="0" max="100">
                            <?php if (isset($errors['saude_bateria_soh'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['saude_bateria_soh']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="garantia_bateria" class="form-label">Garantia da Bateria</label>
                            <input type="text" name="garantia_bateria" id="garantia_bateria" class="form-control <?= isset($errors['garantia_bateria']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['garantia_bateria'] ?? $complemento['garantia_bateria'] ?? '') ?>" maxlength="40">
                            <?php if (isset($errors['garantia_bateria'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['garantia_bateria']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Autonomia -->
                        <div class="col-md-4">
                            <label for="autonomia_wltp_km" class="form-label">Autonomia WLTP (km)</label>
                            <input type="number" name="autonomia_wltp_km" id="autonomia_wltp_km" class="form-control <?= isset($errors['autonomia_wltp_km']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['autonomia_wltp_km'] ?? $complemento['autonomia_wltp_km'] ?? '') ?>" min="0">
                            <?php if (isset($errors['autonomia_wltp_km'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['autonomia_wltp_km']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="autonomia_inmetro_km" class="form-label">Autonomia Inmetro (km) <span class="text-danger">*</span></label>
                            <input type="number" name="autonomia_inmetro_km" id="autonomia_inmetro_km" class="form-control <?= isset($errors['autonomia_inmetro_km']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['autonomia_inmetro_km'] ?? $complemento['autonomia_inmetro_km'] ?? '') ?>" min="0">
                            <?php if (isset($errors['autonomia_inmetro_km'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['autonomia_inmetro_km']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Carregamento DC -->
                        <div class="col-md-4">
                            <label for="potencia_max_dc_kw" class="form-label">Potência Máxima DC (kW) <span class="text-danger">*</span></label>
                            <input type="number" name="potencia_max_dc_kw" id="potencia_max_dc_kw" class="form-control <?= isset($errors['potencia_max_dc_kw']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['potencia_max_dc_kw'] ?? $complemento['potencia_max_dc_kw'] ?? '') ?>" min="0">
                            <?php if (isset($errors['potencia_max_dc_kw'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['potencia_max_dc_kw']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="tipo_conector_dc" class="form-label">Tipo de Conector DC <span class="text-danger">*</span></label>
                            <input type="text" name="tipo_conector_dc" id="tipo_conector_dc" class="form-control <?= isset($errors['tipo_conector_dc']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['tipo_conector_dc'] ?? $complemento['tipo_conector_dc'] ?? '') ?>" maxlength="20">
                            <?php if (isset($errors['tipo_conector_dc'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['tipo_conector_dc']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="tempo_carga_dc_min" class="form-label">Tempo de Carga DC (min)</label>
                            <input type="number" name="tempo_carga_dc_min" id="tempo_carga_dc_min" class="form-control <?= isset($errors['tempo_carga_dc_min']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['tempo_carga_dc_min'] ?? $complemento['tempo_carga_dc_min'] ?? '') ?>" min="0">
                            <?php if (isset($errors['tempo_carga_dc_min'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['tempo_carga_dc_min']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Carregamento AC (opcional) -->
                        <div class="col-md-4">
                            <label for="tipo_conector_ac" class="form-label">Tipo de Conector AC</label>
                            <input type="text" name="tipo_conector_ac" id="tipo_conector_ac" class="form-control <?= isset($errors['tipo_conector_ac']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['tipo_conector_ac'] ?? $complemento['tipo_conector_ac'] ?? '') ?>" maxlength="20">
                            <?php if (isset($errors['tipo_conector_ac'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['tipo_conector_ac']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Consumo energético (opcional) -->
                        <div class="col-md-4">
                            <label for="consumo_energetico_kwh_100km" class="form-label">Consumo Energético (kWh/100km)</label>
                            <input type="number" step="0.1" name="consumo_energetico_kwh_100km" id="consumo_energetico_kwh_100km" class="form-control <?= isset($errors['consumo_energetico_kwh_100km']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['consumo_energetico_kwh_100km'] ?? $complemento['consumo_energetico_kwh_100km'] ?? '') ?>" min="0">
                            <?php if (isset($errors['consumo_energetico_kwh_100km'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['consumo_energetico_kwh_100km']) ?></div>
                            <?php endif; ?>
                        </div>
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
                        <!-- Tipo de Híbrido -->
                        <div class="col-md-6">
                            <label for="tipo_hibrido" class="form-label">Tipo de Híbrido <span class="text-danger">*</span></label>
                            <select name="tipo" id="tipo_hibrido" class="form-select <?= isset($errors['tipo']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <option value="hev" <?= selected($old['tipo'] ?? $complemento['tipo'] ?? '', 'hev') ?>>HEV</option>
                                <option value="mhev" <?= selected($old['tipo'] ?? $complemento['tipo'] ?? '', 'mhev') ?>>MHEV</option>
                                <option value="phev" <?= selected($old['tipo'] ?? $complemento['tipo'] ?? '', 'phev') ?>>PHEV</option>
                            </select>
                            <?php if (isset($errors['tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['tipo']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Modo Elétrico Puro -->
                        <div class="col-md-6">
                            <label for="modo_eletrico_puro" class="form-label">Modo Elétrico Puro <span class="text-danger">*</span></label>
                            <select name="modo_eletrico_puro" id="modo_eletrico_puro" class="form-select <?= isset($errors['modo_eletrico_puro']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <option value="1" <?= selected($old['modo_eletrico_puro'] ?? $complemento['modo_eletrico_puro'] ?? '', 1) ?>>Sim</option>
                                <option value="0" <?= selected($old['modo_eletrico_puro'] ?? $complemento['modo_eletrico_puro'] ?? '', 0) ?>>Não</option>
                            </select>
                            <?php if (isset($errors['modo_eletrico_puro'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['modo_eletrico_puro']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== MOTOR A COMBUSTÃO ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-fuel-pump me-2"></i>Motor a Combustão</h6>
                        </div>

                        <!-- Combustível -->
                        <div class="col-md-6">
                            <label for="combustivel_hibrido" class="form-label">Combustível <span class="text-danger">*</span></label>
                            <select name="combustivel" id="combustivel_hibrido" class="form-select <?= isset($errors['combustivel']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <option value="alcool" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'alcool') ?>>Álcool</option>
                                <option value="diesel" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'diesel') ?>>Diesel</option>
                                <option value="flex" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'flex') ?>>Flex</option>
                                <option value="gasolina" <?= selected($old['combustivel'] ?? $complemento['combustivel'] ?? '', 'gasolina') ?>>Gasolina</option>
                            </select>
                            <?php if (isset($errors['combustivel'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['combustivel']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="motor_combustao_tipo" class="form-label">Motorização <span class="text-danger">*</span></label>
                            <select name="motor_combustao_tipo" id="motor_combustao_tipo" class="form-select <?= isset($errors['motor_combustao_tipo']) ? 'is-invalid' : '' ?>">
                                <option value="">Selecione</option>
                                <?php foreach (motorizacoes_list() as $valor): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= selected($old['motor_combustao_tipo'] ?? $complemento['motor_combustao_tipo'] ?? '', $valor) ?>>
                                        <?= htmlspecialchars($valor) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="outro" <?= selected($old['motor_combustao_tipo'] ?? $complemento['motor_combustao_tipo'] ?? '', 'outro') ?>>Outro (digitar)</option>
                            </select>
                            <?php if (isset($errors['motor_combustao_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['motor_combustao_tipo']) ?></div>
                            <?php endif; ?>
                            
                            <!-- Campo extra para "Outro" -->
                            <input type="text" name="motor_combustao_tipo_outro" id="motor_combustao_tipo_outro" class="form-control mt-2 <?= isset($errors['motor_combustao_tipo']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['motor_combustao_tipo_outro'] ?? '') ?>" 
                                   placeholder="Digite a motorização (ex: 1.8, 2.2, 3.0)" 
                                   style="display: none;">
                            <small class="text-muted">Ex: 1.0, 1.6, 2.0, etc.</small>
                        </div>

                        <div class="col-md-3">
                            <label for="motor_combustao_potencia_cv" class="form-label">Potência (cv) <span class="text-danger">*</span></label>
                            <input type="number" name="motor_combustao_potencia_cv" id="motor_combustao_potencia_cv" class="form-control <?= isset($errors['motor_combustao_potencia_cv']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['motor_combustao_potencia_cv'] ?? $complemento['motor_combustao_potencia_cv'] ?? '') ?>" min="0">
                            <?php if (isset($errors['motor_combustao_potencia_cv'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['motor_combustao_potencia_cv']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-3">
                            <label for="motor_combustao_torque_kgfm" class="form-label">Torque (kgfm)</label>
                            <input type="number" step="0.1" name="motor_combustao_torque_kgfm" id="motor_combustao_torque_kgfm" class="form-control <?= isset($errors['motor_combustao_torque_kgfm']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['motor_combustao_torque_kgfm'] ?? $complemento['motor_combustao_torque_kgfm'] ?? '') ?>" min="0">
                            <?php if (isset($errors['motor_combustao_torque_kgfm'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['motor_combustao_torque_kgfm']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== MOTOR ELÉTRICO ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-battery-charging me-2"></i>Motor Elétrico</h6>
                        </div>
                        <div class="col-md-6">
                            <label for="motor_eletrico_potencia_cv" class="form-label">Potência (cv) <span class="text-danger">*</span></label>
                            <input type="number" name="motor_eletrico_potencia_cv" id="motor_eletrico_potencia_cv" class="form-control <?= isset($errors['motor_eletrico_potencia_cv']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['motor_eletrico_potencia_cv'] ?? $complemento['motor_eletrico_potencia_cv'] ?? '') ?>" min="0">
                            <?php if (isset($errors['motor_eletrico_potencia_cv'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['motor_eletrico_potencia_cv']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="motor_eletrico_torque_kgfm" class="form-label">Torque (kgfm)</label>
                            <input type="number" step="0.1" name="motor_eletrico_torque_kgfm" id="motor_eletrico_torque_kgfm" class="form-control <?= isset($errors['motor_eletrico_torque_kgfm']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['motor_eletrico_torque_kgfm'] ?? $complemento['motor_eletrico_torque_kgfm'] ?? '') ?>" min="0">
                            <?php if (isset($errors['motor_eletrico_torque_kgfm'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['motor_eletrico_torque_kgfm']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== POTÊNCIA COMBINADA ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-arrow-left-right me-2"></i>Potência e Torque Combinados</h6>
                        </div>
                        <div class="col-md-6">
                            <label for="potencia_combinada_cv" class="form-label">Potência Combinada (cv) <span class="text-danger">*</span></label>
                            <input type="number" name="potencia_combinada_cv" id="potencia_combinada_cv" class="form-control <?= isset($errors['potencia_combinada_cv']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['potencia_combinada_cv'] ?? $complemento['potencia_combinada_cv'] ?? '') ?>" min="0">
                            <?php if (isset($errors['potencia_combinada_cv'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['potencia_combinada_cv']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="torque_combinado_kgfm" class="form-label">Torque Combinado (kgfm) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="torque_combinado_kgfm" id="torque_combinado_kgfm" class="form-control <?= isset($errors['torque_combinado_kgfm']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['torque_combinado_kgfm'] ?? $complemento['torque_combinado_kgfm'] ?? '') ?>" min="0">
                            <?php if (isset($errors['torque_combinado_kgfm'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['torque_combinado_kgfm']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== TRAÇÃO E TRANSMISSÃO ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-gear me-2"></i>Tração e Transmissão</h6>
                        </div>
                        <div class="col-md-4">
                            <label for="tracao_tipo_hibrido" class="form-label">Tipo de Tração <span class="text-danger">*</span></label>
                            <select name="tracao_tipo" id="tracao_tipo_hibrido" class="form-select <?= isset($errors['tracao_tipo']) ? 'is-invalid' : '' ?>">
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
                            <label for="transmissao_tipo_hibrido" class="form-label">Tipo de Transmissão <span class="text-danger">*</span></label>
                            <input type="text" name="transmissao_tipo" id="transmissao_tipo_hibrido" class="form-control <?= isset($errors['transmissao_tipo']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['transmissao_tipo'] ?? $complemento['transmissao_tipo'] ?? '') ?>" maxlength="30">
                            <?php if (isset($errors['transmissao_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['transmissao_tipo']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="numero_marchas_hibrido" class="form-label">Número de Marchas</label>
                            <input type="number" name="numero_marchas" id="numero_marchas_hibrido" class="form-control <?= isset($errors['numero_marchas']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['numero_marchas'] ?? $complemento['numero_marchas'] ?? '') ?>" min="0">
                            <?php if (isset($errors['numero_marchas'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['numero_marchas']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== BATERIA ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-battery me-2"></i>Bateria</h6>
                        </div>
                        <div class="col-md-4">
                            <label for="bateria_capacidade_kwh" class="form-label">Capacidade (kWh) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="bateria_capacidade_kwh" id="bateria_capacidade_kwh" class="form-control <?= isset($errors['bateria_capacidade_kwh']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['bateria_capacidade_kwh'] ?? $complemento['bateria_capacidade_kwh'] ?? '') ?>" min="0">
                            <?php if (isset($errors['bateria_capacidade_kwh'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['bateria_capacidade_kwh']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="bateria_tipo" class="form-label">Tipo da Bateria</label>
                            <input type="text" name="bateria_tipo" id="bateria_tipo" class="form-control <?= isset($errors['bateria_tipo']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['bateria_tipo'] ?? $complemento['bateria_tipo'] ?? '') ?>" maxlength="30">
                            <?php if (isset($errors['bateria_tipo'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['bateria_tipo']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="bateria_garantia" class="form-label">Garantia da Bateria</label>
                            <input type="text" name="bateria_garantia" id="bateria_garantia" class="form-control <?= isset($errors['bateria_garantia']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['bateria_garantia'] ?? $complemento['bateria_garantia'] ?? '') ?>" maxlength="40">
                            <?php if (isset($errors['bateria_garantia'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['bateria_garantia']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== AUTONOMIAS ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-speedometer me-2"></i>Autonomias</h6>
                        </div>
                        <div class="col-md-4">
                            <label for="autonomia_eletrica_pbev_km" class="form-label">Autonomia Elétrica (PBEV) (km)</label>
                            <input type="number" name="autonomia_eletrica_pbev_km" id="autonomia_eletrica_pbev_km" class="form-control phev-field <?= isset($errors['autonomia_eletrica_pbev_km']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['autonomia_eletrica_pbev_km'] ?? $complemento['autonomia_eletrica_pbev_km'] ?? '') ?>" min="0">
                            <?php if (isset($errors['autonomia_eletrica_pbev_km'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['autonomia_eletrica_pbev_km']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="autonomia_combinada_km" class="form-label">Autonomia Combinada (km)</label>
                            <input type="number" name="autonomia_combinada_km" id="autonomia_combinada_km" class="form-control phev-field <?= isset($errors['autonomia_combinada_km']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['autonomia_combinada_km'] ?? $complemento['autonomia_combinada_km'] ?? '') ?>" min="0">
                            <?php if (isset($errors['autonomia_combinada_km'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['autonomia_combinada_km']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== CARREGAMENTO (PHEV) ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-plug me-2"></i>Carregamento (específico para PHEV)</h6>
                        </div>
                        <div class="col-md-3">
                            <label for="carregamento_potencia_ac_kw" class="form-label">Potência AC (kW)</label>
                            <input type="number" step="0.1" name="carregamento_potencia_ac_kw" id="carregamento_potencia_ac_kw" class="form-control phev-field <?= isset($errors['carregamento_potencia_ac_kw']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['carregamento_potencia_ac_kw'] ?? $complemento['carregamento_potencia_ac_kw'] ?? '') ?>" min="0">
                            <?php if (isset($errors['carregamento_potencia_ac_kw'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['carregamento_potencia_ac_kw']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="carregamento_tempo_ac_horas" class="form-label">Tempo AC (horas)</label>
                            <input type="number" step="0.1" name="carregamento_tempo_ac_horas" id="carregamento_tempo_ac_horas" class="form-control phev-field <?= isset($errors['carregamento_tempo_ac_horas']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['carregamento_tempo_ac_horas'] ?? $complemento['carregamento_tempo_ac_horas'] ?? '') ?>" min="0">
                            <?php if (isset($errors['carregamento_tempo_ac_horas'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['carregamento_tempo_ac_horas']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="carregamento_potencia_dc_kw" class="form-label">Potência DC (kW)</label>
                            <input type="number" name="carregamento_potencia_dc_kw" id="carregamento_potencia_dc_kw" class="form-control phev-field <?= isset($errors['carregamento_potencia_dc_kw']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['carregamento_potencia_dc_kw'] ?? $complemento['carregamento_potencia_dc_kw'] ?? '') ?>" min="0">
                            <?php if (isset($errors['carregamento_potencia_dc_kw'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['carregamento_potencia_dc_kw']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="carregamento_tipo_conector_ac" class="form-label">Tipo de Conector AC</label>
                            <input type="text" name="carregamento_tipo_conector_ac" id="carregamento_tipo_conector_ac" class="form-control phev-field <?= isset($errors['carregamento_tipo_conector_ac']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['carregamento_tipo_conector_ac'] ?? $complemento['carregamento_tipo_conector_ac'] ?? '') ?>" maxlength="20">
                            <?php if (isset($errors['carregamento_tipo_conector_ac'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['carregamento_tipo_conector_ac']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- ===== CONSUMO E TANQUE ===== -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-secondary"><i class="bi bi-fuel-pump me-2"></i>Consumo e Tanque</h6>
                        </div>
                        <div class="col-md-3">
                            <label for="consumo_cidade_kml_hibrido" class="form-label">Consumo Cidade (km/l) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="consumo_cidade_kml" id="consumo_cidade_kml_hibrido" class="form-control <?= isset($errors['consumo_cidade_kml']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['consumo_cidade_kml'] ?? $complemento['consumo_cidade_kml'] ?? '') ?>" min="0">
                            <?php if (isset($errors['consumo_cidade_kml'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['consumo_cidade_kml']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="consumo_estrada_kml_hibrido" class="form-label">Consumo Estrada (km/l) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="consumo_estrada_kml" id="consumo_estrada_kml_hibrido" class="form-control <?= isset($errors['consumo_estrada_kml']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['consumo_estrada_kml'] ?? $complemento['consumo_estrada_kml'] ?? '') ?>" min="0">
                            <?php if (isset($errors['consumo_estrada_kml'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['consumo_estrada_kml']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="consumo_medio_kml" class="form-label">Consumo Médio (km/l)</label>
                            <input type="number" step="0.1" name="consumo_medio_kml" id="consumo_medio_kml" class="form-control <?= isset($errors['consumo_medio_kml']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['consumo_medio_kml'] ?? $complemento['consumo_medio_kml'] ?? '') ?>" min="0">
                            <?php if (isset($errors['consumo_medio_kml'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['consumo_medio_kml']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="capacidade_tanque_l_hibrido" class="form-label">Capacidade Tanque (L) <span class="text-danger">*</span></label>
                            <input type="number" name="capacidade_tanque_l" id="capacidade_tanque_l_hibrido" class="form-control <?= isset($errors['capacidade_tanque_l']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['capacidade_tanque_l'] ?? $complemento['capacidade_tanque_l'] ?? '') ?>" min="0">
                            <?php if (isset($errors['capacidade_tanque_l'])): ?>
                                <div class="invalid-feedback"><?= implode(', ', $errors['capacidade_tanque_l']) ?></div>
                            <?php endif; ?>
                        </div>
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

    <!-- ========== MODAL MARCA E MODELO ========== -->
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
                                <!-- Itens serão carregados via PHP + JS -->
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="adicionarMarcaBtn">
                                    <i class="bi bi-plus-lg me-1"></i> Adicionar Nova Marca
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
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

            document.querySelectorAll('.categoria-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tipo = this.dataset.tipo;
                    document.getElementById('tipo_veiculo').value = tipo;
                    mostrarCampos(tipo);
                    modal.hide();
                });
            });

            document.getElementById('categoriaModal').addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        <?php else: ?>
            const tipoAtual = '<?= addslashes($tipoAtual) ?>';
            if (tipoAtual) {
                mostrarCampos(tipoAtual);
                if (tipoAtual === 'combustao') {
                    document.querySelector('.gnv-field').style.display = 'block';
                }
            }
        <?php endif; ?>

        // =============================================
        // 2. FUNÇÃO PARA MOSTRAR/OCULTAR CAMPOS
        // =============================================
        function mostrarCampos(tipo) {
            document.getElementById('campos-combustao').style.display = 'none';
            document.getElementById('campos-eletrico').style.display = 'none';
            document.getElementById('campos-hibrido').style.display = 'none';
            document.querySelector('.gnv-field').style.display = 'none';

            if (tipo === 'combustao') {
                document.getElementById('campos-combustao').style.display = 'block';
                document.querySelector('.gnv-field').style.display = 'block';
                toggleFlexFields();
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
            toggleFlexFields();
        }

        // =============================================
        // 4. FUNÇÕES GENÉRICAS PARA "OUTRO" (MOTORIZAÇÃO)
        // =============================================
        /**
         * Mostra/oculta o campo extra para "Outro" e limpa seu valor se não for "outro".
         * @param {string} selectId - ID do elemento <select>
         * @param {string} outroInputId - ID do campo de texto extra
         */
        function toggleMotorOutro(selectId, outroInputId) {
            const select = document.getElementById(selectId);
            const outroInput = document.getElementById(outroInputId);
            if (!select || !outroInput) return;
            const isOutro = select.value === 'outro';
            outroInput.style.display = isOutro ? 'block' : 'none';
            if (!isOutro) outroInput.value = '';
        }

        /**
         * Configura o comportamento do "Outro" para um par select + campo extra.
         * @param {string} selectId - ID do <select>
         * @param {string} outroInputId - ID do campo de texto extra
         * @param {string[]} motorizacoesList - Array com os valores da lista de motorizações
         */
        function setupMotorOutro(selectId, outroInputId, motorizacoesList) {
            const select = document.getElementById(selectId);
            const outroInput = document.getElementById(outroInputId);
            if (!select || !outroInput) return;

            // Event listener para mudança no select
            select.addEventListener('change', function() {
                toggleMotorOutro(selectId, outroInputId);
            });

            // Lógica para edição: se o valor atual não estiver na lista, seleciona "outro" e preenche o campo extra
            const valorAtual = select.value;
            if (valorAtual && valorAtual !== 'outro' && motorizacoesList) {
                if (!motorizacoesList.includes(valorAtual)) {
                    select.value = 'outro';
                    outroInput.value = valorAtual;
                    toggleMotorOutro(selectId, outroInputId);
                }
            }

            // Execução inicial
            toggleMotorOutro(selectId, outroInputId);
        }

        /**
         * Prepara o campo para submissão: se "outro" estiver selecionado, copia o valor do campo extra para o select.
         * Retorna false se o campo extra estiver vazio (para impedir submissão).
         * @param {string} selectId
         * @param {string} outroInputId
         * @returns {boolean}
         */
        function prepareSubmit(selectId, outroInputId) {
            const select = document.getElementById(selectId);
            const outroInput = document.getElementById(outroInputId);
            if (!select || !outroInput) return true;
            if (select.value === 'outro') {
                const valor = outroInput.value.trim();
                if (valor === '') {
                    return false;
                }
                select.value = valor;
            }
            return true;
        }

        // =============================================
        // CONFIGURAR "OUTRO" PARA COMBUSTÃO
        // =============================================
        setupMotorOutro('motor_tipo', 'motor_tipo_outro', CONFIG.motorizacoes);

        // =============================================
        // CONFIGURAR "OUTRO" PARA HÍBRIDO
        // =============================================
        setupMotorOutro('motor_combustao_tipo', 'motor_combustao_tipo_outro', CONFIG.motorizacoes);

        // =============================================
        // 5. VALIDAÇÃO DE GNV (exibe campos extras)
        // =============================================
        const gnvCheckbox = document.getElementById('gnv_instalado');
        const gnvBloco = document.getElementById('bloco-gnv');

        if (gnvCheckbox && gnvBloco) {
            function toggleGNV() {
                gnvBloco.style.display = gnvCheckbox.checked ? 'block' : 'none';
            }
            gnvCheckbox.addEventListener('change', toggleGNV);
            // Executa na inicialização (para edição)
            toggleGNV();
        }

        // =============================================
        // 6. PREPARAR SUBMISSÃO (garantir que os valores de "Outro" sejam enviados corretamente)
        // =============================================
        const form = document.getElementById('veiculoForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Validar e preparar campo de combustão
                if (!prepareSubmit('motor_tipo', 'motor_tipo_outro')) {
                    e.preventDefault();
                    alert('Por favor, digite a motorização personalizada para o motor a combustão.');
                    document.getElementById('motor_tipo_outro').focus();
                    return;
                }
                // Validar e preparar campo do híbrido
                if (!prepareSubmit('motor_combustao_tipo', 'motor_combustao_tipo_outro')) {
                    e.preventDefault();
                    alert('Por favor, digite a motorização personalizada para o motor a combustão (híbrido).');
                    document.getElementById('motor_combustao_tipo_outro').focus();
                    return;
                }
                // O formulário segue normalmente
            });
        }

        // =============================================
        // 7. APLICAR REGRAS CONDICIONAIS PARA HÍBRIDOS
        // =============================================
        const tipoHibridoSelect = document.getElementById('tipo_hibrido');
        const modoEletricoPuro = document.getElementById('modo_eletrico_puro');
        const phevFields = document.querySelectorAll('.phev-field');

        function aplicarRegrasHibrido(tipo) {
            if (!tipo || !CONFIG.regrasHibrido || !CONFIG.regrasHibrido[tipo]) {
                if (modoEletricoPuro) {
                    modoEletricoPuro.closest('.col-md-6').style.display = 'block';
                    modoEletricoPuro.disabled = false;
                }
                phevFields.forEach(field => {
                    const container = field.closest('.col-md-3, .col-md-4, .col-md-6');
                    if (container) {
                        container.style.display = 'block';
                    }
                });
                return;
            }

            const regras = CONFIG.regrasHibrido[tipo];

            if (modoEletricoPuro && regras.modo_eletrico_puro) {
                const regra = regras.modo_eletrico_puro;
                const container = modoEletricoPuro.closest('.col-md-6');

                if (regra.visivel) {
                    container.style.display = 'block';
                    modoEletricoPuro.disabled = false;
                    if (regra.forcar_valor !== null) {
                        modoEletricoPuro.value = regra.forcar_valor;
                        modoEletricoPuro.disabled = true;
                    }
                } else {
                    container.style.display = 'none';
                    if (regra.forcar_valor !== null) {
                        modoEletricoPuro.value = regra.forcar_valor;
                    }
                    modoEletricoPuro.disabled = true;
                }
            }

            const phevVisivel = regras.campos_phev?.visivel ?? false;
            phevFields.forEach(field => {
                const container = field.closest('.col-md-3, .col-md-4, .col-md-6');
                if (container) {
                    container.style.display = phevVisivel ? 'block' : 'none';
                }
            });
        }

        if (tipoHibridoSelect) {
            tipoHibridoSelect.addEventListener('change', function() {
                aplicarRegrasHibrido(this.value);
            });

            const tipoInicial = tipoHibridoSelect.value;
            if (tipoInicial) {
                aplicarRegrasHibrido(tipoInicial);
            } else {
                aplicarRegrasHibrido('');
            }
        }

        // ============================================================
        // MODAL DE SELEÇÃO DE MARCA E MODELO
        // ============================================================

        // Referências DOM
        const modalMarcaModelo = document.getElementById('marcaModeloModal');
        const etapaMarca = document.getElementById('etapa-marca');
        const etapaModelo = document.getElementById('etapa-modelo');
        const etapaResumo = document.getElementById('etapa-resumo');
        const listaMarcas = document.getElementById('lista-marcas');
        const listaModelos = document.getElementById('lista-modelos');
        const buscaMarca = document.getElementById('buscaMarca');
        const buscaModelo = document.getElementById('buscaModelo');
        const voltarMarcaBtn = document.getElementById('voltarMarcaBtn');
        const confirmarBtn = document.getElementById('confirmarSelecaoBtn');
        const resumoMarcaNome = document.getElementById('resumo-marca-nome');
        const resumoModeloNome = document.getElementById('resumo-modelo-nome');
        const resumoMarcaLogo = document.getElementById('resumo-marca-logo');
        const resumoMarcaCard = document.getElementById('resumo-marca');
        const resumoModeloCard = document.getElementById('resumo-modelo');
        const marcaDisplay = document.getElementById('marcaDisplay');
        const modeloDisplay = document.getElementById('modeloDisplay');
        const marcaIdInput = document.getElementById('marca_id');
        const modeloIdInput = document.getElementById('modelo_id');

        // Dados da seleção
        let selectedMarcaId = null;
        let selectedMarcaNome = '';
        let selectedMarcaLogo = '';
        let selectedModeloId = null;
        let selectedModeloNome = '';

        // Inicialização: carregar marcas via PHP
        const marcasData = <?= json_encode($marcas) ?>;

        // Função para renderizar lista de marcas
        function renderMarcas(filtro = '') {
            const filtroLower = filtro.toLowerCase().trim();
            const filtered = marcasData.filter(m => 
                m.nome.toLowerCase().includes(filtroLower)
            );
            listaMarcas.innerHTML = '';
            if (filtered.length === 0) {
                listaMarcas.innerHTML = '<div class="text-center text-muted py-3">Nenhuma marca encontrada.</div>';
                return;
            }
            filtered.forEach(m => {
                const div = document.createElement('div');
                div.className = 'item-lista';
                if (selectedMarcaId === m.id) div.classList.add('selecionado');
                div.innerHTML = `
                    <img src="${m.logo_url || '/assets/images/default-brand.png'}" alt="${m.nome}">
                    <span class="nome">${m.nome}</span>
                `;
                div.addEventListener('click', function() {
                    listaMarcas.querySelectorAll('.item-lista').forEach(el => el.classList.remove('selecionado'));
                    this.classList.add('selecionado');
                    selectedMarcaId = m.id;
                    selectedMarcaNome = m.nome;
                    selectedMarcaLogo = m.logo_url || '/assets/images/default-brand.png';
                    carregarModelos(m.id);
                    irParaEtapa('modelo');
                });
                listaMarcas.appendChild(div);
            });
        }

        // Função para carregar modelos via AJAX
        function carregarModelos(marcaId) {
            listaModelos.innerHTML = '<div class="text-center text-muted py-3">Carregando modelos...</div>';
            fetch(`/api/modelos?marca_id=${marcaId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.sucesso || !data.dados || data.dados.length === 0) {
                        listaModelos.innerHTML = '<div class="text-center text-muted py-3">Nenhum modelo encontrado para esta marca.</div>';
                        return;
                    }
                    const modelos = data.dados;
                    renderModelos(modelos);
                })
                .catch(err => {
                    listaModelos.innerHTML = '<div class="text-center text-danger py-3">Erro ao carregar modelos.</div>';
                    console.error(err);
                });
        }

        function renderModelos(modelos, filtro = '') {
            const filtroLower = filtro.toLowerCase().trim();
            const filtered = modelos.filter(m => 
                m.nome.toLowerCase().includes(filtroLower)
            );
            listaModelos.innerHTML = '';
            if (filtered.length === 0) {
                listaModelos.innerHTML = '<div class="text-center text-muted py-3">Nenhum modelo encontrado.</div>';
                return;
            }
            filtered.forEach(m => {
                const div = document.createElement('div');
                div.className = 'item-lista';
                if (selectedModeloId === m.id) div.classList.add('selecionado');
                div.innerHTML = `<span class="nome">${m.nome}</span>`;
                div.addEventListener('click', function() {
                    listaModelos.querySelectorAll('.item-lista').forEach(el => el.classList.remove('selecionado'));
                    this.classList.add('selecionado');
                    selectedModeloId = m.id;
                    selectedModeloNome = m.nome;
                    atualizarResumo();
                    irParaEtapa('resumo');
                });
                listaModelos.appendChild(div);
            });
        }

        // Atualizar resumo
        function atualizarResumo() {
            resumoMarcaNome.textContent = selectedMarcaNome || 'Nenhuma';
            resumoModeloNome.textContent = selectedModeloNome || 'Nenhum';
            const logoImg = resumoMarcaLogo.querySelector('img');
            if (logoImg) {
                logoImg.src = selectedMarcaLogo || '/assets/images/default-brand.png';
            }
        }

        // Navegação entre etapas
        function irParaEtapa(etapa) {
            etapaMarca.style.display = 'none';
            etapaModelo.style.display = 'none';
            etapaResumo.style.display = 'none';
            if (etapa === 'marca') {
                etapaMarca.style.display = 'block';
                buscaMarca.value = '';
                renderMarcas();
                setTimeout(() => buscaMarca.focus(), 100);
            } else if (etapa === 'modelo') {
                etapaModelo.style.display = 'block';
                buscaModelo.value = '';
                if (window._modelosData) {
                    renderModelos(window._modelosData);
                }
                setTimeout(() => buscaModelo.focus(), 100);
            } else if (etapa === 'resumo') {
                etapaResumo.style.display = 'block';
                atualizarResumo();
            }
        }

        // Event listeners
        if (buscaMarca) {
            buscaMarca.addEventListener('input', function() {
                renderMarcas(this.value);
            });
        }

        if (buscaModelo) {
            buscaModelo.addEventListener('input', function() {
                const modelosAtuais = window._modelosData || [];
                renderModelos(modelosAtuais, this.value);
            });
        }

        if (voltarMarcaBtn) {
            voltarMarcaBtn.addEventListener('click', function() {
                irParaEtapa('marca');
            });
        }

        if (resumoMarcaCard) {
            resumoMarcaCard.addEventListener('click', function() {
                irParaEtapa('marca');
            });
        }
        if (resumoModeloCard) {
            resumoModeloCard.addEventListener('click', function() {
                irParaEtapa('modelo');
            });
        }

        // Confirmar seleção
        if (confirmarBtn) {
            confirmarBtn.addEventListener('click', function() {
                if (!selectedMarcaId || !selectedModeloId) {
                    alert('Por favor, selecione uma marca e um modelo.');
                    return;
                }
                marcaIdInput.value = selectedMarcaId;
                modeloIdInput.value = selectedModeloId;
                marcaDisplay.textContent = selectedMarcaNome;
                marcaDisplay.className = 'badge bg-primary p-2';
                modeloDisplay.textContent = selectedModeloNome;
                modeloDisplay.className = 'badge bg-primary p-2';
                const modalInstance = bootstrap.Modal.getInstance(modalMarcaModelo);
                if (modalInstance) modalInstance.hide();
            });
        }

        // Abrir modal
        const btnSelecionar = document.getElementById('selecionarMarcaModeloBtn');
        if (btnSelecionar) {
            btnSelecionar.addEventListener('click', function() {
                const currentMarcaId = parseInt(marcaIdInput.value);
                const currentModeloId = parseInt(modeloIdInput.value);
                if (currentMarcaId) {
                    const marca = marcasData.find(m => m.id === currentMarcaId);
                    if (marca) {
                        selectedMarcaId = currentMarcaId;
                        selectedMarcaNome = marca.nome;
                        selectedMarcaLogo = marca.logo_url || '/assets/images/default-brand.png';
                    }
                }
                renderMarcas();
                irParaEtapa('marca');
                const modalInstance = new bootstrap.Modal(modalMarcaModelo);
                modalInstance.show();
            });
        }

        modalMarcaModelo.addEventListener('hidden.bs.modal', function() {
            // Opcional
        });

        // ============================================================
        // CONTROLE DOS FORMULÁRIOS DE ADIÇÃO (Marca e Modelo)
        // ============================================================

        // ----- Marca -----
        const adicionarMarcaBtn = document.getElementById('adicionarMarcaBtn');
        const cancelarNovaMarcaBtn = document.getElementById('cancelarNovaMarcaBtn');
        const conteudoMarcaLista = document.getElementById('conteudo-marca-lista');
        const conteudoMarcaForm = document.getElementById('conteudo-marca-form');

        if (adicionarMarcaBtn) {
            adicionarMarcaBtn.addEventListener('click', function() {
                conteudoMarcaLista.style.display = 'none';
                conteudoMarcaForm.style.display = 'block';
                // Limpar campos ao abrir
                document.getElementById('novaMarcaNome').value = '';
                document.getElementById('novaMarcaLogo').value = '';
                document.getElementById('previewMarcaLogo').style.display = 'none';
            });
        }

        if (cancelarNovaMarcaBtn) {
            cancelarNovaMarcaBtn.addEventListener('click', function() {
                conteudoMarcaLista.style.display = 'block';
                conteudoMarcaForm.style.display = 'none';
                // Limpar campos ao cancelar
                document.getElementById('novaMarcaNome').value = '';
                document.getElementById('novaMarcaLogo').value = '';
                document.getElementById('previewMarcaLogo').style.display = 'none';
            });
        }

        // ----- Modelo -----
        const adicionarModeloBtn = document.getElementById('adicionarModeloBtn');
        const cancelarNovoModeloBtn = document.getElementById('cancelarNovoModeloBtn');
        const conteudoModeloLista = document.getElementById('conteudo-modelo-lista');
        const conteudoModeloForm = document.getElementById('conteudo-modelo-form');

        if (adicionarModeloBtn) {
            adicionarModeloBtn.addEventListener('click', function() {
                conteudoModeloLista.style.display = 'none';
                conteudoModeloForm.style.display = 'block';
                document.getElementById('novoModeloNome').value = '';
            });
        }

        if (cancelarNovoModeloBtn) {
            cancelarNovoModeloBtn.addEventListener('click', function() {
                conteudoModeloLista.style.display = 'block';
                conteudoModeloForm.style.display = 'none';
                document.getElementById('novoModeloNome').value = '';
            });
        }

        // ============================================================
        // CONVERSÃO DE IMAGEM PARA WEBP 64x64 (Marca)
        // ============================================================
        const inputLogo = document.getElementById('novaMarcaLogo');
        const previewContainer = document.getElementById('previewMarcaLogo');
        const previewImg = document.getElementById('previewMarcaImg');
        let imagemConvertidaBlob = null;

        if (inputLogo) {
            inputLogo.addEventListener('change', function(e) {
                const file = this.files[0];
                if (!file) return;

                // Valida se é uma imagem
                if (!file.type.startsWith('image/')) {
                    alert('Por favor, selecione um arquivo de imagem válido.');
                    this.value = '';
                    return;
                }

                // Limpa preview anterior
                previewContainer.style.display = 'none';
                previewImg.src = '#';
                imagemConvertidaBlob = null;

                // Lê o arquivo como DataURL
                const reader = new FileReader();
                reader.onload = function(event) {
                    const dataUrl = event.target.result;

                    // Cria uma imagem para obter dimensões
                    const img = new Image();
                    img.onload = function() {
                        try {
                            // 1. Configura canvas 64x64
                            const canvas = document.createElement('canvas');
                            canvas.width = 64;
                            canvas.height = 64;
                            const ctx = canvas.getContext('2d');

                            // 2. Calcula crop centralizado 1:1
                            const size = Math.min(img.width, img.height);
                            const sx = (img.width - size) / 2;
                            const sy = (img.height - size) / 2;

                            // 3. Desenha a imagem recortada e redimensionada
                            ctx.drawImage(img, sx, sy, size, size, 0, 0, 64, 64);

                            // 4. Converte para WebP (qualidade 0.9)
                            canvas.toBlob(function(blob) {
                                if (!blob) {
                                    alert('Erro ao converter imagem para WebP. Tente novamente.');
                                    return;
                                }

                                // 5. Armazena o Blob para envio posterior
                                imagemConvertidaBlob = blob;

                                // 6. Exibe preview da imagem convertida
                                const previewUrl = URL.createObjectURL(blob);
                                previewImg.src = previewUrl;
                                previewContainer.style.display = 'block';

                                console.log('Imagem convertida com sucesso:', {
                                    tamanho: (blob.size / 1024).toFixed(2) + ' KB',
                                    tipo: blob.type
                                });

                            }, 'image/webp', 0.9);

                        } catch (err) {
                            alert('Erro ao processar a imagem: ' + err.message);
                            inputLogo.value = '';
                        }
                    };

                    img.onerror = function() {
                        alert('Erro ao carregar a imagem. Tente novamente.');
                        inputLogo.value = '';
                    };

                    img.src = dataUrl;
                };

                reader.onerror = function() {
                    alert('Erro ao ler o arquivo. Tente novamente.');
                    inputLogo.value = '';
                };

                reader.readAsDataURL(file);
            });
        }

        // ============================================================
        // CONTROLE DE SPINNER E ESTADO DOS BOTÕES
        // ============================================================
        function showSpinner(btnId, spinnerId, textId, loadingText = 'Carregando...') {
            const btn = document.getElementById(btnId);
            const spinner = document.getElementById(spinnerId);
            const text = document.getElementById(textId);
            if (btn) btn.disabled = true;
            if (spinner) spinner.style.display = 'inline-block';
            if (text) text.textContent = loadingText;
        }

        function hideSpinner(btnId, spinnerId, textId, originalText = 'Cadastrar') {
            const btn = document.getElementById(btnId);
            const spinner = document.getElementById(spinnerId);
            const text = document.getElementById(textId);
            if (btn) btn.disabled = false;
            if (spinner) spinner.style.display = 'none';
            if (text) text.textContent = originalText;
        }

        // ============================================================
        // 5. ENVIO VIA AJAX – CRIAÇÃO DE MARCA
        // ============================================================
        const formMarca = document.getElementById('formNovaMarca');
        if (formMarca) {
            formMarca.addEventListener('submit', function(e) {
                e.preventDefault();

                // 1. Validação do campo nome
                const nomeInput = document.getElementById('novaMarcaNome');
                const nome = nomeInput.value.trim();
                if (nome === '') {
                    alert('O nome da marca é obrigatório.');
                    nomeInput.focus();
                    return;
                }

                // 2. Verifica se a imagem foi convertida (se o usuário selecionou um arquivo)
                // Se o usuário selecionou um arquivo, mas a conversão falhou, o blob pode ser null
                // Se não selecionou arquivo, o blob será null e está tudo bem (logo opcional)
                // Se selecionou e o blob é null, significa que a conversão falhou
                const fileInput = document.getElementById('novaMarcaLogo');
                if (fileInput.files.length > 0 && !imagemConvertidaBlob) {
                    alert('A imagem ainda está sendo processada. Aguarde um momento ou selecione novamente.');
                    return;
                }

                // 3. Monta FormData
                const formData = new FormData();
                formData.append('nome', nome);

                // Se houver imagem convertida, adiciona ao FormData
                if (imagemConvertidaBlob) {
                    // Cria um File a partir do Blob com nome e tipo adequados
                    const file = new File([imagemConvertidaBlob], 'logo.webp', { type: 'image/webp' });
                    formData.append('logo', file);
                }

                // 4. Exibe spinner
                showSpinner('salvarNovaMarcaBtn', 'spinnerMarca', 'textoMarcaBtn', 'Cadastrando...');

                // 5. Envia via fetch
                fetch('/api/marcas', {
                    method: 'POST',
                    body: formData,
                    // Não define Content-Type – o browser define com boundary automaticamente
                })
                .then(response => response.json())
                .then(data => {
                    // Esconde spinner
                    hideSpinner('salvarNovaMarcaBtn', 'spinnerMarca', 'textoMarcaBtn', 'Cadastrar');

                    if (data.sucesso) {
                        // 6. Sucesso: mensagem positiva
                        alert('✅ Marca criada com sucesso!');

                        // 7. Atualiza a lista local de marcas
                        const novaMarca = data.dados;
                        marcasData.push(novaMarca);
                        // Ordena por nome
                        marcasData.sort((a, b) => a.nome.localeCompare(b.nome));

                        // 8. Pré-seleciona a nova marca
                        selectedMarcaId = novaMarca.id;
                        selectedMarcaNome = novaMarca.nome;
                        selectedMarcaLogo = novaMarca.logo_url || '/assets/images/default-brand.png';

                        // 9. Re-renderiza a lista
                        renderMarcas();

                        // 10. Limpa formulário e volta para a lista
                        document.getElementById('novaMarcaNome').value = '';
                        document.getElementById('novaMarcaLogo').value = '';
                        document.getElementById('previewMarcaLogo').style.display = 'none';
                        imagemConvertidaBlob = null;
                        conteudoMarcaForm.style.display = 'none';
                        conteudoMarcaLista.style.display = 'block';

                        // 11. Marca o item como selecionado na lista
                        const itens = listaMarcas.querySelectorAll('.item-lista');
                        itens.forEach(item => {
                            const nomeItem = item.querySelector('.nome')?.textContent;
                            if (nomeItem === selectedMarcaNome) {
                                item.classList.add('selecionado');
                            }
                        });

                    } else {
                        // 12. Erro: exibe mensagem
                        const erro = data.erro || 'Erro ao criar marca.';
                        alert('❌ ' + erro);
                    }
                })
                .catch(error => {
                    // 13. Erro de rede
                    hideSpinner('salvarNovaMarcaBtn', 'spinnerMarca', 'textoMarcaBtn', 'Cadastrar');
                    alert('❌ Erro de conexão. Tente novamente.');
                    console.error('Erro ao criar marca:', error);
                });
            });
        }


        // ============================================================
        // 6. ENVIO VIA AJAX – CRIAÇÃO DE MODELO
        // ============================================================
        const formModelo = document.getElementById('formNovoModelo');
        if (formModelo) {
            formModelo.addEventListener('submit', function(e) {
                e.preventDefault();

                // 1. Validação do campo nome
                const nomeInput = document.getElementById('novoModeloNome');
                const nome = nomeInput.value.trim();
                if (nome === '') {
                    alert('O nome do modelo é obrigatório.');
                    nomeInput.focus();
                    return;
                }

                // 2. Verifica se há uma marca selecionada
                if (!selectedMarcaId) {
                    alert('Selecione uma marca antes de adicionar um modelo.');
                    return;
                }

                // 3. Monta FormData
                const formData = new FormData();
                formData.append('marca_id', selectedMarcaId);
                formData.append('nome', nome);

                // 4. Exibe spinner
                showSpinner('salvarNovoModeloBtn', 'spinnerModelo', 'textoModeloBtn', 'Cadastrando...');

                // 5. Envia via fetch
                fetch('/api/modelos', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    // Esconde spinner
                    hideSpinner('salvarNovoModeloBtn', 'spinnerModelo', 'textoModeloBtn', 'Cadastrar');

                    if (data.sucesso) {
                        // 6. Sucesso: mensagem positiva
                        alert('✅ Modelo criado com sucesso!');

                        // 7. Atualiza a lista local de modelos
                        const novoModelo = data.dados;
                        if (!window._modelosData) {
                            window._modelosData = [];
                        }
                        window._modelosData.push(novoModelo);
                        window._modelosData.sort((a, b) => a.nome.localeCompare(b.nome));

                        // 8. Pré-seleciona o novo modelo
                        selectedModeloId = novoModelo.id;
                        selectedModeloNome = novoModelo.nome;

                        // 9. Re-renderiza a lista de modelos
                        renderModelos(window._modelosData);

                        // 10. Limpa formulário e volta para a lista
                        document.getElementById('novoModeloNome').value = '';
                        conteudoModeloForm.style.display = 'none';
                        conteudoModeloLista.style.display = 'block';

                        // 11. Marca o item como selecionado na lista
                        const itens = listaModelos.querySelectorAll('.item-lista');
                        itens.forEach(item => {
                            const nomeItem = item.querySelector('.nome')?.textContent;
                            if (nomeItem === selectedModeloNome) {
                                item.classList.add('selecionado');
                            }
                        });

                        // Atualiza resumo (caso o usuário confirme depois)
                        atualizarResumo();

                    } else {
                        // 12. Erro: exibe mensagem
                        const erro = data.erro || 'Erro ao criar modelo.';
                        alert('❌ ' + erro);
                    }
                })
                .catch(error => {
                    // 13. Erro de rede
                    hideSpinner('salvarNovoModeloBtn', 'spinnerModelo', 'textoModeloBtn', 'Cadastrar');
                    alert('❌ Erro de conexão. Tente novamente.');
                    console.error('Erro ao criar modelo:', error);
                });
            });
        }

        // =============================================
        // CONFIGURAR "OUTRO" PARA COR (simples, sem lista)
        // =============================================
        function setupCorOutro() {
            const select = document.getElementById('cor');
            const outroInput = document.getElementById('cor_outro');
            if (!select || !outroInput) return;

            // Event listener para mudança no select
            select.addEventListener('change', function() {
                const isOutro = select.value === 'outro';
                outroInput.style.display = isOutro ? 'block' : 'none';
                if (!isOutro) outroInput.value = '';
            });

            // Na edição, se o valor salvo não estiver na lista, seleciona "outro" e preenche
            const valorAtual = select.value;
            if (valorAtual && valorAtual !== 'outro') {
                // Verifica se o valor existe na lista de opções (excluindo "outro")
                const options = Array.from(select.options).map(o => o.value);
                if (!options.includes(valorAtual)) {
                    select.value = 'outro';
                    outroInput.value = valorAtual;
                    outroInput.style.display = 'block';
                }
            }

            // Execução inicial
            if (select.value === 'outro') {
                outroInput.style.display = 'block';
            } else {
                outroInput.style.display = 'none';
            }
        }

        // Chamar a função no DOMContentLoaded
        setupCorOutro();

        // ============================================================
        // DROPDOWN DE CORES (abrir ao clicar no input ou botão)
        // ============================================================
        const corInput = document.getElementById('corInput');
        const btnAbrir = document.getElementById('btnAbrirCores');
        const dropdown = document.getElementById('dropdownCores');
        const corHidden = document.getElementById('corSelecionada');
        const corOutro = document.getElementById('cor_outro');
        const corItems = document.querySelectorAll('.cor-item');

        // Função para abrir/fechar dropdown
        function toggleDropdown() {
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
        }

        // Abrir ao clicar no input ou no botão
        if (corInput) {
            corInput.addEventListener('click', toggleDropdown);
        }
        if (btnAbrir) {
            btnAbrir.addEventListener('click', toggleDropdown);
        }

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            const target = e.target;
            if (!target.closest('#corInput') && !target.closest('#btnAbrirCores') && !target.closest('#dropdownCores')) {
                dropdown.style.display = 'none';
            }
        });

        // Selecionar cor ao clicar em um item da lista
        if (corItems.length) {
            corItems.forEach(item => {
                item.addEventListener('click', function() {
                    const cor = this.dataset.cor;
                    const hex = this.dataset.hex;

                    // Atualiza o campo de exibição
                    corInput.value = cor === 'outro' ? 'Outro (digitar)' : cor;

                    // Atualiza o campo oculto
                    corHidden.value = cor;

                    // Remove destaque de todos
                    corItems.forEach(el => el.style.backgroundColor = '');
                    this.style.backgroundColor = '#e9ecef';

                    // Controla campo "Outro"
                    if (cor === 'outro') {
                        corOutro.style.display = 'block';
                        corOutro.focus();
                    } else {
                        corOutro.style.display = 'none';
                        corOutro.value = '';
                    }

                    // Fecha o dropdown
                    dropdown.style.display = 'none';
                });
            });
        }

        // Se houver valor salvo, destaca o item correspondente e atualiza o input
        if (corHidden.value) {
            const valorSalvo = corHidden.value;
            if (valorSalvo === 'outro') {
                corInput.value = 'Outro (digitar)';
                corOutro.style.display = 'block';
            } else {
                corInput.value = valorSalvo;
            }
            corItems.forEach(item => {
                if (item.dataset.cor === valorSalvo) {
                    item.style.backgroundColor = '#e9ecef';
                }
            });
        }

        // ============================================================
        // FORMATAÇÃO DE QUILOMETRAGEM COM PONTOS DE MILHAR
        // ============================================================
        const kmInput = document.getElementById('quilometragem');
        const kmHidden = document.getElementById('quilometragem_real');

        if (kmInput && kmHidden) {
            kmInput.addEventListener('input', function(e) {
                // Remove tudo que não for número
                let raw = this.value.replace(/\D/g, '');
                
                // Se estiver vazio, limpa o hidden
                if (raw === '') {
                    kmHidden.value = '';
                    this.value = '';
                    return;
                }

                // Converte para número inteiro
                const numero = parseInt(raw, 10);
                
                // Formata com pontos de milhar
                const formatado = numero.toLocaleString('pt-BR');
                
                // Atualiza o campo visível com a formatação
                this.value = formatado;
                
                // Armazena o valor real (sem formatação) no campo hidden
                kmHidden.value = numero.toString();
            });

            // Sincroniza o hidden ao carregar (para edição)
            if (kmHidden.value) {
                const numero = parseInt(kmHidden.value, 10);
                if (!isNaN(numero)) {
                    kmInput.value = numero.toLocaleString('pt-BR');
                }
            }
        }

        // ============================================================
        // MÁSCARA DE PREÇO BRASILEIRA
        // ============================================================
        (function() {
            const precoInput = document.getElementById('preco');
            const precoHidden = document.getElementById('preco_real');

            if (!precoInput || !precoHidden) return;

            // Converte número (ex: 45900.67) para string formatada (ex: "45.900,67")
            function formatValue(value) {
                if (value === undefined || value === null || isNaN(value)) return '';
                const num = parseFloat(value);
                if (isNaN(num)) return '';
                return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Obtém apenas os dígitos de uma string
            function getDigits(str) {
                return str.replace(/\D/g, '');
            }

            // Converte uma string de dígitos para um número com 2 casas decimais (centavos)
            function digitsToNumber(digits) {
                if (digits === '') return 0;
                const cents = parseInt(digits, 10);
                if (isNaN(cents)) return 0;
                return cents / 100;
            }

            // Atualiza o campo de exibição e o hidden
            function updateDisplay(rawDigits) {
                const digits = getDigits(rawDigits);
                // Se não houver dígitos, limpa os campos
                if (digits === '') {
                    precoInput.value = '';
                    precoHidden.value = '';
                    return;
                }

                // Converte para valor em reais (com 2 casas)
                const cents = parseInt(digits, 10);
                const reais = cents / 100;
                const formatted = formatValue(reais);
                precoInput.value = formatted;
                precoHidden.value = reais.toFixed(2);
            }

            // Obtém os dígitos atuais do campo (ignorando formatação)
            function getCurrentDigits() {
                const raw = precoInput.value;
                return getDigits(raw);
            }

            // Manipula a entrada do usuário
            precoInput.addEventListener('input', function(e) {
                // Pega os dígitos atuais (já que o campo pode ter formatação)
                let digits = getCurrentDigits();

                // Se o usuário digitou algo que não é número, o campo pode conter caracteres estranhos.
                // Mas nosso getDigits já filtra.
                updateDisplay(digits);
            });

            // Bloqueia teclas que não são números ou vírgula
            precoInput.addEventListener('keydown', function(e) {
                const key = e.key;
                // Permite teclas de navegação, backspace, delete, etc.
                if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End', 'Enter'].includes(key)) return;
                if (e.ctrlKey || e.metaKey) return; // Ctrl+C, Ctrl+V, etc.
                // Permite apenas dígitos e vírgula
                if (!/^[\d,]$/.test(key)) {
                    e.preventDefault();
                }
            });

            // Lida com paste (colar)
            precoInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                if (!pasteData) return;
                // Remove tudo que não for dígito
                const digits = getDigits(pasteData);
                if (digits === '') return;
                // Atualiza o display com os dígitos colados
                updateDisplay(digits);
            });

            // Inicialização: se houver valor no hidden, formata e exibe
            function initializeFromHidden() {
                const realValue = precoHidden.value;
                if (realValue !== '') {
                    const num = parseFloat(realValue);
                    if (!isNaN(num) && num > 0) {
                        const formatted = formatValue(num);
                        precoInput.value = formatted;
                    }
                }
            }
            initializeFromHidden();

            // Sincroniza ao enviar o formulário (garantir que o hidden esteja correto)
            const form = document.getElementById('veiculoForm');
            if (form) {
                form.addEventListener('submit', function() {
                    // Se o campo de exibição estiver vazio, limpa o hidden
                    if (precoInput.value === '') {
                        precoHidden.value = '';
                    } else {
                        // Atualiza o hidden com o valor real
                        const digits = getDigits(precoInput.value);
                        if (digits === '') {
                            precoHidden.value = '';
                        } else {
                            const cents = parseInt(digits, 10);
                            const reais = cents / 100;
                            precoHidden.value = reais.toFixed(2);
                        }
                    }
                });
            }
        })();

        // =============================================
        // CONFIGURAR "OUTRO" PARA MATERIAL DO CILINDRO
        // =============================================
        setupMotorOutro('material_cilindro', 'material_cilindro_outro', []); 
        // Passamos array vazio porque não há lista fixa para verificação
        // (a lógica de edição que verifica se o valor está na lista não será aplicada)

        // =============================================
        // CONFIGURAR "OUTRO" PARA CAPACIDADE DO CILINDRO
        // =============================================
        setupMotorOutro('capacidade_cilindro_m3', 'capacidade_cilindro_m3_outro', []);

        // =============================================
        // CONFIGURAR "OUTRO" PARA LOCALIZAÇÃO DO CILINDRO
        // =============================================
        setupMotorOutro('localizacao_cilindro', 'localizacao_cilindro_outro', []);
    });
</script>

<!-- ========== HELPER FUNCTION ========== -->
<?php
// Função auxiliar para marcar selected em selects
function selected($valorSalvo, $valorAtual): string {
    return ((string) $valorSalvo === (string) $valorAtual) ? 'selected' : '';
}
?>