<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * InsuranceController — Gestão de Apólices de Seguro FinCorp
 * ==============================================================================
 * Responsabilidade: Nicholas (CRUD 10)
 *
 * Conceitos de Domínio & Gestão de Riscos:
 * 1. Apólices Coletivas Corporativas:
 *    - Gestão de apólices de Seguro de Vida em Grupo, Invalidez Permanente e D&O
 *      (Directors & Officers / Responsabilidade Civil de Gestores).
 *
 * 2. Precisão Financeira em Centavos:
 *    - Capital Segurado (`coverage_cents`) e Prêmio Mensal (`premium_cents`) são
 *      armazenados em centavos inteiros conforme o padrão DDD do Value Object Money.
 *
 * 3. Ciclo de Vida da Apólice:
 *    - Emissão de contrato com número de apólice único, vigência anual e cancelamento formal.
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
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        $type  = $_POST['policy_type'] ?? 'D_AND_O';
        $cov   = (float)($_POST['coverage_amount'] ?? 1000000);
        $prem  = (float)($_POST['monthly_premium'] ?? 2500);

        // Busca o ID do colaborador para a apólice
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

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("DELETE FROM insurance_policies WHERE id = ?");
            $stmt->execute([$id]);

            logQuery("DELETE FROM insurance_policies WHERE id = '$id'", [$id], "CRUD 10: InsurancePolicy Delete");

            $_SESSION['flash_message'] = "Apólice de seguro excluída com sucesso!";
        }
        header("Location: index.php?route=insurance");
        exit;
    }
}
