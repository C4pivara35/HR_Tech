<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Enums\BenefitType;

/**
 * Interface BenefitRepositoryInterface
 *
 * Contract for relational persistence and retrieval of corporate benefits (VR, VA, VT, Health, etc.).
 *
 * @package HrTech\Repositories\Contracts
 * @author Valentin (Membro 4 — CRUD 8: Gestão de Benefícios)
 */
interface BenefitRepositoryInterface
{
    public function findById(string $id, string $tenantId): ?Benefit;

    /**
     * @return array<int, Benefit>
     */
    public function findByType(BenefitType|string $type, string $tenantId): array;

    /**
     * @return array<int, Benefit>
     */
    public function findAllByTenant(string $tenantId): array;

    public function save(Benefit $benefit): bool;

    public function update(Benefit $benefit): bool;

    public function delete(string $id, string $tenantId): bool;
}
