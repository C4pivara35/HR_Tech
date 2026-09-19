<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class BenefitController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $stmt = $this->pdo->prepare("SELECT * FROM benefits WHERE tenant_id = ?");
        $stmt->execute([$activeTenantId]);
        $benefits = $stmt->fetchAll();

        logQuery("SELECT FROM benefits WHERE tenant_id = '$activeTenantId'", [$activeTenantId], "CRUD 8: Benefit Strategy Discount");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/benefits/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function store(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $name  = trim($_POST['name'] ?? '');
        $type  = $_POST['type'] ?? 'MEAL_VOUCHER';
        $val   = (float)($_POST['value'] ?? 500);
        $disc  = (float)($_POST['employee_cost_share_percentage'] ?? 6.0);

        if (empty($name)) {
            $_SESSION['flash_error'] = "Nome do benefício obrigatório!";
            header("Location: index.php?route=benefits");
            exit;
        }

        $id = 'ben-' . uniqid();
        $valCents = (int)($val * 100);

        $stmt = $this->pdo->prepare(
            "INSERT INTO benefits (id, tenant_id, type, name, provider, value_cents, employee_cost_share_percentage, is_deductible)
             VALUES (?, ?, ?, ?, 'Provedor Corporativo', ?, ?, 1)"
        );
        $stmt->execute([$id, $activeTenantId, $type, $name, $valCents, $disc]);

        logQuery("INSERT INTO benefits (id, name, discount=$disc%)", [$id], "CRUD 8: Benefit Strategy");

        $_SESSION['flash_message'] = "Novo benefício corporativo registrado!";
        header("Location: index.php?route=benefits");
        exit;
    }
}
