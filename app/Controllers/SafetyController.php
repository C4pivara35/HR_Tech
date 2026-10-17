<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

/**
 * ==============================================================================
 * SafetyController — Saúde & Segurança do Trabalho (SST) - NR-06 & NR-07
 * ==============================================================================
 * Responsabilidade: Nicholas (CRUD 9)
 *
 * Normas Regulamentadoras & Conformidade Legal:
 * 1. NR-06 (Equipamentos de Proteção Individual - EPI):
 *    - Controle de entrega, substituição e higienização com número do CA (Certificado de Aprovação)
 *      válido emitido pelo Ministério do Trabalho e Emprego.
 *
 * 2. NR-07 (Programa de Controle Médico de Saúde Ocupacional - PCMSO / ASO):
 *    - Registro de Atestados de Saúde Ocupacional (Admissional, Periódico, Demissional, Retorno)
 *      com CRM do médico coordenador/examinador e laudo de aptidão física (isFit).
 *
 * 3. Integração com Linha de Produção de Software (LPS - LpsVariabilityEngine):
 *    - No segmento 'INDUSTRIA', atua como barreira dura (Hard Gate): se o trabalhador estiver
 *      sem ASO válido ou com CA expirado, ele é bloqueado de ingressar na fábrica ou bater ponto.
 *    - No segmento 'TECH', a exigência de EPI é dispensada pela política de Home Office / Escritório.
 * ==============================================================================
 */
class SafetyController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-ind';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-ind' : $activeRole;

        $stmt = $this->pdo->prepare(
            "SELECT ea.*,
                    CASE WHEN ea.ca_number = 'N/A' OR ea.ca_number IS NULL THEN 'ASO' ELSE 'EPI' END as record_type,
                    ea.equipment_name as item_name,
                    COALESCE(ea.delivery_date, ea.exam_date, ea.created_at) as issue_date,
                    e.full_name as employee_name, e.cpf
             FROM equipment_aso ea
             JOIN employees e ON ea.employee_id = e.id
             WHERE ea.tenant_id = ?
             ORDER BY ea.created_at DESC"
        );
        $stmt->execute([$activeTenantId]);
        $safetyRecords = $stmt->fetchAll();

        $empStmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? AND is_active = 1");
        $empStmt->execute([$activeTenantId]);
        $employees = $empStmt->fetchAll();

        logQuery("SELECT FROM equipment_aso WHERE tenant_id = '$activeTenantId'", [$activeTenantId], "CRUD 9: EquipmentASO NR-6/NR-7");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/safety/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function deliverEquipment(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-ind';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-ind' : $activeRole;

        $empId  = $_POST['employee_id'] ?? '';
        $item   = trim($_POST['item_name'] ?? '');
        $ca     = trim($_POST['ca_number'] ?? '');

        if (empty($empId) || empty($item) || empty($ca)) {
            $_SESSION['flash_error'] = "Preencha todos os campos do EPI!";
            header("Location: index.php?route=safety");
            exit;
        }

        $id = 'epi-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');
        $today = date('Y-m-d');
        $exp = date('Y-m-d', strtotime('+1 year'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO equipment_aso (id, tenant_id, employee_id, equipment_name, ca_number, ca_expiration_date, delivery_date, expiration_date, is_fit, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $empId, $item, $ca, $exp, $today, $exp, $now]);

        logQuery("INSERT INTO equipment_aso (EPI delivery CA=$ca)", [$id], "CRUD 9: EquipmentASO NR-6");

        $_SESSION['flash_message'] = "EPI entregue e registrado com CA $ca no sistema!";
        header("Location: index.php?route=safety");
        exit;
    }

    public function recordExam(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-ind';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-ind' : $activeRole;

        $empId  = $_POST['employee_id'] ?? '';
        $type   = $_POST['exam_type'] ?? 'PERIODIC';
        $isFit  = isset($_POST['is_fit']) ? 1 : 0;

        if (empty($empId)) {
            $_SESSION['flash_error'] = "Selecione um colaborador!";
            header("Location: index.php?route=safety");
            exit;
        }

        $id = 'aso-' . uniqid();
        $now = date('Y-m-d\TH:i:sP');
        $today = date('Y-m-d');
        $exp = $isFit ? date('Y-m-d', strtotime('+1 year')) : $today;

        $stmt = $this->pdo->prepare(
            "INSERT INTO equipment_aso (id, tenant_id, employee_id, equipment_name, ca_number, exam_type, exam_date, expiration_date, is_fit, created_at)
             VALUES (?, ?, ?, ?, 'N/A', ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $empId, "Atestado ASO: $type", $type, $today, $exp, $isFit, $now]);

        logQuery("INSERT INTO equipment_aso (ASO exam fit=$isFit)", [$id], "CRUD 9: EquipmentASO NR-7 Check");

        if (!$isFit) {
            $_SESSION['flash_error'] = "ATENÇÃO: Colaborador INAPTO! Acesso ao chão de fábrica BLOQUEADO automaticamente no sistema!";
        } else {
            $_SESSION['flash_message'] = "Exame ASO registrado com aptidão médica liberada!";
        }

        header("Location: index.php?route=safety");
        exit;
    }

    public function delete(): void {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $stmt = $this->pdo->prepare("DELETE FROM equipment_aso WHERE id = ?");
            $stmt->execute([$id]);
            logQuery("DELETE FROM equipment_aso WHERE id = '$id'", [$id], "CRUD 9: EquipmentASO Delete");
            $_SESSION['flash_message'] = "Registro de EPI/ASO excluído com sucesso!";
        }
        header("Location: index.php?route=safety");
        exit;
    }
}
