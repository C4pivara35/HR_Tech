<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\ValueObjects\Cpf;

/**
 * Interface EmployeeRepositoryInterface
 *
 * Contract for relational persistence and retrieval of Employee entities.
 *
 * @package HrTech\Repositories\Contracts
 * @author Andryus (Membro 2 — CRUD 3: Cadastro de Colaboradores)
 */
interface EmployeeRepositoryInterface
{
    public function findById(string $id, string $tenantId): ?Employee;

    public function findByCpf(string|Cpf $cpf, string $tenantId): ?Employee;

    public function findByEmail(string $email, string $tenantId): ?Employee;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, Employee>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array;

    /**
     * @return array<int, Employee>
     */
    public function findByDepartment(string $departmentId, string $tenantId): array;

    public function save(Employee $employee): bool;

    public function update(Employee $employee): bool;

    public function delete(string $id, string $tenantId): bool;

    public function existsCpf(string|Cpf $cpf, string $tenantId, ?string $excludeEmployeeId = null): bool;
}
