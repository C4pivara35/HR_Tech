<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class VacationController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $stmt = $this->pdo->prepare(
            "SELECT vr.*, e.full_name as employee_name, e.vacation_days_balance
             FROM vacation_requests vr
             JOIN employees e ON vr.employee_id = e.id
             WHERE e.tenant_id = ?
             ORDER BY vr.created_at DESC"
        );
        $stmt->execute([$activeTenantId]);
        $vacations = $stmt->fetchAll();

        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        logQuery("SELECT FROM vacation_requests WHERE tenant_id = '$activeTenantId'", [$activeTenantId], "CRUD 7: VacationRequest");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/vacations/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function request(): void {
        $empId = $_POST['employee_id'] ?? '';
        $start = $_POST['start_date'] ?? '';
        $days  = (int)($_POST['days_count'] ?? 15);

        if (empty($empId) || empty($start)) {
            $_SESSION['flash_error'] = "Preencha a data de início!";
            header("Location: index.php?route=vacations");
            exit;
        }

        $empStmt = $this->pdo->prepare("SELECT tenant_id FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $tenantId = $empStmt->fetchColumn() ?: 'tenant-tech';

        $endDate = date('Y-m-d', strtotime($start . " +$days days"));
        $id = 'vac-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO vacation_requests (id, tenant_id, employee_id, start_date, end_date, duration_days, abono_pecuniario, advance_thirteenth_salary, abono_days, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 'REQUESTED', ?)"
        );
        $stmt->execute([$id, $tenantId, $empId, $start, $endDate, $days, $now]);

        logQuery("INSERT INTO vacation_requests (id, duration_days=$days, status='REQUESTED')", [$id], "CRUD 7: Vacation Request Workflow");

        $_SESSION['flash_message'] = "Solicitação de $days dias de férias registrada!";
        header("Location: index.php?route=vacations");
        exit;
    }

    public function approve(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE vacation_requests SET status = 'APPROVED' WHERE id = ?");
            $stmt->execute([$id]);

            logQuery("UPDATE vacation_requests SET status = 'APPROVED' WHERE id = '$id'", [$id], "CRUD 7: Vacation Approval Workflow");

            $_SESSION['flash_message'] = "Férias aprovadas pelo departamento pessoal!";
        }
        header("Location: index.php?route=vacations");
        exit;
    }
}
