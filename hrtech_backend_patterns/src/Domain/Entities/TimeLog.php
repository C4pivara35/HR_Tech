<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\TenantScopedInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class TimeLog
 *
 * Implements an immutable biometric or mobile electronic time punch record adhering
 * to Portaria 671/2021 MTE with SHA-256 chained digital signature hashes.
 */
class TimeLog implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    AuditableInterface,
    ArrayableInterface,
    JsonableInterface,
    JsonSerializable
{
    public const string GENESIS_PREVIOUS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public readonly string $signatureHash;

    /**
     * @param string $id Unique punch record identifier
     * @param string $tenantId Tenant identifier
     * @param string $employeeId Colaborador identifier
     * @param DateTimeImmutable $timestamp Punch date and time
     * @param TimeLogType $type Type of punch (ENTRY, INTERVAL_START, etc.)
     * @param GeoLocation $location Geolocation coordinates and accuracy radius
     * @param int $nsr Número Sequencial de Registro (positive integer >= 1)
     * @param string|null $previousHash SHA-256 hash of previous punch (null for genesis)
     * @param string|null $signatureHash Optional pre-computed SHA-256 signature hash
     * @throws ValidationException
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly DateTimeImmutable $timestamp,
        public readonly TimeLogType $type,
        public readonly GeoLocation $location,
        public readonly int $nsr,
        public readonly ?string $previousHash = null,
        ?string $signatureHash = null,
    ) {
        $this->signatureHash = $signatureHash ?? $this->calculateHash($previousHash);
        $this->validate();
    }

    /**
     * Named factory method to record a new time punch.
     */
    public static function record(
        string $id,
        string $tenantId,
        string $employeeId,
        DateTimeImmutable $timestamp,
        TimeLogType $type,
        GeoLocation $location,
        int $nsr,
        ?string $previousHash = null,
        ?string $signatureHash = null
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            employeeId: $employeeId,
            timestamp: $timestamp,
            type: $type,
            location: $location,
            nsr: $nsr,
            previousHash: $previousHash,
            signatureHash: $signatureHash
        );
    }

    /**
     * Calculates the deterministic Portaria 671/2021 MTE tamper-evident SHA-256 hash.
     *
     * @param string|null $previousHash
     * @return string 64-character lowercase hexadecimal string
     */
    public function calculateHash(?string $previousHash = null): string
    {
        $prev = $previousHash ?? $this->previousHash ?? self::GENESIS_PREVIOUS_HASH;

        $payload = sprintf(
            '%s|%s|%s|%s|%s|%d|%.6f|%.6f|%s',
            $this->tenantId,
            $this->employeeId,
            $this->timestamp->format(DateTimeInterface::ATOM),
            $this->type->value,
            $prev,
            $this->nsr,
            $this->location->getLatitude(),
            $this->location->getLongitude(),
            $this->id
        );

        return hash('sha256', $payload);
    }

    /**
     * Verifies cryptographic integrity against the expected hash using constant-time comparison.
     *
     * @param string|null $previousHash
     * @return bool
     */
    public function verifyIntegrity(?string $previousHash = null): bool
    {
        $prev = $previousHash ?? $this->previousHash;
        $expected = $this->calculateHash($prev);
        return hash_equals($this->signatureHash, $expected);
    }

    /**
     * Factory method to generate the next chained TimeLog for this employee and tenant.
     */
    public function createNext(
        string $id,
        DateTimeImmutable $timestamp,
        TimeLogType $type,
        GeoLocation $location
    ): self {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            employeeId: $this->employeeId,
            timestamp: $timestamp,
            type: $type,
            location: $location,
            nsr: $this->nsr + 1,
            previousHash: $this->signatureHash,
        );
    }

    public function getNsr(): int
    {
        return $this->nsr;
    }

    public function getSignatureHash(): string
    {
        return $this->signatureHash;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getType(): TimeLogType
    {
        return $this->type;
    }

    public function getLocation(): GeoLocation
    {
        return $this->location;
    }

    public function getPreviousHash(): ?string
    {
        return $this->previousHash;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    /**
     * Whether the punch represents beginning a period of work.
     */
    public function isEntry(): bool
    {
        return $this->type->isEntry();
    }

    /**
     * Whether the punch represents ending a period of work.
     */
    public function isExit(): bool
    {
        return $this->type->isExit();
    }

    /**
     * Checks if the punch occurred within an approved workplace geofence radius.
     */
    public function isWithinGeofence(GeoLocation $workplace, float $radiusMeters): bool
    {
        return $this->location->isWithinRadius($workplace, $radiusMeters);
    }

    /**
     * Calculates distance from punch location to target coordinates in meters.
     */
    public function distanceTo(GeoLocation $target): float
    {
        return $this->location->distanceTo($target);
    }

    // --------------------------------------------------------------------------
    // Contract Implementations
    // --------------------------------------------------------------------------

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

    public function validate(): void
    {
        $errors = [];

        if (trim($this->id) === '') {
            $errors['id'] = ['TimeLog ID cannot be empty.'];
        }

        if (trim($this->tenantId) === '') {
            $errors['tenant_id'] = ['Tenant ID cannot be empty.'];
        }

        if (trim($this->employeeId) === '') {
            $errors['employee_id'] = ['Employee ID cannot be empty.'];
        }

        if ($this->nsr <= 0) {
            $errors['nsr'] = ["NSR must be a strictly positive integer, {$this->nsr} provided."];
        }

        if (!preg_match('/^[a-f0-9]{64}$/i', $this->signatureHash)) {
            $errors['signature_hash'] = ['Signature hash must be a 64-character hexadecimal SHA-256 string.'];
        }

        if ($this->previousHash !== null && !preg_match('/^[a-f0-9]{64}$/i', $this->previousHash)) {
            $errors['previous_hash'] = ['Previous hash must be a 64-character hexadecimal SHA-256 string.'];
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'TimeLog validation failed.');
        }

        if (!$this->verifyIntegrity()) {
            throw ValidationException::forField(
                'signature_hash',
                'Tamper-evident integrity failure: signature hash does not match computed Portaria 671 hash.'
            );
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
        return 'TimeLog';
    }

    public function toAuditArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'timestamp' => $this->timestamp->format(DateTimeInterface::ATOM),
            'type' => $this->type->value,
            'nsr' => $this->nsr,
            'location' => $this->location->format(),
            'signature_hash' => $this->signatureHash,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'timestamp' => $this->timestamp->format(DateTimeInterface::ATOM),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'location' => $this->location->toArray(),
            'nsr' => $this->nsr,
            'previous_hash' => $this->previousHash,
            'signature_hash' => $this->signatureHash,
            'is_entry' => $this->isEntry(),
            'is_exit' => $this->isExit(),
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
