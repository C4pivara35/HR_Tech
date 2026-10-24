<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * ApprovalController — Central Unificada de Aprovações do Gestor (Tela 11)
 * ==============================================================================
 * Responsabilidades: Valentin & Felipe
 *
 * Transações Gerenciais de Workflow:
 * - Aprovação e Recusa de Solicitações de Férias CLT (com débito atômico de saldo)
 * - Homologação e Rejeição de Retificações/Abonos de Marcações de Ponto (Portaria 671)
 * ==============================================================================
 */
class ApprovalController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        // Férias Pendentes
        $vacStmt = $this->pdo->prepare(
            "SELECT vr.*, e.full_name as employee_name, e.matricula, e.cpf, e.vacation_days_balance, d.name as department_name
             FROM vacation_requests vr
             JOIN employees e ON vr.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE e.tenant_id = ? AND vr.status = 'PENDING'
             ORDER BY vr.created_at ASC"
        );
        $vacStmt->execute([$activeTenantId]);
        $pendingVacations = $vacStmt->fetchAll(PDO::FETCH_ASSOC);

        // Retificações de Ponto Pendentes
        $adjStmt = $this->pdo->prepare(
            "SELECT tar.*, e.full_name as employee_name, e.matricula, e.cpf, d.name as department_name
             FROM time_adjustment_requests tar
             JOIN employees e ON tar.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE e.tenant_id = ? AND tar.status = 'PENDING'
             ORDER BY tar.created_at ASC"
        );
        $adjStmt->execute([$activeTenantId]);
        $pendingAdjustments = $adjStmt->fetchAll(PDO::FETCH_ASSOC);

        // Histórico recente de despachos (últimos 15)
        $histVacStmt = $this->pdo->prepare(
            "SELECT vr.*, e.full_name as employee_name, 'Férias' as item_type, vr.status as resolution
             FROM vacation_requests vr
             JOIN employees e ON vr.employee_id = e.id
             WHERE e.tenant_id = ? AND vr.status != 'PENDING'
             ORDER BY COALESCE(vr.approved_at, vr.created_at) DESC LIMIT 10"
        );
        $histVacStmt->execute([$activeTenantId]);
        $historyVacations = $histVacStmt->fetchAll(PDO::FETCH_ASSOC);

        $histAdjStmt = $this->pdo->prepare(
            "SELECT tar.*, e.full_name as employee_name, 'Ajuste de Ponto' as item_type, tar.status as resolution
             FROM time_adjustment_requests tar
             JOIN employees e ON tar.employee_id = e.id
             WHERE e.tenant_id = ? AND tar.status != 'PENDING'
             ORDER BY tar.created_at DESC LIMIT 10"
        );
        $histAdjStmt->execute([$activeTenantId]);
        $historyAdjustments = $histAdjStmt->fetchAll(PDO::FETCH_ASSOC);

        if (function_exists('logQuery')) {
            logQuery("ApprovalController::index() [Workflow de Aprovações do Gestor]", [$activeTenantId], "Workflow Gestor");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/approvals/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function approveVacation(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("SELECT * FROM vacation_requests WHERE id = ?");
            $stmt->execute([$id]);
            $vac = $stmt->fetch();

            if ($vac && $vac['status'] === 'PENDING') {
                $days = (int)$vac['duration_days'];
                $abono = ((int)$vac['abono_pecuniario'] === 1) ? (int)$vac['abono_days'] : 0;
                $totalDays = $days + $abono;

                $this->pdo->beginTransaction();
                try {
                    $upd = $this->pdo->prepare("UPDATE vacation_requests SET status = 'APPROVED', approved_at = ? WHERE id = ?");
                    $upd->execute([date('Y-m-d H:i:s'), $id]);

                    $deb = $this->pdo->prepare("UPDATE employees SET vacation_days_balance = MAX(0, vacation_days_balance - ?) WHERE id = ?");
                    $deb->execute([$totalDays, $vac['employee_id']]);

                    $this->pdo->commit();
                    $_SESSION['flash_message'] = "Férias aprovadas e saldo de {$totalDays} dias debitado com sucesso!";
                } catch (\Throwable $e) {
                    $this->pdo->rollBack();
                    $_SESSION['flash_error'] = "Erro na aprovação: " . $e->getMessage();
                }
            }
        }
        header("Location: index.php?route=approvals");
        exit;
    }

    public function rejectVacation(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE vacation_requests SET status = 'REJECTED' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = "Solicitação de férias recusada pelo gestor.";
        }
        header("Location: index.php?route=approvals");
        exit;
    }

    public function approveTimelog(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("SELECT * FROM time_adjustment_requests WHERE id = ?");
            $stmt->execute([$id]);
            $req = $stmt->fetch();

            if ($req && $req['status'] === 'PENDING') {
                $this->pdo->beginTransaction();
                try {
                    $upd = $this->pdo->prepare("UPDATE time_adjustment_requests SET status = 'APPROVED', approved_at = ? WHERE id = ?");
                    $upd->execute([date('Y-m-d\TH:i:sP'), $id]);

                    // Insere a batida retificada com encadeamento Portaria 671
                    $reqDate = $req['requested_date'] ?? date('Y-m-d');
                    $reqTime = $req['requested_time'] ?? '08:00';
                    $ts = $reqDate . 'T' . (strlen($reqTime) === 5 ? $reqTime . ':00' : $reqTime) . '-03:00';
                    $type = 'ENTRY';
                    $logId = 'tl-adj-' . uniqid();

                    $nsrStmt = $this->pdo->query("SELECT MAX(nsr), signature_hash FROM time_logs ORDER BY nsr DESC LIMIT 1");
                    $lastRow = $nsrStmt->fetch();
                    $nextNsr  = ((int)($lastRow[0] ?? 0)) + 1;
                    $prevHash = $lastRow['signature_hash'] ?? str_repeat('0', 64);

                    $empStmt = $this->pdo->prepare("SELECT cpf FROM employees WHERE id = ?");
                    $empStmt->execute([$req['employee_id']]);
                    $empCpf = $empStmt->fetchColumn() ?: '000.000.000-00';

                    $rawPayload = $prevHash . '|' . $nextNsr . '|' . $empCpf . '|' . $ts . '|' . $type;
                    $hash = hash('sha256', $rawPayload);

                    $ins = $this->pdo->prepare(
                        "INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, accuracy, nsr, previous_hash, signature_hash)
                         VALUES (?, ?, ?, ?, ?, -25.4284, -49.2733, 10.0, ?, ?, ?)"
                    );
                    $ins->execute([$logId, $req['tenant_id'], $req['employee_id'], $ts, $type, $nextNsr, $prevHash, $hash]);

                    $this->pdo->commit();
                    $_SESSION['flash_message'] = "Ajuste de ponto homologado e registrado no espelho!";
                } catch (\Throwable $e) {
                    $this->pdo->rollBack();
                    $_SESSION['flash_error'] = "Erro na homologação: " . $e->getMessage();
                }
            }
        }
        header("Location: index.php?route=approvals");
        exit;
    }

    public function rejectTimelog(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("UPDATE time_adjustment_requests SET status = 'REJECTED' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = "Solicitação de retificação de ponto recusada pelo gestor.";
        }
        header("Location: index.php?route=approvals");
        exit;
    }
}
