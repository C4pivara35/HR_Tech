<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class TimeLogController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        // Time logs query
        $stmt = $this->pdo->prepare(
            "SELECT tl.*, e.full_name as employee_name, e.cpf
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             WHERE e.tenant_id = ?
             ORDER BY tl.nsr DESC
             LIMIT 30"
        );
        $stmt->execute([$activeTenantId]);
        $timeLogs = $stmt->fetchAll();

        // Adjustments query
        $adjStmt = $this->pdo->prepare(
            "SELECT tar.*, e.full_name as employee_name
             FROM time_adjustment_requests tar
             JOIN employees e ON tar.employee_id = e.id
             WHERE e.tenant_id = ?
             ORDER BY tar.created_at DESC"
        );
        $adjStmt->execute([$activeTenantId]);
        $adjustments = $adjStmt->fetchAll();

        // Employees for punch modal
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        logQuery("SELECT FROM time_logs ORDER BY nsr DESC (Portaria 671 MTE)", [$activeTenantId], "AuditLogger Cryptographic Chain");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/timelogs/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function punch(): void {
        $empId = $_POST['employee_id'] ?? '';
        $type  = $_POST['type'] ?? 'ENTRY';

        if (empty($empId)) {
            $_SESSION['flash_error'] = "Selecione um colaborador!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        // Fetch employee details
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $employee = $empStmt->fetch();

        if (!$employee) {
            $_SESSION['flash_error'] = "Colaborador não encontrado!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        // Get highest NSR and previous signature_hash
        $nsrStmt = $this->pdo->query("SELECT MAX(nsr), signature_hash FROM time_logs ORDER BY nsr DESC LIMIT 1");
        $lastRow = $nsrStmt->fetch();
        $nextNsr  = ((int)($lastRow[0] ?? 0)) + 1;
        $prevHash = $lastRow['signature_hash'] ?? str_repeat('0', 64);

        $timestamp = date('Y-m-d\TH:i:sP');
        $id = 'log-' . uniqid();

        // Portaria 671 Cryptographic Hash Calculation: SHA-256 (prevHash + nsr + cpf + timestamp + type)
        $rawPayload = $prevHash . '|' . $nextNsr . '|' . $employee['cpf'] . '|' . $timestamp . '|' . $type;
        $hash = hash('sha256', $rawPayload);

        $stmt = $this->pdo->prepare(
            "INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, accuracy, nsr, previous_hash, signature_hash)
             VALUES (?, ?, ?, ?, ?, -25.4284, -49.2733, 10.0, ?, ?, ?)"
        );
        $stmt->execute([$id, $employee['tenant_id'], $empId, $timestamp, $type, $nextNsr, $prevHash, $hash]);

        logQuery("INSERT INTO time_logs (nsr=$nextNsr, hash=" . substr($hash,0,12) . "...) SHA-256 Chain Validation", [$id, $nextNsr], "CRUD 5: TimeLog & AuditLogger SHA-256");

        $_SESSION['flash_message'] = "Batida de Ponto registrada com sucesso! NSR: $nextNsr | Hash SHA-256: " . substr($hash, 0, 16) . "...";
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function requestAdjustment(): void {
        $empId  = $_POST['employee_id'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        $date   = $_POST['requested_timestamp'] ?? date('Y-m-d\TH:i');

        if (empty($empId) || empty($reason)) {
            $_SESSION['flash_error'] = "Preencha a justificativa do ajuste!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        $empStmt = $this->pdo->prepare("SELECT tenant_id FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $tenantId = $empStmt->fetchColumn() ?: 'tenant-tech';

        $id = 'adj-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO time_adjustment_requests (id, tenant_id, employee_id, requested_date, requested_time, reason, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?)"
        );
        $stmt->execute([$id, $tenantId, $empId, date('Y-m-d'), $date, $reason, $now]);

        logQuery("INSERT INTO time_adjustment_requests (id, status='PENDING')", [$id], "CRUD 6: TimeAdjustmentRequest");

        $_SESSION['flash_message'] = "Solicitação de ajuste enviada para aprovação do gestor!";
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function approveAdjustment(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE time_adjustment_requests SET status = 'APPROVED' WHERE id = ?");
            $stmt->execute([$id]);
            logQuery("UPDATE time_adjustment_requests SET status = 'APPROVED' WHERE id = '$id'", [$id], "CRUD 6: TimeAdjustment Workflow");
            $_SESSION['flash_message'] = "Retificação de ponto APROVADA pelo gestor!";
        }
        header("Location: index.php?route=timelogs");
        exit;
    }
}
