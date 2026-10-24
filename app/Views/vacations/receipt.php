<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h3 mb-1 text-white"><i class="bi bi-file-earmark-ruled me-2 text-warning"></i>Recibo Oficial de Concessão de Férias</h2>
            <p class="text-muted mb-0">Comprovante legal de quitação de férias, 1/3 constitucional e abono pecuniário (Art. 145 CLT).</p>
        </div>
        <div>
            <span class="badge bg-success text-uppercase me-2"><i class="bi bi-calculator me-1"></i>SE_CALCULO_RECIBO_FERIAS (7 PFB)</span>
            <button onclick="window.print();" class="btn btn-outline-warning btn-sm me-2"><i class="bi bi-printer me-1"></i>Imprimir Recibo</button>
            <a href="index.php?route=vacations" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
        </div>
    </div>
</div>

<?php if (empty($receiptData)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>Nenhum recibo de férias selecionado ou solicitação não encontrada.
    </div>
<?php else: ?>

<!-- Card de Justificativa do Dado Derivado -->
<div class="card bg-dark border-secondary shadow-sm mb-4 se-derived-card">
    <div class="card-header border-secondary py-3 d-flex justify-content-between align-items-center">
        <span class="text-white fw-semibold">
            <i class="bi bi-calculator text-warning me-2"></i>SE_CALCULO_RECIBO_FERIAS — Justificativa do Dado Derivado
        </span>
        <span class="se-badge"><i class="bi bi-info-circle me-1"></i>Regra de Cálculo Legal</span>
    </div>
    <div class="card-body">
        <div class="p-3 rounded bg-black border border-secondary text-light">
            <div class="fw-bold text-warning mb-2"><i class="bi bi-journal-text me-1"></i>Fundamentação Legal e Fórmulas Aplicadas:</div>
            <ul class="mb-0 small text-muted ps-3">
                <li><strong>Remuneração de Férias (Art. 129 e 130 CLT):</strong> Salário mensal (R$ <?= number_format($receiptData['base_salary'], 2, ',', '.') ?>) ÷ 30 dias × <?= $receiptData['days_gozo'] ?> dias gozados = <strong>R$ <?= number_format($receiptData['earnings']['remuneracao_ferias'], 2, ',', '.') ?></strong>.</li>
                <li><strong>Adicional Constitucional de 1/3 (Art. 7º, XVII CF/88):</strong> Remuneração dos dias de férias ÷ 3 = <strong>R$ <?= number_format($receiptData['earnings']['terco_constitucional_cf88'], 2, ',', '.') ?></strong>.</li>
                <?php if (!empty($receiptData['abono_days'])): ?>
                    <li><strong>Abono Pecuniário (Art. 143 CLT):</strong> Conversão de <?= $receiptData['abono_days'] ?> dias de férias em pecúnia = <strong>R$ <?= number_format($receiptData['earnings']['abono_pecuniario_art143'], 2, ',', '.') ?></strong> mais 1/3 constitucional do abono (<strong>R$ <?= number_format($receiptData['earnings']['terco_abono_pecuniario'], 2, ',', '.') ?></strong>). Isenção integral de encargos sociais (Súmula 386 STJ).</li>
                <?php endif; ?>
                <li><strong>Retenções Previdenciárias e Fiscais:</strong> O INSS e o IRRF incidem apenas sobre o salário de férias e seu terço constitucional. Base Tributável = R$ <?= number_format($receiptData['earnings']['remuneracao_ferias'] + $receiptData['earnings']['terco_constitucional_cf88'], 2, ',', '.') ?>. Total Retenções = <strong>R$ <?= number_format($receiptData['deductions']['total_descontos'], 2, ',', '.') ?></strong>.</li>
                <li><strong>Prazo Limite de Liquidação (Art. 145 CLT):</strong> Quitação obrigatória até 2 dias antes do início do período de gozo: <strong class="text-warning"><?= date('d/m/Y', strtotime($receiptData['payment_deadline_legal'])) ?></strong>.</li>
            </ul>
        </div>
    </div>
</div>

<!-- Formal Printable Receipt Card -->
<div class="card bg-dark border-secondary shadow-lg">
    <div class="card-header border-secondary bg-black py-4 text-center">
        <h4 class="text-white mb-1 fw-bold text-uppercase tracking-wider">Recibo de Quitação de Férias e Abono Pecuniário</h4>
        <small class="text-muted">Conforme Artigos 129 a 145 da Consolidação das Leis do Trabalho (CLT)</small>
    </div>
    <div class="card-body p-4 p-md-5">
        <!-- Employee Header Information -->
        <div class="row g-3 mb-4 pb-4 border-bottom border-secondary">
            <div class="col-md-6">
                <span class="text-muted small d-block">Colaborador:</span>
                <span class="fs-5 text-white fw-bold"><?= htmlspecialchars($receiptData['employee_name']) ?></span>
                <div class="small text-muted mt-1">CPF: <?= htmlspecialchars($receiptData['cpf']) ?></div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">Lotação / Cargo:</span>
                <span class="text-white fw-semibold"><?= htmlspecialchars($receiptData['department_name']) ?></span>
                <div class="small text-muted mt-1"><?= htmlspecialchars($receiptData['role_name']) ?></div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">Salário Base Contratual:</span>
                <span class="text-white fw-bold fs-6">R$ <?= number_format($receiptData['base_salary'], 2, ',', '.') ?></span>
            </div>
        </div>

        <!-- Schedule Periods -->
        <div class="row g-3 mb-4 pb-4 border-bottom border-secondary bg-black p-3 rounded">
            <div class="col-md-4">
                <span class="text-muted small d-block">Período Aquisitivo:</span>
                <span class="text-light fw-semibold"><?= htmlspecialchars($receiptData['periodo_aquisitivo']) ?></span>
            </div>
            <div class="col-md-4">
                <span class="text-muted small d-block">Período de Gozo (Descanso):</span>
                <span class="text-warning fw-bold"><?= date('d/m/Y', strtotime(explode(' a ', $receiptData['periodo_gozo'])[0])) ?> a <?= date('d/m/Y', strtotime(explode(' a ', $receiptData['periodo_gozo'])[1])) ?> (<?= $receiptData['days_gozo'] ?> dias)</span>
            </div>
            <div class="col-md-4">
                <span class="text-muted small d-block">Data Limite Quitação (Art. 145):</span>
                <span class="text-info fw-bold"><?= date('d/m/Y', strtotime($receiptData['payment_deadline_legal'])) ?></span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-dark table-bordered border-secondary align-middle">
                <thead class="bg-black text-muted text-uppercase small">
                    <tr>
                        <th style="width: 50%;">Descrição das Rubricas</th>
                        <th class="text-center" style="width: 15%;">Ref.</th>
                        <th class="text-end" style="width: 17%;">Proventos (R$)</th>
                        <th class="text-end" style="width: 18%;">Descontos (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-white">Remuneração de Férias (Art. 129 CLT)</td>
                        <td class="text-center text-muted"><?= $receiptData['days_gozo'] ?>d</td>
                        <td class="text-end text-success fw-bold"><?= number_format($receiptData['earnings']['remuneracao_ferias'], 2, ',', '.') ?></td>
                        <td class="text-end text-muted">-</td>
                    </tr>
                    <tr>
                        <td class="text-white">Adicional de 1/3 Constitucional (Art. 7º, XVII CF/88)</td>
                        <td class="text-center text-muted">33.33%</td>
                        <td class="text-end text-success fw-bold"><?= number_format($receiptData['earnings']['terco_constitucional_cf88'], 2, ',', '.') ?></td>
                        <td class="text-end text-muted">-</td>
                    </tr>
                    <?php if (!empty($receiptData['abono_days'])): ?>
                        <tr>
                            <td class="text-white">Abono Pecuniário de Férias (Art. 143 CLT)</td>
                            <td class="text-center text-muted"><?= $receiptData['abono_days'] ?>d</td>
                            <td class="text-end text-success fw-bold"><?= number_format($receiptData['earnings']['abono_pecuniario_art143'], 2, ',', '.') ?></td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                        <tr>
                            <td class="text-white">1/3 Constitucional sobre Abono Pecuniário</td>
                            <td class="text-center text-muted">33.33%</td>
                            <td class="text-end text-success fw-bold"><?= number_format($receiptData['earnings']['terco_abono_pecuniario'], 2, ',', '.') ?></td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($receiptData['earnings']['adiantamento_decimo_terceiro'])): ?>
                        <tr>
                            <td class="text-white">Adiantamento de 50% do 13º Salário (Lei 4.749/65)</td>
                            <td class="text-center text-muted">50.0%</td>
                            <td class="text-end text-success fw-bold"><?= number_format($receiptData['earnings']['adiantamento_decimo_terceiro'], 2, ',', '.') ?></td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-white">Retenção Previdenciária INSS (Tabela Progressiva)</td>
                        <td class="text-center text-muted">Férias</td>
                        <td class="text-end text-muted">-</td>
                        <td class="text-end text-danger fw-bold"><?= number_format($receiptData['deductions']['inss_ferias'], 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="text-white">Imposto de Renda Retido na Fonte (IRRF Férias)</td>
                        <td class="text-center text-muted">Progressivo</td>
                        <td class="text-end text-muted">-</td>
                        <td class="text-end text-danger fw-bold"><?= number_format($receiptData['deductions']['irrf_ferias'], 2, ',', '.') ?></td>
                    </tr>
                </tbody>
                <tfoot class="bg-black fw-bold">
                    <tr>
                        <td colspan="2" class="text-end text-white">TOTAIS CONSOLIDADOS:</td>
                        <td class="text-end text-success fs-6">R$ <?= number_format($receiptData['earnings']['total_bruto'], 2, ',', '.') ?></td>
                        <td class="text-end text-danger fs-6">R$ <?= number_format($receiptData['deductions']['total_descontos'], 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Net Amount Display -->
        <div class="row align-items-center bg-black p-4 rounded border border-warning mb-5">
            <div class="col-md-7">
                <span class="text-muted d-block small text-uppercase">Valor Líquido a Receber:</span>
                <h2 class="text-warning fw-bold mb-0">R$ <?= number_format($receiptData['net_vacation_amount'], 2, ',', '.') ?></h2>
                <small class="text-muted">A ser creditado via folha/PIX até a data limite estipulada.</small>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <span class="badge bg-success p-2 fs-6"><i class="bi bi-shield-check me-1"></i>Recibo em Conformidade CLT</span>
            </div>
        </div>

        <!-- Declarations and Signature Box -->
        <div class="p-3 bg-dark border border-secondary rounded mb-5 small text-muted">
            Declaro ter recebido a importância líquida supramencionada relativa à remuneração das minhas férias regulamentares e abono pecuniário, que gozarei no período indicado, dando plena e rasa quitação.
        </div>

        <div class="row text-center pt-4">
            <div class="col-md-6 mb-4 mb-md-0">
                <div style="border-top: 1px solid #6c757d; width: 70%; margin: 0 auto; padding-top: 8px;">
                    <span class="text-white fw-bold d-block"><?= htmlspecialchars($receiptData['employee_name']) ?></span>
                    <small class="text-muted">Assinatura do Colaborador (CPF: <?= htmlspecialchars($receiptData['cpf']) ?>)</small>
                </div>
            </div>
            <div class="col-md-6">
                <div style="border-top: 1px solid #6c757d; width: 70%; margin: 0 auto; padding-top: 8px;">
                    <span class="text-white fw-bold d-block">HRTech Core Gestão de Pessoas</span>
                    <small class="text-muted">Departamento de Recursos Humanos</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
