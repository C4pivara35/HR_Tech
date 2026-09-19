<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class InsuranceController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $stmt = $this->pdo->prepare(
            "SELECT ip.*, t.corporate_name as tenant_name
             FROM insurance_policies ip
             JOIN tenants t ON ip.tenant_id = t.id
             WHERE ip.tenant_id = ?
             ORDER BY ip.start_date DESC"
        );
        $stmt->execute([$activeTenantId]);
        $policies = $stmt->fetchAll();

        logQuery("SELECT FROM insurance_policies WHERE tenant_id = '$activeTenantId'", [$activeTenantId], "CRUD 10: InsurancePolicy FinCorp");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/insurance/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function issue(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $type  = $_POST['policy_type'] ?? 'D_AND_O';
        $cov   = (float)($_POST['coverage_amount'] ?? 1000000);
        $prem  = (float)($_POST['monthly_premium'] ?? 2500);

        // Fetch an employee ID for the policy
        $empStmt = $this->pdo->prepare("SELECT id FROM employees WHERE tenant_id = ? LIMIT 1");
        $empStmt->execute([$activeTenantId]);
        $empId = $empStmt->fetchColumn() ?: 'emp-lucas';

        $id = 'pol-' . uniqid();
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime('+1 year'));

        $covCents  = (int)($cov * 100);
        $premCents = (int)($prem * 100);

        $stmt = $this->pdo->prepare(
            "INSERT INTO insurance_policies (id, tenant_id, policy_number, broker_code, insurer_name, employee_id, insured_capital_cents, monthly_premium_cents, status, start_date, end_date)
             VALUES (?, ?, ?, 'BROKER-01', 'FinCorp Seguros', ?, ?, ?, 'ACTIVE', ?, ?)"
        );
        $stmt->execute([$id, $activeTenantId, 'FIN-' . rand(10000,99999), $empId, $covCents, $premCents, $startDate, $endDate]);

        logQuery("INSERT INTO insurance_policies (FINCorp Policy type=$type)", [$id], "CRUD 10: InsurancePolicy Portal FinCorp");

        $_SESSION['flash_message'] = "Apólice corporativa FinCorp emitida com sucesso!";
        header("Location: index.php?route=insurance");
        exit;
    }
}
