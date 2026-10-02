<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use DateTimeImmutable;
use DateTimeZone;
use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\TenantScopedInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class AuditLog
 *
 * Entidade imutável AuditLog.
 * Implementa trilha de auditoria em conformidade com a LGPD com encadeamento criptográfico
 * SHA-256 inviolável entre os registros da empresa.
 */
readonly class AuditLog implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    AuditableInterface,
    ArrayableInterface,
    JsonableInterface,
    JsonSerializable
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * @param string $id Identificador único UUID do evento de auditoria
     * @param string $tenantId Identificador do tenant (isolamento multi-tenant)
     * @param string $actorUserId Usuário ou serviço que executou a operação
     * @param string $action Tipo de ação de auditoria (ex.: 'CREATE', 'UPDATE', 'DELETE', 'EXPORT_LGPD')
     * @param string $entityType Classe ou categoria da entidade afetada (ex.: 'Employee', 'Benefit')
     * @param string $entityId Chave primária da entidade afetada
     * @param array<string, mixed> $previousState Estado anterior à alteração (dados sensíveis sanitizados)
     * @param array<string, mixed> $newState Estado posterior à alteração (dados sensíveis sanitizados)
     * @param string $ipAddress Endereço IP de origem (IPv4 / IPv6)
     * @param string $userAgent Identificação do User-Agent HTTP do cliente ou executor CLI
     * @param DateTimeImmutable $timestamp Carimbo de data/hora da ocorrência (UTC)
     * @param string|null $previousHash Hash SHA-256 do bloco anterior na cadeia de auditoria da empresa
     * @param string $integrityHash Hash SHA-256 inviolável certificando a integridade do registro
     */
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $actorUserId,
        public string $action,
        public string $entityType,
        public string $entityId,
        public array $previousState,
        public array $newState,
        public string $ipAddress,
        public string $userAgent,
        public DateTimeImmutable $timestamp,
        public ?string $previousHash,
        public string $integrityHash
    ) {
        $this->validate();
    }

    /**
     * Método fábrica para instanciar e selar criptograficamente um registro imutável de auditoria.
     * Automatically calculates the integrityHash from payload attributes.
     *
     * @param array<string, mixed> $previousState
     * @param array<string, mixed> $newState
     */
    public static function record(
        string $id,
        string $tenantId,
        string $actorUserId,
        string $action,
        string $entityType,
        string $entityId,
        array $previousState,
        array $newState,
        string $ipAddress = '127.0.0.1',
        string $userAgent = 'HRTech-Core/1.0',
        ?string $previousHash = null,
        ?DateTimeImmutable $timestamp = null
    ): self {
        $occurredAt = $timestamp ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $sanitizedPrevious = self::sanitizeState($previousState);
        $sanitizedNew = self::sanitizeState($newState);

        $computedHash = self::computeHash(
            $id,
            $tenantId,
            $actorUserId,
            $action,
            $entityType,
            $entityId,
            $sanitizedPrevious,
            $sanitizedNew,
            $ipAddress,
            $userAgent,
            $occurredAt,
            $previousHash
        );

        return new self(
            $id,
            $tenantId,
            $actorUserId,
            $action,
            $entityType,
            $entityId,
            $sanitizedPrevious,
            $sanitizedNew,
            $ipAddress,
            $userAgent,
            $occurredAt,
            $previousHash,
            $computedHash
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenantId === trim($tenantId);
    }

    /**
     * Gera o hash canônico de integridade SHA-256 para este registro.
     */
    public function generateHash(?string $previousHash = null): string
    {
        return self::computeHash(
            $this->id,
            $this->tenantId,
            $this->actorUserId,
            $this->action,
            $this->entityType,
            $this->entityId,
            $this->previousState,
            $this->newState,
            $this->ipAddress,
            $this->userAgent,
            $this->timestamp,
            $previousHash ?? $this->previousHash
        );
    }

    /**
     * Verifica que o registro não foi adulterado.
     */
    public function verifyIntegrity(?string $previousHash = null): bool
    {
        $expectedHash = $this->generateHash($previousHash ?? $this->previousHash);
        return hash_equals($expectedHash, $this->integrityHash);
    }

    /**
     * Deterministic canonical payload serialization for SHA-256 hashing.
     *
     * @param array<string, mixed> $previousState
     * @param array<string, mixed> $newState
     */
    private static function computeHash(
        string $id,
        string $tenantId,
        string $actorUserId,
        string $action,
        string $entityType,
        string $entityId,
        array $previousState,
        array $newState,
        string $ipAddress,
        string $userAgent,
        DateTimeImmutable $timestamp,
        ?string $previousHash
    ): string {
        $canonicalPayload = implode('|', [
            $id,
            $tenantId,
            $actorUserId,
            strtoupper(trim($action)),
            $entityType,
            $entityId,
            json_encode(self::ksortRecursive($previousState), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            json_encode(self::ksortRecursive($newState), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $ipAddress,
            $userAgent,
            $timestamp->format('Y-m-d\TH:i:s.u\Z'),
            $previousHash ?? self::GENESIS_HASH,
        ]);

        return hash('sha256', $canonicalPayload);
    }

    /**
     * Recursively redacts sensitive LGPD/security attributes.
     *
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public static function sanitizeState(array $state): array
    {
        $redactedKeys = ['password', 'password_hash', 'token', 'secret', 'salt', 'auth_key', 'credit_card'];
        $sanitized = [];

        foreach ($state as $key => $val) {
            if (in_array(strtolower((string)$key), $redactedKeys, true)) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($val)) {
                $sanitized[$key] = self::sanitizeState($val);
            } else {
                $sanitized[$key] = $val;
            }
        }

        return $sanitized;
    }

    /**
     * Ensures deterministic array key sorting for hashing.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function ksortRecursive(array $data): array
    {
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::ksortRecursive($value);
            }
        }
        return $data;
    }

    public function validate(): void
    {
        $errors = [];

        if (trim($this->id) === '') {
            $errors['id'][] = 'AuditLog ID cannot be empty.';
        }

        if (trim($this->tenantId) === '') {
            $errors['tenant_id'][] = 'Tenant ID cannot be empty.';
        }

        if (trim($this->actorUserId) === '') {
            $errors['actor_user_id'][] = 'Actor user ID cannot be empty.';
        }

        if (trim($this->action) === '') {
            $errors['action'][] = 'Action cannot be empty.';
        }

        if (trim($this->entityType) === '') {
            $errors['entity_type'][] = 'Entity type cannot be empty.';
        }

        if (trim($this->entityId) === '') {
            $errors['entity_id'][] = 'Entity ID cannot be empty.';
        }

        if (strlen($this->integrityHash) !== 64 || !ctype_xdigit($this->integrityHash)) {
            $errors['integrity_hash'][] = 'Integrity hash must be a valid 64-character hexadecimal SHA-256 string.';
        }

        if ($this->previousHash !== null && (strlen($this->previousHash) !== 64 || !ctype_xdigit($this->previousHash))) {
            $errors['previous_hash'][] = 'Previous hash, when provided, must be a 64-character hexadecimal SHA-256 string.';
        }

        if (!$this->verifyIntegrity($this->previousHash)) {
            $errors['integrity_hash'][] = 'Integrity hash mismatch: record data has been tampered with or corrupted.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'AuditLog validation failed.');
        }
    }

    public function isValid(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    public function getAuditIdentifier(): string
    {
        return $this->id;
    }

    public function getAuditCategory(): string
    {
        return 'AuditLog';
    }

    public function toAuditArray(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'actor_user_id' => $this->actorUserId,
            'action' => $this->action,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'previous_state' => $this->previousState,
            'new_state' => $this->newState,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => $this->timestamp->format('c'),
            'previous_hash' => $this->previousHash,
            'integrity_hash' => $this->integrityHash,
        ];
    }

    public function toJson(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
