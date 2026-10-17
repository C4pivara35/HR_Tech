<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * TimeLogController — Controlador de Ponto Eletrônico & Horas Extras
 * ==============================================================================
 * Responsabilidades dos Integrantes:
 * - Felipe: CRUD 5 (Ponto Eletrônico) e CRUD 6 (Retificações de Ponto)
 * - Nicholas & Valentin: Padrão GoF Strategy de Horas Extras e Banco de Horas
 *
 * Fundamentos Legais e Padrões de Projeto:
 * 1. Portaria 671/2021 MTE (Art. 73 a 82):
 *    - NSR (Número Sequencial de Registro) estritamente crescente e contínuo.
 *    - Assinatura Digital encadeada com SHA-256 (blockchain-like):
 *      Payload: previous_hash | nsr | cpf | timestamp | type -> SHA-256
 *    - Imutabilidade absoluta: batidas não podem ser editadas ou excluídas.
 *
 * 2. GoF Strategy Pattern (Horas Extras & Banco de Horas):
 *    - Standard50Strategy: Adicional de 50% em dias úteis (Art. 59 §1 CLT)
 *    - Sunday100Strategy: Adicional de 100% em domingos/feriados (Art. 70 CLT)
 *    - BankHoursStrategy: Saldo em Banco de Horas sem custo financeiro (Art. 59 §2 CLT)
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

        // Time logs query
        $stmt = $this->pdo->prepare(
            "SELECT tl.*, e.full_name as employee_name, e.cpf
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             WHERE e.tenant_id = ?
             ORDER BY tl.nsr DESC
             LIMIT 30"
        );
        $stmt->execute([$activeTenantId]);
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

        // Apuração de Horas Extras e Banco de Horas (Padrão Strategy)
        $otStmt = $this->pdo->prepare(
            "SELECT ot.*, e.full_name as employee_name, e.cpf, e.bank_hours_balance
             FROM overtime_entries ot
             JOIN employees e ON ot.employee_id = e.id
             WHERE ot.tenant_id = ?
             ORDER BY ot.date DESC, ot.created_at DESC"
        );
        $otStmt->execute([$activeTenantId]);
        $overtimeEntries = $otStmt->fetchAll();

        // Colaboradores para o modal de batida de ponto e horas extras
        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        logQuery("SELECT FROM time_logs & overtime_entries (Portaria 671 & Strategy)", [$activeTenantId], "Strategy Overtime & AuditLogger");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/timelogs/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
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

        // Get highest NSR and previous signature_hash
        $nsrStmt = $this->pdo->query("SELECT MAX(nsr), signature_hash FROM time_logs ORDER BY nsr DESC LIMIT 1");
        $lastRow = $nsrStmt->fetch();
        $nextNsr  = ((int)($lastRow[0] ?? 0)) + 1;
        $prevHash = $lastRow['signature_hash'] ?? str_repeat('0', 64);

        $timestamp = date('Y-m-d\TH:i:sP');
        $id = 'log-' . uniqid();

        // Portaria 671 Cryptographic Hash Calculation: SHA-256 (prevHash + nsr + cpf + timestamp + type)
        $rawPayload = $prevHash . '|' . $nextNsr . '|' . $employee['cpf'] . '|' . $timestamp . '|' . $type;
        $hash = hash('sha256', $rawPayload);

        $stmt = $this->pdo->prepare(
            "INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, accuracy, nsr, previous_hash, signature_hash)
             VALUES (?, ?, ?, ?, ?, -25.4284, -49.2733, 10.0, ?, ?, ?)"
        );
        $stmt->execute([$id, $employee['tenant_id'], $empId, $timestamp, $type, $nextNsr, $prevHash, $hash]);

        logQuery(
            "INSERT INTO time_logs (nsr=$nextNsr, hash=" . substr($hash,0,12) . "...) SHA-256 Chain Validation", 
            [$id, $nextNsr], 
            "CRUD 5: TimeLog & AuditLogger SHA-256",
            "View timelogs (Botão Registrar Batida) ➔ Router (?route=timelogs&action=punch) ➔ TimeLogController::punch() ➔ Hash SHA-256 Encadeado ➔ SQLite: time_logs"
        );

        $_SESSION['flash_message'] = "Batida de Ponto registrada com sucesso! NSR: $nextNsr | Hash SHA-256: " . substr($hash, 0, 16) . "...";
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
            "INSERT INTO time_adjustment_requests (id, tenant_id, employee_id, requested_date, requested_time, reason, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?)"
        );
        $stmt->execute([$id, $tenantId, $empId, date('Y-m-d'), $date, $reason, $now]);

        logQuery(
            "INSERT INTO time_adjustment_requests (id, status='PENDING')", 
            [$id], 
            "CRUD 6: TimeAdjustmentRequest",
            "View timelogs (Botão Solicitar Retificação) ➔ Router (?route=timelogs&action=request_adjustment) ➔ TimeLogController::requestAdjustment() ➔ SQLite: time_adjustment_requests"
        );

        $_SESSION['flash_message'] = "Solicitação de ajuste enviada para aprovação do gestor!";
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function approveAdjustment(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE time_adjustment_requests SET status = 'APPROVED' WHERE id = ?");
            $stmt->execute([$id]);
            logQuery(
                "UPDATE time_adjustment_requests SET status = 'APPROVED' WHERE id = '$id'", 
                [$id], 
                "CRUD 6: TimeAdjustment Workflow",
                "View timelogs (Botão Aprovar Verde) ➔ Router (?action=approve_adjustment) ➔ TimeLogController::approveAdjustment() ➔ SQLite: time_adjustment_requests"
            );
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
            logQuery(
                "UPDATE time_adjustment_requests SET status = 'REJECTED' WHERE id = '$id'", 
                [$id], 
                "CRUD 6: TimeAdjustment Rejection",
                "View timelogs (Botão Recusar Laranja) ➔ Router (?action=reject_adjustment) ➔ TimeLogController::rejectAdjustment() ➔ SQLite: time_adjustment_requests"
            );
            $_SESSION['flash_message'] = "Solicitação de retificação RECUSADA pelo gestor!";
        }
        header("Location: index.php?route=timelogs");
        exit;
    }

    public function deleteAdjustment(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("DELETE FROM time_adjustment_requests WHERE id = ?");
            $stmt->execute([$id]);
            logQuery(
                "DELETE FROM time_adjustment_requests WHERE id = '$id'", 
                [$id], 
                "CRUD 6: TimeAdjustment Delete",
                "View timelogs (Botão Lixeira) ➔ Router (?action=delete_adjustment) ➔ TimeLogController::deleteAdjustment() ➔ SQLite: time_adjustment_requests"
            );
            $_SESSION['flash_message'] = "Solicitação de retificação excluída!";
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

        // Salário-hora = Salário Base / 220h (CLT)
        $hourlyRate = round(($emp['base_salary_cents'] / 100) / 220.0, 2);

        // Execução do Padrão Strategy conforme a seleção
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
            $amount = $strat->calculateOvertime($hourlyRate, $hours); // R$ 0,00
            $strategyUsed = "BankHoursStrategy (Banco de Horas CLT)";
            $desc = "Compensação de jornada creditada no banco de horas (Art. 59 §2 CLT)";

            // Atualiza saldo de banco de horas do colaborador em minutos (convenção do domínio CLT)
            $minutesToAdd = (int)round($hours * 60);
            $newBalance = ((int)($emp['bank_hours_balance'] ?? 0)) + $minutesToAdd;
            $upd = $this->pdo->prepare("UPDATE employees SET bank_hours_balance = ? WHERE id = ?");
            $upd->execute([$newBalance, $empId]);
        }

        $id = 'ot-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO overtime_entries (id, tenant_id, employee_id, date, hours, type, hourly_rate, amount, strategy_used, description, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$id, $emp['tenant_id'], $empId, $date, $hours, $type, $hourlyRate, $amount, $strategyUsed, $desc, $now]);

        logQuery(
            "Overtime Strategy: $strategyUsed ($hours h @ R$$hourlyRate/h -> R$ $amount) para {$emp['full_name']}", 
            [$id, $hours, $amount], 
            "Strategy: $strategyUsed",
            "View timelogs (Modal Horas Extras) ➔ Router (?route=timelogs&action=record_overtime) ➔ TimeLogController ➔ OvertimeStrategy ($type) ➔ SQLite: overtime_entries & employees"
        );

        if ($type === 'BANK_HOURS') {
            $_SESSION['flash_message'] = "Lançamento realizado! {$hours}h creditadas no Banco de Horas de {$emp['full_name']} via BankHoursStrategy.";
        } else {
            $_SESSION['flash_message'] = "Horas Extras apuradas com sucesso! R$ " . number_format($amount, 2, ',', '.') . " calculados via $strategyUsed.";
        }

        header("Location: index.php?route=timelogs");
        exit;
    }

    public function deleteOvertime(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $otStmt = $this->pdo->prepare("SELECT * FROM overtime_entries WHERE id = ?");
            $otStmt->execute([$id]);
            $ot = $otStmt->fetch();

            if ($ot && $ot['type'] === 'BANK_HOURS') {
                $minutesToDeduct = (int)round(((float)$ot['hours']) * 60);
                $upd = $this->pdo->prepare("UPDATE employees SET bank_hours_balance = MAX(0, bank_hours_balance - ?) WHERE id = ?");
                $upd->execute([$minutesToDeduct, $ot['employee_id']]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM overtime_entries WHERE id = ?");
            $stmt->execute([$id]);

            logQuery(
                "DELETE FROM overtime_entries WHERE id = '$id'", 
                [$id], 
                "Strategy: Overtime Reversal",
                "View timelogs (Botão Lixeira) ➔ Router (?action=delete_overtime) ➔ TimeLogController ➔ Reversão Banco de Horas ➔ SQLite: overtime_entries"
            );
            $_SESSION['flash_message'] = "Lançamento de horas extras/banco de horas cancelado com sucesso!";
        }
        header("Location: index.php?route=timelogs");
        exit;
    }
}
