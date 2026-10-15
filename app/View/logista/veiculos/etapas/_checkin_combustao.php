
<!-- ============================================================ -->
<!-- CHECK-IN: Confirmação do cadastro (Combustão)                -->
<!-- ============================================================ -->
<div class="modal fade" id="checkinModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Confirme os dados do veículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body checkin-modal">

                <!-- ============================================================ -->
                <!-- Grupo 1: Identificação                                       -->
                <!-- ============================================================ -->
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
                    <span class="checkin-label">Quilometragem</span>
                    <span class="checkin-value" id="checkin-quilometragem">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Cor</span>
                    <span class="checkin-value" id="checkin-cor">—</span>
                </div>
                <div class="checkin-item d-none" id="checkin-item-placa">
                    <span class="checkin-label">Placa</span>
                    <span class="checkin-value" id="checkin-placa">—</span>
                </div>

                <!-- ============================================================ -->
                <!-- Grupo 2: Motorização, Desempenho e Consumo                   -->
                <!-- ============================================================ -->
                <h6 class="checkin-group-title">Motorização, Desempenho e Consumo</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Combustível</span>
                    <span class="checkin-value" id="checkin-combustivel">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Motorização</span>
                    <span class="checkin-value" id="checkin-motorizacao">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Potência Máxima</span>
                    <span class="checkin-value" id="checkin-potencia">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Consumo Cidade</span>
                    <span class="checkin-value" id="checkin-consumo-cidade">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Consumo Estrada</span>
                    <span class="checkin-value" id="checkin-consumo-estrada">—</span>
                </div>
                <div class="checkin-item d-none" id="checkin-item-consumo-cidade-alcool">
                    <span class="checkin-label">Consumo Cidade (Álcool)</span>
                    <span class="checkin-value" id="checkin-consumo-cidade-alcool">—</span>
                </div>
                <div class="checkin-item d-none" id="checkin-item-consumo-estrada-alcool">
                    <span class="checkin-label">Consumo Estrada (Álcool)</span>
                    <span class="checkin-value" id="checkin-consumo-estrada-alcool">—</span>
                </div>

                <!-- ============================================================ -->
                <!-- Grupo 3: Transmissão, Suspensão e Rodas                      -->
                <!-- ============================================================ -->
                <h6 class="checkin-group-title">Transmissão, Suspensão e Rodas</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Tipo de Direção</span>
                    <span class="checkin-value" id="checkin-tipo-direcao">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Tipo de Tração</span>
                    <span class="checkin-value" id="checkin-tipo-tracao">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Tipo de Transmissão</span>
                    <span class="checkin-value" id="checkin-tipo-transmissao">—</span>
                </div>
                <div class="checkin-item d-none" id="checkin-item-numero-marchas">
                    <span class="checkin-label">Número de Marchas</span>
                    <span class="checkin-value" id="checkin-numero-marchas">—</span>
                </div>

                <!-- ============================================================ -->
                <!-- Grupo 4: Dimensões, Portas e Lugares                         -->
                <!-- ============================================================ -->
                <h6 class="checkin-group-title">Dimensões, Portas e Lugares</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Número de Portas</span>
                    <span class="checkin-value" id="checkin-portas">—</span>
                </div>
                <div class="checkin-item">
                    <span class="checkin-label">Número de Lugares</span>
                    <span class="checkin-value" id="checkin-lugares">—</span>
                </div>

                <!-- ============================================================ -->
                <!-- Grupo 5: GNV                                                 -->
                <!-- ============================================================ -->
                <h6 class="checkin-group-title">GNV</h6>
                <div class="checkin-item">
                    <span class="checkin-label">Possui GNV</span>
                    <span class="checkin-value" id="checkin-gnv">—</span>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="checkinEditarBtn">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-success" id="checkinConfirmarBtn">
                    <i class="bi bi-check-lg me-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>