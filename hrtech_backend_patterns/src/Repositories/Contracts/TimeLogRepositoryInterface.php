<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use DateTimeImmutable;
use HrTech\Domain\Entities\TimeLog;

/**
 * Interface TimeLogRepositoryInterface
 *
 * Contract for append-only relational persistence and cryptographic chain verification
 * of Portaria 671 electronic time logs.
 *
 * @package HrTech\Repositories\Contracts
 * @author Felipe (Membro 3 — CRUD 5: Marcação de Ponto)
 */
interface TimeLogRepositoryInterface
{
    public function findById(string $id, string $tenantId): ?TimeLog;

    public function findLastByEmployee(string $employeeId, string $tenantId): ?TimeLog;

    /**
     * @return array<int, TimeLog>
     */
    public function findByEmployeeAndPeriod(
        string $employeeId,
        string $tenantId,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, TimeLog>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array;

    public function getLatestNsr(string $tenantId): int;

    public function save(TimeLog $timeLog): bool;

    public function verifyChainIntegrity(string $tenantId, ?string $employeeId = null): bool;
}
