<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\VacationRequest;
use HrTech\Domain\Enums\VacationStatus;

/**
 * Interface VacationRepositoryInterface
 *
 * Contract for relational persistence and lifecycle updates of CLT vacation requests.
 *
 * @package HrTech\Repositories\Contracts
 * @author Valentin (Membro 4 — CRUD 7: Gestão de Férias)
 */
interface VacationRepositoryInterface
{
    public function findById(string $id, string $tenantId): ?VacationRequest;

    /**
     * @return array<int, VacationRequest>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array;

    /**
     * @return array<int, VacationRequest>
     */
    public function findByStatus(VacationStatus|string $status, string $tenantId): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, VacationRequest>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array;

    public function save(VacationRequest $request): bool;

    public function update(VacationRequest $request): bool;

    public function delete(string $id, string $tenantId): bool;
}
