<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-gift me-2 text-danger"></i>Catálogo de Benefícios & Fatura de Coparticipação</h2>
            <p class="text-muted mb-0">Parametrização de benefícios corporativos, vínculos individuais e apuração de tetos legais CLT/PAT/ANS.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>CE_CATALOGO_BENEFICIOS (4 PFB)</span>
            <span class="badge bg-success text-uppercase me-2"><i class="bi bi-calculator me-1"></i>SE_DEMONSTRATIVO_BENEFICIOS (5 PFB)</span>
            <button class="btn btn-danger btn-sm me-2" data-bs-toggle="modal" data-bs-target="#benefitModal">
                <i class="bi bi-plus-circle me-1"></i>Novo Benefício
            </button>
            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                <i class="bi bi-link-45deg me-1"></i>Vincular a Colaborador
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
<!-- CE_CATALOGO_BENEFICIOS: SEÇÃO 1 — DADOS DE ENTRADA (FILTROS DE PESQUISA) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 ce-input-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-funnel text-info me-2"></i>CE_CATALOGO_BENEFICIOS — Dados de Entrada (Filtros de Pesquisa)
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-down-circle me-1"></i>Parâmetros de Entrada</span>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3">
            <input type="hidden" name="route" value="benefits">

            <div class="col-md-5">
                <label for="search_type" class="form-label text-light small fw-bold">Categoria de Benefício</label>
                <select name="search_type" id="search_type" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todas as Categorias</option>
                    <option value="MEAL_VOUCHER" <?= ($_GET['search_type'] ?? '') === 'MEAL_VOUCHER' ? 'selected' : '' ?>>Vale Refeição (PAT)</option>
                    <option value="FOOD_VOUCHER" <?= ($_GET['search_type'] ?? '') === 'FOOD_VOUCHER' ? 'selected' : '' ?>>Vale Alimentação (PAT)</option>
                    <option value="TRANSPORT" <?= ($_GET['search_type'] ?? '') === 'TRANSPORT' ? 'selected' : '' ?>>Vale Transporte (Lei 7.418/85)</option>
                    <option value="HEALTH_PLAN" <?= ($_GET['search_type'] ?? '') === 'HEALTH_PLAN' ? 'selected' : '' ?>>Plano de Saúde (ANS TISS)</option>
                    <option value="DENTAL_PLAN" <?= ($_GET['search_type'] ?? '') === 'DENTAL_PLAN' ? 'selected' : '' ?>>Plano Odontológico</option>
                    <option value="FLEXIBLE" <?= ($_GET['search_type'] ?? '') === 'FLEXIBLE' ? 'selected' : '' ?>>Multibenefícios Flexíveis</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="search_provider" class="form-label text-light small fw-bold">Provedor / Operadora</label>
                <input type="text" name="search_provider" id="search_provider" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Caju, Flash, Bradesco..." value="<?= htmlspecialchars($_GET['search_provider'] ?? '') ?>">
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filtrar Catálogo
                </button>
                <a href="index.php?route=benefits" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_CATALOGO_BENEFICIOS: SEÇÃO 2 — DADOS DE SAÍDA (CATÁLOGO DISPONÍVEL) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-grid-3x3-gap text-danger me-2"></i>CE_CATALOGO_BENEFICIOS — Dados de Saída (Planos e Pacotes Ativos)
            <span class="badge bg-secondary ms-2"><?= count($benefits) ?> pacote(s)</span>
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-up-circle me-1"></i>Resultados da Consulta</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Nome do Benefício</th>
                        <th>Categoria</th>
                        <th>Provedor Externo</th>
                        <th>Valor Facial (R$)</th>
                        <th>Regra Coparticipação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($benefits)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum benefício encontrado com os filtros informados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($benefits as $b): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($b['name']) ?></div>
                                    <small class="text-muted">ID: <?= htmlspecialchars($b['id']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($b['type']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light"><?= htmlspecialchars($b['provider'] ?? 'Corporativo') ?></span>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">R$ <?= number_format(((int)($b['value_cents'] ?? 0)) / 100, 2, ',', '.') ?></span>
                                </td>
                                <td>
                                    <?php if ($b['type'] === 'TRANSPORT'): ?>
                                        <span class="badge bg-warning text-dark">Teto 6% Salário (Lei 7.418/85)</span>
                                    <?php elseif ($b['type'] === 'MEAL_VOUCHER' || $b['type'] === 'FOOD_VOUCHER'): ?>
                                        <span class="badge bg-primary">Teto 20% PAT (Lei 6.321/76)</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= (float)($b['employee_cost_share_percentage'] ?? 0) ?>% Copag</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="index.php?route=benefits&action=delete&id=<?= urlencode($b['id']) ?>" class="btn btn-outline-danger btn-sm" title="Excluir Benefício" onclick="return confirm('Confirma a exclusão deste benefício do catálogo?');">
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
<!-- SE_DEMONSTRATIVO_BENEFICIOS / SE_FATURA_COPARTICIPACAO — JUSTIFICATIVA DO DADO DERIVADO -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-calculator text-warning me-2"></i>SE_DEMONSTRATIVO_BENEFICIOS — Justificativa do Dado Derivado (Fatura de Coparticipação)
        </span>
        <span class="se-badge"><i class="bi bi-info-circle me-1"></i>Memória de Cálculo Legal</span>
    </div>
    <div class="card-body">
        <div class="p-3 mb-4 rounded bg-black border border-secondary text-light">
            <div class="fw-bold text-warning mb-2"><i class="bi bi-journal-check me-1"></i>Regras de Rateio e Tetos Legais de Desconto:</div>
            <ul class="mb-0 small text-muted ps-3">
                <li><strong>Vale Transporte (Lei Federal nº 7.418/1985):</strong> A participação máxima do empregado é estritamente limitada a <strong>6% do salário-base mensal</strong>. Caso o custo real dos deslocamentos seja superior a 6%, o empregador arca compulsoriamente com 100% da diferença (subsídio patronal isento de natureza salarial).</li>
                <li><strong>Programa de Alimentação do Trabalhador - PAT (Lei nº 6.321/1976):</strong> A coparticipação máxima do colaborador no vale-refeição ou alimentação é de <strong>20% do valor facial da recarga</strong>, sendo os 80% restantes subsidiados pela empresa com incentivo fiscal de dedutibilidade no IRPJ.</li>
                <li><strong>Planos de Saúde e Odontológicos (ANS TISS 4.01.00):</strong> Aplicação de percentuais de coparticipação sobre o valor contratual ou mensalidades com emissão de guia autorizadora.</li>
            </ul>
        </div>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Custo Total Facial dos Benefícios</small>
                    <div class="fs-4 fw-bold text-white">R$ <?= number_format($copayStatement['grand_total_face_value'], 2, ',', '.') ?></div>
                    <small class="text-muted"><?= $copayStatement['total_active_enrollments'] ?> pacotes ativos</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Coparticipação Total Empregados</small>
                    <div class="fs-4 fw-bold text-danger">R$ <?= number_format($copayStatement['grand_total_employee_deduction'], 2, ',', '.') ?></div>
                    <small class="text-muted">Descontos em folha de pagamento</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">Custeio Líquido do Empregador</small>
                    <div class="fs-4 fw-bold text-success">R$ <?= number_format($copayStatement['grand_total_company_subsidy'], 2, ',', '.') ?></div>
                    <small class="text-muted">Investimento empresarial direto</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-black border-secondary p-3">
                    <small class="text-muted">% Subsídio Corporativo</small>
                    <div class="fs-4 fw-bold text-warning"><?= $copayStatement['employer_subsidy_percentage'] ?>%</div>
                    <small class="text-muted">Percentual médio coberto pela empresa</small>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown per Employee -->
        <h6 class="text-white fw-bold mb-3"><i class="bi bi-people me-2 text-info"></i>Extrato Individual de Coparticipação por Colaborador</h6>
        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered border-secondary align-middle mb-0">
                <thead class="bg-black text-muted text-uppercase small">
                    <tr>
                        <th>Colaborador</th>
                        <th>Salário Base</th>
                        <th>Benefício & Provedor</th>
                        <th class="text-end">Valor Facial</th>
                        <th class="text-end">Desconto Empregado</th>
                        <th class="text-end">Subsídio Empresa</th>
                        <th>Enquadramento Legal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($copayStatement['statement_details'])): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Nenhum colaborador com benefícios vinculados no momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($copayStatement['statement_details'] as $empSumm): ?>
                            <?php foreach ($empSumm['benefits'] as $idx => $bItem): ?>
                                <tr>
                                    <?php if ($idx === 0): ?>
                                        <td rowspan="<?= count($empSumm['benefits']) ?>" class="align-middle">
                                            <div class="fw-bold text-white"><?= htmlspecialchars($empSumm['employee_name']) ?></div>
                                            <small class="text-muted">CPF: <?= htmlspecialchars($empSumm['cpf']) ?></small>
                                        </td>
                                        <td rowspan="<?= count($empSumm['benefits']) ?>" class="align-middle text-light">
                                            R$ <?= number_format($empSumm['base_salary'], 2, ',', '.') ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="fw-semibold text-white"><?= htmlspecialchars($bItem['benefit_name']) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($bItem['provider']) ?> (<?= htmlspecialchars($bItem['type']) ?>)</small>
                                    </td>
                                    <td class="text-end text-light">
                                        R$ <?= number_format($bItem['face_value'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end text-danger fw-bold">
                                        R$ <?= number_format($bItem['employee_deduction'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        R$ <?= number_format($bItem['company_subsidy'], 2, ',', '.') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark border border-secondary text-info"><?= htmlspecialchars($bItem['rule_applied']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
                <span class="text-white fw-semibold"><i class="bi bi-credit-card-2-front text-info me-2"></i>AIE_OPERADORA_BENEFICIOS</span>
                <span class="badge bg-primary">7 PFB / Valentin</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Gateway REST com operadoras Caju, Ticket e Flash para recarga programada de cartões multibenefícios.</p>
                <a href="index.php?route=external_simulators&action=test&service=OPERADORA_BENEFICIOS" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-play-circle me-1"></i>Simular Recarga Online
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-semibold"><i class="bi bi-hospital text-danger me-2"></i>AIE_OPERADORA_SAUDE_ANS</span>
                <span class="badge bg-primary">5 PFB / Valentin</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Protocolo TISS 4.01.00 para autorização eletrônica de exames e apuração de coparticipação ambulatorial.</p>
                <a href="index.php?route=external_simulators&action=test&service=OPERADORA_SAUDE_ANS" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-play-circle me-1"></i>Simular Guia TISS ANS
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Novo Benefício (EE_CRIAR_BENEFICIO) -->
<div class="modal fade" id="benefitModal" tabindex="-1" aria-labelledby="benefitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="benefitModalLabel">
                    <i class="bi bi-gift text-danger me-2"></i>Cadastrar Pacote no Catálogo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=benefits&action=store" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="name" class="form-label text-light">Nome do Benefício <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Cartão Alimentação Flexível Caju" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label text-light">Categoria</label>
                            <select name="type" id="type" class="form-select bg-dark text-white border-secondary">
                                <option value="MEAL_VOUCHER">Vale Refeição (PAT)</option>
                                <option value="FOOD_VOUCHER">Vale Alimentação (PAT)</option>
                                <option value="TRANSPORT">Vale Transporte (CLT)</option>
                                <option value="HEALTH_PLAN">Plano de Saúde (ANS)</option>
                                <option value="DENTAL_PLAN">Plano Odontológico</option>
                                <option value="FLEXIBLE">Multibenefícios</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="value" class="form-label text-light">Valor Facial Mensal (R$)</label>
                            <input type="number" step="0.01" name="value" id="value" class="form-control bg-dark text-white border-secondary" value="650.00" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="provider" class="form-label text-light">Operadora / Provedor</label>
                            <input type="text" name="provider" id="provider" class="form-control bg-dark text-white border-secondary" value="Caju Benefícios Flexíveis">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="employee_cost_share_percentage" class="form-label text-light">% Coparticipação Padrão</label>
                            <input type="number" step="0.1" name="employee_cost_share_percentage" id="employee_cost_share_percentage" class="form-control bg-dark text-white border-secondary" value="6.0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Salvar no Catálogo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Vincular a Colaborador (EE_VINCULAR_BENEFICIO) -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="assignModalLabel">
                    <i class="bi bi-link-45deg text-warning me-2"></i>EE_VINCULAR_BENEFICIO — Atribuir Pacote
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=benefits&action=assign" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="assign_emp" class="form-label text-light">Colaborador Beneficiário <span class="text-danger">*</span></label>
                        <select name="employee_id" id="assign_emp" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o colaborador...</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= htmlspecialchars($emp['id']) ?>">
                                    <?= htmlspecialchars($emp['full_name']) ?> (CPF: <?= htmlspecialchars($emp['cpf'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="assign_ben" class="form-label text-light">Pacote de Benefício <span class="text-danger">*</span></label>
                        <select name="benefit_id" id="assign_ben" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selecione o benefício...</option>
                            <?php foreach ($benefits as $b): ?>
                                <option value="<?= htmlspecialchars($b['id']) ?>">
                                    <?= htmlspecialchars($b['name']) ?> — R$ <?= number_format(((int)$b['value_cents']) / 100, 2, ',', '.') ?> (<?= htmlspecialchars($b['type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="custom_cost_share" class="form-label text-light">Coparticipação Customizada (%) <small class="text-muted">(Opcional)</small></label>
                        <input type="number" step="0.1" name="custom_cost_share" id="custom_cost_share" class="form-control bg-dark text-white border-secondary" placeholder="Deixe em branco para usar a regra padrão do benefício">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check2-circle me-1"></i>Vincular Benefício</button>
                </div>
            </form>
        </div>
    </div>
</div>
