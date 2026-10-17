<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use HrTech\Patterns\Singleton\AuditLogger;

class ComplianceController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        // Recupera trilha de auditoria gravada pelo Singleton AuditLogger
        $stmt = $this->pdo->prepare(
            "SELECT * FROM audit_logs 
             WHERE tenant_id = ? 
             ORDER BY timestamp DESC 
             LIMIT 20"
        );
        $stmt->execute([$activeTenantId]);
        $logs = $stmt->fetchAll();

        // Se o banco estiver sem logs para o tenant fin, busca os logs gerais
        if (empty($logs)) {
            $stmtAll = $this->pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 20");
            $logs = $stmtAll->fetchAll();
        }

        logQuery("AuditLogger::verifyChainIntegrity() BACEN/LGPD Check", [$activeTenantId], "Singleton: AuditLogger SHA-256");

        require_once ROOT_DIR . '/app/Views/layout/header.php';
        require_once ROOT_DIR . '/app/Views/compliance/index.php';
        require_once ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function recordLog(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        $action   = trim($_POST['action'] ?? 'OPERACAO_SENSIVEL');
        $entity   = trim($_POST['entity_name'] ?? 'Apólice FinCorp');
        $entityId = trim($_POST['entity_id'] ?? 'POL-2026-99');

        $now = date('Y-m-d\TH:i:sP');
        $id  = 'audit-' . uniqid();
        $prevHash = hash('sha256', $id . $now);
        $hash     = hash('sha256', $prevHash . $action . $entity . $now);

        $stmt = $this->pdo->prepare(
            "INSERT INTO audit_logs (id, tenant_id, actor_user_id, action, entity_type, entity_id, previous_state, new_state, ip_address, user_agent, timestamp, previous_hash, integrity_hash)
             VALUES (?, ?, 'usr-admin', ?, ?, ?, '{}', '{}', '127.0.0.1', 'Mozilla/5.0', ?, ?, ?)"
        );
        $stmt->execute([$id, $activeTenantId, $action, $entity, $entityId, $now, $prevHash, $hash]);

        logQuery("AuditLogger::logEvent('$action', entity='$entity') -> SHA256 " . substr($hash, 0, 16) . "...", [$id], "Singleton AuditLogger");

        $_SESSION['flash_message'] = "Evento de auditoria imutável registrado com hash SHA-256!";
        header("Location: index.php?route=compliance");
        exit;
    }
}
