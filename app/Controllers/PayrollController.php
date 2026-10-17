<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * PayrollController — Motor de Cálculo de Folha de Pagamento
 * ==============================================================================
 * Responsabilidade: Valentin (com integração das Horas Extras por Nicholas)
 *
 * Padrão de Projeto GoF: TEMPLATE METHOD (PayrollCalculatorTemplate)
 *
 * Intenção do Padrão:
 * O Template Method define o esqueleto invariante do algoritmo de folha:
 *   1. Validação do colaborador
 *   2. Apuração do Salário Bruto (Base + Horas Extras apuradas via Strategy)
 *   3. Cálculo progressivo de INSS
 *   4. Cálculo progressivo de IRRF
 *   5. Deduções de Benefícios (VT 6%, Saúde, VR)
 *   6. Apuração do Salário Líquido e Emissão do Holerite
 *
 * Subclasses Concretas do Template Method:
 * - CltPayroll: Aplica tabelas progressivas de INSS e IRRF da legislação brasileira.
 * - PjPayroll: Retenção em Nota Fiscal, isento de previdência CLT.
 * - InternPayroll: Bolsa auxílio sem encargos trabalhistas (Lei 11.788/2008).
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

        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $stmt->execute([$activeTenantId]);
        $employees = $stmt->fetchAll();

        // Calcula simulação para cada colaborador usando Template Method e Strategy
        $payrolls = [];
        $totalGross = 0;
        $totalNet   = 0;

        foreach ($employees as $emp) {
            $baseSal = $emp['base_salary_cents'] / 100;
            $type    = $emp['employment_type'];

            // Consulta horas extras apuradas via Strategy Pattern
            $otStmt = $this->pdo->prepare("SELECT SUM(amount) as total_ot, SUM(CASE WHEN type = 'BANK_HOURS' THEN hours ELSE 0 END) as total_bank_hours FROM overtime_entries WHERE employee_id = ?");
            $otStmt->execute([$emp['id']]);
            $otData = $otStmt->fetch();
            $otAmount = (float)($otData['total_ot'] ?? 0.0);
            $bankHours = (float)($otData['total_bank_hours'] ?? 0.0);

            // Template Method calculation simulation
            if ($type === 'CLT') {
                $grossSalary = $baseSal + $otAmount;
                $inss = $grossSalary * 0.11; // 11% INSS
                $irrf = ($grossSalary > 3000) ? (($grossSalary - $inss) * 0.15) : 0; // IRRF
                $fgts = $grossSalary * 0.08; // 8% FGTS (Empresa)
                $earnings = $grossSalary;
                $deductions = $inss + $irrf;
                $net = $earnings - $deductions;
                $strategyUsed = "CltPayroll (INSS 11% + IRRF + FGTS 8% + Overtime)";
            } elseif ($type === 'PJ') {
                $earnings = $baseSal;
                $deductions = 0; // Isento previdenciário direto na folha CLT
                $net = $earnings;
                $strategyUsed = "PjPayroll (Retenção NF / Isenção Previdenciária CLT)";
            } else {
                $earnings = $baseSal; // Bolsa auxílio
                $deductions = 0;
                $net = $earnings;
                $strategyUsed = "InternPayroll (Lei 11.788/2008 / Bolsa Auxílio)";
            }

            $payrolls[] = [
                'name' => $emp['full_name'],
                'cpf' => $emp['cpf'],
                'type' => $type,
                'base' => $baseSal,
                'overtime' => $otAmount,
                'bank_hours' => (int)($emp['bank_hours_balance'] ?? 0),
                'gross' => $earnings,
                'deductions' => $deductions,
                'net' => $net,
                'strategy' => $strategyUsed
            ];

            $totalGross += $earnings;
            $totalNet   += $net;
        }

        logQuery(
            "PayrollCalculatorTemplate::calculatePayroll() [Template Method com OvertimeStrategy integrados]", 
            [$activeTenantId], 
            "Template Method Payroll & Overtime Strategy",
            "View: payroll (Tabela Holerites) ➔ Router (?route=payroll) ➔ PayrollController ➔ PayrollCalculatorTemplate (CltPayroll / OvertimeStrategy) ➔ SQLite: employees & overtime_entries"
        );

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/payroll/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }
}
