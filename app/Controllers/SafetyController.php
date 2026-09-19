<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class SafetyController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $stmt = $this->pdo->prepare(
            "SELECT ea.*, e.full_name as employee_name, e.cpf
             FROM equipment_aso ea
             JOIN employees e ON ea.employee_id = e.id
             WHERE e.tenant_id = ?
             ORDER BY ea.created_at DESC"
        );
        $stmt->execute([$activeTenantId]);
        $safetyRecords = $stmt->fetchAll();

        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        logQuery("SELECT FROM equipment_aso WHERE tenant_id = '$activeTenantId'", [$activeTenantId], "CRUD 9: EquipmentASO NR-6/NR-7");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/safety/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function deliverEquipment(): void {
        $empId  = $_POST['employee_id'] ?? '';
        $item   = trim($_POST['item_name'] ?? '');
        $ca     = trim($_POST['ca_number'] ?? '');

        if (empty($empId) || empty($item) || empty($ca)) {
            $_SESSION['flash_error'] = "Preencha todos os campos do EPI!";
            header("Location: index.php?route=safety");
            exit;
        }

        $id = 'epi-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');
        $exp = date('Y-m-d', strtotime('+1 year'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO equipment_aso (id, employee_id, record_type, item_name, ca_number, issue_date, expiration_date, is_fit, created_at)
             VALUES (?, ?, 'EPI', ?, ?, ?, ?, 1, ?)"
        );
        $stmt->execute([$id, $empId, $item, $ca, date('Y-m-d'), $exp, $now]);

        logQuery("INSERT INTO equipment_aso (EPI delivery CA=$ca)", [$id], "CRUD 9: EquipmentASO NR-6");

        $_SESSION['flash_message'] = "EPI entregue e registrado com CA $ca no sistema!";
        header("Location: index.php?route=safety");
        exit;
    }

    public function recordExam(): void {
        $empId  = $_POST['employee_id'] ?? '';
        $type   = $_POST['exam_type'] ?? 'PERIODIC';
        $isFit  = isset($_POST['is_fit']) ? 1 : 0;

        if (empty($empId)) {
            $_SESSION['flash_error'] = "Selecione um colaborador!";
            header("Location: index.php?route=safety");
            exit;
        }

        $id = 'aso-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');
        $exp = $isFit ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d');

        $stmt = $this->pdo->prepare(
            "INSERT INTO equipment_aso (id, employee_id, record_type, item_name, ca_number, issue_date, expiration_date, is_fit, created_at)
             VALUES (?, ?, 'ASO', ?, 'N/A', ?, ?, ?, ?)"
        );
        $stmt->execute([$id, $empId, "Atestado ASO: $type", date('Y-m-d'), $exp, $isFit, $now]);

        logQuery("INSERT INTO equipment_aso (ASO exam fit=$isFit)", [$id], "CRUD 9: EquipmentASO NR-7 Check");

        if (!$isFit) {
            $_SESSION['flash_error'] = "ATENÇÃO: Colaborador INAPTO! Acesso ao chão de fábrica BLOQUEADO automaticamente no sistema!";
        } else {
            $_SESSION['flash_message'] = "Exame ASO registrado com aptidão médica liberada!";
        }

        header("Location: index.php?route=safety");
        exit;
    }
}
