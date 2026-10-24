<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Services\External\AieNtpService;
use App\Services\External\AieRelogioRepService;

/**
 * ==============================================================================
 * TimeLogController — Ponto Eletrônico Portaria 671 MTE & Espelho Mensal
 * ==============================================================================
 * Funções de APF Conectadas:
 * - EE_BATER_PONTO_671 (Felipe - 4 PFB)
 * - EE_AJUSTE_ABONO_PONTO (Felipe - 4 PFB)
 * - SE_EXTRATO_ESPELHO_PONTO (Felipe - 7 PFB / Alta Complexidade)
 *   Apuração mensal de jornada: pareamento diário, hora noturna reduzida (52m30s
 *   per Art. 73 CLT com fator 60/52.5 = 1.142857), adicional noturno de 20%,
 *   tolerância diária de 10 minutos (Art. 58 §1º CLT) e saldo diário.
 * - SE_RELATORIO_HORAS_EXTRAS (Felipe - 5 PFB / Média Complexidade)
 *   Apuração consolidada de horas extras a 50%, 100% e saldo de banco de horas.
 * - CE_CONSULTAR_BATIDAS_DIA (Felipe - 3 PFB / Baixa Complexidade)
 * - CE_CONSULTAR_BANCO_HORAS (Felipe - 3 PFB / Baixa Complexidade)
 * ==============================================================================
 */
