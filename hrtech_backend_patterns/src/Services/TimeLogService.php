<?php

declare(strict_types=1);

namespace HrTech\Services;

use DateTimeImmutable;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\TimeLogRepositoryInterface;

/**
 * Class TimeLogService
 *
 * Domain service orchestrating electronic time punches, NSR sequential counter increments,
 * SHA-256 cryptographic chain integrity, and Portaria 671/2021 MTE compliance.
 *
 * @package HrTech\Services
 * @author Felipe (Membro 3 — CRUD 5: Marcação de Ponto)
 */
class TimeLogService
{
    public function __construct(
        private readonly TimeLogRepositoryInterface $repository
    ) {
    }

    /**
     * Records an immutable electronic time punch conforming to Portaria 671/2021 MTE.
     *
     * @throws ValidationException
     */
    public function recordPunch(
        string $id,
        string $tenantId,
        string $employeeId,
        DateTimeImmutable $timestamp,
        TimeLogType|string $type,
        GeoLocation $location
    ): TimeLog {
        $logType = $type instanceof TimeLogType ? $type : TimeLogType::from($type);

        // Atomic NSR increment for this tenant
        $nextNsr = $this->repository->getLatestNsr($tenantId) + 1;

        // Obtain cryptographic link to last punch for this employee
        $lastPunch = $this->repository->findLastByEmployee($employeeId, $tenantId);
        $previousHash = $lastPunch?->signatureHash ?? TimeLog::GENESIS_PREVIOUS_HASH;

        $timeLog = new TimeLog(
            id: $id,
            tenantId: $tenantId,
            employeeId: $employeeId,
            timestamp: $timestamp,
            type: $logType,
            location: $location,
            nsr: $nextNsr,
            previousHash: $previousHash
        );

        $this->repository->save($timeLog);

        return $timeLog;
    }

    public function getTimeLog(string $id, string $tenantId): ?TimeLog
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @return array<int, TimeLog>
     */
    public function listEmployeePunches(
        string $employeeId,
        string $tenantId,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        return $this->repository->findByEmployeeAndPeriod($employeeId, $tenantId, $start, $end);
    }

    /**
     * Verifies the cryptographic chain integrity of all punches in the tenant ledger.
     */
    public function verifyTamperProofChain(string $tenantId, ?string $employeeId = null): bool
    {
        return $this->repository->verifyChainIntegrity($tenantId, $employeeId);
    }

    /**
     * Strict legal prohibition: time marks cannot be modified under Portaria 671/2021 MTE.
     *
     * @throws InvalidOperationException
     */
    public function updatePunch(): never
    {
        throw new InvalidOperationException(
            'Direct modification of time marks is strictly prohibited by Portaria 671/2021 MTE. Use TimeAdjustmentRequest instead.'
        );
    }

    /**
     * Strict legal prohibition: time marks cannot be deleted under Portaria 671/2021 MTE.
     *
     * @throws InvalidOperationException
     */
    public function deletePunch(): never
    {
        throw new InvalidOperationException(
            'Direct deletion of time marks is strictly prohibited by Portaria 671/2021 MTE.'
        );
    }
}
