<?php
/**
 * Layout específico para o wizard de cadastro/edição de veículos.
 * 
 * Variáveis esperadas:
 *   - $content     : string  HTML do conteúdo das etapas (gerado pela view específica)
 *   - $titulo      : string  Título da página
 *   - $action      : string  URL de submissão do formulário
 *   - $tipo        : string  Tipo do veículo (combustao, eletrico, hibrido)
 *   - $isEdit      : bool    Indica se é edição
 *   - $veiculoId   : int     ID do veículo (na edição)
 *   - $error       : string  Mensagem de erro (opcional)
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Cadastrar Veículo') ?> - Meu Carango</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
        <style>
            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            footer {
                margin-top: auto;
            }

            /* Efeito de zoom nos cards do dashboard */
            .card-zoom {
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .card-zoom:hover {
                transform: translateY(-5px);
                box-shadow: 0 1rem 2rem rgba(0,0,0,.15) !important;
            }

            /* Estilos para a lista de itens */
            .lista-items .item-lista {
                padding: 10px 15px;
                border-bottom: 1px solid #eee;
                cursor: pointer;
                transition: background 0.2s;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .lista-items .item-lista:hover {
                background-color: #dfe0e1;
            }
            .lista-items .item-lista.selecionado {
                background-color: #cfdae5;
                border-left: 4px solid #0d6efd;
            }
            .lista-items .item-lista img {
                width: 40px;
                height: 40px;
                object-fit: contain;
                border-radius: 4px;
                background: #f8f9fa;
            }
            .lista-items .item-lista .nome {
                font-weight: 500;
            }

            /* Overlay de edição nos cards de resumo */
            .resumo-card {
                position: relative;
                transition: transform 0.2s;
            }
            .resumo-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
            }
            .resumo-card .editar-overlay {
                position: absolute;
                top: 8px;
                right: 12px;
                opacity: 0;
                transition: opacity 0.2s;
            }
            .resumo-card:hover .editar-overlay {
                opacity: 1;
            }
            .resumo-card .editar-overlay i {
                font-size: 1.2rem;
                background: white;
                padding: 4px 6px;
                border-radius: 50%;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            /* Badge de exibição no formulário */
            .brand-model-display .badge {
                font-size: 1rem;
                padding: 0.6rem 1rem;
            }

            /* Remove setas do Chrome, Safari, Edge, Opera */
            input[type="number"]::-webkit-inner-spin-button,
            input[type="number"]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            /* Remove setas do Firefox */
            input[type="number"] {
                -moz-appearance: textfield;
            }
        </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Cabeçalho do wizard -->
        <div class="wizard-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2><?= htmlspecialchars($titulo ?? 'Cadastrar Veículo') ?></h2>
                <span class="badge bg-secondary" id="step-indicator">Etapa 1 de 0</span>
            </div>
            
            <!-- Barra de progresso -->
            <div class="progress mt-2" style="height: 8px;">
                <div id="progress-bar" class="progress-bar progress-bar-striped" 
                     role="progressbar" style="width: 0%;" 
                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
            
            <!-- Lista de etapas (opcional) -->
            <div class="step-labels d-flex justify-content-between mt-2 small text-muted" id="step-labels">
                <!-- Preenchido via JavaScript -->
            </div>
        </div>

        <!-- Mensagem de erro (flash) -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i> <?= nl2br(htmlspecialchars($error)) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <form id="veiculoForm" action="<?= $action ?>" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="tipo_veiculo" value="<?= htmlspecialchars($tipo) ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <!-- Container das etapas (o conteúdo vem da view específica) -->
            <div id="wizard-container">
                <?= $content ?? '' ?>
            </div>

            <!-- Rodapé com botões de navegação -->
            <div class="wizard-footer d-flex justify-content-between mt-4 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary" id="btnAnterior" disabled>
                    <i class="bi bi-arrow-left"></i> Anterior
                </button>
                
                <button type="button" class="btn btn-primary" id="btnProximo">
                    Próximo <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts do wizard (JS de navegação, toggles, etc.) -->
    <script>
        // ============================================================
        // MODAL DE SELEÇÃO DE MARCA E MODELO
        // ============================================================

        // =============================================
        // 0. INICIALIZAÇÃO DOS BADGES E MODAL DE CATEGORIA
        // =============================================

        // Dados das marcas e modelos (carregados via PHP)
        const marcasData = <?= json_encode($marcas) ?>;
        const modelosData = <?= json_encode($modelos) ?>;

        // Obtém referências para os campos ocultos e badges
        const marcaIdInput = document.getElementById('marca_id');
        const modeloIdInput = document.getElementById('modelo_id');
        const marcaDisplay = document.getElementById('marcaDisplay');
        const modeloDisplay = document.getElementById('modeloDisplay');

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
        // NOVO: referência ao botão "Próximo"
        const btnProximoMarca = document.getElementById('btnProximoMarca');

        // Função para atualizar os badges com base nos IDs atuais
        function atualizarBadges() {
            const marcaId = marcaIdInput.value;
            const modeloId = modeloIdInput.value;

            // Atualiza badge da marca
            if (marcaId) {
                const marca = marcasData.find(m => m.id == marcaId);
                if (marca) {
                    marcaDisplay.textContent = marca.nome;
                    marcaDisplay.className = 'badge bg-primary p-2';
                }
            } else {
                marcaDisplay.textContent = 'Nenhuma marca selecionada';
                marcaDisplay.className = 'badge bg-secondary p-2';
            }

            // Atualiza badge do modelo
            if (modeloId && modelosData[modeloId]) {
                modeloDisplay.textContent = modelosData[modeloId];
                modeloDisplay.className = 'badge bg-primary p-2';
            } else {
                modeloDisplay.textContent = 'Nenhum modelo selecionado';
                modeloDisplay.className = 'badge bg-secondary p-2';
            }
        }

        // Dados da seleção
        let selectedMarcaId = null;
        let selectedMarcaNome = '';
        let selectedMarcaLogo = '';
        let selectedModeloId = null;
        let selectedModeloNome = '';

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
                    // Habilita o botão "Próximo"
                    if (btnProximoMarca) {
                        btnProximoMarca.disabled = false;
                    }
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
                    window._modelosData = modelos; // Armazena para filtro
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
                // Atualiza estado do botão Próximo
                if (btnProximoMarca) {
                    btnProximoMarca.disabled = (selectedMarcaId === null);
                }
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

        // NOVO: Evento do botão "Próximo"
        if (btnProximoMarca) {
            btnProximoMarca.addEventListener('click', function() {
                if (!selectedMarcaId) {
                    alert('Por favor, selecione uma marca primeiro.');
                    return;
                }
                // Carrega os modelos da marca selecionada
                carregarModelos(selectedMarcaId);
                // Vai para a etapa de modelo
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

                // Limpa feedback ao abrir o modal
                const feedbackEl = document.getElementById('marcaModeloFeedback');
                feedbackEl.style.display = 'none';
                feedbackEl.classList.remove('d-block');
                marcaDisplay.classList.remove('badge-danger', 'border', 'border-danger');
                modeloDisplay.classList.remove('badge-danger', 'border', 'border-danger');
            });
        }

        // Abrir modal
        const btnSelecionar = document.getElementById('selecionarMarcaModeloBtn');
        if (btnSelecionar) {
            btnSelecionar.addEventListener('click', function() {
                const currentMarcaId = parseInt(marcaIdInput.value);
                const currentModeloId = parseInt(modeloIdInput.value);
                // Resetar seleção (mas manter a atual se houver)
                selectedMarcaId = null;
                selectedMarcaNome = '';
                selectedMarcaLogo = '';
                selectedModeloId = null;
                selectedModeloNome = '';

                if (currentMarcaId) {
                    const marca = marcasData.find(m => m.id === currentMarcaId);
                    if (marca) {
                        selectedMarcaId = currentMarcaId;
                        selectedMarcaNome = marca.nome;
                        selectedMarcaLogo = marca.logo_url || '/assets/images/default-brand.png';
                    }
                }
                if (currentModeloId) {
                    // Se houver um modelo selecionado, poderíamos carregar a lista, mas não temos os dados ainda
                    // O usuário poderá selecionar novamente na etapa modelo
                }

                // Desabilita o botão Próximo (será habilitado quando clicar em uma marca)
                if (btnProximoMarca) {
                    btnProximoMarca.disabled = (selectedMarcaId === null);
                }

                renderMarcas();
                irParaEtapa('marca');
                const modalInstance = new bootstrap.Modal(modalMarcaModelo);
                modalInstance.show();
            });
        }

        modalMarcaModelo.addEventListener('hidden.bs.modal', function() {
            // Remove is-invalid dos campos do modal
            document.querySelectorAll('#marcaModeloModal .is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
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

        // ================================================
        // INICIALIZAÇÃO TOOLTIPS
        // ================================================
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // =============================================
        // FUNÇÕES GENÉRICAS PARA "OUTRO" (MOTORIZAÇÃO)
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

            // Controla required
            if (isOutro) {
                outroInput.setAttribute('required', 'required');
                outroInput.classList.add('requires-validation');
            } else {
                outroInput.removeAttribute('required');
                outroInput.classList.remove('requires-validation');
                outroInput.value = '';
            }

            // REMOVE O ERRO DO SELECT PRINCIPAL
            select.classList.remove('is-invalid');

            // REMOVE O ERRO DO CAMPO EXTRA (se estiver oculto ou não)
            outroInput.classList.remove('is-invalid');

            if (!isOutro) outroInput.value = '';
        }

        function adicionarListenerOutro(selectId, extraId) {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.addEventListener('change', function() {
                toggleMotorOutro(selectId, extraId);
            });
        }

        // =============================================
        // MÁSCARA DE MILHAR
        // =============================================

        document.querySelectorAll('[data-mascara-milhar]').forEach(function(visual) {
            // Evita aplicar múltiplas vezes (caso o script seja executado mais de uma vez)
            if (visual.dataset.mascaraAplicada) return;

            const hiddenId = visual.id.replace(/_visual$/, '');
            const hidden = document.getElementById(hiddenId);
            if (!hidden) return;

            function formatar(valor) {
                const numeros = String(valor).replace(/\D/g, '');
                if (numeros === '') return '';
                return Number(numeros).toLocaleString('pt-BR');
            }

            visual.addEventListener('input', function() {
                const puro = this.value.replace(/\D/g, '');
                this.value = puro ? formatar(puro) : '';
                hidden.value = puro;
            });

            if (hidden.value) {
                visual.value = formatar(hidden.value);
                hidden.value = hidden.value.replace(/\D/g, '');
            }

            visual.dataset.mascaraAplicada = 'true';
        });

        // ============================================================
        // DROPDOWN DE CORES (abrir ao clicar no input ou botão)
        // ============================================================
        const corInput = document.getElementById('corInput');
        const btnAbrir = document.getElementById('btnAbrirCores');
        const dropdown = document.getElementById('dropdownCores');
        const corHidden = document.getElementById('corSelecionada');
        const corOutro = document.getElementById('cor_outro');
        const corItems = document.querySelectorAll('.cor-item');
        const corSwatch = document.getElementById('corSwatch');
        const corSwatchInner = document.getElementById('corSwatchInner');

        // Função para atualizar o swatch de cor
        function atualizarSwatch(cor, hex) {
            if (cor && hex && cor !== 'outro') {
                corSwatchInner.style.backgroundColor = hex;
                corSwatch.style.display = 'inline-flex';
                corSwatch.style.alignItems = 'center';
                corSwatch.style.justifyContent = 'center';
            } else {
                corSwatch.style.display = 'none';
            }
        }

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

                    // Atualiza o swatch
                    atualizarSwatch(cor, hex);

                    // Remove destaque de todos
                    corItems.forEach(el => el.style.backgroundColor = '');
                    this.style.backgroundColor = '#e9ecef';

                    // Controla campo "Outro"
                    if (cor === 'outro') {
                        corOutro.style.display = 'block';
                        corOutro.setAttribute('required', 'required');
                        corOutro.classList.add('requires-validation');
                        corOutro.focus();
                    } else {
                        corOutro.style.display = 'none';
                        corOutro.removeAttribute('required');
                        corOutro.classList.remove('requires-validation');
                        corOutro.value = '';
                    }

                    // Fecha o dropdown
                    dropdown.style.display = 'none';

                    // ✅ APENAS REMOVE A CLASSE DE ERRO
                    // O Bootstrap controla a visibilidade do feedback automaticamente
                    corInput.classList.remove('is-invalid');
                });
            });
        }

        // Se houver valor salvo, destaca o item correspondente e atualiza o input
        if (corHidden.value) {
            const valorSalvo = corHidden.value;
            if (valorSalvo === 'outro') {
                corInput.value = 'Outro (digitar)';
                corOutro.style.display = 'block';
                atualizarSwatch(null, null); // oculta swatch para "outro"
            } else {
                corInput.value = valorSalvo;
                // Busca o hex da cor salva
                const item = document.querySelector(`.cor-item[data-cor="${valorSalvo}"]`);
                if (item) {
                    const hex = item.dataset.hex;
                    atualizarSwatch(valorSalvo, hex);
                }
            }
            corItems.forEach(item => {
                if (item.dataset.cor === valorSalvo) {
                    item.style.backgroundColor = '#e9ecef';
                }
            });
        }

        // =============================================
        // CONFIGURAR "OUTRO" PARA COR (simples, sem lista)
        // =============================================
        document.getElementById('corInput').addEventListener('change', function() {
            toggleMotorOutro('cor', 'cor_outro');
        });

        // =============================================
        // VALIDAÇÃO DE PLACA (apenas letras e números)
        // =============================================
        const camposPlaca = document.querySelectorAll('[data-tipo="placa"]');

        if (camposPlaca.length > 0) {
            // Função para verificar se a tecla é permitida
            function isCaracterePlacaPermitido(tecla) {
                // Permite teclas de navegação e controle
                const teclasPermitidas = [
                    'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight',
                    'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter', 'Escape'
                ];
                if (teclasPermitidas.includes(tecla)) {
                    return true;
                }
                // Permite combinações com Ctrl (ex: Ctrl+C, Ctrl+V, Ctrl+A)
                if (event.ctrlKey || event.metaKey) {
                    return true;
                }
                // Permite apenas letras e números
                return /^[a-zA-Z0-9]$/.test(tecla);
            }

            camposPlaca.forEach(function(campo) {
                // 1. Listener keydown: bloqueia caracteres inválidos antes de serem inseridos
                campo.addEventListener('keydown', function(event) {
                    if (!isCaracterePlacaPermitido(event.key)) {
                        event.preventDefault();
                    }
                });

                // 2. Listener input: limpa caracteres inválidos e converte para maiúsculas
                campo.addEventListener('input', function() {
                    // Remove tudo que não for letra ou número e converte para maiúsculas
                    this.value = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                });
            });
        }
    </script>

    <script>
        // ============================================================
        // 1. VARIÁVEIS GLOBAIS
        // ============================================================
        let steps = [];           // Lista de elementos .wizard-step
        let currentStep = 0;      // Índice da etapa atual

        // ============================================================
        // 2. FUNÇÕES DO WIZARD
        // ============================================================

        function iniciarWizard() {
            steps = document.querySelectorAll('.wizard-step');
            if (steps.length === 0) return;
            currentStep = 0;
            steps.forEach((el, i) => {
                el.style.display = (i === 0) ? 'block' : 'none';
            });
            atualizarProgresso();
            atualizarBotoes();
        }

        function mostrarEtapa(index) {
            if (index < 0 || index >= steps.length) return;
            steps.forEach((el, i) => {
                el.style.display = (i === index) ? 'block' : 'none';
            });
            currentStep = index;
            atualizarProgresso();
            atualizarBotoes();
        }

        function proximaEtapa() {
            if (!validarEtapa()) return;
            if (currentStep < steps.length - 1) {
                mostrarEtapa(currentStep + 1);
            } else {
                // Última etapa: submeter formulário
                document.getElementById('veiculoForm').submit();
            }
        }

        function etapaAnterior() {
            if (currentStep > 0) {
                mostrarEtapa(currentStep - 1);
            }
        }

        function validarEtapa() {
            // Pega os campos visíveis da etapa atual (inputs, selects, textareas)
            const etapaAtual = steps[currentStep];
            const campos = etapaAtual.querySelectorAll('input, select, textarea');
            let valido = true;

            // ===== VALIDAÇÃO HTML5 NATIVA =====
            campos.forEach(campo => {
                if (!campo.checkValidity()) {
                    campo.classList.add('is-invalid');
                    valido = false;
                } else {
                    campo.classList.remove('is-invalid');
                }
            });

            // ===== VALIDAÇÃO CUSTOMIZADA: COR =====
            const corSelecionada = document.getElementById('corSelecionada');
            const corInput = document.getElementById('corInput');
            const corOutro = document.getElementById('cor_outro');

            if (corSelecionada && corInput) {
                const valorCor = corSelecionada.value;
                let corValida = false;

                if (valorCor === 'outro') {
                    // Modo "Outro": valida o campo personalizado
                    if (corOutro) {
                        const outroValor = corOutro.value.trim();
                        if (outroValor !== '') {
                            corValida = true;
                            corOutro.classList.remove('is-invalid');
                            // Remove erro do campo principal (se houver)
                            corInput.classList.remove('is-invalid');
                        } else {
                            corValida = false;
                            corOutro.classList.add('is-invalid');
                            // Remove erro do campo principal (para não mostrar mensagem duplicada)
                            corInput.classList.remove('is-invalid');
                        }
                    }
                } else {
                    // Modo normal (cor selecionada da lista)
                    corValida = valorCor !== '' && valorCor !== null;
                    if (corValida) {
                        corInput.classList.remove('is-invalid');
                        // Se houver erro no campo "Outro", remove (caso tenha sido deixado)
                        if (corOutro) corOutro.classList.remove('is-invalid');
                    } else {
                        corInput.classList.add('is-invalid');
                        if (corOutro) corOutro.classList.remove('is-invalid');
                    }
                }

                if (!corValida) {
                    valido = false;
                }
            }

            // ===== VALIDAÇÃO CUSTOMIZADA: MARCA E MODELO =====
            const marcaId = document.getElementById('marca_id').value;
            const modeloId = document.getElementById('modelo_id').value;
            const feedbackEl = document.getElementById('marcaModeloFeedback');
            const marcaBadge = document.getElementById('marcaDisplay');
            const modeloBadge = document.getElementById('modeloDisplay');

            let marcaModeloValido = true;

            if (!marcaId) {
                marcaBadge.classList.add('badge-danger', 'border', 'border-danger');
                marcaModeloValido = false;
            } else {
                marcaBadge.classList.remove('badge-danger', 'border', 'border-danger');
            }

            if (!modeloId) {
                modeloBadge.classList.add('badge-danger', 'border', 'border-danger');
                marcaModeloValido = false;
            } else {
                modeloBadge.classList.remove('badge-danger', 'border', 'border-danger');
            }

            if (!marcaModeloValido) {
                feedbackEl.style.display = 'block';
                feedbackEl.classList.add('d-block');
                valido = false;
            } else {
                feedbackEl.style.display = 'none';
                feedbackEl.classList.remove('d-block');
            }

            // ===== SE HOUVER ERRO, ROLA ATÉ O PRIMEIRO CAMPO INVÁLIDO =====
            if (!valido) {
                const primeiroInvalido = etapaAtual.querySelector('.is-invalid');
                if (primeiroInvalido) {
                    primeiroInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    primeiroInvalido.focus();
                }
            }

            // ATUALIZA OS BADGES COM OS VALORES ATUAIS (independente de erro)
            atualizarBadges();

            return valido;
        } 

        function atualizarProgresso() {
            const total = steps.length;
            const atual = currentStep + 1;
            const percentual = (atual / total) * 100;

            // Atualiza barra de progresso
            const barra = document.getElementById('progress-bar');
            if (barra) {
                barra.style.width = percentual + '%';
                barra.setAttribute('aria-valuenow', percentual);
            }

            // Atualiza indicador "Etapa X de Y"
            const indicador = document.getElementById('step-indicator');
            if (indicador) {
                indicador.textContent = `Etapa ${atual} de ${total}`;
            }

            // (Opcional) Atualiza labels das etapas
            // ...
        }

        function atualizarBotoes() {
            const btnAnterior = document.getElementById('btnAnterior');
            const btnProximo = document.getElementById('btnProximo');
            if (btnAnterior) {
                btnAnterior.disabled = (currentStep === 0);
            }
            if (btnProximo) {
                const isUltima = (currentStep === steps.length - 1);
                btnProximo.innerHTML = isUltima ? 'Salvar <i class="bi bi-check-lg"></i>' : 'Próximo <i class="bi bi-arrow-right"></i>';
            }
        }

        // ============================================================
        // 3. INICIALIZAÇÃO
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializa o wizard
            iniciarWizard();

            // ATUALIZA OS BADGES AO CARREGAR A PÁGINA (com os valores de $old)
            atualizarBadges();

            // Event listeners dos botões
            const btnAnterior = document.getElementById('btnAnterior');
            const btnProximo = document.getElementById('btnProximo');
            if (btnAnterior) btnAnterior.addEventListener('click', etapaAnterior);
            if (btnProximo) btnProximo.addEventListener('click', proximaEtapa);

            // ================================================
            // VALIDAÇÃO DE PONTO/VÍRGULA E BLOQUEIO DE CARACTERES INVÁLIDOS
            // ================================================

            // Seleciona todos os campos com data-tipo="inteiro"
            const camposInteiros = document.querySelectorAll('[data-tipo="inteiro"]');

            if (camposInteiros.length === 0) return;

            // Função para validar se o caractere é permitido (número, ponto ou vírgula)
            function isCaracterePermitido(tecla) {
                // Permite teclas de navegação e controle
                if (tecla === 'Backspace' || tecla === 'Delete' || tecla === 'Tab' ||
                    tecla === 'ArrowLeft' || tecla === 'ArrowRight' || tecla === 'ArrowUp' || tecla === 'ArrowDown' ||
                    tecla === 'Home' || tecla === 'End' || tecla === 'Enter') {
                    return true;
                }

                // Permite combinações com Ctrl (ex: Ctrl+C, Ctrl+V, Ctrl+A)
                if (event.ctrlKey || event.metaKey) {
                    return true;
                }

                // Verifica se a tecla é um número, ponto ou vírgula
                return /^[0-9.,]$/.test(tecla);
            }

            // Para cada campo, adiciona os listeners
            camposInteiros.forEach(function(campo) {
                // 1. Listener keydown: bloqueia caracteres inválidos antes de serem inseridos
                campo.addEventListener('keydown', function(event) {
                    if (!isCaracterePermitido(event.key)) {
                        event.preventDefault(); // Impede a entrada do caractere
                    }
                });

                // 2. Listener input: validação de ponto/vírgula + limpeza de colagem
                campo.addEventListener('input', function() {
                    // 2a. Limpeza de caracteres inválidos (fallback para colagem)
                    this.value = this.value.replace(/[^0-9.,]/g, '');

                    // 2b. Obtém o valor atual (já limpo)
                    const valor = this.value;

                    // 2c. Verifica se contém ponto ou vírgula
                    const contemPontoVirgula = /[.,]/.test(valor);

                    // 2d. Encontra os elementos de feedback
                    const container = this.closest('.input-group, .col-md-4, .mb-3') || this.parentNode;
                    const feedbackRequired = container.querySelector('.invalid-feedback:not(.feedback-pontovirgula)');
                    const feedbackPontovirgula = container.querySelector('.feedback-pontovirgula');

                    if (contemPontoVirgula) {
                        // Caso contenha ponto ou vírgula
                        this.classList.add('is-invalid');

                        if (feedbackPontovirgula) {
                            feedbackPontovirgula.style.display = 'block';
                            feedbackPontovirgula.classList.remove('d-none');
                        }

                        if (feedbackRequired) {
                            feedbackRequired.style.display = 'none';
                            feedbackRequired.classList.add('d-none');
                        }
                    } else {
                        // Caso NÃO contenha ponto ou vírgula
                        this.classList.remove('is-invalid');

                        if (feedbackPontovirgula) {
                            feedbackPontovirgula.style.display = 'none';
                            feedbackPontovirgula.classList.add('d-none');
                        }

                        if (feedbackRequired) {
                            feedbackRequired.style.display = '';
                            feedbackRequired.classList.remove('d-none');
                        }
                    }
                });
            });

            // =============================================
            // CONTROLE DE CAMPOS FLEX (etanol) – Combustão e Híbrido
            // =============================================

            /**
             * Controla a exibição dos campos de etanol com base no valor do select de combustível.
             * @param {string} selectId - ID do <select> de combustível
             * @param {string} containerId - ID do container que será mostrado/ocultado
             */
            function toggleFlexFields(selectId, containerId) {
                const select = document.getElementById(selectId);
                const container = document.getElementById(containerId);
                if (!select || !container) return;

                const isFlex = select.value === 'flex';
                container.style.display = isFlex ? 'block' : 'none';

                const inputs = container.querySelectorAll('input, select, textarea');
                inputs.forEach(el => {
                    el.disabled = !isFlex;
                    if (isFlex) {
                        // SEMPRE adiciona required quando for flex
                        el.setAttribute('required', 'required');
                    } else {
                        el.removeAttribute('required');
                    }
                });
            }

            // Configurar para o campo de combustível da combustão (se existir)
            const combustivelSelect = document.getElementById('combustivel');
            if (combustivelSelect) {
                combustivelSelect.addEventListener('change', function() {
                    toggleFlexFields('combustivel', 'flex-fields');
                });
                // Estado inicial (edição)
                toggleFlexFields('combustivel', 'flex-fields');
            }

            // Configurar para o campo de combustível do híbrido
            const combustivelHibrido = document.getElementById('combustivel_hibrido');
            if (combustivelHibrido) {
                combustivelHibrido.addEventListener('change', function() {
                    toggleFlexFields('combustivel_hibrido', 'flex-fields-hibrido');
                });
                // Estado inicial (edição)
                toggleFlexFields('combustivel_hibrido', 'flex-fields-hibrido');
            }

            // ============================================================
            // CONTROLE DE VISIBILIDADE DO NÚMERO DE MARCHAS
            // ============================================================

            /**
             * Configura a lógica de exibição do campo de marchas baseado no tipo de transmissão.
             * @param {string} transmissaoId - ID do <select> de transmissão
             * @param {string} marchasId    - ID do <select> de número de marchas
             * @param {string[]} tiposCvt   - Array de valores que indicam "sem marchas fixas"
             */
            function configurarMarchasCvt(transmissaoId, marchasId, tiposCvt) {
                const transmissao = document.getElementById(transmissaoId);
                const marchas = document.getElementById(marchasId);
                if (!transmissao || !marchas) return;

                function toggleMarchas() {
                    const valor = transmissao.value;
                    const isCvt = tiposCvt.includes(valor);
                    const container = marchas.closest('.col-md-4');

                    if (container) {
                        container.style.display = (valor !== '' && !isCvt) ? 'block' : 'none';
                    }

                    // Remove ou adiciona o required conforme a visibilidade
                    if (valor === '' || isCvt) {
                        marchas.removeAttribute('required');
                        marchas.value = '';
                        marchas.selectedIndex = 0;
                    } else {
                        marchas.setAttribute('required', 'required');
                    }
                }

                transmissao.addEventListener('change', toggleMarchas);
                // Executa uma vez para inicializar (na edição)
                toggleMarchas();
            }

            // Configurar para a seção Combustão
            configurarMarchasCvt(
                'transmissao_tipo',          // ID do select de transmissão (combustão)
                'numero_marchas',            // ID do select de marchas
                ['Automática CVT', 'CVT']    // Valores que indicam CVT
            );

            // Configurar para a seção Híbrido
            configurarMarchasCvt(
                'transmissao_tipo_hibrido',       // ID do select de transmissão híbrido
                'numero_marchas_hibrido',         // ID do select de marchas híbrido
                ['CVT', 'e-CVT', 'Automática CVT'] // Valores comuns para híbridos
            );

            // ============================================================
            // CONTROLE DE EXIBIÇÃO DO VOLUME DA CAÇAMBA (baseado na carroceria)
            // ============================================================

            // Lista de carrocerias que possuem caçamba
            const TIPOS_COM_CACAMBA = ['picape', 'utilitario', 'suv', 'crossover'];

            /**
             * Controla a visibilidade e o required do campo "Volume da caçamba".
             */
            function toggleCacamba() {
                const carroceriaSelect = document.getElementById('carroceria');
                const cacambaContainer = document.getElementById('cacamba-container');
                const cacambaHidden = document.getElementById('volume_cacamba_l');
                const cacambaVisual = document.getElementById('volume_cacamba_l_visual');

                if (!carroceriaSelect || !cacambaContainer) return;

                const valorSelecionado = carroceriaSelect.value;
                const exibir = TIPOS_COM_CACAMBA.includes(valorSelecionado);

                // Exibe ou oculta o container
                cacambaContainer.style.display = exibir ? 'block' : 'none';

                // Controla o atributo required (apenas no campo visual, pois o hidden não é validado)
                if (cacambaVisual) {
                    if (exibir) {
                        cacambaVisual.setAttribute('required', 'required');
                    } else {
                        cacambaVisual.removeAttribute('required');
                        // Limpa os valores para não enviar dados indesejados
                        if (cacambaHidden) cacambaHidden.value = '';
                        cacambaVisual.value = '';
                    }
                }
            }

            // Adiciona o listener ao select de carroceria (que está na etapa Básico)
            const carroceriaSelect = document.getElementById('carroceria');
            if (carroceriaSelect) {
                carroceriaSelect.addEventListener('change', toggleCacamba);
                // Executa uma vez para aplicar o estado inicial (edição)
                toggleCacamba();
            }

            // ============================================================
            // CONTROLE DE EXIBIÇÃO DOS CAMPOS GNV (baseado no select Sim/Não)
            // ============================================================
            function toggleGNV() {
                const gnvSelect = document.getElementById('gnv_instalado');
                const gnvBloco = document.getElementById('gnv-fields');
                if (!gnvSelect || !gnvBloco) return;

                const isSim = gnvSelect.value === '1';
                gnvBloco.style.display = isSim ? 'block' : 'none';

                const campos = gnvBloco.querySelectorAll('input, select, textarea');
                campos.forEach(campo => {
                    campo.disabled = !isSim;
                    if (isSim) {
                        // SEMPRE adiciona required quando "Sim" estiver selecionado
                        campo.setAttribute('required', 'required');
                    } else {
                        campo.removeAttribute('required');
                        // Limpa valores se não for GNV
                        if (campo.tagName === 'SELECT') {
                            campo.selectedIndex = 0;
                        } else {
                            campo.value = '';
                        }
                        // Remove qualquer estado de erro residual
                        campo.classList.remove('is-invalid');
                    }
                });
            }

            // Adiciona o listener e inicializa
            const gnvSelect = document.getElementById('gnv_instalado');
            if (gnvSelect) {
                gnvSelect.addEventListener('change', toggleGNV);
                toggleGNV(); // Estado inicial (edição)
            }

            // ============================================================
            // REMOVER IS-INVALID EM TEMPO REAL AO CORRIGIR CAMPOS OBRIGATÓRIOS
            // ============================================================
            const veiculoForm = document.getElementById('veiculoForm');

            if (veiculoForm) {
                // Para inputs e textareas (detecta digitação)
                veiculoForm.addEventListener('input', function(e) {
                    const campo = e.target;
                    // Filtra apenas campos de formulário
                    if (!campo.matches('input, textarea')) return;
                    // Verifica se é obrigatório e está visível
                    if (!campo.hasAttribute('required')) return;
                    if (campo.offsetParent === null) return; // oculto

                    // Remove erro se o campo tiver valor
                    if (campo.value.trim() !== '') {
                        campo.classList.remove('is-invalid');
                    }
                });

                // Para selects (detecta mudança de opção)
                veiculoForm.addEventListener('change', function(e) {
                    const campo = e.target;
                    if (!campo.matches('select')) return;
                    if (!campo.hasAttribute('required')) return;
                    if (campo.offsetParent === null) return;

                    if (campo.value !== '' && campo.value !== null) {
                        campo.classList.remove('is-invalid');
                    }
                });
            }

            // =============================================
            // CONFIGURAR "OUTRO" PARA CARROCERIA
            // =============================================
            adicionarListenerOutro('carroceria', 'carroceria_outro');

            // =============================================
            // CONFIGURAR "OUTRO" PARA CILINDRADA
            // =============================================
            adicionarListenerOutro('motor_tipo', 'motor_tipo_outro');

            // =============================================
            // CONFIGURAR "OUTRO" PARA SUSPENSÃO
            // =============================================
            adicionarListenerOutro('suspensao_dianteira', 'suspensao_dianteira_outro');
            adicionarListenerOutro('suspensao_traseira', 'suspensao_traseira_outro');

            // =============================================
            // CONFIGURAR "OUTRO" PARA ARO DO PNEU
            // =============================================
            adicionarListenerOutro('pneu_aro', 'pneu_aro_outro');

            // =============================================
            // CONFIGURAR "OUTRO" PARA CAPACIDADE DO CILINDRO
            // =============================================
            adicionarListenerOutro('capacidade_cilindro_m3', 'capacidade_cilindro_m3_outro');

            // =============================================
            // CONFIGURAR "OUTRO" PARA LOCALIZAÇÃO DO CILINDRO
            // =============================================
            adicionarListenerOutro('localizacao_cilindro', 'localizacao_cilindro_outro');
        });

        // ============================================================
        // SCRIPT DE DEPURAÇÃO – CAPTURA ERROS AO CLICAR EM "PRÓXIMO"
        // ============================================================
        (function() {
            // Salva a função original
            const originalProxima = window.proximaEtapa || function() {
                console.warn('⚠️ Função proximaEtapa não encontrada. Nada a fazer.');
            };

            // Substitui por uma versão com captura de erros
            window.proximaEtapa = function() {
                console.log('🔍 === INÍCIO DA DEPURAÇÃO ===');
                console.log('📌 Botão "Próximo" clicado');

                try {
                    // 1. Verifica campos inválidos na etapa atual
                    const etapaAtual = document.querySelector('.wizard-step:not([style*="display: none"])') || 
                                       document.querySelector('.wizard-step');
                    if (etapaAtual) {
                        const invalidos = etapaAtual.querySelectorAll('.is-invalid');
                        if (invalidos.length > 0) {
                            console.warn(`⚠️ ${invalidos.length} campo(s) com classe 'is-invalid' (antes de validar):`);
                            invalidos.forEach(el => {
                                console.log(`   - ${el.id || el.name || el.tagName}`);
                            });
                        } else {
                            console.log('✅ Nenhum campo com is-invalid encontrado antes da validação.');
                        }
                    }

                    // 2. Chama a função original (que executa a validação)
                    console.log('🔄 Chamando função original proximaEtapa...');
                    const result = originalProxima.call(window);

                    // 3. Verifica se houve retorno (se a função retornou algo)
                    console.log('✅ Função original executada. Resultado:', result);

                } catch (error) {
                    // 4. Captura e exibe qualquer erro lançado
                    console.error('❌ ERRO CAPTURADO na função proximaEtapa:');
                    console.error('   Mensagem:', error.message);
                    console.error('   Stack:', error.stack);
                    console.error('   Detalhes:', error);

                    // Tenta identificar a origem do erro
                    if (error instanceof ReferenceError) {
                        console.error('   🔍 Parece ser um erro de referência (variável não definida).');
                    } else if (error instanceof TypeError) {
                        console.error('   🔍 Parece ser um erro de tipo (ex: tentativa de acessar propriedade de null).');
                    } else if (error instanceof SyntaxError) {
                        console.error('   🔍 Parece ser um erro de sintaxe (código malformado).');
                    } else {
                        console.error('   🔍 Erro de outro tipo.');
                    }
                }

                console.log('🔍 === FIM DA DEPURAÇÃO ===');
            };

            console.log('✅ Script de depuração ativado!');
            console.log('💡 Clique em "Próximo" para ver os logs de erro.');
            console.log('💡 Para desativar, recarregue a página.');
        })();
    </script>
</body>
</html>