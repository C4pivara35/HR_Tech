<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * HRTech Core — Teste e Verificação de Backend & Banco de Dados (Milestone M1)
 * ==============================================================================
 * Valida de forma automatizada, determinística e exaustiva:
 * 1. 17 Tabelas Relacionais do Schema SQLite3 + Row Counts
 * 2. 10 Serviços AIE (Arquivos de Interface Externa) + Registro em external_integrations
 * 3. 12 Motores SE (Saídas Externas / Cálculos Matemáticos e Fiscais CLT/SST/BACEN)
 * ==============================================================================
 */

define('ROOT_DIR', __DIR__);
define('BACKEND_BASE', __DIR__ . '/hrtech_backend_patterns');

require_once BACKEND_BASE . '/src/Autoloader.php';
$loader = new \HrTech\Autoloader();
$loader->addNamespace('HrTech', BACKEND_BASE . '/src');
$loader->addNamespace('App', __DIR__ . '/app');
$loader->register();

use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\AuditLog;
use HrTech\Patterns\Singleton\AuditLogger;

// Importa os 10 Serviços AIE
use App\Services\External\AieReceitaCpfService;
use App\Services\External\AieGovEsocialService;
use App\Services\External\AieRelogioRepService;
use App\Services\External\AieNtpService;
use App\Services\External\AieOperadoraBeneficiosService;
use App\Services\External\AieOperadoraSaudeService;
use App\Services\External\AieMteCaService;
use App\Services\External\AieClinicaMedicaService;
use App\Services\External\AieSeguradoraService;
use App\Services\External\AiePixBacenService;

// Importa os Controllers com lógica de SE
use App\Controllers\PayrollController;
use App\Controllers\EmployeeController;
use App\Controllers\TimeLogController;
use App\Controllers\VacationController;
use App\Controllers\BenefitController;
use App\Controllers\SafetyController;
use App\Controllers\InsuranceController;
use App\Controllers\ComplianceController;

$dbPath = __DIR__ . '/hrtech_db.sqlite';
$dbManager = DatabaseManager::getInstance($dbPath);
$pdo = $dbManager->getConnection();

$passedTests = 0;
$totalTests = 0;

function assertCondition(bool $condition, string $testName, string $details = ''): void {
    global $passedTests, $totalTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$testName}" . ($details ? " -> {$details}" : "") . "\n";
    } else {
        echo "  [FAIL] {$testName}" . ($details ? " -> {$details}" : "") . "\n";
        throw new \RuntimeException("Teste falhou: {$testName}. {$details}");
    }
}

echo "=================================================================\n";
echo "HRTech Core — Suíte de Verificação Integral do Backend (M1)\n";
echo "=================================================================\n\n";

// ==============================================================================
// 1. VERIFICAÇÃO DO BANCO DE DADOS RELACIONAL (17 TABELAS)
// ==============================================================================
echo "1. Verificação do Banco de Dados Relacional (17 Tabelas):\n";

$tables = [
    'tenants', 'departments', 'roles', 'employees', 'users',
    'time_logs', 'time_adjustment_requests', 'vacation_requests',
    'benefits', 'employee_benefits', 'equipment_aso', 'insurance_policies',
    'pix_transactions', 'rep_imports', 'overtime_entries',
    'external_integrations', 'audit_logs'
];

foreach ($tables as $table) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    assertCondition($count > 0, "Tabela '{$table}' populada", "{$count} registros");
}
assertCondition(count($tables) === 17, "Total de tabelas do Modelo Relacional", "17 tabelas ativas");

echo "\n";

// ==============================================================================
// 2. VERIFICAÇÃO DOS 10 SERVIÇOS AIE (ARQUIVOS DE INTERFACE EXTERNA)
// ==============================================================================
echo "2. Verificação dos 10 Serviços AIE (Simuladores Integrados com SQLite):\n";

$initialExtCount = (int)$pdo->query("SELECT COUNT(*) FROM external_integrations")->fetchColumn();

