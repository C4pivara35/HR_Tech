<?php

declare(strict_types=1);

namespace HrTech\Patterns\Singleton;

use DateTimeImmutable;
use DateTimeZone;
use HrTech\Contracts\SingletonInterface;
use HrTech\Domain\Entities\AuditLog;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;

/**
 * Class AuditLogger
 *
 * Centralized compliance and audit logging singleton implementing LGPD compliance
 * tracking with tamper-evident SHA-256 cryptographic hash chaining across records.
 */
class AuditLogger implements SingletonInterface
{
    private static ?self $instance = null;

    /**
     * In-memory chain of AuditLog records.
     *
     * @var array<int, AuditLog>
     */
    private array $logs = [];

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization.
     *
     * @throws InvalidOperationException
     */
    public function __wakeup(): void
    {
        throw new InvalidOperationException('Cannot unserialize singleton AuditLogger.');
    }

    /**
     * Returns the unique singleton instance.
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Resets the singleton instance and clears logs (for test isolation and teardown).
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->clearLogs();
            self::$instance = null;
        }
    }

    /**
     * Records an audit event, appending it to the cryptographic SHA-256 chain.
     *
     * @param string $tenantId
     * @param string $action
     * @param string $entityType
     * @param string $entityId
     * @param array<string, mixed> $payload State change data or direct newState array
     * @param string|null $userId User or system actor executing the operation
     * @return AuditLog
     * @throws ValidationException
     */
    public function log(
        string $tenantId,
        string $action,
        string $entityType,
        string $entityId,
        array $payload,
        ?string $userId = null
    ): AuditLog {
        $cleanTenantId = trim($tenantId);
        $cleanAction = trim($action);
        $cleanEntityType = trim($entityType);
        $cleanEntityId = trim($entityId);

        if ($cleanTenantId === '') {
            throw ValidationException::forField('tenant_id', 'Audit tenant ID cannot be empty.');
        }
        if ($cleanAction === '') {
            throw ValidationException::forField('action', 'Audit action cannot be empty.');
        }
        if ($cleanEntityType === '') {
            throw ValidationException::forField('entity_type', 'Audit entity type cannot be empty.');
        }
        if ($cleanEntityId === '') {
            throw ValidationException::forField('entity_id', 'Audit entity ID cannot be empty.');
        }

        $actorUserId = $userId !== null && trim($userId) !== ''
            ? trim($userId)
            : (string)($payload['actor_user_id'] ?? $payload['user_id'] ?? 'SYSTEM');

        // Extract previous/new state if partitioned, otherwise use whole payload as new_state
        if (array_key_exists('previous_state', $payload) || array_key_exists('new_state', $payload)) {
            $previousState = (array)($payload['previous_state'] ?? []);
            $newState = (array)($payload['new_state'] ?? []);
        } else {
            $previousState = [];
            $newState = $payload;
        }

        $ipAddress = (string)($payload['ip_address'] ?? '127.0.0.1');
        $userAgent = (string)($payload['user_agent'] ?? 'HRTech-Core/1.0');

        $timestamp = isset($payload['timestamp']) && $payload['timestamp'] instanceof DateTimeImmutable
            ? $payload['timestamp']
            : new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $logId = (string)($payload['id'] ?? $this->generateUuid());

        // Previous hash links to the last log in the chain
        $lastLog = end($this->logs);
        $previousHash = $lastLog !== false ? $lastLog->integrityHash : AuditLog::GENESIS_HASH;

        $auditLog = AuditLog::record(
            id: $logId,
            tenantId: $cleanTenantId,
            actorUserId: $actorUserId,
            action: $cleanAction,
            entityType: $cleanEntityType,
            entityId: $cleanEntityId,
            previousState: $previousState,
            newState: $newState,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            previousHash: $previousHash,
            timestamp: $timestamp
        );

        $this->logs[] = $auditLog;

        return $auditLog;
    }

    /**
     * Returns all recorded logs in the chain, optionally filtered by tenant ID.
     *
     * @param string|null $tenantId
     * @return array<int, AuditLog>
     */
    public function getLogs(?string $tenantId = null): array
    {
        if ($tenantId === null) {
            return $this->logs;
        }

        $targetTenant = trim($tenantId);
        return array_values(array_filter(
            $this->logs,
            fn(AuditLog $l) => $l->belongsToTenant($targetTenant)
        ));
    }

    /**
     * Returns the most recent audit log entry, or null if no logs exist.
     *
     * @param string|null $tenantId
     * @return AuditLog|null
     */
    public function getLastLog(?string $tenantId = null): ?AuditLog
    {
        $filtered = $this->getLogs($tenantId);
        $last = end($filtered);

        return $last !== false ? $last : null;
    }

    /**
     * Returns the number of logs currently retained.
     *
     * @param string|null $tenantId
     * @return int
     */
    public function count(?string $tenantId = null): int
    {
        return count($this->getLogs($tenantId));
    }

    /**
     * Verifies the cryptographic integrity of the linear SHA-256 block chain.
     * Ensures each block verifies its own hash and accurately references the previous block.
     *
     * @return bool
     */
    public function verifyChainIntegrity(): bool
    {
        if (empty($this->logs)) {
            return true;
        }

        $expectedPrevHash = AuditLog::GENESIS_HASH;

        foreach ($this->logs as $log) {
            // Check that the entry's previousHash pointer matches the preceding block's integrityHash
            $actualPrev = $log->previousHash ?? AuditLog::GENESIS_HASH;
            if ($actualPrev !== $expectedPrevHash) {
                return false;
            }

            // Verify entry content has not been mutated
            if (!$log->verifyIntegrity($expectedPrevHash)) {
                return false;
            }

            $expectedPrevHash = $log->integrityHash;
        }

        return true;
    }

    /**
     * Clears all recorded audit logs.
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Generates a pseudo-random UUID v4 string.
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
