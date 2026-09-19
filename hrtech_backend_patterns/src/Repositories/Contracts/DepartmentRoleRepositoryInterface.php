<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\Department;
use HrTech\Domain\Entities\Role;

/**
 * Interface DepartmentRoleRepositoryInterface
 *
 * Contract for relational persistence and retrieval of Department and Role organizational entities.
 *
 * @package HrTech\Repositories\Contracts
 * @author Andryus (Membro 2 — CRUD 4: Gestão de Cargos e Departamentos)
 */
interface DepartmentRoleRepositoryInterface
{
    // --- Departments ---
    public function findDepartmentById(string $id, string $tenantId): ?Department;

    public function findDepartmentByCode(string $code, string $tenantId): ?Department;

    /**
     * @return array<int, Department>
     */
    public function findDepartmentsByTenant(string $tenantId, bool $onlyActive = false): array;

    public function saveDepartment(Department $department): bool;

    public function updateDepartment(Department $department): bool;

    public function deleteDepartment(string $id, string $tenantId): bool;

    // --- Roles ---
    public function findRoleById(string $id, ?string $tenantId = null): ?Role;

    /**
     * @return array<int, Role>
     */
    public function findRolesByTenant(string $tenantId): array;

    /**
     * @return array<int, Role>
     */
    public function findRolesByDepartment(string $departmentId, string $tenantId): array;

    public function saveRole(Role $role): bool;

    public function updateRole(Role $role): bool;

    public function deleteRole(string $id, ?string $tenantId = null): bool;
}
