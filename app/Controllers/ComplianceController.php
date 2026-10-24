<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use DateTimeImmutable;
use DateTimeZone;
use HrTech\Patterns\Singleton\AuditLogger;
use HrTech\Domain\Entities\AuditLog;

/**
 * Controller de Conformidade, Auditoria Imutável e LGPD/BACEN
 * 
 * Implementa:
 * - SE_EXPORTAR_TRILHA_AUDITORIA: Exportação e verificação exaustiva da cadeia SHA-256
 *   com emissão de Laudo Pericial de Integridade Criptográfica (LGPD Art. 37/46, BACEN Resolução 4.893)
 * - EE_GRAVAR_EVENTO_AUDITORIA: Gravação encadeada com integridade criptográfica
 */
class ComplianceController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        // Filtros CE_LOGS_AUDITORIA_LGPD
        $filterActor = trim($_GET['search_actor'] ?? '');
        $filterAction = trim($_GET['search_action'] ?? '');
        $filterEntity = trim($_GET['search_entity'] ?? '');

        $report = $this->exportAuditTrail($activeTenantId);

        $sql = "SELECT * FROM audit_logs WHERE 1=1";
        $params = [];

        if ($activeRole !== 'admin') {
            $sql .= " AND (tenant_id = ? OR tenant_id = 'tenant-all')";
            $params[] = $activeTenantId;
        }

        if (!empty($filterActor)) {
            $sql .= " AND actor_user_id LIKE ?";
            $params[] = "%$filterActor%";
        }
        if (!empty($filterAction)) {
            $sql .= " AND action = ?";
            $params[] = $filterAction;
        }
        if (!empty($filterEntity)) {
            $sql .= " AND entity_type = ?";
            $params[] = $filterEntity;
        }

        $sql .= " ORDER BY rowid DESC LIMIT 30";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (function_exists('logQuery')) {
            logQuery("AuditLogger::verifyChainIntegrity() [CE_LOGS_AUDITORIA_LGPD & SE_EXPORTAR_TRILHA_AUDITORIA]", [
                'tenant' => $activeTenantId,
                'total_blocos' => $report['total_blocks'],
                'cadeia_valida' => $report['is_chain_valid'] ? 'SIM' : 'NÃO',
                'hash_terminal' => substr($report['terminal_hash'], 0, 16) . '...'
            ], "SE_EXPORTAR_TRILHA_AUDITORIA");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/compliance/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function verifyChain(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        $report = $this->exportAuditTrail($activeTenantId);

        if ($report['is_chain_valid']) {
            $_SESSION['flash_message'] = "LAUDO PERICIAL EMITIDO: Cadeia de auditoria SHA-256 100% ÍNTEGRA ({$report['total_blocks']} blocos validados sem adulteração). Terminal: " . substr($report['terminal_hash'], 0, 16) . "...";
        } else {
            $_SESSION['flash_error'] = "ALERTA DE VIOLAÇÃO: Foram detectados blocos adulterados na cadeia de blocos de auditoria!";
        }

        header("Location: index.php?route=compliance");
        exit;
    }

    /**
     * SE_EXPORTAR_TRILHA_AUDITORIA:
     * Valida integralmente a cadeia de blocos de auditoria desde o hash gênese até o bloco terminal,
     * recomputando o payload canônico e verificando cada assinatura SHA-256.
     * Emite Laudo Pericial de Integridade Criptográfica LGPD / BACEN.
     *
     * @param string|null $tenantId Tenant alvo para filtragem ou null/'tenant-all' para todos
     * @return array<string, mixed>
     */
    public function exportAuditTrail(?string $tenantId = null): array {
        // Busca todos os blocos na ordem cronológica de inserção (rowid crescente)
        $stmtAll = $this->pdo->query("SELECT * FROM audit_logs ORDER BY rowid ASC");
        $allRows = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

        $expectedPrevHash = AuditLog::GENESIS_HASH;
        $totalChainBlocks = count($allRows);
        $validBlocksCount = 0;
        $tamperedBlocks = [];
        $verifiedBlocks = [];

        foreach ($allRows as $index => $row) {
            $prev = !empty($row['previous_state']) ? json_decode($row['previous_state'], true) : [];
            $new = !empty($row['new_state']) ? json_decode($row['new_state'], true) : [];
            $ts = new DateTimeImmutable($row['timestamp'] ?? 'now');

            $actualPrevHash = $row['previous_hash'] ?? AuditLog::GENESIS_HASH;
            $pointerValid = hash_equals($expectedPrevHash, $actualPrevHash);

            $blockValid = false;
            try {
                $logEntity = new AuditLog(
                    $row['id'],
                    $row['tenant_id'],
                    $row['actor_user_id'],
                    $row['action'],
                    $row['entity_type'],
                    $row['entity_id'],
                    is_array($prev) ? $prev : [],
                    is_array($new) ? $new : [],
                    $row['ip_address'] ?? '127.0.0.1',
                    $row['user_agent'] ?? 'HRTech-Core/1.0',
                    $ts,
                    $actualPrevHash,
                    $row['integrity_hash']
                );

                $blockValid = $pointerValid && $logEntity->verifyIntegrity($expectedPrevHash);
            } catch (\Throwable $e) {
                $blockValid = false;
            }

            if ($blockValid) {
                $validBlocksCount++;
            } else {
                $tamperedBlocks[] = [
                    'sequence' => $index + 1,
                    'block_id' => $row['id'],
                    'expected_prev_hash' => $expectedPrevHash,
                    'actual_prev_hash' => $actualPrevHash,
                    'stored_hash' => $row['integrity_hash'],
                    'reason' => !$pointerValid ? 'Ponteiro previous_hash violado' : 'Payload alterado'
                ];
            }

            $matchesTenant = ($tenantId === null || $tenantId === 'tenant-all' || $row['tenant_id'] === $tenantId);

            $verifiedBlocks[] = [
                'sequence' => $index + 1,
                'id' => $row['id'],
                'tenant_id' => $row['tenant_id'],
                'actor_user_id' => $row['actor_user_id'],
                'action' => $row['action'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'timestamp' => $row['timestamp'],
                'previous_hash' => $row['previous_hash'],
                'integrity_hash' => $row['integrity_hash'],
                'is_valid' => $blockValid,
                'in_tenant_scope' => $matchesTenant,
            ];

            // Avança o ponteiro criptográfico para o próximo bloco
            $expectedPrevHash = $row['integrity_hash'];
        }

        $isChainValid = ($totalChainBlocks > 0 && $validBlocksCount === $totalChainBlocks);
        $terminalHash = $totalChainBlocks > 0 ? $allRows[$totalChainBlocks - 1]['integrity_hash'] : AuditLog::GENESIS_HASH;

        // Filtra blocos de escopo para retorno detalhado do tenant
        $tenantScopeBlocks = array_values(array_filter(
            $verifiedBlocks,
            fn(array $b) => $b['in_tenant_scope']
        ));

        return [
            'engine' => 'SE_EXPORTAR_TRILHA_AUDITORIA',
            'requested_tenant' => $tenantId ?? 'tenant-all',
            'verification_timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'),
            'algorithm' => 'SHA-256 (FIPS PUB 180-4)',
            'genesis_hash' => AuditLog::GENESIS_HASH,
            'terminal_hash' => $terminalHash,
            'total_blocks' => $totalChainBlocks,
            'valid_blocks' => $validBlocksCount,
            'tampered_blocks_count' => count($tamperedBlocks),
            'tampered_blocks' => $tamperedBlocks,
            'is_chain_valid' => $isChainValid,
            'laudo_pericial' => [
                'titulo' => 'Laudo Pericial de Integridade Criptográfica LGPD / BACEN',
                'orgao_emissor' => 'HRTech Core Compliance Engine',
                'normas_atendidas' => [
                    'LGPD (Lei 13.709/2018) Art. 37 (Registro de Tratamento de Dados)',
                    'LGPD (Lei 13.709/2018) Art. 46 (Padrões de Segurança e Imutabilidade)',
                    'BACEN Resolução CMN nº 4.893/2020 Art. 10 (Trilhas de Auditoria de Operações Financeiras)',
                    'MTE Portaria 671/2021 (Inviolabilidade de Registros e Não Repúdio)',
                    'ISO/IEC 27001:2022 Seção A.8.15 (Registros de Auditoria)'
                ],
                'parecer_conclusivo' => $isChainValid
                    ? 'A cadeia de custódia digital foi submetida a teste exaustivo de dispersão criptográfica SHA-256. Todos os blocos apresentam continuidade estrita de ponteiros e integridade canônica de dados sem detecção de mutações ou adulterações.'
                    : 'FALHA DE INTEGRIDADE DETECTADA: Foi identificada violação de encadeamento ou alteração indevida de dados na cadeia de custódia.',
                'status_laudo' => $isChainValid ? 'CONFORME / INVIOLÁVEL' : 'NÃO CONFORME / VIOLADO',
                'garantia_nao_repudio' => $isChainValid,
            ],
            'tenant_blocks_count' => count($tenantScopeBlocks),
            'blocks' => $tenantScopeBlocks
        ];
    }

    /**
     * Endpoint web para exportação do Laudo e Trilha em JSON
     */
    public function export(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        $report = $this->exportAuditTrail($activeTenantId);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="laudo_auditoria_lgpd_' . $activeTenantId . '.json"');
        echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * EE_GRAVAR_EVENTO_AUDITORIA:
     * Grava novo evento com garantia estrita de encadeamento criptográfico SHA-256
     */
    public function recordLog(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-fin';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-fin' : $activeRole;

        $action   = trim($_POST['action'] ?? 'OPERACAO_SENSIVEL');
        $entity   = trim($_POST['entity_name'] ?? 'Apólice FinCorp');
        $entityId = trim($_POST['entity_id'] ?? 'POL-2026-99');

        // Busca o último hash do banco de dados para encadeamento contínuo
        $lastHash = $this->pdo->query("SELECT integrity_hash FROM audit_logs ORDER BY rowid DESC LIMIT 1")->fetchColumn();
        $prevHash = $lastHash !== false && !empty($lastHash) ? (string)$lastHash : AuditLog::GENESIS_HASH;

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $logId = 'audit-' . uniqid();

        $logEntity = AuditLog::record(
            id: $logId,
            tenantId: $activeTenantId,
            actorUserId: $_SESSION['user_id'] ?? 'usr-admin',
            action: $action,
            entityType: $entity,
            entityId: $entityId,
            previousState: [],
            newState: [
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'logged_at' => $now->format('c')
            ],
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            userAgent: $_SERVER['HTTP_USER_AGENT'] ?? 'HRTech-Web/1.0',
            previousHash: $prevHash,
            timestamp: $now
        );

        $stmt = $this->pdo->prepare(
            "INSERT INTO audit_logs (id, tenant_id, actor_user_id, action, entity_type, entity_id, previous_state, new_state, ip_address, user_agent, timestamp, previous_hash, integrity_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $logEntity->id,
            $logEntity->tenantId,
            $logEntity->actorUserId,
            $logEntity->action,
            $logEntity->entityType,
            $logEntity->entityId,
            json_encode($logEntity->previousState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            json_encode($logEntity->newState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $logEntity->ipAddress,
            $logEntity->userAgent,
            $logEntity->timestamp->format('Y-m-d\TH:i:s.u\Z'),
            $logEntity->previousHash,
            $logEntity->integrityHash
        ]);

        if (function_exists('logQuery')) {
            logQuery("AuditLogger::logEvent('$action', entity='$entity') -> SHA256 " . substr($logEntity->integrityHash, 0, 16) . "...", [$logId], "Singleton AuditLogger");
        }

        $_SESSION['flash_message'] = "Evento de auditoria imutável registrado com hash SHA-256 (" . substr($logEntity->integrityHash, 0, 12) . "...)!";
        header("Location: index.php?route=compliance");
        exit;
    }
}