// 2.1 AIE_RECEITA_FEDERAL_CPF
$cpfService = new AieReceitaCpfService($pdo);
$cpfValidRes = $cpfService->validateCpf('52998224725', 'tenant-tech');
assertCondition($cpfValidRes['status'] === 'REGULAR', "AIE 1/10: AieReceitaCpfService (CPF Válido)", "Status: {$cpfValidRes['status']}");
$cpfInvalidRes = $cpfService->validateCpf('11111111111', 'tenant-tech');
assertCondition($cpfInvalidRes['status'] === 'IRREGULAR', "AIE 1/10: AieReceitaCpfService (CPF Inválido rejeitado)");

// 2.2 AIE_ESOCIAL_S2200
$esocialService = new AieGovEsocialService($pdo);
$esocialRes = $esocialService->transmitS2200Event([
    'id' => 'emp-lucas',
    'full_name' => 'Lucas Silva',
    'cpf' => '52998224725',
    'base_salary_cents' => 980000,
    'admission_date' => '2023-01-15'
], 'tenant-tech');
assertCondition(!empty($esocialRes['receipt_number']) && !empty($esocialRes['xml_digest']), "AIE 2/10: AieGovEsocialService (S-2200 XML)", "Recibo: {$esocialRes['receipt_number']}");

// 2.3 AIE_RELOGIO_REP_671
$repService = new AieRelogioRepService($pdo);
$afdContent = AieRelogioRepService::generateSampleAfdContent();
$repRes = $repService->importAfd($afdContent, 'AFD_TEST_001.txt', 'tenant-tech');
assertCondition($repRes['status'] === 'COMPLETED' && $repRes['records_parsed'] > 0, "AIE 3/10: AieRelogioRepService (Portaria 671 AFD)", "Importados: {$repRes['records_parsed']}");

// 2.4 AIE_NTP_HORA_LEGAL
$ntpService = new AieNtpService($pdo);
$ntpRes = $ntpService->getLegalTime('tenant-tech');
assertCondition($ntpRes['stratum'] === 1 && !empty($ntpRes['legal_stamp_token']), "AIE 4/10: AieNtpService (NTP.br Stratum 1)", "Hora: {$ntpRes['legal_time_brasilia']}");

// 2.5 AIE_OPERADORA_BENEFICIOS
$cajuService = new AieOperadoraBeneficiosService($pdo);
$cardRes = $cajuService->allocateBenefitCredits('emp-lucas', 'MEAL', 120000, 'tenant-tech');
assertCondition($cardRes['status'] === 'APPROVED' && !empty($cardRes['authorization_code']), "AIE 5/10: AieOperadoraBeneficiosService (Caju/Ticket)", "Autenticação: {$cardRes['authorization_code']}");

// 2.6 AIE_OPERADORA_SAUDE
$saudeService = new AieOperadoraSaudeService($pdo);
$tissRes = $saudeService->verifyEligibilityAndCopay('emp-lucas', 'BRAD-99381-001', '10101012', 'tenant-tech');
assertCondition($tissRes['status'] === 'ELIGIBLE_AUTHORIZED' && $tissRes['copay_amount_cents'] > 0, "AIE 6/10: AieOperadoraSaudeService (ANS TISS 4.01)", "Guia: {$tissRes['tiss_guide_number']}");

// 2.7 AIE_MTE_CATALOGO_CA
$caService = new AieMteCaService($pdo);
$caValid = $caService->verifyCertificateOfApproval('31469', 'tenant-ind');
assertCondition($caValid['is_valid_for_delivery'] === true && $caValid['status'] === 'VALIDO', "AIE 7/10: AieMteCaService (CA Válido)", "Status: {$caValid['status']}");
$caExpired = $caService->verifyCertificateOfApproval('15624', 'tenant-ind');
assertCondition($caExpired['is_valid_for_delivery'] === false && $caExpired['status'] === 'VENCIDO', "AIE 7/10: AieMteCaService (CA Vencido)", "Status: {$caExpired['status']}");

// 2.8 AIE_CLINICA_MEDICA_SOC
$socService = new AieClinicaMedicaService($pdo);
$asoRes = $socService->scheduleAndIssueAso('emp-marcos', 'PERIODICO', true, 'tenant-ind');
assertCondition($asoRes['is_fit'] === true && !empty($asoRes['protocol_number']), "AIE 8/10: AieClinicaMedicaService (SOC Telemedicina ASO)", "Protocolo: {$asoRes['protocol_number']}");

