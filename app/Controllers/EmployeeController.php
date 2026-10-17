<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * EmployeeController — Gestão de Colaboradores & Admissão
 * ==============================================================================
 * Responsabilidade: Andryus (CRUD 3 e CRUD 4)
 *
 * Padrões de Projeto & Conceitos de Domínio (DDD):
 * 1. Value Object Money (Martin Fowler):
 *    - O salário base é gravado em centavos inteiros (`base_salary_cents`).
 *    - Elimina erros de arredondamento e dízimas periódicas de tipos `float`.
 *
 * 2. Value Object Cpf:
 *    - Validação matemática de Módulo 11 para os dígitos verificadores.
 *    - Proteção de dados sensíveis e conformidade LGPD via máscara visual.
 *
 * 3. Banco de Horas e Jornada CLT:
 *    - O saldo do banco de horas é controlado em minutos inteiros (`bank_hours_balance`).
 *    - Divisor mensal padrão de 220 horas para contratos CLT de 44h semanais.
 * ==============================================================================
 */
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

        // Busca departamentos e cargos para o modal de cadastro
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

        // Validação estrita via camada src/ (Domain, Value Objects & Exceptions)
        try {
            $moneyVo = \HrTech\Domain\ValueObjects\Money::fromFloat($salary);
            $salCents = $moneyVo->getCents();

            // Validação de CPF via src/Domain/ValueObjects/Cpf.php
            if (!\HrTech\Domain\ValueObjects\Cpf::isValid($cpf)) {
                throw \HrTech\Exceptions\ValidationException::forField('cpf', "CPF '$cpf' inválido nos dígitos verificadores (Módulo 11 em src/Domain/ValueObjects/Cpf.php).");
            }
            $cpfVo = new \HrTech\Domain\ValueObjects\Cpf($cpf);
        } catch (\HrTech\Exceptions\ValidationException $e) {
            $_SESSION['flash_error'] = "Violação de Domínio (src/Domain): " . $e->getMessage();
            header("Location: index.php?route=employees");
            exit;
        }

        $id = 'emp-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO employees 
             (id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, is_active, vacation_days_balance, bank_hours_balance, created_at)
             VALUES (?, ?, ?, ?, ?, '(41)99999-9999', '1990-01-01', ?, ?, ?, ?, ?, 1, 30, 0, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $cpf, $name, $email, date('Y-m-d'), $deptId, $roleId, $salCents, $type, $now]);

        logQuery(
            "INSERT INTO employees (id, tenant_id, full_name, cpf, base_salary_cents) VALUES ('$id', '$activeTenantId', '$name', '$cpf', $salCents)", 
            [$id, $name, $salCents], 
            "CRUD 3: Employee Admissão",
            "View employees ➔ Router (?route=employees&action=store) ➔ EmployeeController ➔ src/Domain/ValueObjects (Money & Cpf) ➔ src/Exceptions (ValidationException) ➔ SQLite: employees"
        );

        $_SESSION['flash_message'] = "Colaborador '$name' cadastrado com sucesso no SQLite!";
        header("Location: index.php?route=employees");
        exit;
    }

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            logQuery(
                "UPDATE employees SET is_active = 0 WHERE id = '$id'", 
                [$id], 
                "CRUD 3: Employee Termination",
                "View employees (Botão Desativar) ➔ Router (?action=delete&id=$id) ➔ EmployeeController::delete() ➔ Soft Delete ➔ SQLite: employees"
            );
            $_SESSION['flash_message'] = "Colaborador desativado com sucesso!";
        }
        header("Location: index.php?route=employees");
        exit;
    }
}
