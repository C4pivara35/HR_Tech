<?php

declare(strict_types=1);

namespace HrTech\Services;

use HrTech\Domain\Entities\Department;
use HrTech\Domain\Entities\Role;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\DepartmentRoleRepositoryInterface;

/**
 * Class DepartmentRoleService
 *
 * Domain service managing departmental tree hierarchies, cost centers,
 * job roles, permissions, and organizational governance.
 *
 * @package HrTech\Services
 * @author Andryus (Membro 2 — CRUD 4: Gestão de Cargos e Departamentos)
 */
class DepartmentRoleService
{
    public function __construct(
        private readonly DepartmentRoleRepositoryInterface $repository
    ) {
    }

    // ==========================================
    // DEPARTMENTS
    // ==========================================

    public function createDepartment(
        string $id,
        string $tenantId,
        string $code,
        string $name,
        string $costCenter,
        ?string $managerId = null,
        ?string $parentDepartmentId = null
    ): Department {
        $cleanCode = strtoupper(trim($code));

        $existing = $this->repository->findDepartmentByCode($cleanCode, $tenantId);
        if ($existing !== null) {
            throw ValidationException::forField('code', "Department code '{$cleanCode}' already exists in this tenant.");
        }

        $department = new Department(
            id: $id,
            tenantId: $tenantId,
            code: $cleanCode,
            name: $name,
            costCenter: $costCenter,
            managerId: $managerId,
            parentDepartmentId: $parentDepartmentId,
            isActive: true
        );

        $this->repository->saveDepartment($department);

        return $department;
    }

    public function getDepartment(string $id, string $tenantId): ?Department
    {
        return $this->repository->findDepartmentById($id, $tenantId);
    }

    /**
     * @return array<int, Department>
     */
    public function listDepartments(string $tenantId, bool $onlyActive = false): array
    {
        return $this->repository->findDepartmentsByTenant($tenantId, $onlyActive);
    }

    public function updateDepartment(
        string $id,
        string $tenantId,
        string $code,
        string $name,
        string $costCenter,
        ?string $managerId = null,
        ?string $parentDepartmentId = null
    ): Department {
        $department = $this->getDepartment($id, $tenantId);
        if ($department === null) {
            throw new InvalidOperationException("Department '{$id}' not found in tenant '{$tenantId}'.");
        }

        if ($parentDepartmentId === $id) {
            throw new InvalidOperationException("Department cannot be its own parent.");
        }

        $department->updateDetails($code, $name, $costCenter);
        if ($managerId !== null) {
            $department->assignManager($managerId);
        }
        if ($parentDepartmentId !== null) {
            $department->setParentDepartment($parentDepartmentId);
        }

        $this->repository->updateDepartment($department);

        return $department;
    }

    public function assignDepartmentManager(string $id, string $tenantId, string $managerId): Department
    {
        $department = $this->getDepartment($id, $tenantId);
        if ($department === null) {
            throw new InvalidOperationException("Department '{$id}' not found in tenant '{$tenantId}'.");
        }

        $department->assignManager($managerId);
        $this->repository->updateDepartment($department);

        return $department;
    }

    public function deleteDepartment(string $id, string $tenantId): bool
    {
        return $this->repository->deleteDepartment($id, $tenantId);
    }

    // ==========================================
    // ROLES
    // ==========================================

    /**
     * @param array<int, string> $permissions
     */
    public function createRole(
        string $id,
        ?string $tenantId,
        string $name,
        string $description = '',
        int $hierarchyLevel = 1,
        array $permissions = [],
        ?string $departmentId = null
    ): Role {
        $role = new Role(
            id: $id,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            hierarchyLevel: $hierarchyLevel,
            permissions: $permissions,
            departmentId: $departmentId
        );

        $this->repository->saveRole($role);

        return $role;
    }

    public function getRole(string $id, ?string $tenantId = null): ?Role
    {
        return $this->repository->findRoleById($id, $tenantId);
    }

    /**
     * @return array<int, Role>
     */
    public function listRoles(string $tenantId): array
    {
        return $this->repository->findRolesByTenant($tenantId);
    }

    /**
     * @return array<int, Role>
     */
    public function listRolesByDepartment(string $departmentId, string $tenantId): array
    {
        return $this->repository->findRolesByDepartment($departmentId, $tenantId);
    }

    /**
     * @param array<int, string> $permissions
     */
    public function updateRole(
        string $id,
        ?string $tenantId,
        string $name,
        string $description,
        int $hierarchyLevel,
        array $permissions,
        ?string $departmentId = null
    ): Role {
        $role = $this->getRole($id, $tenantId);
        if ($role === null) {
            throw new InvalidOperationException("Role '{$id}' not found.");
        }

        $role->updateInfo($name, $description, $hierarchyLevel);
        $role->setPermissions($permissions);
        if ($departmentId !== null) {
            $role->assignToDepartment($departmentId);
        }

        $this->repository->updateRole($role);

        return $role;
    }

    public function deleteRole(string $id, ?string $tenantId = null): bool
    {
        return $this->repository->deleteRole($id, $tenantId);
    }
}
