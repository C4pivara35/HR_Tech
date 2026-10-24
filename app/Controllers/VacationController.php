<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use HrTech\Patterns\TemplateMethod\Payroll\CltPayroll;

/**
 * ==============================================================================
 * VacationController — Concessão e Recibo Formal de Férias CLT (Art. 129 a 145)
 * ==============================================================================
 * Funções de APF Conectadas:
 * - EE_SOLICITAR_FERIAS (Valentin - 4 PFB)
 * - SE_CALCULO_RECIBO_FERIAS (Valentin - 7 PFB / Alta Complexidade)
 *   Emissão formal do Recibo de Férias CLT:
 *   1. Remuneração dos dias de descanso proporcional ao salário base
 *   2. 1/3 Constitucional sobre os dias gozados (Art. 7º, XVII CF/88)
 *   3. Abono Pecuniário (venda facultativa de até 10 dias - Art. 143 CLT)
 *   4. 1/3 Constitucional sobre o Abono Pecuniário
 *   5. Adiantamento da 1ª parcela do 13º salário (50% do salário base, se requerido)
 *   6. Retenções legais de INSS e IRRF (incidentes exclusivamente sobre o gozo e 1/3)
 *   7. Apuração do valor líquido e data limite de quitação (2 dias antes do gozo - Art. 145 CLT)
 * ==============================================================================
 */
