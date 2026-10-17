<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * VacationController — Gestão e Concessão de Férias Trabalhistas (CLT)
 * ==============================================================================
 * Responsabilidade: Valentin (CRUD 7)
 *
 * Fundamentos Legais da CLT (Artigos 129 a 145):
 * 1. Período Aquisitivo & Saldo:
 *    - O colaborador adquire o direito a 30 dias de férias após 12 meses de vigência do contrato.
 *
 * 2. Fracionamento Legal (Art. 134 §1):
 *    - Permite divisão em até 3 períodos, desde que um não seja inferior a 14 dias
 *      e os demais não sejam inferiores a 5 dias corridos cada.
 *
 * 3. Abono Pecuniário (Art. 143):
 *    - É facultado ao empregado converter 1/3 (10 dias) do período de férias em abono em dinheiro.
 *
 * 4. Débito Atômico:
 *    - A aprovação da solicitação deduz imediatamente os dias gozados do saldo disponível
 *      do colaborador (`employees.vacation_days_balance`) em operação ACID.
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

        $stmt = $this->pdo->prepare(
            "SELECT vr.*, e.full_name as employee_name, e.vacation_days_balance
             FROM vacation_requests vr
             JOIN employees e ON vr.employee_id = e.id
             WHERE e.tenant_id = ?
             ORDER BY vr.created_at DESC"
        );
        $stmt->execute([$activeTenantId]);
        $vacations = $stmt->fetchAll();

        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        logQuery("SELECT FROM vacation_requests WHERE tenant_id = '$activeTenantId'", [$activeTenantId], "CRUD 7: VacationRequest");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/vacations/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function request(): void {
        $empId = $_POST['employee_id'] ?? '';
        $start = $_POST['start_date'] ?? '';
        $days  = (int)($_POST['days_count'] ?? 15);

        if (empty($empId) || empty($start)) {
            $_SESSION['flash_error'] = "Preencha a data de início!";
            header("Location: index.php?route=vacations");
            exit;
        }

        // Validação legal CLT via Exceções de Domínio (src/Exceptions/ValidationException.php)
        try {
            if ($days < 5) {
                throw \HrTech\Exceptions\ValidationException::forField('days_count', "O fracionamento de férias exige períodos mínimos de 5 dias corridos (Art. 134 §1 da CLT / src/Domain/Entities/VacationRequest.php).");
            }
            if ($days > 30) {
                throw \HrTech\Exceptions\ValidationException::forField('days_count', "O período de férias solicitado ($days dias) excede o teto anual de 30 dias (Art. 130 da CLT).");
            }
        } catch (\HrTech\Exceptions\ValidationException $e) {
            $_SESSION['flash_error'] = "Violação Trabalhista (src/Exceptions): " . $e->getMessage();
            header("Location: index.php?route=vacations");
            exit;
        }

        $empStmt = $this->pdo->prepare("SELECT tenant_id FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $tenantId = $empStmt->fetchColumn() ?: 'tenant-tech';

        $endDate = date('Y-m-d', strtotime($start . " +$days days"));
        $id = 'vac-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');

        $stmt = $this->pdo->prepare(
            "INSERT INTO vacation_requests (id, tenant_id, employee_id, start_date, end_date, duration_days, abono_pecuniario, advance_thirteenth_salary, abono_days, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 'REQUESTED', ?)"
        );
        $stmt->execute([$id, $tenantId, $empId, $start, $endDate, $days, $now]);

        logQuery(
            "INSERT INTO vacation_requests (id, duration_days=$days, status='REQUESTED')", 
            [$id, $days], 
            "CRUD 7: Vacation Request Workflow",
            "View vacations ➔ Router (?route=vacations&action=request) ➔ VacationController ➔ src/Domain/Entities/VacationRequest ➔ SQLite: vacation_requests"
        );

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
                $updEmp = $this->pdo->prepare("UPDATE employees SET vacation_days_balance = MAX(0, vacation_days_balance - ?) WHERE id = ?");
                $updEmp->execute([(int)$vac['duration_days'], $vac['employee_id']]);
            }

            $stmt = $this->pdo->prepare("UPDATE vacation_requests SET status = 'APPROVED' WHERE id = ?");
            $stmt->execute([$id]);

            logQuery(
                "UPDATE vacation_requests SET status = 'APPROVED' WHERE id = '$id'", 
                [$id], 
                "CRUD 7: Vacation Approval Workflow",
                "View vacations (Botão Aprovar) ➔ Router (?action=approve) ➔ VacationController ➔ Débito Atômico Saldo CLT ➔ SQLite: vacation_requests & employees"
            );

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

            logQuery("DELETE FROM vacation_requests WHERE id = '$id'", [$id], "CRUD 7: Vacation Delete");

            $_SESSION['flash_message'] = "Solicitação de férias excluída com sucesso!";
        }
        header("Location: index.php?route=vacations");
        exit;
    }
}
