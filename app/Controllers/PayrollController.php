<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use DateTimeImmutable;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Patterns\TemplateMethod\Payroll\CltPayroll;
use HrTech\Patterns\TemplateMethod\Payroll\PjPayroll;
use HrTech\Patterns\TemplateMethod\Payroll\InternPayroll;
use HrTech\Patterns\Strategy\BenefitDiscount\TransportationVoucherStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\HealthPlanStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\MealVoucherStrategy;

/**
 * ==============================================================================
 * PayrollController — Motor de Cálculo de Folha de Pagamento & Provisões CLT
 * ==============================================================================
 * Funções de APF Conectadas:
 * - SE_EMITIR_HOLERITE_MENSAL (Andryus Zolet - 7 PFB / Alta Complexidade)
 *   Motor CltPayroll: 4 faixas progressivas INSS (7.5%-14%), IRRF progressivo (0%-27.5%),
 *   dedução de dependentes (R$ 189,59), teto de VT 6% e cálculo do salário líquido.
 *
 * - SE_RECIBO_PROVISAO_13_FERIAS (Andryus Zolet - 4 PFB / Baixa Complexidade)
 *   Cômputo atuarial de provisões mensais: 1/12 avos 13º, 1/12 avos férias, 1/3 constitucional
 *   e FGTS 8% sobre provisões.
 * ==============================================================================
 */
