<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\Tenant;
use HrTech\Domain\ValueObjects\Cnpj;

/**
 * Interface TenantRepositoryInterface
 *
 * Contract for relational persistence and retrieval of Tenant entities.
 *
 * @package HrTech\Repositories\Contracts
 * @author Fernando Lopes Duarte (Membro 1 — CRUD 1: Gestão de Tenants)
 */
interface TenantRepositoryInterface
{
    public function findById(string $id): ?Tenant;

    public function findByCnpj(string|Cnpj $cnpj): ?Tenant;

    /**
     * @return array<int, Tenant>
     */
    public function findAll(bool $onlyActive = false): array;

    public function save(Tenant $tenant): bool;

    public function update(Tenant $tenant): bool;

    public function delete(string $id): bool;

    public function exists(string $id): bool;

    public function existsCnpj(string|Cnpj $cnpj, ?string $excludeTenantId = null): bool;
}
