<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

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

        // Calculate simulation for each employee using Template Method & Strategy
        $payrolls = [];
        $totalGross = 0;
        $totalNet   = 0;

        foreach ($employees as $emp) {
            $baseSal = $emp['base_salary_cents'] / 100;
            $type    = $emp['employment_type'];

            // Template Method calculation simulation
            if ($type === 'CLT') {
                $inss = $baseSal * 0.11; // 11% INSS
                $irrf = ($baseSal > 3000) ? ($baseSal * 0.15) : 0; // IRRF
                $fgts = $baseSal * 0.08; // 8% FGTS (Empresa)
                $earnings = $baseSal;
                $deductions = $inss + $irrf;
                $net = $earnings - $deductions;
                $strategyUsed = "CLT (INSS, IRRF Progressivo & FGTS 8%)";
            } elseif ($type === 'PJ') {
                $earnings = $baseSal;
                $deductions = 0; // Isento previdenciário direto na folha CLT
                $net = $earnings;
                $strategyUsed = "PJ (Retenção NF / Isenção Previdenciária CLT)";
            } else {
                $earnings = $baseSal; // Bolsa auxílio
                $deductions = 0;
                $net = $earnings;
                $strategyUsed = "Estágio (Lei 11.788/2008 / Bolsa Auxílio Integ)";
            }

            $payrolls[] = [
                'name' => $emp['full_name'],
                'cpf' => $emp['cpf'],
                'type' => $type,
                'base' => $baseSal,
                'deductions' => $deductions,
                'net' => $net,
                'strategy' => $strategyUsed
            ];

            $totalGross += $baseSal;
            $totalNet   += $net;
        }

        logQuery("PayrollCalculatorTemplate::calculate() [Template Method + Strategies: CLT/PJ/Intern]", [$activeTenantId], "Template Method Payroll & Strategy");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/payroll/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }
}
