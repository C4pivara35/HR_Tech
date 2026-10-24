<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-check2-circle me-2 text-warning"></i>Central de Aprovações do Gestor</h2>
            <p class="text-muted mb-0">Workflow unificado de homologação de solicitações de férias e retificações de jornada de trabalho.</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark me-2"><i class="bi bi-shield-check me-1"></i>Tela 11 — Workflow Gerencial</span>
            <span class="badge bg-info text-dark"><i class="bi bi-diagram-3 me-1"></i>CLT / Portaria 671</span>
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

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-dark border-secondary">
            <div class="card-body d-flex align-items-center">
                <div class="rounded p-3 bg-primary bg-opacity-25 text-primary me-3">
                    <i class="bi bi-calendar-event fs-3"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">Férias Pendentes</h6>
                    <h3 class="card-title text-white mb-0"><?= count($pendingVacations) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark border-secondary">
            <div class="card-body d-flex align-items-center">
                <div class="rounded p-3 bg-warning bg-opacity-25 text-warning me-3">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">Retificações de Ponto</h6>
                    <h3 class="card-title text-white mb-0"><?= count($pendingAdjustments) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark border-secondary">
            <div class="card-body d-flex align-items-center">
                <div class="rounded p-3 bg-success bg-opacity-25 text-success me-3">
                    <i class="bi bi-clipboard2-check fs-3"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">Histórico Despachado</h6>
                    <h3 class="card-title text-white mb-0"><?= count($historyVacations) + count($historyAdjustments) ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Vacation Requests Section -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-calendar-check text-primary me-2"></i>Solicitações de Férias Pendentes de Homologação
            <span class="badge bg-secondary ms-2"><?= count($pendingVacations) ?> pendente(s)</span>
        </span>
        <span class="badge bg-primary">EE_SOLICITAR_FERIAS (Valentin)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pendingVacations)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                Nenhuma solicitação de férias aguardando despacho no momento.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead class="table-dark text-muted text-uppercase small">
                        <tr>
                            <th>Colaborador</th>
                            <th>Período Solicitado</th>
                            <th>Dias Gozo</th>
                            <th>Abono Pecuniário</th>
                            <th>Saldo Atual</th>
                            <th>Data Solicitação</th>
                            <th class="text-end">Ações do Gestor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingVacations as $vac): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($vac['employee_name']) ?></div>
                                    <small class="text-muted">Matrícula: <?= htmlspecialchars($vac['matricula'] ?? 'N/A') ?> | CPF: <?= htmlspecialchars($vac['cpf'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light">
                                        <?= date('d/m/Y', strtotime($vac['start_date'])) ?> até <?= date('d/m/Y', strtotime($vac['end_date'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark fw-bold"><?= (int)$vac['duration_days'] ?> dias</span>
                                </td>
                                <td>
                                    <?php if (!empty($vac['abono_pecuniario'])): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-cash-coin me-1"></i>Sim (<?= (int)$vac['abono_days'] ?> dias)</span>
                                    <?php else: ?>
                                        <span class="text-muted">Não</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= (int)$vac['vacation_days_balance'] ?> dias</span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($vac['created_at'])) ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?route=approvals&action=approve_vacation&id=<?= urlencode($vac['id']) ?>" class="btn btn-success" title="Aprovar e debitar saldo" onclick="return confirm('Confirma a APROVAÇÃO destas férias e débito automático de saldo?');">
                                            <i class="bi bi-check-lg me-1"></i>Aprovar
                                        </a>
                                        <a href="index.php?route=approvals&action=reject_vacation&id=<?= urlencode($vac['id']) ?>" class="btn btn-outline-danger" title="Recusar solicitação" onclick="return confirm('Confirma a RECUSA desta solicitação de férias?');">
                                            <i class="bi bi-x-lg me-1"></i>Recusar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pending Time Adjustment Requests Section -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-clock-history text-warning me-2"></i>Retificações e Abonos de Ponto Pendentes
            <span class="badge bg-secondary ms-2"><?= count($pendingAdjustments) ?> pendente(s)</span>
        </span>
        <span class="badge bg-warning text-dark">EE_AJUSTE_ABONO_PONTO (Felipe)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pendingAdjustments)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                Nenhum pedido de ajuste de ponto pendente de homologação.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead class="table-dark text-muted text-uppercase small">
                        <tr>
                            <th>Colaborador</th>
                            <th>Data Ocorrência</th>
                            <th>Horário Solicitado</th>
                            <th>Justificativa Legal</th>
                            <th>Solicitado Em</th>
                            <th class="text-end">Ações do Gestor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingAdjustments as $adj): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($adj['employee_name']) ?></div>
                                    <small class="text-muted">Matrícula: <?= htmlspecialchars($adj['matricula'] ?? 'N/A') ?> | CPF: <?= htmlspecialchars($adj['cpf'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light">
                                        <?= date('d/m/Y', strtotime($adj['requested_date'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($adj['requested_time']) ?></span>
                                </td>
                                <td style="max-width: 320px;">
                                    <div class="text-truncate text-light" title="<?= htmlspecialchars($adj['reason'] ?? '') ?>">
                                        <?= htmlspecialchars($adj['reason'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($adj['created_at'])) ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?route=approvals&action=approve_timelog&id=<?= urlencode($adj['id']) ?>" class="btn btn-success" title="Homologar e assinar no espelho" onclick="return confirm('Confirma a homologação deste ajuste no espelho de ponto?');">
                                            <i class="bi bi-check-lg me-1"></i>Homologar
                                        </a>
                                        <a href="index.php?route=approvals&action=reject_timelog&id=<?= urlencode($adj['id']) ?>" class="btn btn-outline-danger" title="Recusar ajuste" onclick="return confirm('Confirma a rejeição deste ajuste?');">
                                            <i class="bi bi-x-lg me-1"></i>Rejeitar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- History of Decisions -->
<div class="card bg-dark border-secondary shadow-sm">
    <div class="card-header border-secondary py-3">
        <span class="text-white fw-semibold"><i class="bi bi-clock-history me-2 text-info"></i>Últimos Despachos do Gestor</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Tipo</th>
                        <th>Colaborador</th>
                        <th>Detalhes</th>
                        <th>Status Resolução</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $allHistory = array_merge($historyVacations, $historyAdjustments);
                    if (empty($allHistory)): 
                    ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Nenhum despacho recente encontrado no histórico.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allHistory as $h): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($h['item_type']) ?></span>
                                </td>
                                <td class="text-white">
                                    <?= htmlspecialchars($h['employee_name']) ?>
                                </td>
                                <td class="small text-muted">
                                    <?php if ($h['item_type'] === 'Férias'): ?>
                                        Gozo: <?= (int)$h['duration_days'] ?> dias (<?= date('d/m/Y', strtotime($h['start_date'])) ?> a <?= date('d/m/Y', strtotime($h['end_date'])) ?>)
                                    <?php else: ?>
                                        Data: <?= date('d/m/Y', strtotime($h['requested_date'])) ?> às <?= htmlspecialchars($h['requested_time']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($h['resolution'] === 'APPROVED'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>APROVADO</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>RECUSADO</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
