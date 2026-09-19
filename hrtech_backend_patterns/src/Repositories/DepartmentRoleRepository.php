<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\Department;
use HrTech\Domain\Entities\Role;
use HrTech\Repositories\Contracts\DepartmentRoleRepositoryInterface;
use PDO;

/**
 * Class DepartmentRoleRepository
 *
 * SQLite PDO implementation for persistence and retrieval of Department and Role entities.
 *
 * @package HrTech\Repositories
 * @author Andryus (Membro 2 — CRUD 4: Gestão de Cargos e Departamentos)
 */
class DepartmentRoleRepository implements DepartmentRoleRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    // ==========================================
    // DEPARTMENTS
    // ==========================================

    public function findDepartmentById(string $id, string $tenantId): ?Department
    {
        $stmt = $this->pdo->prepare('SELECT * FROM departments WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrateDepartment($row) : null;
    }

    public function findDepartmentByCode(string $code, string $tenantId): ?Department
    {
        $stmt = $this->pdo->prepare('SELECT * FROM departments WHERE code = :code AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':code' => strtoupper(trim($code)), ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrateDepartment($row) : null;
    }

    /**
     * @return array<int, Department>
     */
    public function findDepartmentsByTenant(string $tenantId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM departments WHERE tenant_id = :tenant_id';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrateDepartment'], $rows);
    }

    public function saveDepartment(Department $department): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO departments (
                id, tenant_id, code, name, cost_center, manager_id, parent_department_id, is_active, created_at, updated_at
            ) VALUES (
                :id, :tenant_id, :code, :name, :cost_center, :manager_id, :parent_department_id, :is_active, :created_at, :updated_at
            )'
        );

        return $stmt->execute([
            ':id' => $department->getId(),
            ':tenant_id' => $department->getTenantId(),
            ':code' => $department->getCode(),
            ':name' => $department->getName(),
            ':cost_center' => $department->getCostCenter(),
            ':manager_id' => $department->getManagerId(),
            ':parent_department_id' => $department->getParentDepartmentId(),
            ':is_active' => $department->isActive() ? 1 : 0,
            ':created_at' => $department->getCreatedAt()->format(DateTimeInterface::ATOM),
            ':updated_at' => $department->getUpdatedAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    public function updateDepartment(Department $department): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE departments
             SET code = :code,
                 name = :name,
                 cost_center = :cost_center,
                 manager_id = :manager_id,
                 parent_department_id = :parent_department_id,
                 is_active = :is_active,
                 updated_at = :updated_at
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $department->getId(),
            ':tenant_id' => $department->getTenantId(),
            ':code' => $department->getCode(),
            ':name' => $department->getName(),
            ':cost_center' => $department->getCostCenter(),
            ':manager_id' => $department->getManagerId(),
            ':parent_department_id' => $department->getParentDepartmentId(),
            ':is_active' => $department->isActive() ? 1 : 0,
            ':updated_at' => ($department->getUpdatedAt() ?? new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ]);
    }

    public function deleteDepartment(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM departments WHERE id = :id AND tenant_id = :tenant_id');
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    // ==========================================
    // ROLES
    // ==========================================

    public function findRoleById(string $id, ?string $tenantId = null): ?Role
    {
        $sql = 'SELECT * FROM roles WHERE id = :id';
        $params = [':id' => $id];

        if ($tenantId !== null) {
            $sql .= ' AND (tenant_id = :tenant_id OR tenant_id IS NULL)';
            $params[':tenant_id'] = $tenantId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? $this->hydrateRole($row) : null;
    }

    /**
     * @return array<int, Role>
     */
    public function findRolesByTenant(string $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM roles WHERE tenant_id = :tenant_id OR tenant_id IS NULL ORDER BY hierarchy_level ASC, name ASC'
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrateRole'], $rows);
    }

    /**
     * @return array<int, Role>
     */
    public function findRolesByDepartment(string $departmentId, string $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM roles WHERE department_id = :department_id AND (tenant_id = :tenant_id OR tenant_id IS NULL) ORDER BY hierarchy_level ASC'
        );
        $stmt->execute([':department_id' => $departmentId, ':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrateRole'], $rows);
    }

    public function saveRole(Role $role): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO roles (
                id, tenant_id, name, description, hierarchy_level, permissions, department_id, created_at, updated_at
            ) VALUES (
                :id, :tenant_id, :name, :description, :hierarchy_level, :permissions, :department_id, :created_at, :updated_at
            )'
        );

        return $stmt->execute([
            ':id' => $role->getId(),
            ':tenant_id' => $role->getTenantId(),
            ':name' => $role->getName(),
            ':description' => $role->getDescription(),
            ':hierarchy_level' => $role->getHierarchyLevel(),
            ':permissions' => json_encode($role->getPermissions(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':department_id' => $role->getDepartmentId(),
            ':created_at' => $role->getCreatedAt()->format(DateTimeInterface::ATOM),
            ':updated_at' => $role->getUpdatedAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    public function updateRole(Role $role): bool
    {
        $sql = 'UPDATE roles
                SET name = :name,
                    description = :description,
                    hierarchy_level = :hierarchy_level,
                    permissions = :permissions,
                    department_id = :department_id,
                    updated_at = :updated_at
                WHERE id = :id';
        $params = [
            ':id' => $role->getId(),
            ':name' => $role->getName(),
            ':description' => $role->getDescription(),
            ':hierarchy_level' => $role->getHierarchyLevel(),
            ':permissions' => json_encode($role->getPermissions(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':department_id' => $role->getDepartmentId(),
            ':updated_at' => ($role->getUpdatedAt() ?? new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ];

        if ($role->getTenantId() !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params[':tenant_id'] = $role->getTenantId();
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteRole(string $id, ?string $tenantId = null): bool
    {
        $sql = 'DELETE FROM roles WHERE id = :id';
        $params = [':id' => $id];

        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    private function hydrateDepartment(array $row): Department
    {
        return new Department(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            code: (string)$row['code'],
            name: (string)$row['name'],
            costCenter: (string)$row['cost_center'],
            managerId: $row['manager_id'] !== null ? (string)$row['manager_id'] : null,
            parentDepartmentId: $row['parent_department_id'] !== null ? (string)$row['parent_department_id'] : null,
            isActive: (bool)$row['is_active'],
            createdAt: new DateTimeImmutable((string)$row['created_at'])
        );
    }

    private function hydrateRole(array $row): Role
    {
        $permissions = json_decode((string)($row['permissions'] ?? '[]'), true) ?? [];
        return new Role(
            id: (string)$row['id'],
            tenantId: $row['tenant_id'] !== null ? (string)$row['tenant_id'] : null,
            name: (string)$row['name'],
            description: (string)$row['description'],
            hierarchyLevel: (int)$row['hierarchy_level'],
            permissions: $permissions,
            departmentId: $row['department_id'] !== null ? (string)$row['department_id'] : null,
            createdAt: new DateTimeImmutable((string)$row['created_at'])
        );
    }
}
