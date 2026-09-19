<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class EmployeeController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $stmt = $this->pdo->prepare(
            "SELECT e.*, t.trading_name as tenant_name, d.name as department_name, r.name as role_name
             FROM employees e
             LEFT JOIN tenants t ON e.tenant_id = t.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN roles r ON e.role_id = r.id
             WHERE e.tenant_id = ? AND e.is_active = 1
             ORDER BY e.created_at DESC"
        );
        $stmt->execute([$activeTenantId]);
        $employees = $stmt->fetchAll();

        // Fetch departments and roles for the add modal
        $deptStmt = $this->pdo->prepare("SELECT * FROM departments WHERE tenant_id = ? AND is_active = 1");
        $deptStmt->execute([$activeTenantId]);
        $departments = $deptStmt->fetchAll();

        $roleStmt = $this->pdo->prepare("SELECT * FROM roles WHERE tenant_id = ?");
        $roleStmt->execute([$activeTenantId]);
        $roles = $roleStmt->fetchAll();

        logQuery("SELECT FROM employees WHERE tenant_id = '$activeTenantId'", [$activeTenantId], "DatabaseManager SQLite");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/employees/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function store(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $name   = trim($_POST['full_name'] ?? '');
        $cpf    = trim($_POST['cpf'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $salary = (float)($_POST['base_salary'] ?? 0);
        $type   = $_POST['employment_type'] ?? 'CLT';
        $deptId = $_POST['department_id'] ?? null;
        $roleId = $_POST['role_id'] ?? null;

        if (empty($name) || empty($cpf) || empty($email)) {
            $_SESSION['flash_error'] = "Preencha todos os campos obrigatórios!";
            header("Location: index.php?route=employees");
            exit;
        }

        if (empty($deptId)) {
            $deptStmt = $this->pdo->prepare("SELECT id FROM departments WHERE tenant_id = ? LIMIT 1");
            $deptStmt->execute([$activeTenantId]);
            $deptId = $deptStmt->fetchColumn() ?: 'dept-ti';
        }

        if (empty($roleId)) {
            $roleStmt = $this->pdo->prepare("SELECT id FROM roles WHERE tenant_id = ? LIMIT 1");
            $roleStmt->execute([$activeTenantId]);
            $roleId = $roleStmt->fetchColumn() ?: 'role-dev';
        }

        $id = 'emp-' . uniqid();
        $salCents = (int)($salary * 100);
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO employees 
             (id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, is_active, vacation_days_balance, bank_hours_balance, created_at)
             VALUES (?, ?, ?, ?, ?, '(41)99999-9999', '1990-01-01', ?, ?, ?, ?, ?, 1, 30, 0, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $cpf, $name, $email, date('Y-m-d'), $deptId, $roleId, $salCents, $type, $now]);

        logQuery("INSERT INTO employees (id, tenant_id, full_name, cpf, base_salary_cents) VALUES ('$id', '$activeTenantId', '$name', '$cpf', $salCents)", [$id, $name], "CRUD 3: Employee");

        $_SESSION['flash_message'] = "Colaborador '$name' cadastrado com sucesso no SQLite!";
        header("Location: index.php?route=employees");
        exit;
    }

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            logQuery("UPDATE employees SET is_active = 0 WHERE id = '$id'", [$id], "CRUD 3: Employee Termination");
            $_SESSION['flash_message'] = "Colaborador desativado com sucesso!";
        }
        header("Location: index.php?route=employees");
        exit;
    }
}
