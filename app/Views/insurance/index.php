<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-shield-check me-2 text-success"></i>Gestão de Seguros Corporativos & Sinistralidade Atuarial</h2>
            <p class="text-muted mb-0">Subscrição de apólices coletivas de vida, D&O executivo, tarifação SUSEP e conciliação de liquidação via PIX SPI.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>CE_CONSULTAR_APOLICE_VIGENTE (4 PFB)</span>
            <span class="badge bg-success text-uppercase me-2"><i class="bi bi-calculator me-1"></i>SE_RELATORIO_SINISTRALIDADE (7 PFB)</span>
            <span class="badge bg-info text-dark text-uppercase me-2"><i class="bi bi-send-check me-1"></i>EE_EMITIR_APOLICE_SEGURO (4 PFB)</span>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#policyModal">
                <i class="bi bi-plus-circle me-1"></i>Emitir Apólice
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
<!-- CE_CONSULTAR_APOLICE_VIGENTE: SEÇÃO 1 — DADOS DE ENTRADA (FILTROS) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 ce-input-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-funnel text-info me-2"></i>CE_CONSULTAR_APOLICE_VIGENTE — Dados de Entrada (Filtros de Pesquisa)
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-down-circle me-1"></i>Parâmetros de Entrada</span>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3">
            <input type="hidden" name="route" value="insurance">

            <div class="col-md-5">
                <label for="search_broker" class="form-label text-light small fw-bold">Buscar por Corretor / Nº Apólice</label>
                <input type="text" name="search_broker" id="search_broker" class="form-control bg-dark text-white border-secondary" placeholder="Ex: BRK-001 ou POL-FIN..." value="<?= htmlspecialchars($_GET['search_broker'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label for="search_status" class="form-label text-light small fw-bold">Status da Apólice</label>
                <select name="search_status" id="search_status" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos os Status</option>
                    <option value="ACTIVE" <?= ($_GET['search_status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Vigente (ACTIVE)</option>
                    <option value="PENDING" <?= ($_GET['search_status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Em Subscrição (PENDING)</option>
                    <option value="EXPIRED" <?= ($_GET['search_status'] ?? '') === 'EXPIRED' ? 'selected' : '' ?>>Expirada (EXPIRED)</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Consultar Apólices
                </button>
                <a href="index.php?route=insurance" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_CONSULTAR_APOLICE_VIGENTE: SEÇÃO 2 — DADOS DE SAÍDA (APÓLICES VIGENTES) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-shield-check text-success me-2"></i>CE_CONSULTAR_APOLICE_VIGENTE — Dados de Saída (Apólices Ativas FinCorp)
            <span class="badge bg-secondary ms-2"><?= count($policies) ?> apólice(s)</span>
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-up-circle me-1"></i>Resultados da Consulta</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Número da Apólice</th>
                        <th>Segurado Titular</th>
                        <th>Corretor / Seguradora</th>
                        <th>Capital Segurado (Teto)</th>
                        <th>Prêmio Mensal</th>
                        <th>Vigência</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($policies)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Nenhuma apólice de seguro vigente encontrada para os filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($policies as $pol): ?>
                            <tr>
                                <td>
                                    <code class="text-info fw-bold fs-6"><?= htmlspecialchars($pol['policy_number']) ?></code>
                                </td>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($pol['employee_name'] ?? 'Segurado Coletivo') ?></div>
                                    <small class="text-muted">CPF: <?= htmlspecialchars($pol['cpf'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light"><?= htmlspecialchars($pol['broker_code'] ?? 'BRK-001') ?></span>
                                    <small class="text-muted d-block"><?= htmlspecialchars($pol['insurer_name'] ?? 'FinCorp Seguros') ?></small>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">R$ <?= number_format(((int)($pol['insured_capital_cents'] ?? 0)) / 100, 2, ',', '.') ?></span>
                                </td>
                                <td>
                                    <span class="text-white">R$ <?= number_format(((int)($pol['monthly_premium_cents'] ?? 0)) / 100, 2, ',', '.') ?></span>
                                </td>
                                <td>
                                    <small class="text-muted d-block"><?= date('d/m/Y', strtotime($pol['start_date'])) ?> até</small>
                                    <span class="badge bg-dark border border-secondary text-warning"><?= date('d/m/Y', strtotime($pol['end_date'])) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><i class="bi bi-shield-fill-check me-1"></i><?= htmlspecialchars($pol['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <a href="index.php?route=pix" class="btn btn-outline-warning btn-sm" title="Gerenciar Cobrança PIX SPI">
                                        <i class="bi bi-qr-code"></i>
                                    </a>
                                    <a href="index.php?route=insurance&action=delete&id=<?= urlencode($pol['id']) ?>" class="btn btn-outline-danger btn-sm ms-1" title="Excluir Apólice" onclick="return confirm('Confirma a exclusão desta apólice?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- SE_RELATORIO_SINISTRALIDADE — JUSTIFICATIVA DO DADO DERIVADO -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-calculator text-warning me-2"></i>SE_RELATORIO_SINISTRALIDADE — Justificativa do Dado Derivado & Análise Atuarial
        </span>
        <span class="se-badge"><i class="bi bi-graph-up-arrow me-1"></i>Benchmark SUSEP (70%)</span>
    </div>
    <div class="card-body">
        <div class="p-3 mb-4 rounded bg-black border border-secondary text-light">
            <div class="fw-bold text-warning mb-2"><i class="bi bi-rulers me-1"></i>Fórmula Matemática & Parâmetros Atuariais de Solvência:</div>
            <div class="mb-2">
                <code class="se-formula p-2 rounded d-inline-block">Sinistralidade (%) = (Sinistros Indenizados Pagos / Prêmios Anuais Arrecadados) × 100</code>
            </div>
            <ul class="mb-0 small text-muted ps-3">
                <li><strong>Benchmark Regulatório da SUSEP (Circular nº 600/2020):</strong> O ponto de equilíbrio técnico-financeiro da carteira de seguros corporativos situa-se em <strong>70% de sinistralidade</strong>. Índices inferiores a 70% geram superávit técnico para reserva atuarial. Índices superiores a 70% acionam gatilho compulsório de reajuste técnico anual de recomposição de carteira.</li>
                <li><strong>Projeção de Reajuste Técnico:</strong> Se $\text{Sinistralidade} > 70\%$, aplica-se $\text{Reajuste} = (\text{Sinistralidade} - 70) \times 1.15$ para solvência contínua.</li>
            </ul>
        </div>

        <!-- KPI Metrics -->
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Prêmios Anuais Arrecadados</small>
                    <div class="fs-4 fw-bold text-white">R$ <?= number_format($lossRatio['annual_premiums_collected'], 2, ',', '.') ?></div>
                    <small class="text-muted"><?= $lossRatio['policies_count'] ?> apólices na carteira</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Sinistros Históricos Pagos</small>
                    <div class="fs-4 fw-bold text-danger">R$ <?= number_format($lossRatio['claims_paid_historical'], 2, ',', '.') ?></div>
                    <small class="text-muted">Indenizações liquidadas</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Índice de Sinistralidade Atual</small>
                    <div class="fs-4 fw-bold <?= $lossRatio['loss_ratio_percent'] <= 70.0 ? 'text-success' : 'text-danger' ?>">
                        <?= $lossRatio['loss_ratio_percent'] ?>%
                    </div>
                    <small class="text-muted">Alvo: ≤ 70.0% (SUSEP)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Margem de Superávit / Saldo</small>
                    <div class="fs-4 fw-bold <?= $lossRatio['portfolio_surplus_reais'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        R$ <?= number_format($lossRatio['portfolio_surplus_reais'], 2, ',', '.') ?>
                    </div>
                    <small class="badge <?= $lossRatio['loss_ratio_percent'] <= 70.0 ? 'bg-success' : 'bg-danger' ?> mt-1">
                        <?= htmlspecialchars($lossRatio['actuarial_health']) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CONEXÕES DE APIS EXTERNAS (AIEs) -->
<!-- ============================================================================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-semibold"><i class="bi bi-shield-check text-info me-2"></i>AIE_FINCORP_SEGURADORA</span>
                <span class="badge bg-primary">7 PFB / Fernando</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Gateway de subscrição atuarial externa SUSEP para tarifação de riscos de vida coletivo e cobertura executiva D&O.</p>
                <a href="index.php?route=external_simulators&action=test&service=FINCORP_SEGURADORA" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-play-circle me-1"></i>Simular Subscrição Externa
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-semibold"><i class="bi bi-qr-code text-warning me-2"></i>AIE_PIX_BACEN_GATEWAY</span>
                <span class="badge bg-primary">5 PFB / Fernando</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Cobrança dinâmica EMVCo com liquidação imediata no Sistema de Pagamentos Instantâneos (SPI) do Banco Central.</p>
                <a href="index.php?route=pix" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-arrow-right-circle me-1"></i>Abrir Terminal PIX BACEN
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Emitir Apólice (EE_EMITIR_APOLICE_SEGURO) -->
<div class="modal fade" id="policyModal" tabindex="-1" aria-labelledby="policyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="policyModalLabel">
                    <i class="bi bi-shield-plus text-success me-2"></i>EE_EMITIR_APOLICE_SEGURO — Subscrição FinCorp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=insurance&action=issue" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="ins_employee_id" class="form-label text-light">Colaborador Titular / Segurado <span class="text-danger">*</span></label>
                        <select name="employee_id" id="ins_employee_id" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o segurado...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= htmlspecialchars($emp['id']) ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (CPF: <?= htmlspecialchars($emp['cpf'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="policy_type" class="form-label text-light">Ramo de Cobertura</label>
                            <select name="policy_type" id="policy_type" class="form-select bg-dark text-white border-secondary">
                                <option value="LIFE_GROUP">Vida em Grupo Corporativo</option>
                                <option value="D_AND_O">Responsabilidade Civil D&O</option>
                                <option value="ACCIDENTS">Acidentes Pessoais Coletivo</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="broker_code" class="form-label text-light">Código do Corretor SUSEP</label>
                            <input type="text" name="broker_code" id="broker_code" class="form-control bg-dark text-white border-secondary" value="BRK-001">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="coverage_amount" class="form-label text-light">Capital Segurado Pretendido (R$) <span class="text-danger">*</span></label>
                        <input type="number" step="50000" name="coverage_amount" id="coverage_amount" class="form-control bg-dark text-white border-secondary" value="500000" required>
                        <div class="form-text text-muted">A subscrição automática do prêmio e cobrança PIX serão calculadas pelo Gateway FinCorp.</div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-check me-1"></i>Emitir Apólice e Gerar PIX</button>
                </div>
            </form>
        </div>
    </div>
</div>