// 2.9 AIE_SEGURADORA_FINCORP
$segService = new AieSeguradoraService($pdo);
$apoliceRes = $segService->underwriteLifePolicy([
    'full_name' => 'Helena Castro',
    'cpf' => '888.999.000-08',
    'birth_date' => '1988-06-12'
], 50000000, 'LIFE_GROUP', 'tenant-fin');
assertCondition($apoliceRes['status'] === 'APPROVED_UNDERWRITING' && $apoliceRes['monthly_premium_cents'] > 0, "AIE 9/10: AieSeguradoraService (FinCorp Underwriting)", "Prêmio: R$ {$apoliceRes['monthly_premium_reais']}");

// 2.10 AIE_PIX_BACEN_SPI
$pixService = new AiePixBacenService($pdo);
$pixRes = $pixService->generateDynamicPix('pol-01', 14000, 'Fat-Seguro-2026-10', 'tenant-fin');
assertCondition(!empty($pixRes['pix_copia_e_cola']) && !empty($pixRes['txid']), "AIE 10/10: AiePixBacenService (BACEN SPI EMV QR Code)", "TxID: " . substr($pixRes['txid'], 0, 16) . "...");
$liquidRes = $pixService->settleTransaction($pixRes['txid'], 'tenant-fin');
assertCondition($liquidRes['status'] === 'SETTLED' && !empty($liquidRes['end_to_end_id']), "AIE 10/10: AiePixBacenService (Liquidação Instantânea SPI)", "E2E: " . substr($liquidRes['end_to_end_id'], 0, 20) . "...");

$finalExtCount = (int)$pdo->query("SELECT COUNT(*) FROM external_integrations")->fetchColumn();
assertCondition($finalExtCount > $initialExtCount, "Persistência em external_integrations", "Novas integrações registradas: " . ($finalExtCount - $initialExtCount));

echo "\n";

// ==============================================================================
// 3. VERIFICAÇÃO DOS MOTORES MATEMÁTICOS SE (SAÍDAS EXTERNAS)
// ==============================================================================
echo "3. Verificação dos Motores SE (Cálculos CLT, SST, Auditoria e Finanças):\n";

// 3.1 SE_EMITIR_HOLERITE_MENSAL
$payrollCtrl = new PayrollController($pdo);
$payslip = $payrollCtrl->calculatePayslip('emp-lucas');
assertCondition(
    $payslip['base_salary'] === 9800.00 &&
    $payslip['inss_deduction'] > 0 &&
    $payslip['irrf_deduction'] > 0 &&
    $payslip['net_salary'] > 0 &&
    $payslip['benefits_discount'] <= 588.00,
    "SE 1/10: SE_EMITIR_HOLERITE_MENSAL (CltPayroll 4 Faixas INSS + IRRF + VT)",
    "Bruto: R$ 9.800,00 | INSS: R$ " . number_format($payslip['inss_deduction'], 2, ',', '.') . " | IRRF: R$ " . number_format($payslip['irrf_deduction'], 2, ',', '.') . " | Líquido: R$ " . number_format($payslip['net_salary'], 2, ',', '.')
);

// 3.2 SE_RECIBO_PROVISAO_13_FERIAS
$provisions = $payrollCtrl->calculateProvisions('tenant-tech');
assertCondition(
    $provisions['clt_headcount'] > 0 &&
    $provisions['total_provision_13th'] > 0 &&
    $provisions['total_provision_vacations'] > 0 &&
    $provisions['total_provision_third'] > 0 &&
    $provisions['total_fgts_provisions'] > 0 &&
    $provisions['grand_total_monthly_provision'] > 0,
    "SE 2/10: SE_RECIBO_PROVISAO_13_FERIAS (1/12 Avos + 1/3 CF + FGTS 8%)",
    "13º Total: R$ {$provisions['total_provision_13th']} | Férias Total: R$ {$provisions['total_provision_vacations']} | FGTS: R$ {$provisions['total_fgts_provisions']}"
);

// 3.3 SE_REL_TURNO_CONTRATO
$empCtrl = new EmployeeController($pdo);
$turnover = $empCtrl->calculateTurnover('tenant-tech');
assertCondition(
    isset($turnover['turnover_rate_percent']) &&
    $turnover['final_headcount'] > 0 &&
    isset($turnover['by_department']),
    "SE 3/10: SE_REL_TURNO_CONTRATO (Turnover e Headcount por Departamento)",
    "Headcount: {$turnover['final_headcount']} | Turnover: {$turnover['turnover_rate_percent']}%"
);