class VacationController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $filterEmp = trim($_GET['search_employee_id'] ?? '');
        $filterStatus = trim($_GET['search_status'] ?? '');

        $sql = "SELECT vr.*, e.full_name as employee_name, e.matricula, e.cpf, e.vacation_days_balance, e.base_salary_cents, e.admission_date,
                       d.name as department_name
                FROM vacation_requests vr
                JOIN employees e ON vr.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.tenant_id = ?";
        $params = [$activeTenantId];

        if (!empty($filterEmp)) {
            $sql .= " AND vr.employee_id = ?";
            $params[] = $filterEmp;
        }
        if (!empty($filterStatus)) {
            $sql .= " AND vr.status = ?";
            $params[] = $filterStatus;
        }

        $sql .= " ORDER BY vr.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $vacations = $stmt->fetchAll();

        // Lista de colaboradores para consulta de saldos e períodos aquisitivos (CE_CONSULTAR_SALDO_FERIAS)
        $empSql = "SELECT e.*, d.name as department_name, r.name as role_name
                   FROM employees e
                   LEFT JOIN departments d ON e.department_id = d.id
                   LEFT JOIN roles r ON e.role_id = r.id
                   WHERE e.tenant_id = ? AND e.is_active = 1";
        $empParams = [$activeTenantId];
        if (!empty($filterEmp)) {
            $empSql .= " AND e.id = ?";
            $empParams[] = $filterEmp;
        }
        $empSql .= " ORDER BY e.full_name ASC";
        $empStmt = $this->pdo->prepare($empSql);
        $empStmt->execute($empParams);
        $employees = $empStmt->fetchAll();

        // Apuração do Recibo de Férias com 1/3 e Abono (SE_CALCULO_RECIBO_FERIAS)
        $receiptId = $_GET['receipt_id'] ?? ($vacations[0]['id'] ?? null);
        $vacationReceipt = null;
        if (!empty($receiptId)) {
            try {
                $vacationReceipt = $this->calculateVacationReceipt($receiptId);
            } catch (\Throwable $e) {
                $vacationReceipt = null;
            }
        }

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM vacation_requests [CE_CONSULTAR_SALDO_FERIAS & SE_CALCULO_RECIBO_FERIAS]", [$activeTenantId], "SE_CALCULO_RECIBO_FERIAS");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/vacations/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    /**
     * Tela dedicada de Recibo Formal de Férias CLT (SE_CALCULO_RECIBO_FERIAS)
     */
    public function receipt(): void {
        $id = $_GET['id'] ?? '';
        if (empty($id) || is_numeric($id)) {
            $offset = max(0, ((int)$id) - 1);
            $stmt = $this->pdo->prepare("SELECT id FROM vacation_requests ORDER BY start_date ASC LIMIT 1 OFFSET ?");
            $stmt->execute([$offset]);
            $foundId = $stmt->fetchColumn();
            $id = $foundId ?: 'vac-01';
        }

        $receiptData = null;
        if (!empty($id)) {
            $receiptData = $this->calculateVacationReceipt($id);
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/vacations/receipt.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function request(): void {
        $empId = $_POST['employee_id'] ?? '';
        $start = $_POST['start_date'] ?? '';
        $days  = (int)($_POST['days_count'] ?? 15);
        $abono = isset($_POST['abono_pecuniario']) ? 1 : 0;
        $abonoDays = $abono ? (int)($_POST['abono_days'] ?? 10) : 0;
        $adv13 = isset($_POST['advance_thirteenth_salary']) ? 1 : 0;

        if (empty($empId) || empty($start)) {
            $_SESSION['flash_error'] = "Preencha a data de início!";
            header("Location: index.php?route=vacations");
            exit;
        }

        // Validação legal CLT via Exceções de Domínio
        try {
            if ($days < 5) {
                throw \HrTech\Exceptions\ValidationException::forField('days_count', "O fracionamento de férias exige períodos mínimos de 5 dias corridos (Art. 134 §1 da CLT).");
            }
            if (($days + $abonoDays) > 30) {
                throw \HrTech\Exceptions\ValidationException::forField('days_count', "O somatório de dias de férias ($days) e abono ($abonoDays) não pode exceder o teto anual de 30 dias.");
            }
        } catch (\HrTech\Exceptions\ValidationException $e) {
            $_SESSION['flash_error'] = "Violação Trabalhista (CLT): " . $e->getMessage();
            header("Location: index.php?route=vacations");
            exit;
        }

        $empStmt = $this->pdo->prepare("SELECT tenant_id, admission_date FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $emp = $empStmt->fetch();
        $tenantId = $emp['tenant_id'] ?? 'tenant-tech';

        // Estimativa do período aquisitivo (12 meses anteriores)
        $acqIni = date('Y-m-d', strtotime('-1 year', strtotime($start)));
        $acqFim = date('Y-m-d', strtotime('-1 day', strtotime($start)));

        $endDate = date('Y-m-d', strtotime($start . " +$days days"));
        $id = 'vac-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO vacation_requests 
             (id, tenant_id, employee_id, start_date, end_date, duration_days, abono_pecuniario, advance_thirteenth_salary, abono_days, periodo_aquisitivo_inicio, periodo_aquisitivo_fim, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'REQUESTED', ?)"
        );
        $stmt->execute([$id, $tenantId, $empId, $start, $endDate, $days, $abono, $adv13, $abonoDays, $acqIni, $acqFim, $now]);

        if (function_exists('logQuery')) {
            logQuery(
                "INSERT INTO vacation_requests (id, days=$days, abono=$abonoDays, adv13=$adv13)", 
                [$id, $days], 
                "EE_SOLICITAR_FERIAS",
                "View vacations ➔ Router (?route=vacations&action=request) ➔ VacationController::request() ➔ SQLite: vacation_requests"
            );
        }

        $_SESSION['flash_message'] = "Solicitação de $days dias de férias registrada com sucesso!";
        header("Location: index.php?route=vacations");
        exit;
    }

    public function approve(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $vacStmt = $this->pdo->prepare("SELECT * FROM vacation_requests WHERE id = ?");
            $vacStmt->execute([$id]);
            $vac = $vacStmt->fetch();

            if ($vac) {
                // Débito atômico do saldo de férias do colaborador (Art. 134 CLT)
                $totalDaysToDeduct = (int)$vac['duration_days'] + (int)$vac['abono_days'];
                $updEmp = $this->pdo->prepare("UPDATE employees SET vacation_days_balance = MAX(0, vacation_days_balance - ?) WHERE id = ?");
                $updEmp->execute([$totalDaysToDeduct, $vac['employee_id']]);
            }

            $stmt = $this->pdo->prepare("UPDATE vacation_requests SET status = 'APPROVED', approved_at = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d\TH:i:sP'), $id]);

            $_SESSION['flash_message'] = "Férias aprovadas pelo gestor e saldo do colaborador debitado!";
        }
        header("Location: index.php?route=vacations");
        exit;
    }

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("DELETE FROM vacation_requests WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = "Solicitação de férias excluída com sucesso!";
        }
        header("Location: index.php?route=vacations");
        exit;
    }

    /**
     * SE_CALCULO_RECIBO_FERIAS — Motor de cálculo do Recibo Oficial de Férias CLT com 1/3, Abono e Encargos.
     *
     * @param string $vacationId
     * @return array<string, mixed>
     */
    public function calculateVacationReceipt(string $vacationId): array {
        $stmt = $this->pdo->prepare(
            "SELECT vr.*, e.full_name as employee_name, e.cpf, e.base_salary_cents, e.admission_date, d.name as department_name, r.name as role_name
             FROM vacation_requests vr
             JOIN employees e ON vr.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN roles r ON e.role_id = r.id
             WHERE vr.id = ?"
        );
        $stmt->execute([$vacationId]);
        $vac = $stmt->fetch();

        if (!$vac) {
            throw new \InvalidArgumentException("Solicitação de férias {$vacationId} não encontrada.");
        }

        $baseSalary = ((int)$vac['base_salary_cents']) / 100.0;
        $dailyRate = $baseSalary / 30.0;
        $daysGozo = (int)$vac['duration_days'];
        $hasAbono = ((int)$vac['abono_pecuniario']) === 1;
        $abonoDays = $hasAbono ? (int)$vac['abono_days'] : 0;
        $hasAdvance13th = ((int)$vac['advance_thirteenth_salary']) === 1;

        // 1. Remuneração dos dias de descanso
        $remunerationGozo = round($dailyRate * $daysGozo, 2);

        // 2. 1/3 Constitucional de Férias (Art. 7º, XVII da CF/88)
        $constitutionalThird = round($remunerationGozo / 3.0, 2);

        // 3. Abono Pecuniário (Art. 143 da CLT) - Venda de dias
        $abonoAmount = $hasAbono ? round($dailyRate * $abonoDays, 2) : 0.0;

        // 4. 1/3 Constitucional sobre o Abono Pecuniário
        $abonoThirdAmount = $hasAbono ? round($abonoAmount / 3.0, 2) : 0.0;

        // 5. Adiantamento da 1ª parcela do 13º Salário (50% do salário base contratual)
        $advance13thAmount = $hasAdvance13th ? round($baseSalary * 0.50, 2) : 0.0;

        // 6. Base de incidência previdenciária e fiscal
        // Súmula 386 do STJ: O abono pecuniário de férias e seu respectivo terço têm natureza indenizatória, sendo ISENTOS de INSS e IRRF.
        $taxableBase = $remunerationGozo + $constitutionalThird;

        // Cálculo do INSS Progressivo sobre as férias
        $inss = 0.0;
        $prevLimit = 0.0;
        foreach (CltPayroll::INSS_BRACKETS as $bracket) {
            if ($taxableBase > $prevLimit) {
                $taxableInBracket = min($taxableBase, $bracket['limit']) - $prevLimit;
                $inss += $taxableInBracket * $bracket['rate'];
                $prevLimit = $bracket['limit'];
            } else {
                break;
            }
        }
        $inss = round($inss, 2);

        // Cálculo do IRRF Progressivo sobre as férias
        $baseIrrf = max(0.0, $taxableBase - $inss);
        $irrf = 0.0;
        foreach (CltPayroll::IRRF_BRACKETS as $b) {
            if ($baseIrrf <= $b['limit']) {
                $irrf = max(0.0, ($baseIrrf * $b['rate']) - $b['deduction']);
                break;
            }
        }
        $irrf = round($irrf, 2);

        // Total bruto dos proventos
        $totalEarnings = round($remunerationGozo + $constitutionalThird + $abonoAmount + $abonoThirdAmount + $advance13thAmount, 2);
        
        // Total dos descontos
        $totalDeductions = round($inss + $irrf, 2);

        // Salário líquido a receber
        $netPayment = round($totalEarnings - $totalDeductions, 2);

        // Prazo legal de pagamento (Art. 145 CLT: até 2 dias antes do início do gozo)
        $paymentDeadline = date('Y-m-d', strtotime('-2 days', strtotime($vac['start_date'])));

        return [
            'vacation_id' => $vac['id'],
            'employee_id' => $vac['employee_id'],
            'employee_name' => $vac['employee_name'],
            'cpf' => $vac['cpf'],
            'department_name' => $vac['department_name'] ?? 'N/A',
            'role_name' => $vac['role_name'] ?? 'N/A',
            'periodo_aquisitivo' => ($vac['periodo_aquisitivo_inicio'] ?? '2025-01-01') . ' a ' . ($vac['periodo_aquisitivo_fim'] ?? '2025-12-31'),
            'periodo_gozo' => $vac['start_date'] . ' a ' . $vac['end_date'],
            'days_gozo' => $daysGozo,
            'abono_days' => $abonoDays,
            'base_salary' => $baseSalary,
            'earnings' => [
                'remuneracao_ferias' => $remunerationGozo,
                'terco_constitucional_cf88' => $constitutionalThird,
                'abono_pecuniario_art143' => $abonoAmount,
                'terco_abono_pecuniario' => $abonoThirdAmount,
                'adiantamento_decimo_terceiro' => $advance13thAmount,
                'total_bruto' => $totalEarnings
            ],
            'deductions' => [
                'inss_ferias' => $inss,
                'irrf_ferias' => $irrf,
                'total_descontos' => $totalDeductions
            ],
            'net_vacation_amount' => $netPayment,
            'payment_deadline_legal' => $paymentDeadline,
            'legal_basis' => 'Artigos 129 a 145 da CLT e Art. 7º, XVII da Constituição Federal de 1988',
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }
}
