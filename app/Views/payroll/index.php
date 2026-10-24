<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-cash-stack me-2 text-success"></i>Folha de Pagamento & Provisões CLT (Template Method)</h2>
            <p class="text-muted mb-0">Processamento da folha mensal com 4 faixas progressivas INSS, IRRF com dependentes e provisões atuariais de 13º e férias.</p>
        </div>
        <div>
            <span class="badge bg-primary text-uppercase me-2"><i class="bi bi-tag-fill me-1"></i>CE_CONSULTAR_FICHA_FINANCEIRA (4 PFB)</span>
            <span class="badge bg-success text-uppercase me-2"><i class="bi bi-calculator me-1"></i>SE_EMITIR_HOLERITE_MENSAL (7 PFB)</span>
            <span class="badge bg-warning text-dark text-uppercase me-2"><i class="bi bi-piggy-bank me-1"></i>SE_RECIBO_PROVISAO_13_FERIAS (4 PFB)</span>
            <button onclick="window.print()" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-printer me-1"></i>Imprimir Holerites
            </button>
        </div>
    </div>
</div>

<!-- Financial Summary KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <span class="text-muted small d-block">Folha Bruta Consolidada</span>
                <h3 class="text-white fw-bold mb-0">R$ <?= number_format($totalGross, 2, ',', '.') ?></h3>
                <small class="text-muted"><?= count($payrolls) ?> colaboradores ativos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <span class="text-muted small d-block">Folha Líquida a Pagar</span>
                <h3 class="text-success fw-bold mb-0">R$ <?= number_format($totalNet, 2, ',', '.') ?></h3>
                <small class="text-muted">Total a creditar via PIX/banco</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <span class="text-muted small d-block">Total Descontos (INSS/IRRF)</span>
                <h3 class="text-danger fw-bold mb-0">R$ <?= number_format($totalGross - $totalNet, 2, ',', '.') ?></h3>
                <small class="text-muted">Retenções previdenciárias e fiscais</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <span class="text-muted small d-block">Provisões Atuariais Mês</span>
                <h3 class="text-warning fw-bold mb-0">R$ <?= number_format($provisions['grand_total_monthly_provision'], 2, ',', '.') ?></h3>
                <small class="text-muted">13º + Férias + 1/3 + FGTS 8%</small>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_CONSULTAR_FICHA_FINANCEIRA: SEÇÃO 1 — DADOS DE ENTRADA (FILTROS) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 ce-input-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-funnel text-info me-2"></i>CE_CONSULTAR_FICHA_FINANCEIRA — Dados de Entrada (Filtros de Pesquisa)
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-down-circle me-1"></i>Parâmetros de Entrada</span>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3">
            <input type="hidden" name="route" value="payroll">

            <div class="col-md-5">
                <label for="search_employee_id" class="form-label text-light small fw-bold">Colaborador</label>
                <select name="search_employee_id" id="search_employee_id" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos os Colaboradores</option>
                    <?php foreach ($allEmployeesList as $ae): ?>
                        <option value="<?= htmlspecialchars($ae['id']) ?>" <?= ($_GET['search_employee_id'] ?? '') === $ae['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ae['full_name']) ?> (<?= htmlspecialchars($ae['matricula'] ?? 'N/A') ?> — <?= htmlspecialchars($ae['employment_type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="search_type" class="form-label text-light small fw-bold">Regime Contratual</label>
                <select name="search_type" id="search_type" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos os Regimes</option>
                    <option value="CLT" <?= ($_GET['search_type'] ?? '') === 'CLT' ? 'selected' : '' ?>>CLT (Consolidação das Leis do Trabalho)</option>
                    <option value="PJ" <?= ($_GET['search_type'] ?? '') === 'PJ' ? 'selected' : '' ?>>Pessoa Jurídica (PJ / Emissão NF)</option>
                    <option value="ESTAGIO" <?= ($_GET['search_type'] ?? '') === 'ESTAGIO' ? 'selected' : '' ?>>Estágio (Lei 11.788/2008)</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Consultar Ficha
                </button>
                <a href="index.php?route=payroll" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================================== -->
<!-- CE_CONSULTAR_FICHA_FINANCEIRA: SEÇÃO 2 — DADOS DE SAÍDA (DEMONSTRATIVO GERAL) -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-table text-success me-2"></i>CE_CONSULTAR_FICHA_FINANCEIRA — Dados de Saída (Fichas Financeiras Processadas)
            <span class="badge bg-secondary ms-2"><?= count($payrolls) ?> registro(s)</span>
        </span>
        <span class="ce-badge"><i class="bi bi-arrow-up-circle me-1"></i>Resultados da Consulta</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-dark text-muted text-uppercase small">
                    <tr>
                        <th>Colaborador</th>
                        <th>Regime</th>
                        <th>Salário Base</th>
                        <th>H. Extras</th>
                        <th>Salário Bruto</th>
                        <th>INSS</th>
                        <th>IRRF</th>
                        <th>Benefícios</th>
                        <th>Salário Líquido</th>
                        <th>FGTS (8%)</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrolls)): ?>
                        <tr><td colspan="11" class="text-center py-4 text-muted">Nenhum colaborador encontrado com os filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payrolls as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($p['name']) ?></div>
                                    <small class="text-muted">CPF: <?= htmlspecialchars($p['cpf']) ?> | Matrícula: <?= htmlspecialchars($p['matricula']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $p['type'] === 'CLT' ? 'bg-primary' : ($p['type'] === 'PJ' ? 'bg-warning text-dark' : 'bg-info text-dark') ?>">
                                        <?= htmlspecialchars($p['type']) ?>
                                    </span>
                                </td>
                                <td>R$ <?= number_format($p['base'], 2, ',', '.') ?></td>
                                <td>
                                    <?php if ($p['overtime'] > 0): ?>
                                        <span class="text-warning fw-bold">+ R$ <?= number_format($p['overtime'], 2, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">R$ 0,00</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-white fw-bold">R$ <?= number_format($p['gross'], 2, ',', '.') ?></td>
                                <td class="text-danger">R$ <?= number_format($p['inss'], 2, ',', '.') ?></td>
                                <td class="text-danger">R$ <?= number_format($p['irrf'], 2, ',', '.') ?></td>
                                <td class="text-danger">R$ <?= number_format($p['benefits_discount'], 2, ',', '.') ?></td>
                                <td class="text-success fw-bold fs-6">R$ <?= number_format($p['net'], 2, ',', '.') ?></td>
                                <td class="text-muted">R$ <?= number_format($p['fgts'], 2, ',', '.') ?></td>
                                <td class="text-end">
                                    <a href="index.php?route=payroll&search_employee_id=<?= urlencode($p['id']) ?>" class="btn btn-outline-info btn-sm" title="Ver Holerite Detalhado">
                                        <i class="bi bi-file-earmark-ruled"></i>
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
<!-- SE_EMITIR_HOLERITE_MENSAL — JUSTIFICATIVA DO DADO DERIVADO & RECIBO DETALHADO -->
<!-- ============================================================================== -->
<?php if (!empty($detailedSlip)): ?>
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-calculator text-warning me-2"></i>SE_EMITIR_HOLERITE_MENSAL — Justificativa do Dado Derivado (Holerite Oficial CLT)
        </span>
        <span class="se-badge"><i class="bi bi-file-earmark-check me-1"></i>Template Method CltPayroll</span>
    </div>
    <div class="card-body">
        <div class="p-3 mb-4 rounded bg-black border border-secondary text-light">
            <div class="fw-bold text-warning mb-2"><i class="bi bi-journal-code me-1"></i>Regras de Cálculo Previdenciário e Tributário Aplicadas:</div>
            <ul class="mb-0 small text-muted ps-3">
                <li><strong>INSS Progressivo em 4 Faixas Oficiais:</strong> Aplicação do algoritmo de fatiamento por faixa com alíquotas marginais progressivas (7.5% até R$ 1.412,00; 9% até R$ 2.666,68; 12% até R$ 4.000,03; 14% até o teto de R$ 7.786,02). Total retido: <strong class="text-danger">R$ <?= number_format($detailedSlip['inss_deduction'], 2, ',', '.') ?></strong>.</li>
                <li><strong>Imposto de Renda (IRRF) Retido na Fonte:</strong> Base de cálculo = $\text{Salário Bruto} - \text{INSS} - (\text{Dependentes} \times \text{R\$ 189,59})$. Aplicação da tabela progressiva com parcela a deduzir. Total retido: <strong class="text-danger">R$ <?= number_format($detailedSlip['irrf_deduction'], 2, ',', '.') ?></strong>.</li>
                <li><strong>Teto Legal de Vale Transporte (Lei 7.418/85):</strong> Desconto de VT limitado estritamente a 6% do salário-base (R$ <?= number_format($detailedSlip['base_salary'] * 0.06, 2, ',', '.') ?>). Total retido de benefícios: <strong class="text-danger">R$ <?= number_format($detailedSlip['benefits_discount'], 2, ',', '.') ?></strong>.</li>
                <li><strong>FGTS Patronal (Lei 8.036/90):</strong> 8% sobre a remuneração bruta total (<strong>R$ <?= number_format($detailedSlip['fgts_company'], 2, ',', '.') ?></strong>), recolhido pelo empregador sem desconto do colaborador.</li>
            </ul>
        </div>

        <!-- Official Payslip Mockup -->
        <div class="border border-secondary rounded p-4 bg-black">
            <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-3 mb-3">
                <div>
                    <h5 class="text-white mb-0 fw-bold">HRTech Core Gestão de Pessoas S.A.</h5>
                    <small class="text-muted">Demonstrativo Mensal de Pagamento de Salário</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary">Competência: 09/2026</span>
                </div>
            </div>

            <div class="row g-2 mb-3 small">
                <div class="col-md-6">
                    <span class="text-muted">Colaborador:</span>
                    <strong class="text-white d-block"><?= htmlspecialchars($detailedSlip['employee_name']) ?></strong>
                    <span class="text-muted">CPF: <?= htmlspecialchars($detailedSlip['cpf']) ?> | Matrícula: <?= htmlspecialchars($detailedSlip['matricula']) ?></span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Regime:</span>
                    <strong class="text-white d-block"><?= htmlspecialchars($detailedSlip['contract_type']) ?></strong>
                </div>
                <div class="col-md-3 text-end">
                    <span class="text-muted">Salário Base:</span>
                    <strong class="text-white d-block">R$ <?= number_format($detailedSlip['base_salary'], 2, ',', '.') ?></strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-sm table-bordered border-secondary mb-3">
                    <thead class="bg-dark text-muted text-uppercase small">
                        <tr>
                            <th>Cód. Rubrica</th>
                            <th>Descrição do Evento</th>
                            <th>Referência</th>
                            <th class="text-end">Vencimentos (R$)</th>
                            <th class="text-end">Descontos (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>001</td>
                            <td>Salário Base Contratual</td>
                            <td>30 dias</td>
                            <td class="text-end text-success fw-bold"><?= number_format($detailedSlip['base_salary'], 2, ',', '.') ?></td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                        <?php if ($detailedSlip['overtime_amount'] > 0): ?>
                            <tr>
                                <td>010</td>
                                <td>Horas Extras (50% / 100% Strategy)</td>
                                <td>Horas apuradas</td>
                                <td class="text-end text-success fw-bold"><?= number_format($detailedSlip['overtime_amount'], 2, ',', '.') ?></td>
                                <td class="text-end text-muted">-</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($detailedSlip['inss_deduction'] > 0): ?>
                            <tr>
                                <td>101</td>
                                <td>INSS Empregado (Tabela Progressiva 4 Faixas)</td>
                                <td>Faixas CLT</td>
                                <td class="text-end text-muted">-</td>
                                <td class="text-end text-danger fw-bold"><?= number_format($detailedSlip['inss_deduction'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($detailedSlip['irrf_deduction'] > 0): ?>
                            <tr>
                                <td>102</td>
                                <td>IRRF s/ Salário (Com Dedução de Dependentes)</td>
                                <td><?= $detailedSlip['dependents_claimed'] ?> dep.</td>
                                <td class="text-end text-muted">-</td>
                                <td class="text-end text-danger fw-bold"><?= number_format($detailedSlip['irrf_deduction'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($detailedSlip['benefits_discount'] > 0): ?>
                            <tr>
                                <td>201</td>
                                <td>Coparticipação Vale Transporte (Teto 6% Lei 7.418)</td>
                                <td>6.00%</td>
                                <td class="text-end text-muted">-</td>
                                <td class="text-end text-danger fw-bold"><?= number_format($detailedSlip['benefits_discount'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-black fw-bold">
                        <tr>
                            <td colspan="3" class="text-end text-white">TOTAIS CONSOLIDADOS:</td>
                            <td class="text-end text-success">R$ <?= number_format($detailedSlip['gross_salary'], 2, ',', '.') ?></td>
                            <td class="text-end text-danger">R$ <?= number_format($detailedSlip['total_deductions'], 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row align-items-center bg-dark p-3 rounded border border-success">
                <div class="col-md-6">
                    <span class="text-muted small text-uppercase">Valor Líquido a Receber:</span>
                    <h3 class="text-success fw-bold mb-0">R$ <?= number_format($detailedSlip['net_salary'], 2, ',', '.') ?></h3>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted small">FGTS Depositado (Empresa 8%): </span>
                    <strong class="text-white">R$ <?= number_format($detailedSlip['fgts_company'], 2, ',', '.') ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================================== -->
<!-- SE_RECIBO_PROVISAO_13_FERIAS — PROVISÕES MENSAIS ATUARIAIS CLT -->
<!-- ============================================================================== -->
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-piggy-bank text-warning me-2"></i>SE_RECIBO_PROVISAO_13_FERIAS — Provisões Atuariais de 13º e Férias CLT
        </span>
        <span class="se-badge"><i class="bi bi-pie-chart me-1"></i>Custo Atuarial Mensal</span>
    </div>
    <div class="card-body">
        <div class="p-3 mb-4 rounded bg-black border border-secondary text-light">
            <div class="fw-bold text-warning mb-2"><i class="bi bi-calculator me-1"></i>Regra de Derivação das Provisões Mensais:</div>
            <div class="mb-2">
                <code class="se-formula p-2 rounded d-inline-block">Provisão = (Salário / 12 [13º]) + (Salário / 12 [Férias]) + ((Salário / 12) / 3 [1/3 CF]) + (Subtotal × 8% [FGTS])</code>
            </div>
            <p class="small text-muted mb-0">Cada mês trabalhado gera para a empresa a obrigação contábil e atuarial de provisionar 1/12 avos da remuneração futura para quitação tempestiva sem impacto no fluxo de caixa no encerramento do exercício fiscal.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered border-secondary align-middle mb-0">
                <thead class="bg-black text-muted text-uppercase small">
                    <tr>
                        <th>Colaborador CLT</th>
                        <th class="text-end">Salário Base</th>
                        <th class="text-end">Provisão 13º (1/12)</th>
                        <th class="text-end">Provisão Férias (1/12)</th>
                        <th class="text-end">1/3 Constitucional</th>
                        <th class="text-end">FGTS s/ Prov. (8%)</th>
                        <th class="text-end">Provisão Total Mês</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($provisions['details'] as $pd): ?>
                        <tr>
                            <td class="fw-bold text-white"><?= htmlspecialchars($pd['name']) ?></td>
                            <td class="text-end text-light">R$ <?= number_format($pd['base_salary'], 2, ',', '.') ?></td>
                            <td class="text-end text-info">R$ <?= number_format($pd['provision_13th_monthly'], 2, ',', '.') ?></td>
                            <td class="text-end text-info">R$ <?= number_format($pd['provision_vacation_monthly'], 2, ',', '.') ?></td>
                            <td class="text-end text-warning">R$ <?= number_format($pd['provision_vacation_third'], 2, ',', '.') ?></td>
                            <td class="text-end text-danger">R$ <?= number_format($pd['fgts_on_provisions'], 2, ',', '.') ?></td>
                            <td class="text-end text-success fw-bold">R$ <?= number_format($pd['total_monthly_provision'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-black fw-bold">
                    <tr>
                        <td class="text-white">TOTAIS MENSAIS PROVISIONADOS:</td>
                        <td class="text-end text-light">R$ <?= number_format($provisions['total_base_salaries'], 2, ',', '.') ?></td>
                        <td class="text-end text-info">R$ <?= number_format($provisions['total_provision_13th'], 2, ',', '.') ?></td>
                        <td class="text-end text-info">R$ <?= number_format($provisions['total_provision_vacations'], 2, ',', '.') ?></td>
                        <td class="text-end text-warning">R$ <?= number_format($provisions['total_provision_third'], 2, ',', '.') ?></td>
                        <td class="text-end text-danger">R$ <?= number_format($provisions['total_fgts_provisions'], 2, ',', '.') ?></td>
                        <td class="text-end text-success fs-6">R$ <?= number_format($provisions['grand_total_monthly_provision'], 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
