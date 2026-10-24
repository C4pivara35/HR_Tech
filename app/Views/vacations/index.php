<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-umbrella me-2 text-warning"></i>Gestão de Férias e Saldos Aquisitivos CLT</h2>
            <p class="text-muted mb-0">Controle de períodos aquisitivos, cálculo de 1/3 constitucional, abono pecuniário e emissão de recibos.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>CE_CONSULTAR_SALDO_FERIAS (4 PFB)</span>
            <span class="badge bg-success text-uppercase me-2"><i class="bi bi-calculator me-1"></i>SE_CALCULO_RECIBO_FERIAS (7 PFB)</span>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#vacationModal">
                <i class="bi bi-calendar-plus me-1"></i>Solicitar Férias
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
<!-- CE_CONSULTAR_SALDO_FERIAS: SEÇÃO 1 — DADOS DE ENTRADA (FILTROS) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 ce-input-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-funnel-fill text-info me-2"></i>CE_CONSULTAR_SALDO_FERIAS — Dados de Entrada (Filtros de Pesquisa)
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-down-circle me-1"></i>Parâmetros de Entrada</span>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3">
            <input type="hidden" name="route" value="vacations">
            
            <div class="col-md-5">
                <label for="search_employee_id" class="form-label text-light small fw-bold">Filtrar por Colaborador</label>
                <select name="search_employee_id" id="search_employee_id" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos os Colaboradores Ativos</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['id']) ?>" <?= ($_GET['search_employee_id'] ?? '') === $emp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['full_name']) ?> (Matrícula: <?= htmlspecialchars($emp['matricula'] ?? 'N/A') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="search_status" class="form-label text-light small fw-bold">Status da Solicitação</label>
                <select name="search_status" id="search_status" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos os Status</option>
                    <option value="REQUESTED" <?= ($_GET['search_status'] ?? '') === 'REQUESTED' ? 'selected' : '' ?>>Em Análise (REQUESTED)</option>
                    <option value="APPROVED" <?= ($_GET['search_status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>Aprovadas (APPROVED)</option>
                    <option value="REJECTED" <?= ($_GET['search_status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Recusadas (REJECTED)</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Consultar Saldo
                </button>
                <a href="index.php?route=vacations" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_CONSULTAR_SALDO_FERIAS: SEÇÃO 2 — DADOS DE SAÍDA (SALDOS E PERÍODOS AQUISITIVOS) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-wallet2 text-success me-2"></i>CE_CONSULTAR_SALDO_FERIAS — Dados de Saída (Quadro de Saldos Aquisitivos)
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-up-circle me-1"></i>Resultados da Consulta</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Colaborador</th>
                        <th>Departamento / Cargo</th>
                        <th>Admissão CLT</th>
                        <th>Saldo Disponível</th>
                        <th>Elegibilidade</th>
                        <th class="text-end">Ação Rápida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum colaborador encontrado com os filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): 
                            $balance = (int)($emp['vacation_days_balance'] ?? 30);
                            $admDate = !empty($emp['admission_date']) ? date('d/m/Y', strtotime($emp['admission_date'])) : 'N/A';
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($emp['full_name']) ?></div>
                                    <small class="text-muted">CPF: <?= htmlspecialchars($emp['cpf'] ?? '') ?> | Matrícula: <?= htmlspecialchars($emp['matricula'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($emp['department_name'] ?? 'Geral') ?></span>
                                    <small class="text-muted d-block"><?= htmlspecialchars($emp['role_name'] ?? 'CLT') ?></small>
                                </td>
                                <td class="text-light"><?= $admDate ?></td>
                                <td>
                                    <?php if ($balance >= 30): ?>
                                        <span class="badge bg-danger fs-6"><?= $balance ?> dias (Período Vencido)</span>
                                    <?php elseif ($balance >= 15): ?>
                                        <span class="badge bg-warning text-dark fs-6"><?= $balance ?> dias disponíveis</span>
                                    <?php else: ?>
                                        <span class="badge bg-success fs-6"><?= $balance ?> dias</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($balance >= 5): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Apto para Gozo (Art. 134)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-clock me-1"></i>Em Aquisição</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#vacationModal" onclick="document.getElementById('modal_employee_id').value='<?= $emp['id'] ?>';">
                                        <i class="bi bi-calendar-plus me-1"></i>Agendar
                                    </button>
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
<!-- SE_CALCULO_RECIBO_FERIAS — JUSTIFICATIVA DO DADO DERIVADO -->
<!-- ============================================================================== -->
<?php if (!empty($vacationReceipt)): ?>
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-calculator text-warning me-2"></i>SE_CALCULO_RECIBO_FERIAS — Justificativa do Dado Derivado
        </span>
        <span class="se-badge"><i class="bi bi-info-circle me-1"></i>Memória de Cálculo CLT</span>
    </div>
    <div class="card-body">
        <div class="p-3 mb-3 rounded bg-black border border-secondary text-light">
            <div class="fw-bold text-warning mb-2"><i class="bi bi-book me-1"></i>Regra Legal & Matemática de Derivação (Artigos 129 a 145 CLT + Art. 7º, XVII CF/88):</div>
            <div class="small text-muted mb-2">
                1. <strong>Remuneração de Férias:</strong> Salário-Base ÷ 30 dias × <?= $vacationReceipt['days_gozo'] ?> dias de descanso = 
                <span class="text-white fw-bold">R$ <?= number_format($vacationReceipt['earnings']['remuneracao_ferias'], 2, ',', '.') ?></span>.
            </div>
            <div class="small text-muted mb-2">
                2. <strong>1/3 Constitucional (Art. 7º, XVII CF/88):</strong> Remuneração de Férias ÷ 3 = 
                <span class="text-white fw-bold">R$ <?= number_format($vacationReceipt['earnings']['terco_constitucional_cf88'], 2, ',', '.') ?></span>.
            </div>
            <?php if (!empty($vacationReceipt['abono_days'])): ?>
                <div class="small text-muted mb-2">
                    3. <strong>Abono Pecuniário (Art. 143 CLT - <?= $vacationReceipt['abono_days'] ?> dias):</strong> Salário-Base ÷ 30 × <?= $vacationReceipt['abono_days'] ?> = 
                    <span class="text-white fw-bold">R$ <?= number_format($vacationReceipt['earnings']['abono_pecuniario_art143'], 2, ',', '.') ?></span> 
                    + 1/3 do Abono (<span class="text-white fw-bold">R$ <?= number_format($vacationReceipt['earnings']['terco_abono_pecuniario'], 2, ',', '.') ?></span>).
                    <span class="text-info fst-italic ms-1">(Natureza Indenizatória — Súmula 386 STJ: Isento de INSS e IRRF)</span>.
                </div>
            <?php endif; ?>
            <?php if (!empty($vacationReceipt['earnings']['adiantamento_decimo_terceiro'])): ?>
                <div class="small text-muted mb-2">
                    4. <strong>Adiantamento de 50% do 13º Salário (Lei 4.749/65):</strong> 
                    <span class="text-white fw-bold">R$ <?= number_format($vacationReceipt['earnings']['adiantamento_decimo_terceiro'], 2, ',', '.') ?></span>.
                </div>
            <?php endif; ?>
            <div class="small text-muted mb-2">
                5. <strong>Descontos Previdenciários e Fiscais:</strong> INSS Progressivo (<span class="text-danger fw-bold">R$ <?= number_format($vacationReceipt['deductions']['inss_ferias'], 2, ',', '.') ?></span>) e IRRF Progressivo (<span class="text-danger fw-bold">R$ <?= number_format($vacationReceipt['deductions']['irrf_ferias'], 2, ',', '.') ?></span>) incidentes unicamente sobre a base tributável de gozo + 1/3.
            </div>
            <div class="small text-muted">
                6. <strong>Prazo Improrrogável de Pagamento:</strong> Art. 145 CLT exige liquidação financeira até 2 dias antes do início do descanso (<span class="text-warning fw-bold"><?= date('d/m/Y', strtotime($vacationReceipt['payment_deadline_legal'])) ?></span>). O desrespeito acarreta dobra da remuneração (Súmula 450 TST).
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Total Proventos Bruto</small>
                    <div class="fs-4 fw-bold text-success">R$ <?= number_format($vacationReceipt['earnings']['total_bruto'], 2, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Total Retenções Legais</small>
                    <div class="fs-4 fw-bold text-danger">R$ <?= number_format($vacationReceipt['deductions']['total_descontos'], 2, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Valor Líquido do Recibo</small>
                    <div class="fs-4 fw-bold text-warning">R$ <?= number_format($vacationReceipt['net_vacation_amount'], 2, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-center">
                <a href="index.php?route=vacations&action=receipt&id=<?= urlencode($vacationReceipt['vacation_id']) ?>" class="btn btn-warning w-100 py-3 fw-bold">
                    <i class="bi bi-printer me-2"></i>Emitir Recibo Formal
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================================== -->
<!-- HISTÓRICO DE SOLICITAÇÕES DE FÉRIAS REGISTRADAS -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-list-check text-info me-2"></i>Solicitações e Recibos Emitidos
            <span class="badge bg-secondary ms-2"><?= count($vacations) ?> registro(s)</span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Colaborador</th>
                        <th>Período de Gozo</th>
                        <th>Dias Gozo</th>
                        <th>Abono</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vacations)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma solicitação de férias cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($vacations as $vac): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($vac['employee_name']) ?></div>
                                    <small class="text-muted">Matrícula: <?= htmlspecialchars($vac['matricula'] ?? 'N/A') ?> | Dep: <?= htmlspecialchars($vac['department_name'] ?? 'Geral') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light">
                                        <?= date('d/m/Y', strtotime($vac['start_date'])) ?> até <?= date('d/m/Y', strtotime($vac['end_date'])) ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-info text-dark fw-bold"><?= (int)$vac['duration_days'] ?> dias</span></td>
                                <td>
                                    <?php if (!empty($vac['abono_pecuniario'])): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-cash-coin me-1"></i><?= (int)$vac['abono_days'] ?> dias</span>
                                    <?php else: ?>
                                        <span class="text-muted">Não</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($vac['status'] === 'APPROVED'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aprovada</span>
                                    <?php elseif ($vac['status'] === 'REJECTED'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Recusada</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Em Análise</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?route=vacations&action=receipt&id=<?= urlencode($vac['id']) ?>" class="btn btn-outline-warning" title="Visualizar Recibo">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        <?php if ($vac['status'] !== 'APPROVED'): ?>
                                            <a href="index.php?route=vacations&action=approve&id=<?= urlencode($vac['id']) ?>" class="btn btn-outline-success" title="Aprovar e debitar saldo" onclick="return confirm('Confirma aprovação das férias?');">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?route=vacations&action=delete&id=<?= urlencode($vac['id']) ?>" class="btn btn-outline-danger" title="Excluir" onclick="return confirm('Excluir solicitação de férias?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Agendamento / Solicitação de Férias CLT (EE_SOLICITAR_FERIAS) -->
<div class="modal fade" id="vacationModal" tabindex="-1" aria-labelledby="vacationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="vacationModalLabel">
                    <i class="bi bi-umbrella text-warning me-2"></i>Solicitar Concessão de Férias (Art. 134 CLT)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=vacations&action=request" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="modal_employee_id" class="form-label text-light">Colaborador Solicitante <span class="text-danger">*</span></label>
                        <select name="employee_id" id="modal_employee_id" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o colaborador...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= htmlspecialchars($emp['id']) ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (Saldo: <?= (int)$emp['vacation_days_balance'] ?> dias)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label text-light">Data de Início do Gozo <span class="text-danger">*</span></label>
                            <input type="date" class="form-control bg-dark text-white border-secondary" id="start_date" name="start_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                            <div class="form-text text-muted">Aviso prévio mínimo de 30 dias (Art. 135 CLT).</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="days_count" class="form-label text-light">Dias de Gozo <span class="text-danger">*</span></label>
                            <select class="form-select bg-dark text-white border-secondary" id="days_count" name="days_count" required>
                                <option value="30">30 Dias (Período Integral)</option>
                                <option value="20">20 Dias (Comum com Venda de 10 dias)</option>
                                <option value="15" selected>15 Dias (1º Período Fracionado)</option>
                                <option value="10">10 Dias (Fracionamento Legal)</option>
                                <option value="5">5 Dias (Mínimo Legal Art. 134 §1º)</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 mb-3 rounded bg-black border border-secondary">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="abono_pecuniario" name="abono_pecuniario" value="1" onchange="document.getElementById('abono_days_container').style.display = this.checked ? 'block' : 'none';">
                            <label class="form-check-label text-light fw-bold" for="abono_pecuniario">
                                Optar por Abono Pecuniário (Venda de Férias - Art. 143 CLT)
                            </label>
                        </div>
                        <div id="abono_days_container" style="display: none;" class="mt-2 ps-4">
                            <label for="abono_days" class="form-label text-light small">Quantidade de Dias Vendidos (Máximo 1/3 = 10 dias)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" id="abono_days" name="abono_days" min="1" max="10" value="10">
                            <div class="form-text text-info small">Isento de incidência de INSS e IRRF (Súmula 386 STJ).</div>
                        </div>

                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="advance_thirteenth_salary" name="advance_thirteenth_salary" value="1">
                            <label class="form-check-label text-light fw-bold" for="advance_thirteenth_salary">
                                Solicitar Adiantamento da 1ª Parcela do 13º Salário (50%)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check2-circle me-1"></i>Confirmar Solicitação</button>
                </div>
            </form>
        </div>
    </div>
</div>
