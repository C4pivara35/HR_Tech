<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-shield-lock me-2 text-success"></i>Trilha de Auditoria Imutável LGPD & BACEN (Ledger SHA-256)</h2>
            <p class="text-muted mb-0">Cadeia criptográfica inviolável de custódia digital para operações financeiras, acessos e consentimentos LGPD.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>CE_LOGS_AUDITORIA_LGPD (4 PFB)</span>
            <span class="badge bg-success text-uppercase me-2"><i class="bi bi-calculator me-1"></i>SE_EXPORTAR_TRILHA_AUDITORIA (7 PFB)</span>
            <a href="index.php?route=compliance&action=verify_chain" class="btn btn-outline-success btn-sm me-2">
                <i class="bi bi-shield-check me-1"></i>Validar Cadeia Criptográfica
            </a>
            <a href="index.php?route=compliance&action=export" class="btn btn-outline-info btn-sm me-2">
                <i class="bi bi-download me-1"></i>Exportar Laudo JSON
            </a>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#auditModal">
                <i class="bi bi-plus-circle me-1"></i>Gravar Evento
            </button>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- ============================================================================== -->
<!-- SE_EXPORTAR_TRILHA_AUDITORIA — JUSTIFICATIVA DO DADO DERIVADO & LAUDO PERICIAL -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-shield-check text-success me-2"></i>SE_EXPORTAR_TRILHA_AUDITORIA — Justificativa do Dado Derivado (Laudo Pericial de Integridade)
        </span>
        <span class="se-badge"><i class="bi bi-cpu me-1"></i>SHA-256 Blockchain Ledger</span>
    </div>
    <div class="card-body">
        <div class="p-3 mb-4 rounded bg-black border border-secondary text-light">
            <div class="fw-bold text-success mb-2"><i class="bi bi-journal-check me-1"></i>Conformidade Regulatória Estrita & Garantia de Não Repúdio:</div>
            <ul class="mb-0 small text-muted ps-3">
                <li><strong>LGPD (Lei 13.709/2018) Art. 37 & 46:</strong> Registro pericial comprobatório de todas as operações de tratamento de dados pessoais sensíveis com salvaguardas de imutabilidade e não repúdio.</li>
                <li><strong>BACEN (Resolução CMN nº 4.893/2020 Art. 10):</strong> Rastreabilidade contínua de transações financeiras, emissão de apólices e conciliação de pagamentos com trilha de auditoria inviolável.</li>
                <li><strong>Algoritmo de Encadeamento Criptográfico:</strong> Cada bloco calcula $H_n = \text{SHA-256}(H_{n-1} \,\|\, \text{Payload Canônico})$. Qualquer mutação posterior invalida instantaneamente toda a cadeia subsequente.</li>
            </ul>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Total de Blocos Encadeados</small>
                    <div class="fs-4 fw-bold text-white"><?= $report['total_blocks'] ?></div>
                    <small class="text-muted"><?= $report['valid_blocks'] ?> blocos válidos</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Status do Encadeamento</small>
                    <div class="fs-4 fw-bold <?= $report['is_chain_valid'] ? 'text-success' : 'text-danger' ?>">
                        <?= $report['is_chain_valid'] ? '100% ÍNTEGRO' : 'VIOLADO' ?>
                    </div>
                    <small class="text-muted"><?= $report['tampered_blocks_count'] ?> anomalias detectadas</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Hash Gênese da Cadeia</small>
                    <div class="text-info font-monospace small text-truncate" title="<?= htmlspecialchars($report['genesis_hash']) ?>">
                        <?= htmlspecialchars(substr($report['genesis_hash'], 0, 16)) ?>...
                    </div>
                    <small class="text-muted">Bloco #0 Inicial</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Hash Terminal Atual</small>
                    <div class="text-warning font-monospace small text-truncate" title="<?= htmlspecialchars($report['terminal_hash']) ?>">
                        <?= htmlspecialchars(substr($report['terminal_hash'], 0, 16)) ?>...
                    </div>
                    <small class="text-muted">Bloco #<?= $report['total_blocks'] ?> Topo</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_LOGS_AUDITORIA_LGPD: SEÇÃO 1 — DADOS DE ENTRADA (FILTROS) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 ce-input-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-funnel text-info me-2"></i>CE_LOGS_AUDITORIA_LGPD — Dados de Entrada (Filtros de Pesquisa)
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-down-circle me-1"></i>Parâmetros de Entrada</span>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3">
            <input type="hidden" name="route" value="compliance">

            <div class="col-md-4">
                <label for="search_actor" class="form-label text-light small fw-bold">Usuário / Operador</label>
                <input type="text" name="search_actor" id="search_actor" class="form-control bg-dark text-white border-secondary" placeholder="Ex: usr-admin, sistema..." value="<?= htmlspecialchars($_GET['search_actor'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label for="search_action" class="form-label text-light small fw-bold">Ação Auditada</label>
                <input type="text" name="search_action" id="search_action" class="form-control bg-dark text-white border-secondary" placeholder="Ex: EE_EMITIR_APOLICE_SEGURO..." value="<?= htmlspecialchars($_GET['search_action'] ?? '') ?>">
            </div>

            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrar Ledger
                </button>
                <a href="index.php?route=compliance" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_LOGS_AUDITORIA_LGPD: SEÇÃO 2 — DADOS DE SAÍDA (LEDGER IMUTÁVEL) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-journal-text text-success me-2"></i>CE_LOGS_AUDITORIA_LGPD — Dados de Saída (Ledger Criptográfico de Auditoria)
            <span class="badge bg-secondary ms-2"><?= count($logs) ?> evento(s)</span>
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-up-circle me-1"></i>Resultados da Consulta</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0 font-monospace small">
                <thead class="table-dark text-muted text-uppercase">
                    <tr>
                        <th style="font-family: inherit;">Data / Hora (UTC)</th>
                        <th style="font-family: inherit;">Operador</th>
                        <th style="font-family: inherit;">Ação</th>
                        <th style="font-family: inherit;">Entidade</th>
                        <th style="font-family: inherit;">Hash Anterior (Prev)</th>
                        <th style="font-family: inherit;">Assinatura SHA-256</th>
                        <th style="font-family: inherit;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted font-sans">Nenhum evento de auditoria localizado para os filtros informados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td>
                                    <span class="text-light"><?= date('d/m/Y H:i:s', strtotime($l['timestamp'])) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-info"><?= htmlspecialchars($l['actor_user_id'] ?? 'sistema') ?></span>
                                </td>
                                <td>
                                    <strong class="text-white"><?= htmlspecialchars($l['action']) ?></strong>
                                </td>
                                <td>
                                    <span class="text-muted"><?= htmlspecialchars($l['entity_type'] ?? '') ?> (<?= htmlspecialchars($l['entity_id'] ?? '') ?>)</span>
                                </td>
                                <td>
                                    <span class="text-secondary" title="<?= htmlspecialchars($l['previous_hash'] ?? '') ?>">
                                        <?= htmlspecialchars(substr($l['previous_hash'] ?? '000000', 0, 10)) ?>...
                                    </span>
                                </td>
                                <td>
                                    <code class="text-warning fw-bold" title="<?= htmlspecialchars($l['integrity_hash']) ?>">
                                        <?= htmlspecialchars(substr($l['integrity_hash'], 0, 16)) ?>...
                                    </code>
                                </td>
                                <td>
                                    <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>IMUTÁVEL</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Gravar Evento (EE_GRAVAR_EVENTO_AUDITORIA) -->