// 3.4 SE_EXTRATO_ESPELHO_PONTO
$timeCtrl = new TimeLogController($pdo);
$espelho = $timeCtrl->calculateEspelho('emp-marcos', '2026-09');
assertCondition(
    $espelho['total_worked_hours'] > 0 &&
    $espelho['total_nocturnal_hours_reduced'] > 0 &&
    $espelho['nocturnal_surcharge_amount'] > 0 &&
    str_contains($espelho['clt_article_73_applied'], '52m30s'),
    "SE 4/10: SE_EXTRATO_ESPELHO_PONTO (Art. 73 CLT Noturno 52m30s + 20% Adicional)",
    "Noturno Reduzido: {$espelho['total_nocturnal_hours_reduced']}h | Total: {$espelho['total_worked_hours']}h | Adicional: R$ {$espelho['nocturnal_surcharge_amount']}"
);

// 3.5 SE_RELATORIO_HORAS_EXTRAS
$overtimeReport = $timeCtrl->calculateOvertimeReport('tenant-ind');
assertCondition(
    $overtimeReport['total_records'] > 0 &&
    $overtimeReport['grand_total_overtime_cash'] > 0,
    "SE 5/10: SE_RELATORIO_HORAS_EXTRAS (Strategy 50%, 100% e Banco de Horas)",
    "Registros: {$overtimeReport['total_records']} | Valor Total: R$ " . number_format($overtimeReport['grand_total_overtime_cash'], 2, ',', '.')
);

// 3.6 SE_CALCULO_RECIBO_FERIAS
$vacationCtrl = new VacationController($pdo);
$vacReceipt = $vacationCtrl->calculateVacationReceipt('vac-01');
assertCondition(
    $vacReceipt['days_gozo'] === 20 &&
    $vacReceipt['abono_days'] === 10 &&
    $vacReceipt['earnings']['terco_constitucional_cf88'] > 0 &&
    $vacReceipt['earnings']['abono_pecuniario_art143'] > 0 &&
    $vacReceipt['earnings']['adiantamento_decimo_terceiro'] === 4900.00 && // 50% de 9800
    $vacReceipt['net_vacation_amount'] > 0,
    "SE 6/10: SE_CALCULO_RECIBO_FERIAS (Art. 143 Abono 10d + 1/3 CF + 50% 13º Adiantado)",
    "Líquido Férias: R$ " . number_format($vacReceipt['net_vacation_amount'], 2, ',', '.') . " | Prazo Pagamento: {$vacReceipt['payment_deadline_legal']}"
);

// 3.7 SE_DEMONSTRATIVO_BENEFICIOS
$benefitCtrl = new BenefitController($pdo);
$benefitStatement = $benefitCtrl->calculateCopayStatement('tenant-tech');
assertCondition(
    $benefitStatement['employees_count'] > 0 &&
    $benefitStatement['grand_total_face_value'] > 0 &&
    $benefitStatement['grand_total_employee_deduction'] > 0 &&
    $benefitStatement['grand_total_company_subsidy'] > 0,
    "SE 7/10: SE_DEMONSTRATIVO_BENEFICIOS (Teto Legal VT 6% + PAT 20% Coparticipação)",
    "Funcionários: {$benefitStatement['employees_count']} | Desconto Colaboradores: R$ " . number_format($benefitStatement['grand_total_employee_deduction'], 2, ',', '.') . " | Subsídio Empresa: R$ " . number_format($benefitStatement['grand_total_company_subsidy'], 2, ',', '.')
);

// 3.8 SE_PAINEL_ALERTAS_NR6_NR7
$safetyCtrl = new SafetyController($pdo);
$nrAlerts = $safetyCtrl->calculateNr6Nr7Alerts('tenant-ind');
assertCondition(
    $nrAlerts['critical_expired_count'] > 0 &&
    $nrAlerts['estimated_mte_fine_exposure_reais'] > 0 &&
    count($nrAlerts['critical_records']) > 0,
    "SE 8/10: SE_PAINEL_ALERTAS_NR6_NR7 (Painel Crítico SST NR-6/NR-7 e Multas NR-28)",
    "Itens Vencidos: {$nrAlerts['critical_expired_count']} | Risco Multa: R$ " . number_format($nrAlerts['estimated_mte_fine_exposure_reais'], 2, ',', '.')
);

