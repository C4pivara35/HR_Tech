<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * OrganogramController — Estrutura Organizacional & Organograma Interativo
 * ==============================================================================
 * Responsabilidade: Andryus Zolet
 *
 * Funções de APF Conectadas:
 * - ALI_DEPARTAMENTOS_CARGOS (Andryus Zolet - 7 PFB / Baixa Complexidade)
 *   Tabelas: departments (id, tenant_id, code, name, manager_employee_id, is_active)
 *            roles (id, tenant_id, department_id, name, hierarchy_level, base_salary_cents)
 * - EE_CRIAR_DEPARTAMENTO_CARGO (Andryus Zolet - 3 PFB / Baixa Complexidade)
 *   Criação de novas unidades organizacionais, centros de custo e alçadas de cargos.
 * - CE_ORGANOGRAMA_GRAFICO (Andryus Zolet - 3 PFB / Baixa Complexidade)
 *   Filtro de busca por departamento/cargo e renderização gráfica da árvore com contagem de liderados.
 * ==============================================================================
 */
class OrganogramController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $filterDept = trim($_GET['dept_filter'] ?? '');
        $filterSearch = trim($_GET['search_term'] ?? '');

        // Busca departamentos com gestores e total de colaboradores
        $sql = "SELECT d.*, 
                       m.full_name as manager_name, 
                       m.email as manager_email,
                       (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id AND e.is_active = 1) as headcount,
                       (SELECT AVG(e.base_salary_cents)/100 FROM employees e WHERE e.department_id = d.id AND e.is_active = 1) as avg_salary,
                       (SELECT COUNT(*) FROM roles r WHERE r.department_id = d.id) as roles_count
                FROM departments d
                LEFT JOIN employees m ON d.manager_id = m.id
                WHERE d.tenant_id = ?";

        $params = [$activeTenantId];
        if (!empty($filterDept)) {
            $sql .= " AND d.id = ?";
            $params[] = $filterDept;
        }
        if (!empty($filterSearch)) {
            $sql .= " AND (d.name LIKE ? OR d.code LIKE ?)";
            $params[] = "%$filterSearch%";
            $params[] = "%$filterSearch%";
        }

        $sql .= " ORDER BY d.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Busca todos os cargos agrupados por departamento
        $rolesStmt = $this->pdo->prepare(
            "SELECT r.*, d.name as department_name,
                    (SELECT COUNT(*) FROM employees e WHERE e.role_id = r.id AND e.is_active = 1) as occupants_count
             FROM roles r
             JOIN departments d ON r.department_id = d.id
             WHERE r.tenant_id = ?
             ORDER BY r.hierarchy_level ASC, r.name ASC"
        );
        $rolesStmt->execute([$activeTenantId]);
        $allRoles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

        $rolesByDept = [];
        foreach ($allRoles as $role) {
            $rolesByDept[$role['department_id']][] = $role;
        }

        // Colaboradores para definir gestor no modal
        $empStmt = $this->pdo->prepare("SELECT id, full_name, email FROM employees WHERE tenant_id = ? AND is_active = 1 ORDER BY full_name ASC");
        $empStmt->execute([$activeTenantId]);
        $potentialManagers = $empStmt->fetchAll(PDO::FETCH_ASSOC);

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM departments & roles [CE_ORGANOGRAMA_GRAFICO]", [$activeTenantId], "ALI_DEPARTAMENTOS_CARGOS");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/organogram/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function createDepartment(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $managerId = !empty($_POST['manager_employee_id']) ? $_POST['manager_employee_id'] : null;

        if (empty($name) || empty($code)) {
            $_SESSION['flash_error'] = "Nome e Código do Departamento são obrigatórios!";
            header("Location: index.php?route=organogram");
            exit;
        }

        $id = 'dept-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $code)) . '-' . substr(uniqid(), -4);
        $now = date('Y-m-d H:i:s');

        $costCenter = 'CC-' . strtoupper($code);
        $stmt = $this->pdo->prepare(
            "INSERT INTO departments (id, tenant_id, code, name, cost_center, manager_id, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $code, $name, $costCenter, $managerId, $now]);

        if (function_exists('logQuery')) {
            logQuery("INSERT INTO departments (id='$id', code='$code', name='$name')", [$id], "EE_CRIAR_DEPARTAMENTO_CARGO");
        }

        $_SESSION['flash_message'] = "Departamento '$name' ($code) criado com sucesso!";
        header("Location: index.php?route=organogram");
        exit;
    }

    public function createRole(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $deptId = $_POST['department_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $level = (int)($_POST['hierarchy_level'] ?? 1);
        $salary = (float)($_POST['base_salary'] ?? 3000);

        if (empty($deptId) || empty($name)) {
            $_SESSION['flash_error'] = "Selecione o departamento e informe o nome do cargo!";
            header("Location: index.php?route=organogram");
            exit;
        }

        $id = 'role-' . uniqid();
        $salaryCents = (int)round($salary * 100);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            "INSERT INTO roles (id, tenant_id, department_id, name, hierarchy_level, base_salary_cents, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $deptId, $name, $level, $salaryCents, $now]);

        if (function_exists('logQuery')) {
            logQuery("INSERT INTO roles (id='$id', name='$name', level=$level)", [$id], "EE_CRIAR_DEPARTAMENTO_CARGO");
        }

        $_SESSION['flash_message'] = "Cargo '$name' vinculado ao departamento com sucesso!";
        header("Location: index.php?route=organogram");
        exit;
    }
}
