<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\InsurancePolicy;
use HrTech\Domain\Enums\PolicyStatus;

/**
 * Interface InsurancePolicyRepositoryInterface
 *
 * Contract for relational persistence and retrieval of FinCorp Broker insurance policies.
 *
 * @package HrTech\Repositories\Contracts
 * @author Nicholas (Membro 5 — CRUD 10: Apólices FinCorp)
 */
interface InsurancePolicyRepositoryInterface
{
    public function findById(string $id, string $tenantId): ?InsurancePolicy;

    public function findByPolicyNumber(string $policyNumber, string $tenantId): ?InsurancePolicy;

    /**
     * @return array<int, InsurancePolicy>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array;

    /**
     * @return array<int, InsurancePolicy>
     */
    public function findByStatus(PolicyStatus|string $status, string $tenantId): array;

    /**
     * @return array<int, InsurancePolicy>
     */
    public function findAllByTenant(string $tenantId): array;

    public function save(InsurancePolicy $policy): bool;

    public function update(InsurancePolicy $policy): bool;

    public function delete(string $id, string $tenantId): bool;
}