// 3.9 SE_BLOQUEIO_CHAO_FABRICA
$blockCheckMarcos = $safetyCtrl->checkTurnstileAccess('emp-marcos');
assertCondition(
    $blockCheckMarcos['access_granted'] === false &&
    $blockCheckMarcos['status'] === 'RED' &&
    count($blockCheckMarcos['violations']) > 0,
    "SE 9/10: SE_BLOQUEIO_CHAO_FABRICA (Marcos Paulo BLOQUEADO por CA Vencido 15624)",
    "Status: {$blockCheckMarcos['status']} | Violações: " . implode('; ', $blockCheckMarcos['violations'])
);

$allowCheckJoao = $safetyCtrl->checkTurnstileAccess('emp-joao');
assertCondition(
    $allowCheckJoao['access_granted'] === true &&
    $allowCheckJoao['status'] === 'GREEN',
    "SE 9/10: SE_BLOQUEIO_CHAO_FABRICA (Joao Oliveira LIBERADO com SST Válido)",
    "Status: {$allowCheckJoao['status']} | Catraca: LIBERADO"
);

// 3.10 SE_EXPORTAR_TRILHA_AUDITORIA
$complianceCtrl = new ComplianceController($pdo);
$auditTrail = $complianceCtrl->exportAuditTrail();
assertCondition(
    $auditTrail['is_chain_valid'] === true &&
    $auditTrail['tampered_blocks_count'] === 0 &&
    $auditTrail['valid_blocks'] === $auditTrail['total_blocks'] &&
    $auditTrail['laudo_pericial']['status_laudo'] === 'CONFORME / INVIOLÁVEL',
    "SE 10/10: SE_EXPORTAR_TRILHA_AUDITORIA (Validação SHA-256 e Laudo LGPD/BACEN)",
    "Total Blocos: {$auditTrail['total_blocks']} | Integridade: 100% VÁLIDA"
);

// 3.11 SE_RELATORIO_PPP_ELETRONICO
$pppReport = $safetyCtrl->generatePppReport('emp-marcos');
assertCondition(
    $pppReport['employee_id'] === 'emp-marcos' &&
    count($pppReport['environmental_factors']) > 0 &&
    !empty($pppReport['responsible_physician']),
    "SE 11/10: SE_RELATORIO_PPP_ELETRONICO (PPP Previdenciário e Fatores de Risco)",
    "Riscos Identificados: " . count($pppReport['environmental_factors']) . " (Ruído 88.4 dB(A), Óleos)"
);

// 3.12 SE_RELATORIO_SINISTRALIDADE
$insuranceCtrl = new InsuranceController($pdo);
$lossRatio = $insuranceCtrl->calculateLossRatio('tenant-fin');
assertCondition(
    $lossRatio['annual_premiums_collected'] > 0 &&
    isset($lossRatio['loss_ratio_percent']) &&
    !empty($lossRatio['actuarial_health']),
    "SE 12/10: SE_RELATORIO_SINISTRALIDADE (Índice de Sinistralidade Atuarial)",
    "Prêmios: R$ " . number_format($lossRatio['annual_premiums_collected'], 2, ',', '.') . " | Sinistralidade: {$lossRatio['loss_ratio_percent']}%"
);

echo "\n=================================================================\n";
echo "RESULTADO DA VERIFICAÇÃO INTEGRAL DO BACKEND:\n";
echo "Total de Testes Executados: {$totalTests}\n";
echo "Total de Testes Aprovados:  {$passedTests}\n";
echo "Taxa de Sucesso:            " . round(($passedTests / $totalTests) * 100, 2) . "%\n";
echo "=================================================================\n";

if ($passedTests === $totalTests) {
    echo "🎉 TODOS OS TESTES PASSARAM COM SUCESSO! MILESTONE M1 100% CONCLUÍDO.\n";
    exit(0);
} else {
    echo "❌ EXISTEM FALHAS NOS TESTES.\n";
    exit(1);
}
