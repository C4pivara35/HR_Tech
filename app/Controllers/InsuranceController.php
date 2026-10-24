<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Services\External\AieSeguradoraService;
use App\Services\External\AiePixBacenService;

/**
 * ==============================================================================
 * InsuranceController — Gestão de Apólices FinCorp & Sinistralidade Atuarial
 * ==============================================================================
 * Funções de APF Conectadas:
 * - EE_EMITIR_APOLICE_SEGURO (Fernando Lopes Duarte - 4 PFB)
 * - SE_RELATORIO_SINISTRALIDADE (Fernando Lopes Duarte - 7 PFB / Alta Complexidade)
 *   Fórmula atuarial de sinistralidade: (Sinistros Pagos / Prêmios Arrecadados) * 100,
 *   projeção de reajuste técnico e margem atuarial perante o benchmark SUSEP (70%).
 * ==============================================================================
 */
class InsuranceController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        // Filtros CE_CONSULTAR_APOLICE_VIGENTE
        $filterBroker = trim($_GET['search_broker'] ?? '');
        $filterStatus = trim($_GET['search_status'] ?? '');

        $sql = "SELECT ip.*, t.corporate_name as tenant_name, e.full_name as employee_name, e.cpf
                FROM insurance_policies ip
                JOIN tenants t ON ip.tenant_id = t.id
                LEFT JOIN employees e ON ip.employee_id = e.id
                WHERE ip.tenant_id = ?";
        $params = [$activeTenantId];

        if (!empty($filterBroker)) {
            $sql .= " AND (ip.broker_code LIKE ? OR ip.policy_number LIKE ?)";
            $params[] = "%$filterBroker%";
            $params[] = "%$filterBroker%";
        }
        if (!empty($filterStatus)) {
            $sql .= " AND ip.status = ?";
            $params[] = $filterStatus;
        }

        $sql .= " ORDER BY ip.start_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $policies = $stmt->fetchAll();

        // SE_RELATORIO_SINISTRALIDADE — Cálculo atuarial de sinistralidade e margem de solvência
        $lossRatio = $this->calculateLossRatio($activeTenantId);

        // Busca transações PIX vinculadas
        $pixStmt = $this->pdo->prepare(
            "SELECT px.*, ip.policy_number 
             FROM pix_transactions px 
             LEFT JOIN insurance_policies ip ON px.policy_id = ip.id 
             WHERE px.tenant_id = ? 
             ORDER BY px.created_at DESC"
        );
        $pixStmt->execute([$activeTenantId]);
        $pixList = $pixStmt->fetchAll();

        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM insurance_policies [CE_CONSULTAR_APOLICE_VIGENTE & SE_RELATORIO_SINISTRALIDADE]", [$activeTenantId], "SE_RELATORIO_SINISTRALIDADE");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/insurance/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function issue(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        $empId = $_POST['employee_id'] ?? '';
        $type  = $_POST['policy_type'] ?? 'LIFE_GROUP';
        $cov   = (float)($_POST['coverage_amount'] ?? 500000);
        $brokerCode = trim($_POST['broker_code'] ?? 'BRK-001');

        if (empty($empId)) {
            $empStmt = $this->pdo->prepare("SELECT id FROM employees WHERE tenant_id = ? LIMIT 1");
            $empStmt->execute([$activeTenantId]);
            $empId = $empStmt->fetchColumn() ?: 'emp-helena';
        }

        $empRowStmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $empRowStmt->execute([$empId]);
        $employee = $empRowStmt->fetch();

        $covCents = (int)($cov * 100);

        // Subscrição atuarial externa simulada junto ao Gateway FinCorp (AIE_FINCORP_SEGURADORA)
        $seguradoraService = new AieSeguradoraService($this->pdo);
        $underwriting = $seguradoraService->underwriteLifePolicy($employee, $covCents, $type, $activeTenantId);

        $id = 'pol-' . uniqid();
        $policyNumber = $underwriting['policy_number'];
        $premCents = (int)$underwriting['monthly_premium_cents'];
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+1 year'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO insurance_policies (id, tenant_id, policy_number, broker_code, insurer_name, employee_id, insured_capital_cents, monthly_premium_cents, status, start_date, end_date, coverage_details)
             VALUES (?, ?, ?, ?, 'FinCorp Seguros S.A.', ?, ?, ?, 'ACTIVE', ?, ?, ?)"
        );
        $stmt->execute([
            $id, 
            $activeTenantId, 
            $policyNumber, 
            $brokerCode, 
            $empId, 
            $covCents, 
            $premCents, 
            $startDate, 
            $endDate, 
            json_encode($underwriting['coverages'])
        ]);

        // Geração da cobrança PIX imediata (AIE_PIX_BACEN_GATEWAY)
        $pixService = new AiePixBacenService($this->pdo);
        $pixBilling = $pixService->generateDynamicPix($id, $premCents, "Prêmio Mensal Apólice $policyNumber", $activeTenantId);

        if (function_exists('logQuery')) {
            logQuery(
                "EE_EMITIR_APOLICE_SEGURO (Policy=$policyNumber, Capital=R$ $cov, PixTxID={$pixBilling['txid']})", 
                [$id], 
                "EE_EMITIR_APOLICE_SEGURO",
                "View insurance ➔ Router (?route=insurance&action=issue) ➔ InsuranceController::issue() ➔ AieSeguradoraService ➔ AiePixBacenService ➔ SQLite: insurance_policies"
            );
        }

        $_SESSION['flash_message'] = "Apólice $policyNumber emitida! Cobrança PIX gerada: TxID {$pixBilling['txid']}";
        header("Location: index.php?route=insurance");
        exit;
    }

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("DELETE FROM insurance_policies WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = "Apólice de seguro excluída com sucesso!";
        }
        header("Location: index.php?route=insurance");
        exit;
    }

    /**
     * SE_RELATORIO_SINISTRALIDADE — Cálculo atuarial de sinistralidade (Loss Ratio) e ajuste técnico.
     *
     * Fórmula:
     * Sinistralidade (%) = (Sinistros Pagos / Prêmios Arrecadados) * 100
     *
     * Benchmark SUSEP: 70% de equilíbrio técnico.
     *
     * @param string $tenantId
     * @return array<string, mixed>
     */
    public function calculateLossRatio(string $tenantId = 'tenant-fin'): array {
        // Total de prêmios arrecadados no tenant
        $stmtPrem = $this->pdo->prepare(
            "SELECT SUM(monthly_premium_cents) as total_prem_cents, COUNT(*) as policies_count 
             FROM insurance_policies WHERE tenant_id = ?"
        );
        $stmtPrem->execute([$tenantId]);
        $premData = $stmtPrem->fetch();

        // 12 meses de arrecadação contratual
        $annualPremiums = (((int)($premData['total_prem_cents'] ?? 0)) * 12) / 100.0;
        
        // Simulação histórica de sinistros ocorridos no período
        $claimsPaid = 42500.00; // Valor consolidado de indenizações pagas
        $lossRatioPercent = $annualPremiums > 0 ? round(($claimsPaid / $annualPremiums) * 100, 2) : 0.0;

        $targetEquilibrium = 70.0;
        $isDeficitary = $lossRatioPercent > $targetEquilibrium;

        $recommendedRateAdjustment = 0.0;
        if ($isDeficitary) {
            // Fator de correção atuarial de recomposição de carteira
            $recommendedRateAdjustment = round(($lossRatioPercent - $targetEquilibrium) * 1.15, 2);
        }

        $surplusMargin = round($annualPremiums - $claimsPaid, 2);

        return [
            'tenant_id' => $tenantId,
            'policies_count' => (int)($premData['policies_count'] ?? 0),
            'annual_premiums_collected' => round($annualPremiums, 2),
            'claims_paid_historical' => round($claimsPaid, 2),
            'loss_ratio_percent' => $lossRatioPercent,
            'target_equilibrium_benchmark' => $targetEquilibrium,
            'actuarial_health' => $isDeficitary ? 'DEFICITÁRIO (RISCO ELEVADO)' : 'SUPERAVITÁRIO (EQUILÍBRIO TÉCNICO)',
            'recommended_rate_adjustment_percent' => $recommendedRateAdjustment,
            'portfolio_surplus_reais' => $surplusMargin,
            'formula' => 'Sinistralidade (%) = (Sinistros Indenizados / Prêmios Arrecadados) * 100',
            'regulatory_reference' => 'SUSEP - Circular nº 600/2020 e Princípios Atuariais do IBA',
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }
}
