<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use DateTimeImmutable;
use HrTech\Domain\Entities\EquipmentASO;

/**
 * Interface EquipmentASORepositoryInterface
 *
 * Contract for relational persistence and expiration querying of NR-6 PPE and NR-7 ASO records.
 *
 * @package HrTech\Repositories\Contracts
 * @author Nicholas (Membro 5 — CRUD 9: Controle de EPIs e ASO)
 */
interface EquipmentASORepositoryInterface
{
    public function findById(string $id, string $tenantId): ?EquipmentASO;

    /**
     * @return array<int, EquipmentASO>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array;

    /**
     * @return array<int, EquipmentASO>
     */
    public function findExpiredExams(string $tenantId, ?DateTimeImmutable $referenceDate = null): array;

    /**
     * @return array<int, EquipmentASO>
     */
    public function findExpiredEquipment(string $tenantId, ?DateTimeImmutable $referenceDate = null): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, EquipmentASO>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array;

    public function save(EquipmentASO $record): bool;

    public function update(EquipmentASO $record): bool;

    public function delete(string $id, string $tenantId): bool;
}