class PayrollController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        // Filtros CE_CONSULTAR_FICHA_FINANCEIRA
        $filterEmp = trim($_GET['search_employee_id'] ?? $_GET['id'] ?? '');
        if (is_numeric($filterEmp)) {
            $offset = max(0, ((int)$filterEmp) - 1);
            $empStmt = $this->pdo->prepare("SELECT id FROM employees WHERE tenant_id = ? AND is_active = 1 ORDER BY full_name ASC LIMIT 1 OFFSET ?");
            $empStmt->execute([$activeTenantId, $offset]);
            $foundId = $empStmt->fetchColumn();
            if ($foundId) {
                $filterEmp = $foundId;
            }
        }
        $filterType = trim($_GET['search_type'] ?? '');

        $sql = "SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1";
        $params = [$activeTenantId];

        if (!empty($filterEmp)) {
            $sql .= " AND id = ?";
            $params[] = $filterEmp;
        }
        if (!empty($filterType)) {
            $sql .= " AND employment_type = ?";
            $params[] = $filterType;
        }

        $sql .= " ORDER BY full_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll();

        // Lista de todos os colaboradores para o filtro
        $allEmpStmt = $this->pdo->prepare("SELECT id, full_name, matricula, employment_type FROM employees WHERE tenant_id = ? AND is_active = 1 ORDER BY full_name ASC");
        $allEmpStmt->execute([$activeTenantId]);
        $allEmployeesList = $allEmpStmt->fetchAll();

        $payrolls = [];
        $totalGross = 0;
        $totalNet   = 0;

        foreach ($employees as $emp) {
            $slip = $this->calculatePayslip($emp['id']);
            $payrolls[] = [
                'id' => $emp['id'],
                'name' => $emp['full_name'],
                'matricula' => $emp['matricula'] ?? 'MAT-0000',
                'cpf' => $emp['cpf'],
                'type' => $emp['employment_type'],
                'base' => $slip['base_salary'],
                'overtime' => $slip['overtime_amount'],
                'bank_hours' => (int)($emp['bank_hours_balance'] ?? 0),
                'gross' => $slip['gross_salary'],
                'inss' => $slip['inss_deduction'],
                'irrf' => $slip['irrf_deduction'],
                'benefits_discount' => $slip['benefits_discount'],
                'deductions' => $slip['total_deductions'],
                'net' => $slip['net_salary'],
                'fgts' => $slip['fgts_company'],
                'strategy' => $slip['strategy_name']
            ];

            $totalGross += $slip['gross_salary'];
            $totalNet   += $slip['net_salary'];
        }

        // SE_RECIBO_PROVISAO_13_FERIAS — Provisões mensais atuariais
        $provisions = $this->calculateProvisions($activeTenantId);

        // Holerite detalhado do colaborador selecionado ou primeiro da lista
        $selectedEmpId = !empty($filterEmp) ? $filterEmp : ($employees[0]['id'] ?? 'emp-lucas');
        $detailedSlip = null;
        try {
            $detailedSlip = $this->calculatePayslip($selectedEmpId);
        } catch (\Throwable $e) {
            $detailedSlip = null;
        }

        if (function_exists('logQuery')) {
            logQuery(
                "PayrollCalculatorTemplate::calculatePayroll() [CE_CONSULTAR_FICHA_FINANCEIRA & SE_EMITIR_HOLERITE_MENSAL]", 
                [$activeTenantId], 
                "SE_EMITIR_HOLERITE_MENSAL"
            );
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/payroll/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    /**
     * SE_EMITIR_HOLERITE_MENSAL — Emite o demonstrativo formal de holerite com motor CltPayroll.
     *
     * @param string $employeeId ID do colaborador
     * @param array<string, mixed> $extraOptions
     * @return array<string, mixed>
     */
    public function calculatePayslip(string $employeeId, array $extraOptions = []): array {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $emp = $stmt->fetch();

        if (!$emp) {
            throw new \InvalidArgumentException("Colaborador {$employeeId} não encontrado no banco.");
        }

        $baseSal = ((int)$emp['base_salary_cents']) / 100.0;
        $type = strtoupper((string)$emp['employment_type']);

        // Apuração de horas extras via overtime_entries
        $otStmt = $this->pdo->prepare(
            "SELECT SUM(amount) as total_ot, SUM(CASE WHEN type = 'BANK_HOURS' THEN hours ELSE 0 END) as total_bank_hours 
             FROM overtime_entries WHERE employee_id = ?"
        );
        $otStmt->execute([$employeeId]);
        $otData = $otStmt->fetch();
        $otAmount = (float)($otData['total_ot'] ?? 0.0);

        // Desconto de VT limitado ao teto legal de 6% do salário base (Art. 4º Lei 7.418/85)
        $vtStmt = $this->pdo->prepare(
            "SELECT b.value_cents 
             FROM employee_benefits eb 
             JOIN benefits b ON eb.benefit_id = b.id 
             WHERE eb.employee_id = ? AND b.type = 'TRANSPORT' AND eb.status = 'ACTIVE' LIMIT 1"
        );
        $vtStmt->execute([$employeeId]);
        $vtCost = ($vtStmt->fetchColumn() ?: 0) / 100.0;
        $vtDeduction = min($vtCost, $baseSal * 0.06);

        // Instanciação da Entidade Employee do Domínio
        $empVo = new Employee(
            id: $emp['id'],
            tenantId: $emp['tenant_id'],
            cpf: new Cpf($emp['cpf']),
            fullName: $emp['full_name'],
            email: $emp['email'],
            phone: $emp['phone'] ?? '(41)99999-9999',
            birthDate: new DateTimeImmutable($emp['birth_date'] ?? '1990-01-01'),
            admissionDate: new DateTimeImmutable($emp['admission_date'] ?? '2020-01-01'),
            departmentId: $emp['department_id'],
            roleId: $emp['role_id'],
            baseSalary: Money::fromCents((int)$emp['base_salary_cents']),
            employmentType: match ($type) {
                'PJ' => EmploymentType::PJ,
                'ESTAGIO', 'INTERN' => EmploymentType::INTERN,
                default => EmploymentType::CLT
            },
            isActive: (bool)$emp['is_active'],
            vacationDaysBalance: (int)$emp['vacation_days_balance'],
            bankHoursBalance: (int)$emp['bank_hours_balance']
        );

        $dependents = (int)($extraOptions['dependents'] ?? 1);

        if ($type === 'PJ') {
            $calculator = new PjPayroll();
            $result = $calculator->calculatePayroll($empVo, ['gross_salary' => $baseSal]);
            $inss = 0.0;
            $irrf = 0.0;
            $benefits = 0.0;
            $gross = $baseSal;
            $net = $baseSal;
            $fgts = 0.0;
            $strategyName = 'PjPayroll (Nota Fiscal / Isenção Previdenciária CLT)';
        } elseif ($type === 'ESTAGIO' || $type === 'INTERN') {
            $calculator = new InternPayroll();
            $result = $calculator->calculatePayroll($empVo, ['gross_salary' => $baseSal]);
            $inss = 0.0;
            $irrf = 0.0;
            $benefits = 0.0;
            $gross = $baseSal;
            $net = $baseSal;
            $fgts = 0.0;
            $strategyName = 'InternPayroll (Bolsa Auxílio / Lei 11.788/2008)';
        } else {
            // CLT com 4 faixas progressivas oficiais de INSS e tabela progressiva de IRRF
            $calculator = new CltPayroll([
                new TransportationVoucherStrategy(),
                new HealthPlanStrategy(),
                new MealVoucherStrategy()
            ]);

            $payrollData = [
                'overtime_amount' => $otAmount,
                'dependents' => $dependents,
                'benefits_discount' => round($vtDeduction, 2)
            ];

            $result = $calculator->calculatePayroll($empVo, $payrollData);
            $gross = (float)$result['breakdown']['gross_salary'];
            $inss = (float)$result['breakdown']['inss_deduction'];
            $irrf = (float)$result['breakdown']['irrf_deduction'];
            $benefits = (float)$result['breakdown']['benefits_discount'];
            $net = (float)$result['breakdown']['net_salary'];
            $fgts = round($gross * 0.08, 2);
            $strategyName = 'CltPayroll (INSS Progressivo 4 Faixas + IRRF com Dependentes + FGTS 8%)';
        }

        return [
            'employee_id' => $emp['id'],
            'matricula' => $emp['matricula'] ?? 'MAT-' . substr(preg_replace('/\D/','',$emp['cpf']), 0, 5),
            'employee_name' => $emp['full_name'],
            'cpf' => $emp['cpf'],
            'contract_type' => $type,
            'base_salary' => $baseSal,
            'overtime_amount' => $otAmount,
            'gross_salary' => $gross,
            'inss_deduction' => $inss,
            'irrf_deduction' => $irrf,
            'benefits_discount' => $benefits,
            'total_deductions' => round($inss + $irrf + $benefits, 2),
            'net_salary' => $net,
            'fgts_company' => $fgts,
            'dependents_claimed' => $dependents,
            'strategy_name' => $strategyName,
            'inss_brackets_applied' => CltPayroll::INSS_BRACKETS,
            'calculation_timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * SE_RECIBO_PROVISAO_13_FERIAS — Provisões mensais atuariais de 1/12 avos, 1/3 constitucional e FGTS.
     *
     * @param string $tenantId
     * @return array<string, mixed>
     */
    public function calculateProvisions(string $tenantId = 'tenant-tech'): array {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND employment_type = 'CLT' AND is_active = 1");
        $stmt->execute([$tenantId]);
        $employees = $stmt->fetchAll();

        $rows = [];
        $totalBase = 0.0;
        $total13 = 0.0;
        $totalVac = 0.0;
        $totalThird = 0.0;
        $totalFgts = 0.0;
        $totalMonthlyProvision = 0.0;

        foreach ($employees as $emp) {
            $base = ((int)$emp['base_salary_cents']) / 100.0;
            
            // Provisão mensal de 1/12 avos de 13º salário
            $prov13 = round($base / 12.0, 2);
            
            // Provisão mensal de 1/12 avos de férias
            $provVac = round($base / 12.0, 2);
            
            // 1/3 Constitucional sobre a provisão de férias
            $provThird = round($provVac / 3.0, 2);
            
            // FGTS de 8% incidente sobre a soma das provisões trabalhistas
            $provFgts = round(($prov13 + $provVac + $provThird) * 0.08, 2);
            
            // Custo total da provisão mensal por colaborador
            $itemTotal = round($prov13 + $provVac + $provThird + $provFgts, 2);

            $rows[] = [
                'employee_id' => $emp['id'],
                'name' => $emp['full_name'],
                'base_salary' => $base,
                'provision_13th_monthly' => $prov13,
                'provision_vacation_monthly' => $provVac,
                'provision_vacation_third' => $provThird,
                'fgts_on_provisions' => $provFgts,
                'total_monthly_provision' => $itemTotal
            ];

            $totalBase += $base;
            $total13 += $prov13;
            $totalVac += $provVac;
            $totalThird += $provThird;
            $totalFgts += $provFgts;
            $totalMonthlyProvision += $itemTotal;
        }

        return [
            'tenant_id' => $tenantId,
            'clt_headcount' => count($employees),
            'total_base_salaries' => round($totalBase, 2),
            'total_provision_13th' => round($total13, 2),
            'total_provision_vacations' => round($totalVac, 2),
            'total_provision_third' => round($totalThird, 2),
            'total_fgts_provisions' => round($totalFgts, 2),
            'grand_total_monthly_provision' => round($totalMonthlyProvision, 2),
            'formula' => 'Provisão = (Base / 12 [13º]) + (Base / 12 [Férias]) + ((Base / 12) / 3 [1/3 CF]) + (Soma * 8% [FGTS])',
            'calculated_at' => date('Y-m-d H:i:s'),
            'details' => $rows
        ];
    }
}
