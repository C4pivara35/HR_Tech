<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Services\External\AieGovEsocialService;
use App\Services\External\AieReceitaCpfService;

/**
 * ==============================================================================
 * EmployeeController — Gestão de Colaboradores, Admissão & eSocial
 * ==============================================================================
 * Responsabilidade: Andryus Zolet (CRUD 3 e CRUD 4)
 *
 * Funções de APF Conectadas:
 * - EE_CADASTRAR_COLABORADOR (Andryus Zolet - 4 PFB)
 * - EE_CRIAR_DEPARTAMENTO_CARGO (Andryus Zolet - 3 PFB)
 * - SE_REL_TURNO_CONTRATO (Andryus Zolet - 4 PFB / Baixa Complexidade)
 *   Fórmula matemática de rotatividade: Turnover (%) = ((Admissões + Demissões) / 2) / Headcount Médio * 100
 * - SE_XML_ESOCIAL_S2200 (Andryus Zolet - 7 PFB / Alta Complexidade)
 *   Geração do XML canônico S-2200 do eSocial com digestão SHA-256 e recibo oficial.
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

        // Filtros de busca (CE_BUSCA_COLABORADOR)
        $filterName = trim($_GET['search_name'] ?? '');
        $filterCpf  = trim($_GET['search_cpf'] ?? '');
        $filterDept = trim($_GET['search_dept'] ?? '');
        $filterType = trim($_GET['search_type'] ?? '');
        $filterStatus = $_GET['search_status'] ?? 'active';

        $sql = "SELECT e.*, t.trading_name as tenant_name, d.name as department_name, r.name as role_name
                FROM employees e
                LEFT JOIN tenants t ON e.tenant_id = t.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN roles r ON e.role_id = r.id
                WHERE e.tenant_id = ?";
        $params = [$activeTenantId];

        if ($filterStatus === 'active') {
            $sql .= " AND e.is_active = 1";
        } elseif ($filterStatus === 'inactive') {
            $sql .= " AND e.is_active = 0";
        }

        if (!empty($filterName)) {
            $sql .= " AND e.full_name LIKE ?";
            $params[] = "%$filterName%";
        }
        if (!empty($filterCpf)) {
            $sql .= " AND e.cpf LIKE ?";
            $params[] = "%$filterCpf%";
        }
        if (!empty($filterDept)) {
            $sql .= " AND e.department_id = ?";
            $params[] = $filterDept;
        }
        if (!empty($filterType)) {
            $sql .= " AND e.employment_type = ?";
            $params[] = $filterType;
        }

        $sql .= " ORDER BY e.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll();

        // Busca departamentos e cargos para o modal de cadastro e filtros
        $deptStmt = $this->pdo->prepare("SELECT * FROM departments WHERE tenant_id = ? AND is_active = 1");
        $deptStmt->execute([$activeTenantId]);
        $departments = $deptStmt->fetchAll();

        $roleStmt = $this->pdo->prepare("SELECT * FROM roles WHERE tenant_id = ?");
        $roleStmt->execute([$activeTenantId]);
        $roles = $roleStmt->fetchAll();

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM employees WHERE tenant_id = '$activeTenantId' [CE_BUSCA_COLABORADOR]", [$activeTenantId], "CE_BUSCA_COLABORADOR");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/employees/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    /**
     * Tela 05 — Formulário Completo de Admissão & Validador de CPF Receita Federal (AIE_RECEITA_CPF)
     */
    public function newEmployee(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $deptStmt = $this->pdo->prepare("SELECT * FROM departments WHERE tenant_id = ? AND is_active = 1");
        $deptStmt->execute([$activeTenantId]);
        $departments = $deptStmt->fetchAll();

        $roleStmt = $this->pdo->prepare("SELECT * FROM roles WHERE tenant_id = ?");
        $roleStmt->execute([$activeTenantId]);
        $roles = $roleStmt->fetchAll();

        // Simulação prévia opcional se veio CPF por parâmetro
        $simulatedCpf = trim($_GET['cpf'] ?? '52998224725');
        $rfService = new AieReceitaCpfService($this->pdo);
        $rfCheck = $rfService->validateCpf($simulatedCpf, $activeTenantId);

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/employees/new.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    /**
     * Tela 06 — Ficha Cadastral do Colaborador & Emissor eSocial S-2200 (SE_XML_ESOCIAL_S2200)
     */
    public function detail(): void {
        $id = $_GET['id'] ?? '';
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        if (empty($id) || is_numeric($id)) {
            $offset = max(0, ((int)$id) - 1);
            $firstEmp = $this->pdo->prepare("SELECT id FROM employees WHERE tenant_id = ? ORDER BY full_name ASC LIMIT 1 OFFSET ?");
            $firstEmp->execute([$activeTenantId, $offset]);
            $foundId = $firstEmp->fetchColumn();
            if ($foundId) {
                $id = $foundId;
            } else {
                $anyEmp = $this->pdo->query("SELECT id FROM employees ORDER BY full_name ASC LIMIT 1");
                $id = $anyEmp->fetchColumn() ?: 'emp-ana';
            }
        }

        $stmt = $this->pdo->prepare(
            "SELECT e.*, d.name as department_name, d.code as department_code, r.name as role_name, r.hierarchy_level
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN roles r ON e.role_id = r.id
             WHERE e.id = ?"
        );
        $stmt->execute([$id]);
        $employee = $stmt->fetch();

        if (!$employee) {
            $_SESSION['flash_error'] = "Colaborador {$id} não encontrado!";
            header("Location: index.php?route=employees");
            exit;
        }

        // Gera eSocial S-2200 XML canônico (SE_XML_ESOCIAL_S2200)
        $esocialService = new AieGovEsocialService($this->pdo);
        $esocialTransmission = $esocialService->transmitS2200Event($employee, $employee['tenant_id']);

        // Benefícios vinculados
        $benStmt = $this->pdo->prepare(
            "SELECT b.*, eb.custom_cost_share, eb.enrolled_at 
             FROM employee_benefits eb 
             JOIN benefits b ON eb.benefit_id = b.id 
             WHERE eb.employee_id = ? AND eb.status = 'ACTIVE'"
        );
        $benStmt->execute([$id]);
        $employeeBenefits = $benStmt->fetchAll();

        // Registros SST
        $sstStmt = $this->pdo->prepare("SELECT * FROM equipment_aso WHERE employee_id = ? ORDER BY expiration_date DESC");
        $sstStmt->execute([$id]);
        $sstRecords = $sstStmt->fetchAll();

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/employees/detail.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function validateCpf(): void {
        $cpf = trim($_GET['cpf'] ?? '');
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $rfService = new AieReceitaCpfService($this->pdo);
        $res = $rfService->validateCpf($cpf, $activeTenantId);

        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
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
            $roleId = $roleStmt->fetchColumn() ?: 'role-dev-sr';
        }

        // Validação estrita via camada src/ (Domain, Value Objects & Exceptions)
        try {
            $moneyVo = \HrTech\Domain\ValueObjects\Money::fromFloat($salary);
            $salCents = $moneyVo->getCents();

            // Validação de CPF via src/Domain/ValueObjects/Cpf.php
            if (!\HrTech\Domain\ValueObjects\Cpf::isValid($cpf)) {
                throw \HrTech\Exceptions\ValidationException::forField('cpf', "CPF '$cpf' inválido nos dígitos verificadores (Módulo 11).");
            }
        } catch (\HrTech\Exceptions\ValidationException $e) {
            $_SESSION['flash_error'] = "Violação de Domínio (src/Domain): " . $e->getMessage();
            header("Location: index.php?route=employees");
            exit;
        }

        // Integração simulada com Receita Federal para validação do CPF (AIE_RECEITA_FEDERAL_CPF)
        $rfService = new AieReceitaCpfService($this->pdo);
        $rfResponse = $rfService->validateCpf($cpf, $activeTenantId);

        $id = 'emp-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');
        $matricula = 'MAT-' . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO employees 
             (id, tenant_id, matricula, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, is_active, vacation_days_balance, bank_hours_balance, created_at)
             VALUES (?, ?, ?, ?, ?, ?, '(41)99999-9999', '1990-01-01', ?, ?, ?, ?, ?, 1, 30, 0, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $matricula, $cpf, $name, $email, date('Y-m-d'), $deptId, $roleId, $salCents, $type, $now]);

        if (function_exists('logQuery')) {
            logQuery(
                "INSERT INTO employees (id, tenant_id, full_name, cpf, matricula=$matricula)", 
                [$id, $name, $salCents], 
                "EE_CADASTRAR_COLABORADOR",
                "View employees ➔ Router (?route=employees&action=store) ➔ EmployeeController::store() ➔ AieReceitaCpfService ➔ SQLite: employees"
            );
        }

        $_SESSION['flash_message'] = "Colaborador '$name' admitido com matrícula $matricula! Receita Federal: " . $rfResponse['status'];
        header("Location: index.php?route=employees");
        exit;
    }

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE employees SET is_active = 0, termination_date = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d'), $id]);
            if (function_exists('logQuery')) {
                logQuery(
                    "UPDATE employees SET is_active = 0 WHERE id = '$id'", 
                    [$id], 
                    "CRUD 3: Employee Termination",
                    "View employees (Botão Desativar) ➔ Router (?action=delete&id=$id) ➔ EmployeeController::delete() ➔ Soft Delete ➔ SQLite: employees"
                );
            }
            $_SESSION['flash_message'] = "Colaborador desativado com sucesso!";
        }
        header("Location: index.php?route=employees");
        exit;
    }

    /**
     * SE_REL_TURNO_CONTRATO — Apuração matemática de rotatividade de pessoal (Turnover Rate).
     *
     * Fórmula oficial:
     * Turnover (%) = ((Admissões + Demissões) / 2) / Headcount Médio * 100
     *
     * @param string $tenantId
     * @return array<string, mixed>
     */
    public function calculateTurnover(string $tenantId = 'tenant-tech'): array {
        // Total de ativos
        $stmtActive = $this->pdo->prepare("SELECT COUNT(*) FROM employees WHERE tenant_id = ? AND is_active = 1");
        $stmtActive->execute([$tenantId]);
        $activeHeadcount = (int)$stmtActive->fetchColumn();

        // Admissões no ano corrente (2026)
        $stmtAdm = $this->pdo->prepare("SELECT COUNT(*) FROM employees WHERE tenant_id = ? AND admission_date >= '2026-01-01'");
        $stmtAdm->execute([$tenantId]);
        $admissions = (int)$stmtAdm->fetchColumn();

        // Demissões registradas
        $stmtDem = $this->pdo->prepare("SELECT COUNT(*) FROM employees WHERE tenant_id = ? AND (is_active = 0 OR termination_date IS NOT NULL)");
        $stmtDem->execute([$tenantId]);
        $terminations = (int)$stmtDem->fetchColumn();

        // Headcount inicial estimado = Ativos + Demissões - Admissões
        $initialHeadcount = max(1, $activeHeadcount + $terminations - $admissions);
        $finalHeadcount = $activeHeadcount;
        $averageHeadcount = ($initialHeadcount + $finalHeadcount) / 2.0;

        // Cálculo rigoroso do turnover percentual
        $turnoverRate = round(((($admissions + $terminations) / 2.0) / $averageHeadcount) * 100, 2);

        // Agregação por regime contratual
        $stmtReg = $this->pdo->prepare(
            "SELECT employment_type, COUNT(*) as qty, SUM(base_salary_cents) as total_sal_cents 
             FROM employees WHERE tenant_id = ? AND is_active = 1 
             GROUP BY employment_type"
        );
        $stmtReg->execute([$tenantId]);
        $byRegime = $stmtReg->fetchAll();

        // Agregação por departamento
        $stmtDept = $this->pdo->prepare(
            "SELECT d.name as department_name, d.code, COUNT(e.id) as headcount, AVG(e.base_salary_cents)/100 as avg_salary 
             FROM departments d 
             LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1 
             WHERE d.tenant_id = ? 
             GROUP BY d.id"
        );
        $stmtDept->execute([$tenantId]);
        $byDepartment = $stmtDept->fetchAll();

        return [
            'tenant_id' => $tenantId,
            'initial_headcount' => $initialHeadcount,
            'admissions' => $admissions,
            'terminations' => $terminations,
            'final_headcount' => $finalHeadcount,
            'average_headcount' => $averageHeadcount,
            'turnover_rate_percent' => $turnoverRate,
            'formula_applied' => 'Turnover = [((Admissões + Desligamentos) / 2) / Efetivo Médio] * 100',
            'by_regime' => $byRegime,
            'by_department' => $byDepartment,
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * SE_XML_ESOCIAL_S2200 — Gera o XML S-2200 e simula transmissão ao eSocial Gov.br.
     */
    public function generateEsocialS2200(string $employeeId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $emp = $stmt->fetch();

        if (!$emp) {
            throw new \InvalidArgumentException("Colaborador {$employeeId} não encontrado.");
        }

        $service = new AieGovEsocialService($this->pdo);
        return $service->transmitS2200Event($emp, $emp['tenant_id']);
    }
}
