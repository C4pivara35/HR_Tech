<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\TimeAdjustmentRequest;
use HrTech\Domain\Enums\AdjustmentStatus;

/**
 * Interface TimeAdjustmentRepositoryInterface
 *
 * Contract for relational persistence and workflow state updates of time adjustment requests.
 *
 * @package HrTech\Repositories\Contracts
 * @author Felipe (Membro 3 — CRUD 6: Ajustes de Ponto)
 */
interface TimeAdjustmentRepositoryInterface
{
    public function findById(string $id, string $tenantId): ?TimeAdjustmentRequest;

    /**
     * @return array<int, TimeAdjustmentRequest>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array;

    /**
     * @return array<int, TimeAdjustmentRequest>
     */
    public function findByStatus(AdjustmentStatus|string $status, string $tenantId): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, TimeAdjustmentRequest>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array;

    public function save(TimeAdjustmentRequest $request): bool;

    public function update(TimeAdjustmentRequest $request): bool;

    public function delete(string $id, string $tenantId): bool;
}
