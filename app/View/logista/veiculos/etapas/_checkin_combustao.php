<!-- ============================================================ -->
<!-- CHECK-IN: Confirmação do cadastro (Combustão)                -->
<!-- ============================================================ -->
<div class="modal fade" id="checkinModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirme os dados do veículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body checkin-modal">

                <!-- Grupo 1: Identificação -->
                <h6 class="checkin-group-title">Identificação</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Marca</span>
                    <span class="checkin-value" id="checkin-marca">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Modelo</span>
                    <span class="checkin-value" id="checkin-modelo">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Carroceria</span>
                    <span class="checkin-value" id="checkin-carroceria">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Ano do Modelo</span>
                    <span class="checkin-value" id="checkin-ano-modelo">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Cor</span>
                    <span class="checkin-value" id="checkin-cor">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Número de Lugares</span>
                    <span class="checkin-value" id="checkin-lugares">—</span>
                </div>

                <!-- Grupo 2: Motorização -->
                <h6 class="checkin-group-title">Motorização</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Combustível</span>
                    <span class="checkin-value" id="checkin-combustivel">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Motorização</span>
                    <span class="checkin-value" id="checkin-motorizacao">—</span>
                </div>

                <!-- Grupo 3: Transmissão -->
                <h6 class="checkin-group-title">Transmissão</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Tipo de Direção</span>
                    <span class="checkin-value" id="checkin-tipo-direcao">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Tipo de Transmissão</span>
                    <span class="checkin-value" id="checkin-tipo-transmissao">—</span>
                </div>

                <!-- Grupo 4: Extras -->
                <h6 class="checkin-group-title">Extras</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Possui GNV</span>
                    <span class="checkin-value" id="checkin-gnv">—</span>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="checkinEditarBtn">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-success" id="checkinConfirmarBtn">
                    <i class="bi bi-check-lg me-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>