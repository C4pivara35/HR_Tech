<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-shield-shaded me-2 text-warning"></i>Saúde & Segurança do Trabalho (SST) — NR-06 & NR-07</h2>
            <p class="text-muted mb-0">Controle rigoroso de fichas de EPI, ASOs clínicos, barreira de catraca fabril e Perfil Profissiográfico Previdenciário.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>CE_CONSULTAR_HISTORICO_CLINICO (4 PFB)</span>
            <span class="badge bg-danger text-uppercase me-2"><i class="bi bi-door-closed me-1"></i>SE_BLOQUEIO_CHAO_FABRICA (5 PFB)</span>
            <span class="badge bg-warning text-dark text-uppercase me-2"><i class="bi bi-bell-fill me-1"></i>SE_PAINEL_ALERTAS_NR6_NR7 (5 PFB)</span>
            <span class="badge bg-info text-dark text-uppercase me-2"><i class="bi bi-file-earmark-medical me-1"></i>SE_RELATORIO_PPP_ELETRONICO (5 PFB)</span>
            <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#epiModal">
                <i class="bi bi-shield-plus me-1"></i>Entregar EPI
            </button>
            <button class="btn btn-info btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#asoModal">
                <i class="bi bi-heart-pulse me-1"></i>Registrar ASO
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
<!-- SE_BLOQUEIO_CHAO_FABRICA — CATRACA INDUSTRIAL & SEMÁFORO EM TEMPO REAL -->
<!-- ============================================================================== -->
<?php if (!empty($turnstileResult)): ?>
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-door-closed text-danger me-2"></i>SE_BLOQUEIO_CHAO_FABRICA — Barreira Impeditiva de Catraca Industrial (Hard Gate)
        </span>
        <span class="se-badge"><i class="bi bi-cpu me-1"></i>Portaria MTE / NR-01</span>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-3 text-center mb-3 mb-lg-0">
                <div class="turnstile-box <?= $turnstileResult['status'] === 'GREEN' ? 'active-green' : 'active-red' ?> p-4 rounded text-center">
                    <div class="d-flex justify-content-center gap-3 mb-2">
                        <div class="turnstile-light red <?= $turnstileResult['status'] === 'RED' ? 'active' : '' ?>"></div>
                        <div class="turnstile-light green <?= $turnstileResult['status'] === 'GREEN' ? 'active' : '' ?>"></div>
                    </div>
                    <div class="fs-4 fw-bold mt-2 <?= $turnstileResult['status'] === 'GREEN' ? 'text-success' : 'text-danger' ?>">
                        <?= $turnstileResult['status'] === 'GREEN' ? 'ACESSO LIBERADO' : 'ACESSO BLOQUEADO' ?>
                    </div>
                    <small class="text-muted d-block mt-1"><?= htmlspecialchars($turnstileResult['turnstile_gate'] ?? 'CATRACA_01') ?></small>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="p-3 rounded bg-black border border-secondary text-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong class="text-white fs-5"><?= htmlspecialchars($turnstileResult['employee_name'] ?? 'Colaborador') ?></strong>
                            <span class="text-muted ms-2">(Matrícula: <?= htmlspecialchars($turnstileResult['matricula'] ?? 'N/A') ?> — <?= htmlspecialchars($turnstileResult['department'] ?? 'Operações') ?>)</span>
                        </div>
                        <span class="badge <?= $turnstileResult['status'] === 'GREEN' ? 'bg-success' : 'bg-danger' ?> fs-6">
                            <?= $turnstileResult['status'] === 'GREEN' ? 'CONFORME NR-06 & NR-07' : 'IMPEDIMENTO LEGAL' ?>
                        </span>
                    </div>

                    <div class="small mb-2">
                        <strong>Justificativa da Trava Operacional:</strong>
                        <span class="text-muted"><?= htmlspecialchars($turnstileResult['legal_consequence'] ?? '') ?></span>
                    </div>

                    <?php if (!empty($turnstileResult['violations'])): ?>
                        <div class="mt-2 p-2 rounded bg-danger bg-opacity-10 border border-danger">
                            <span class="text-danger fw-bold small"><i class="bi bi-x-octagon me-1"></i>Violações Detectadas no Crachá:</span>
                            <ul class="mb-0 small text-light ps-3 mt-1">
                                <?php foreach ($turnstileResult['violations'] as $v): ?>
                                    <li><?= htmlspecialchars($v) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="mt-2 p-2 rounded bg-success bg-opacity-10 border border-success text-success small">
                            <i class="bi bi-check-circle me-1"></i>ASO em dia (Apto) e todos os EPIs com CA válidos homologados pelo Ministério do Trabalho.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================================== -->