class TimeLogController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        // Filtros CE_CONSULTAR_BATIDAS_DIA
        $filterEmp = trim($_GET['search_employee_id'] ?? '');
        $filterDate = trim($_GET['search_date'] ?? '');

        // Time logs query com filtros
        $sql = "SELECT tl.*, e.full_name as employee_name, e.cpf, e.matricula
                FROM time_logs tl
                JOIN employees e ON tl.employee_id = e.id
                WHERE e.tenant_id = ?";
        $params = [$activeTenantId];

        if (!empty($filterEmp)) {
            $sql .= " AND tl.employee_id = ?";
            $params[] = $filterEmp;
        }
        if (!empty($filterDate)) {
            $sql .= " AND tl.timestamp LIKE ?";
            $params[] = "$filterDate%";
        }

        $sql .= " ORDER BY tl.nsr DESC LIMIT 40";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $timeLogs = $stmt->fetchAll();

        // Adjustments query
        $adjStmt = $this->pdo->prepare(
            "SELECT tar.*, e.full_name as employee_name
             FROM time_adjustment_requests tar
             JOIN employees e ON tar.employee_id = e.id
             WHERE e.tenant_id = ?
             ORDER BY tar.created_at DESC"
        );
        $adjStmt->execute([$activeTenantId]);
        $adjustments = $adjStmt->fetchAll();

        // Colaboradores ativos para filtros e seletores
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        // Colaborador alvo para o espelho de ponto calculado (SE_EXTRATO_ESPELHO_PONTO)
        $targetEmpId = !empty($filterEmp) ? $filterEmp : ($employees[0]['id'] ?? 'emp-ana');
        $espelhoMonth = !empty($filterDate) ? substr($filterDate, 0, 7) : '2026-09';
        
        $espelhoData = null;
        try {
            $espelhoData = $this->calculateEspelho($targetEmpId, $espelhoMonth);
        } catch (\Throwable $e) {
            // Em caso de falta de batidas no mês, gera estrutura segura
            $espelhoData = [
                'employee_id' => $targetEmpId,
                'period' => $espelhoMonth,
                'total_regular_hours' => 0.0,
                'total_nocturnal_hours_reduced' => 0.0,
                'total_nocturnal_surcharge_reais' => 0.0,
                'total_balance_minutes' => 0,
                'daily_records' => []
            ];
        }

        // Lotes de REP importados (AIE_RELOGIO_REP_671)
        $repStmt = $this->pdo->prepare("SELECT * FROM rep_imports WHERE tenant_id = ? ORDER BY imported_at DESC LIMIT 5");
        $repStmt->execute([$activeTenantId]);
        $repImports = $repStmt->fetchAll();

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM time_logs [CE_CONSULTAR_BATIDAS_DIA & SE_EXTRATO_ESPELHO_PONTO]", [$activeTenantId], "SE_EXTRATO_ESPELHO_PONTO");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/timelogs/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    /**
     * Tela 02 — Dashboard do Colaborador & Extrato Analítico do Banco de Horas (ALI_BANCO_HORAS, SE_APURACAO_BANCO_HORAS, CE_EXTRATO_BANCO_HORAS)
     */
    public function bankHours(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $filterEmp = trim($_GET['search_employee_id'] ?? '');
        $filterType = trim($_GET['search_type'] ?? '');

        // Colaboradores para seletor
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        $selectedEmpId = !empty($filterEmp) ? $filterEmp : ($employees[0]['id'] ?? 'emp-ana');

        // Extrato analítico de horas extras / banco de horas
        $sql = "SELECT ot.*, e.full_name as employee_name, e.cpf, e.bank_hours_balance
                FROM overtime_entries ot
                JOIN employees e ON ot.employee_id = e.id
                WHERE ot.tenant_id = ?";
        $params = [$activeTenantId];

        if (!empty($selectedEmpId)) {
            $sql .= " AND ot.employee_id = ?";
            $params[] = $selectedEmpId;
        }
        if (!empty($filterType)) {
            $sql .= " AND ot.type = ?";
            $params[] = $filterType;
        }

        $sql .= " ORDER BY ot.date DESC, ot.created_at DESC";
        $otStmt = $this->pdo->prepare($sql);
        $otStmt->execute($params);
        $overtimeEntries = $otStmt->fetchAll();

        // Dados do colaborador selecionado
        $selEmpStmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $selEmpStmt->execute([$selectedEmpId]);
        $currentEmployee = $selEmpStmt->fetch() ?: ($employees[0] ?? []);

        // Consolidação para relatório de horas extras (SE_RELATORIO_HORAS_EXTRAS)
        $overtimeReport = null;
        try {
            $overtimeReport = $this->calculateOvertimeReport($selectedEmpId, '2026-09');
        } catch (\Throwable $e) {
            $overtimeReport = [
                'employee_id' => $selectedEmpId,
                'standard_50_hours' => 0.0,
                'standard_50_amount' => 0.0,
                'sunday_100_hours' => 0.0,
                'sunday_100_amount' => 0.0,
                'bank_hours_credited' => 0.0,
                'grand_total_amount' => 0.0
            ];
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/timelogs/bank_hours.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    /**
     * Tela 09 — Formulário de Solicitação de Ajuste de Ponto (EE_AJUSTE_ABONO_PONTO)
     */
    public function requestAdjustmentView(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/timelogs/request_adjustment.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    /**
     * Importação do AFD pelo Relógio REP Portaria 671 (AIE_RELOGIO_REP_671)
     */
    public function importRep(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $repService = new AieRelogioRepService($this->pdo);
        $res = $repService->importAfdFile('REP-001', $activeTenantId);

        $_SESSION['flash_message'] = "Arquivo AFD importado com sucesso do Relógio {$res['rep_identifier']}! Lote {$res['batch_id']}: {$res['records_imported']} marcações registradas.";
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function punch(): void {
        $empId = $_POST['employee_id'] ?? '';
        $type  = $_POST['type'] ?? 'ENTRY';

        if (empty($empId)) {
            $_SESSION['flash_error'] = "Selecione um colaborador!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        // Fetch employee details
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $employee = $empStmt->fetch();

        if (!$employee) {
            $_SESSION['flash_error'] = "Colaborador não encontrado!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        // Sincronização oficial de hora legal NTP.br (AIE_NTP_HORA_LEGAL)
        $ntpService = new AieNtpService($this->pdo);
        $ntpInfo = $ntpService->getLegalTime($employee['tenant_id']);
        $timestamp = $ntpInfo['legal_time_brasilia'];

        // Get highest NSR and previous signature_hash
        $nsrStmt = $this->pdo->query("SELECT MAX(nsr), signature_hash FROM time_logs ORDER BY nsr DESC LIMIT 1");
        $lastRow = $nsrStmt->fetch();
        $nextNsr  = ((int)($lastRow[0] ?? 0)) + 1;
        $prevHash = $lastRow['signature_hash'] ?? str_repeat('0', 64);

        $id = 'log-' . uniqid();

        // Portaria 671 Cryptographic Hash Calculation: SHA-256 (prevHash + nsr + cpf + timestamp + type)
        $rawPayload = $prevHash . '|' . $nextNsr . '|' . $employee['cpf'] . '|' . $timestamp . '|' . $type;
        $hash = hash('sha256', $rawPayload);

        $stmt = $this->pdo->prepare(
            "INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, accuracy, nsr, previous_hash, signature_hash)
             VALUES (?, ?, ?, ?, ?, -25.4284, -49.2733, 10.0, ?, ?, ?)"
        );
        $stmt->execute([$id, $employee['tenant_id'], $empId, $timestamp, $type, $nextNsr, $prevHash, $hash]);

        if (function_exists('logQuery')) {
            logQuery(
                "INSERT INTO time_logs (nsr=$nextNsr, hash=" . substr($hash,0,12) . "...) SHA-256 Chain Validation NTP.br", 
                [$id, $nextNsr], 
                "EE_BATER_PONTO_671",
                "View timelogs ➔ Router (?route=timelogs&action=punch) ➔ TimeLogController::punch() ➔ AieNtpService ➔ SQLite: time_logs"
            );
        }

        $_SESSION['flash_message'] = "Batida de Ponto registrada com sucesso! NSR: $nextNsr | Carimbo NTP.br: {$ntpInfo['server_used']}";
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function requestAdjustment(): void {
        $empId  = $_POST['employee_id'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        $date   = $_POST['requested_timestamp'] ?? date('Y-m-d\TH:i');

        if (empty($empId) || empty($reason)) {
            $_SESSION['flash_error'] = "Preencha a justificativa do ajuste!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        $empStmt = $this->pdo->prepare("SELECT tenant_id FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $tenantId = $empStmt->fetchColumn() ?: 'tenant-tech';

        $id = 'adj-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO time_adjustment_requests (id, tenant_id, employee_id, requested_date, original_time, requested_time, reason, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?)"
        );
        $stmt->execute([$id, $tenantId, $empId, date('Y-m-d'), $date, $reason, $now]);

        if (function_exists('logQuery')) {
            logQuery(
                "INSERT INTO time_adjustment_requests (id, status='PENDING')", 
                [$id], 
                "EE_AJUSTE_ABONO_PONTO",
                "View timelogs ➔ Router (?route=timelogs&action=request_adjustment) ➔ TimeLogController::requestAdjustment() ➔ SQLite: time_adjustment_requests"
            );
        }

        $_SESSION['flash_message'] = "Solicitação de ajuste enviada para aprovação do gestor!";
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function approveAdjustment(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE time_adjustment_requests SET status = 'APPROVED', approved_at = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d\TH:i:sP'), $id]);
            if (function_exists('logQuery')) {
                logQuery(
                    "UPDATE time_adjustment_requests SET status = 'APPROVED' WHERE id = '$id'", 
                    [$id], 
                    "EE_AJUSTE_ABONO_PONTO: Approve",
                    "View timelogs ➔ Router (?action=approve_adjustment) ➔ TimeLogController::approveAdjustment() ➔ SQLite: time_adjustment_requests"
                );
            }
            $_SESSION['flash_message'] = "Retificação de ponto APROVADA pelo gestor!";
        }
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function rejectAdjustment(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE time_adjustment_requests SET status = 'REJECTED' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = "Solicitação de retificação RECUSADA pelo gestor!";
        }
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function recordOvertime(): void {
        $empId = $_POST['employee_id'] ?? '';
        $hours = (float)($_POST['hours'] ?? 0);
        $type  = $_POST['type'] ?? 'STANDARD_50';
        $date  = $_POST['date'] ?? date('Y-m-d');

        if (empty($empId) || $hours <= 0) {
            $_SESSION['flash_error'] = "Informe o colaborador e uma quantidade válida de horas!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $emp = $empStmt->fetch();

        if (!$emp) {
            $_SESSION['flash_error'] = "Colaborador não encontrado!";
            header("Location: index.php?route=timelogs");
            exit;
        }

        $hourlyRate = round(($emp['base_salary_cents'] / 100) / 220.0, 2);

        if ($type === 'STANDARD_50') {
            $strat = new \HrTech\Patterns\Strategy\Overtime\Standard50Strategy();
            $amount = $strat->calculateOvertime($hourlyRate, $hours);
            $strategyUsed = "Standard50Strategy (Hora Extra +50%)";
            $desc = "Hora extra em dia útil com adicional legal de 50% (Art. 59 §1 CLT)";
        } elseif ($type === 'SUNDAY_100') {
            $strat = new \HrTech\Patterns\Strategy\Overtime\Sunday100Strategy();
            $amount = $strat->calculateOvertime($hourlyRate, $hours);
            $strategyUsed = "Sunday100Strategy (Hora Extra +100%)";
            $desc = "Hora extra em domingo ou feriado com adicional de 100% (Art. 70 CLT)";
        } else {
            $strat = new \HrTech\Patterns\Strategy\Overtime\BankHoursStrategy(1.0);
            $amount = $strat->calculateOvertime($hourlyRate, $hours);
            $strategyUsed = "BankHoursStrategy (Banco de Horas CLT)";
            $desc = "Compensação de jornada creditada no banco de horas (Art. 59 §2 CLT)";

            $minutesToAdd = (int)round($hours * 60);
            $newBalance = ((int)($emp['bank_hours_balance'] ?? 0)) + $minutesToAdd;
            $upd = $this->pdo->prepare("UPDATE employees SET bank_hours_balance = ? WHERE id = ?");
            $upd->execute([$newBalance, $empId]);
        }

        $id = 'ot-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');
        $exp = date('Y-m-d', strtotime('+6 months'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO overtime_entries (id, tenant_id, employee_id, date, hours, type, hourly_rate, amount, strategy_used, description, expiration_date, status_fechamento, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)"
        );
        $stmt->execute([$id, $emp['tenant_id'], $empId, $date, $hours, $type, $hourlyRate, $amount, $strategyUsed, $desc, $exp, $now]);

        $_SESSION['flash_message'] = "Lançamento de horas extras processado via $strategyUsed!";
        header("Location: index.php?route=timelogs");
        exit;
    }

    /**
     * SE_EXTRATO_ESPELHO_PONTO — Apuração mensal detalhada da jornada com hora noturna CLT e tolerância.
     *
     * Regras Legais:
     * 1. Hora Noturna Ficta (Art. 73 CLT): Entre 22h e 05h, 1 hora = 52m30s (fator 60 / 52.5 = 1.142857)
     * 2. Adicional Noturno: +20% sobre o salário-hora para as horas noturnas reduzidas
     * 3. Tolerância Art. 58 §1º CLT: Variações de até 5 minutos por batida (máx. 10 min diários) são desconsideradas
     * 4. Saldo diário contra jornada contratual padrão de 8 horas (480 minutos)
     *
     * @param string $employeeId
     * @param string $period YYYY-MM
     * @return array<string, mixed>
     */
    public function calculateEspelho(string $employeeId, string $period = '2026-09'): array {
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $empStmt->execute([$employeeId]);
        $emp = $empStmt->fetch();

        if (!$emp) {
            throw new \InvalidArgumentException("Colaborador {$employeeId} não encontrado.");
        }

        $baseSalary = ((int)$emp['base_salary_cents']) / 100.0;
        $hourlyRate = round($baseSalary / 220.0, 2);

        // Busca todas as batidas do mês ordenadas cronologicamente
        $stmt = $this->pdo->prepare(
            "SELECT * FROM time_logs 
             WHERE employee_id = ? AND timestamp LIKE ? 
             ORDER BY timestamp ASC"
        );
        $stmt->execute([$employeeId, "{$period}%"]);
        $logs = $stmt->fetchAll();

        // Agrupamento por dia
        $days = [];
        foreach ($logs as $log) {
            $dt = substr((string)$log['timestamp'], 0, 10);
            $days[$dt][] = $log;
        }

        $dailyDetails = [];
        $totalWorkedMinutes = 0;
        $totalNocturnalMinutes = 0;
        $totalNocturnalReducedMinutes = 0;
        $totalBalanceMinutes = 0;

        foreach ($days as $date => $punches) {
            $e1 = null; $s1 = null; $e2 = null; $s2 = null;
            $workedMinutesDay = 0;
            $nocturnalMinutesDay = 0;

            // Pareamento sequencial (Entrada -> Saída)
            for ($i = 0; $i < count($punches); $i += 2) {
                if (isset($punches[$i], $punches[$i + 1])) {
                    $tIn = strtotime($punches[$i]['timestamp']);
                    $tOut = strtotime($punches[$i + 1]['timestamp']);
                    if ($tOut > $tIn) {
                        $spanMinutes = (int)round(($tOut - $tIn) / 60);
                        $workedMinutesDay += $spanMinutes;

                        // Verifica incidência noturna (22:00 às 05:00)
                        $hourIn = (int)date('H', $tIn);
                        $hourOut = (int)date('H', $tOut);
                        if ($hourIn >= 22 || $hourIn < 5 || $hourOut <= 5) {
                            $nocturnalMinutesDay += $spanMinutes;
                        }
                    }
                }
            }

            // Mapeamento dos marcadores para exibição visual
            if (isset($punches[0])) $e1 = substr((string)$punches[0]['timestamp'], 11, 5);
            if (isset($punches[1])) $s1 = substr((string)$punches[1]['timestamp'], 11, 5);
            if (isset($punches[2])) $e2 = substr((string)$punches[2]['timestamp'], 11, 5);
            if (isset($punches[3])) $s2 = substr((string)$punches[3]['timestamp'], 11, 5);

            // Redução ficta noturna: 1h noturna = 52m30s (fator = 60 / 52.5 = 1.142857)
            $nocturnalFactor = 60.0 / 52.5;
            $reducedNocturnalMinutes = round($nocturnalMinutesDay * $nocturnalFactor);
            $adjustedWorkedMinutes = ($workedMinutesDay - $nocturnalMinutesDay) + (int)$reducedNocturnalMinutes;

            // Tolerância Art. 58 §1º CLT: variação diária de até 10 minutos é neutralizada para a jornada contratual (480 min)
            $diffFromStandard = $adjustedWorkedMinutes - 480;
            if (abs($diffFromStandard) <= 10) {
                $dailyBalance = 0; // Neutralizado pela tolerância legal
            } else {
                $dailyBalance = $diffFromStandard;
            }

            $dailyDetails[] = [
                'date' => $date,
                'day_of_week' => date('D', strtotime($date)),
                'e1' => $e1 ?? '--:--',
                's1' => $s1 ?? '--:--',
                'e2' => $e2 ?? '--:--',
                's2' => $s2 ?? '--:--',
                'raw_worked_minutes' => $workedMinutesDay,
                'nocturnal_minutes' => $nocturnalMinutesDay,
                'nocturnal_reduced_minutes' => (int)$reducedNocturnalMinutes,
                'adjusted_worked_minutes' => $adjustedWorkedMinutes,
                'daily_balance_minutes' => $dailyBalance,
                'daily_balance_hours' => round($dailyBalance / 60.0, 2)
            ];

            $totalWorkedMinutes += $adjustedWorkedMinutes;
            $totalNocturnalMinutes += $nocturnalMinutesDay;
            $totalNocturnalReducedMinutes += (int)$reducedNocturnalMinutes;
            $totalBalanceMinutes += $dailyBalance;
        }

        // Adicional noturno: 20% do salário-hora sobre as horas noturnas apuradas
        $nocturnalHoursDec = $totalNocturnalReducedMinutes / 60.0;
        $nocturnalAdditionalValue = round($nocturnalHoursDec * $hourlyRate * 0.20, 2);

        return [
            'employee_id' => $emp['id'],
            'employee_name' => $emp['full_name'],
            'cpf' => $emp['cpf'],
            'period' => $period,
            'hourly_rate' => $hourlyRate,
            'days_recorded' => count($dailyDetails),
            'total_worked_hours' => round($totalWorkedMinutes / 60.0, 2),
            'total_nocturnal_hours_reduced' => round($nocturnalHoursDec, 2),
            'nocturnal_surcharge_amount' => $nocturnalAdditionalValue,
            'consolidated_balance_minutes' => $totalBalanceMinutes,
            'consolidated_balance_hours' => round($totalBalanceMinutes / 60.0, 2),
            'clt_article_73_applied' => 'Redução de hora noturna (52m30s - Fator 1.142857) e adicional de 20%',
            'clt_article_58_applied' => 'Tolerância diária legal de 10 minutos',
            'days' => $dailyDetails,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * SE_RELATORIO_HORAS_EXTRAS — Apuração consolidada de adicionais a 50%, 100% e Banco de Horas.
     */
    public function calculateOvertimeReport(string $tenantId = 'tenant-tech'): array {
        $stmt = $this->pdo->prepare(
            "SELECT ot.*, e.full_name as employee_name, e.cpf, e.base_salary_cents 
             FROM overtime_entries ot 
             JOIN employees e ON ot.employee_id = e.id 
             WHERE ot.tenant_id = ? 
             ORDER BY ot.date DESC"
        );
        $stmt->execute([$tenantId]);
        $rows = $stmt->fetchAll();

        $total50 = 0.0;
        $total100 = 0.0;
        $totalBankHours = 0.0;

        foreach ($rows as $r) {
            if ($r['type'] === 'OVERTIME_50') {
                $total50 += (float)$r['amount'];
            } elseif ($r['type'] === 'SUNDAY_100') {
                $total100 += (float)$r['amount'];
            } else {
                $totalBankHours += (float)$r['hours'];
            }
        }

        return [
            'tenant_id' => $tenantId,
            'total_records' => count($rows),
            'total_overtime_50_amount' => round($total50, 2),
            'total_overtime_100_amount' => round($total100, 2),
            'total_bank_hours_credited' => round($totalBankHours, 2),
            'grand_total_overtime_cash' => round($total50 + $total100, 2),
            'entries' => $rows,
            'reported_at' => date('Y-m-d H:i:s')
        ];
    }
}