<div class="modal fade" id="auditModal" tabindex="-1" aria-labelledby="auditModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="auditModalLabel">
                    <i class="bi bi-shield-lock text-success me-2"></i>EE_GRAVAR_EVENTO_AUDITORIA — Registro no Ledger
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=compliance&action=record_log" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="action" class="form-label text-light">Ação Realizada <span class="text-danger">*</span></label>
                        <select name="action" id="action" class="form-select bg-dark text-white border-secondary" required>
                            <option value="REPASSE_COMISSAO_CORRETOR">Repasse de Comissão ao Corretor SUSEP</option>
                            <option value="EXPORTACAO_DADOS_LGPD">Exportação de Relatório de Dados Sensíveis LGPD</option>
                            <option value="AUTORIZACAO_PROCEDIMENTO_ANS">Autorização de Procedimento Médico TISS ANS</option>
                            <option value="HOMOLOGACAO_RECIBO_FERIAS">Homologação de Recibo Formal de Férias CLT</option>
                            <option value="LIQUIDACAO_PIX_SPI">Liquidação de Prêmio via Gateway PIX SPI</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="entity_name" class="form-label text-light">Tipo da Entidade Auditada <span class="text-danger">*</span></label>
                        <input type="text" name="entity_name" id="entity_name" class="form-control bg-dark text-white border-secondary" value="InsurancePolicy" required>
                    </div>

                    <div class="mb-3">
                        <label for="entity_id" class="form-label text-light">ID do Registro Afetado <span class="text-danger">*</span></label>
                        <input type="text" name="entity_id" id="entity_id" class="form-control bg-dark text-white border-secondary" value="POL-<?= rand(1000, 9999) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-lock me-1"></i>Assinar com SHA-256 e Gravar</button>
                </div>
            </form>
        </div>
    </div>
</div>