<!-- SE_PAINEL_ALERTAS_NR6_NR7 — MATRIZ PREDITIVA E MULTAS MTE -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>SE_PAINEL_ALERTAS_NR6_NR7 — Matriz Preditiva de Vencimentos & Risco Fiscal MTE
        </span>
        <span class="se-badge"><i class="bi bi-cash-stack me-1"></i>Exposição Financeira MTE</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Total Registros Auditados</small>
                    <div class="fs-4 fw-bold text-white"><?= $nrAlerts['total_audited_records'] ?></div>
                    <small class="text-muted">Fichas de EPI e Laudos ASO</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Vencidos / Críticos</small>
                    <div class="fs-4 fw-bold text-danger"><?= $nrAlerts['critical_expired_count'] ?></div>
                    <small class="text-danger">Bloqueio imediato na catraca</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">A Vencer em até 30 Dias</small>
                    <div class="fs-4 fw-bold text-warning"><?= $nrAlerts['alert_30_days_count'] ?></div>
                    <small class="text-warning">Agendamento urgente</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Risco Estimado de Multa MTE</small>
                    <div class="fs-4 fw-bold text-danger">R$ <?= number_format($nrAlerts['estimated_mte_fine_exposure_reais'], 2, ',', '.') ?></div>
                    <small class="text-muted">Portaria MTE 4.090/2022</small>
                </div>
            </div>
        </div>

        <div class="p-3 rounded bg-black border border-secondary text-muted small">
            <strong class="text-white"><i class="bi bi-info-circle me-1"></i>Regra de Cálculo da Exposição Fiscal:</strong>
            A matriz preditiva avalia o delta em dias corridos até a data de expiração ($d = \text{Data Expiração} - \text{Hoje}$). Registros com $d \le 0$ configuram auto de infração gravíssimo sob as Normas Regulamentadoras NR-06 (EPI vencido/ausente) e NR-07 (ASO vencido), incorrendo em multa média de R$ 2.850,00 por colaborador autuado.
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_CONSULTAR_HISTORICO_CLINICO: SEÇÃO 1 — DADOS DE ENTRADA (FILTROS) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 ce-input-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-funnel text-info me-2"></i>CE_CONSULTAR_HISTORICO_CLINICO — Dados de Entrada (Filtros de Pesquisa)
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-down-circle me-1"></i>Parâmetros de Entrada</span>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3">
            <input type="hidden" name="route" value="safety">

            <div class="col-md-5">
                <label for="search_employee_id" class="form-label text-light small fw-bold">Colaborador / Prontuário</label>
                <select name="search_employee_id" id="search_employee_id" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos os Colaboradores</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['id']) ?>" <?= ($_GET['search_employee_id'] ?? '') === $emp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['full_name']) ?> (Matrícula: <?= htmlspecialchars($emp['matricula'] ?? 'N/A') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="search_record_type" class="form-label text-light small fw-bold">Tipo de Registro SST</label>
                <select name="search_record_type" id="search_record_type" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos os Registros (EPIs e ASOs)</option>
                    <option value="EPI" <?= ($_GET['search_record_type'] ?? '') === 'EPI' ? 'selected' : '' ?>>Apenas EPIs (NR-06)</option>
                    <option value="ASO" <?= ($_GET['search_record_type'] ?? '') === 'ASO' ? 'selected' : '' ?>>Apenas Atestados ASO (NR-07)</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Consultar Prontuário
                </button>
                <a href="index.php?route=safety" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_CONSULTAR_HISTORICO_CLINICO: SEÇÃO 2 — DADOS DE SAÍDA (PRONTUÁRIO SST) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-clipboard2-pulse text-success me-2"></i>CE_CONSULTAR_HISTORICO_CLINICO — Dados de Saída (Prontuário de Segurança)
            <span class="badge bg-secondary ms-2"><?= count($safetyRecords) ?> registro(s)</span>
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-up-circle me-1"></i>Resultados da Consulta</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Tipo</th>
                        <th>Colaborador</th>
                        <th>Equipamento / Exame</th>
                        <th>Certificado de Aprovação (CA)</th>
                        <th>Data Emissão / Validade</th>
                        <th>Parecer de Aptidão</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($safetyRecords)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Nenhum registro clínico ou entrega de EPI encontrado com os filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($safetyRecords as $r): 
                            $isExpired = !empty($r['expiration_date']) && $r['expiration_date'] < date('Y-m-d');
                        ?>
                            <tr>
                                <td>
                                    <?php if ($r['record_type'] === 'EPI'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-shield-fill me-1"></i>EPI NR-06</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><i class="bi bi-heart-pulse-fill me-1"></i>ASO NR-07</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($r['employee_name']) ?></div>
                                    <small class="text-muted">CPF: <?= htmlspecialchars($r['cpf'] ?? '') ?> | Dep: <?= htmlspecialchars($r['department_name'] ?? 'Geral') ?></small>
                                </td>
                                <td>
                                    <span class="text-light fw-semibold"><?= htmlspecialchars($r['item_name']) ?></span>
                                    <?php if (!empty($r['exam_type'])): ?>
                                        <small class="text-muted d-block">Tipo: <?= htmlspecialchars($r['exam_type']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['ca_number']) && $r['ca_number'] !== 'N/A'): ?>
                                        <code class="text-info fw-bold">CA <?= htmlspecialchars($r['ca_number']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted"><?= date('d/m/Y', strtotime($r['issue_date'])) ?></span>
                                    <div class="small <?= $isExpired ? 'text-danger fw-bold' : 'text-success' ?>">
                                        Val: <?= date('d/m/Y', strtotime($r['expiration_date'])) ?>
                                        <?= $isExpired ? '(VENCIDO)' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ((int)$r['is_fit'] === 1 && !$isExpired): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>APTO</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>INAPTO / BLOQUEADO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="index.php?route=safety&search_employee_id=<?= urlencode($r['employee_id']) ?>" class="btn btn-outline-info btn-sm" title="Verificar Catraca e PPP">
                                        <i class="bi bi-person-badge"></i>
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
<!-- SE_RELATORIO_PPP_ELETRONICO — PERFIL PROFISSIOGRÁFICO PREVIDENCIÁRIO (S-2240) -->
<!-- ============================================================================== -->
<?php if (!empty($pppReport)): ?>
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-file-earmark-medical text-info me-2"></i>SE_RELATORIO_PPP_ELETRONICO — Laudo Pericial do PPP (eSocial Evento S-2240)
        </span>
        <span class="se-badge"><i class="bi bi-file-text me-1"></i>Perfil Previdenciário</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <span class="text-muted small d-block">Trabalhador Avaliado:</span>
                <span class="fs-5 text-white fw-bold"><?= htmlspecialchars($pppReport['employee_name']) ?></span>
                <div class="small text-muted">CPF: <?= htmlspecialchars($pppReport['cpf']) ?> | Matrícula: <?= htmlspecialchars($pppReport['matricula']) ?></div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">Setor / Função:</span>
                <span class="text-white fw-semibold"><?= htmlspecialchars($pppReport['department'] ?? 'Geral') ?></span>
                <div class="small text-muted"><?= htmlspecialchars($pppReport['role'] ?? 'Operador') ?></div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">Médico Coordenador PCMSO:</span>
                <span class="text-white fw-semibold"><?= htmlspecialchars($pppReport['responsible_physician']) ?></span>
            </div>
        </div>

        <h6 class="text-white fw-bold mb-2"><i class="bi bi-radioactive me-2 text-warning"></i>Fatores de Risco e Intensidades Medidas em Campo</h6>
        <div class="table-responsive mb-3">
            <table class="table table-dark table-sm table-bordered border-secondary align-middle">
                <thead class="bg-black text-muted text-uppercase small">
                    <tr>
                        <th>Grupo de Risco</th>
                        <th>Agente Nocivo Identificado</th>
                        <th>Intensidade / Concentração</th>
                        <th>Limite de Tolerância (NR-15)</th>
                        <th>Técnica de Medição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pppReport['environmental_factors'] as $factor): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($factor['factor_type']) ?></span></td>
                            <td class="text-white"><?= htmlspecialchars($factor['agent']) ?></td>
                            <td class="text-warning fw-bold"><?= htmlspecialchars($factor['intensity']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($factor['limit']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($factor['technique']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-2 rounded bg-black border border-secondary text-muted small">
            <i class="bi bi-shield-check text-success me-1"></i><strong>Medidas de Proteção Coletiva (EPC) e Individual (EPI):</strong>
            <?= htmlspecialchars($pppReport['protection_measures']['EPC']) ?> | EPIs: <?= htmlspecialchars($pppReport['protection_measures']['EPI']) ?>.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================================== -->
<!-- CONEXÕES DE APIS EXTERNAS (AIEs) -->
<!-- ============================================================================== -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-semibold"><i class="bi bi-patch-check text-warning me-2"></i>AIE_CATALOGO_CAEPI_MTE</span>
                <span class="badge bg-primary">5 PFB / Nicolas</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Validação em tempo real dos Certificados de Aprovação (CA) na base oficial do Ministério do Trabalho e Emprego.</p>
                <a href="index.php?route=external_simulators&action=test&service=CATALOGO_CAEPI_MTE" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-play-circle me-1"></i>Consultar Base CAEPI MTE
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-semibold"><i class="bi bi-hospital text-info me-2"></i>AIE_CLINICA_MEDICINA_OCUP</span>
                <span class="badge bg-primary">5 PFB / Nicolas</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Integração com clínicas credenciadas e SOC Telemedicina para agendamento de exames e laudos ASO assinados.</p>
                <a href="index.php?route=external_simulators&action=test&service=CLINICA_MEDICINA_OCUP" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-play-circle me-1"></i>Simular Emissão SOC / ASO
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Entregar EPI (EE_REGISTRAR_ENTREGA_EPI) -->
<div class="modal fade" id="epiModal" tabindex="-1" aria-labelledby="epiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="epiModalLabel">
                    <i class="bi bi-shield-plus text-warning me-2"></i>EE_REGISTRAR_ENTREGA_EPI — Ficha de EPI (NR-06)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=safety&action=deliver_equipment" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="epi_employee_id" class="form-label text-light">Colaborador Receptor <span class="text-danger">*</span></label>
                        <select name="employee_id" id="epi_employee_id" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o colaborador...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= htmlspecialchars($emp['id']) ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (CPF: <?= htmlspecialchars($emp['cpf'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="item_name" class="form-label text-light">Descrição do Equipamento de Proteção <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" id="item_name" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Protetor Auricular Tipo Plug de Silicone 3M" required>
                    </div>

                    <div class="mb-3">
                        <label for="ca_number" class="form-label text-light">Número do Certificado de Aprovação (CA) <span class="text-danger">*</span></label>
                        <input type="text" name="ca_number" id="ca_number" class="form-control bg-dark text-white border-secondary" placeholder="Ex: 31469" required>
                        <div class="form-text text-muted">O CA será validado online no portal CAEPI do Ministério do Trabalho.</div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check2-circle me-1"></i>Registrar Entrega Formal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Registrar Exame ASO (EE_REGISTRAR_EXAME_ASO) -->
<div class="modal fade" id="asoModal" tabindex="-1" aria-labelledby="asoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="asoModalLabel">
                    <i class="bi bi-heart-pulse text-info me-2"></i>EE_REGISTRAR_EXAME_ASO — Laudo Médico (NR-07)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=safety&action=record_exam" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="aso_employee_id" class="form-label text-light">Colaborador Avaliado <span class="text-danger">*</span></label>
                        <select name="employee_id" id="aso_employee_id" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o colaborador...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= htmlspecialchars($emp['id']) ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (CPF: <?= htmlspecialchars($emp['cpf'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="exam_type" class="form-label text-light">Natureza do Exame</label>
                            <select name="exam_type" id="exam_type" class="form-select bg-dark text-white border-secondary">
                                <option value="ADMISSIONAL">Admissional</option>
                                <option value="PERIODIC" selected>Periódico Anual</option>
                                <option value="RETORNO_TRABALHO">Retorno ao Trabalho</option>
                                <option value="MUDANCA_RISCO">Mudança de Riscos Ocupacionais</option>
                                <option value="DEMISSIONAL">Demissional</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="is_fit" class="form-label text-light">Parecer Médico de Aptidão</label>
                            <select name="is_fit" id="is_fit" class="form-select bg-dark text-white border-secondary">
                                <option value="1" selected>APTO para a Função</option>
                                <option value="0">INAPTO Temporário</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="physician_name" class="form-label text-light">Médico Examinador</label>
                            <input type="text" name="physician_name" id="physician_name" class="form-control bg-dark text-white border-secondary" value="Dr. Carlos Eduardo Mendonça">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="physician_crm" class="form-label text-light">Registro Profissional CRM</label>
                            <input type="text" name="physician_crm" id="physician_crm" class="form-control bg-dark text-white border-secondary" value="CRM-PR 28.941">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-dark"><i class="bi bi-save me-1"></i>Salvar Laudo ASO</button>
                </div>
            </form>
        </div>
    </div>
</div>
