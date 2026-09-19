<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\StringableInterface;
use HrTech\Contracts\TenantScopedInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class Role
 *
 * Represents an organizational position or permission set with hierarchical authority.
 */
class Role implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    ArrayableInterface,
    JsonableInterface,
    AuditableInterface,
    StringableInterface,
    JsonSerializable
{
    private string $id;
    private ?string $tenantId;
    private string $name;
    private string $description;
    private int $hierarchyLevel;
    /** @var array<int, string> */
    private array $permissions;
    private ?string $departmentId;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    /**
     * @param string $id Unique role ID.
     * @param ?string $tenantId Tenant identifier if tenant-specific.
     * @param string $name Role title (e.g., 'Senior Software Engineer').
     * @param string $description Detailed description of responsibilities.
     * @param int $hierarchyLevel Hierarchical level from 1 (lowest) to 100 (highest authority).
     * @param array<int, string> $permissions List of granted permission slugs.
     * @param ?string $departmentId Department scope (null if company-wide).
     * @param DateTimeImmutable|string|null $createdAt
     * @param ?int $level Alias for $hierarchyLevel.
     * @throws ValidationException
     */
    public function __construct(
        string $id,
        ?string $tenantId = null,
        string $name = '',
        string $description = '',
        int $hierarchyLevel = 1,
        array $permissions = [],
        ?string $departmentId = null,
        DateTimeImmutable|string|null $createdAt = null,
        ?int $level = null
    ) {
        $this->id = trim($id);

        if ($level !== null) {
            $hierarchyLevel = $level;
        }

        // Handle positional call signature new Role($id, $name, $level, $permissions, ...)
        if (is_int($name)) {
            $hierarchyLevel = $name;
            $name = (string)$tenantId;
            $tenantId = null;
        }

        $this->tenantId = $tenantId !== null ? trim($tenantId) : null;
        $this->name = trim($name);
        $this->description = trim($description);
        $this->hierarchyLevel = $hierarchyLevel;
        $this->permissions = $this->normalizePermissions($permissions);
        $this->departmentId = $departmentId !== null ? trim($departmentId) : null;

        if ($createdAt === null) {
            $this->createdAt = new DateTimeImmutable();
        } elseif (is_string($createdAt)) {
            $this->createdAt = new DateTimeImmutable($createdAt);
        } else {
            $this->createdAt = $createdAt;
        }

        $this->updatedAt = $this->createdAt;
        $this->validate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId ?? '';
    }

    public function belongsToTenant(string $tenantId): bool
    {
        if ($this->tenantId === null) {
            return true; // company-wide/system role applies to all tenants
        }
        return $this->tenantId === trim($tenantId);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHierarchyLevel(): int
    {
        return $this->hierarchyLevel;
    }

    public function getLevel(): int
    {
        return $this->hierarchyLevel;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<int, string>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getDepartmentId(): ?string
    {
        return $this->departmentId;
    }

    public function getDepartmentScope(): ?string
    {
        return $this->departmentId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isDepartmentScoped(): bool
    {
        return $this->departmentId !== null;
    }

    public function isCompanyWide(): bool
    {
        return $this->departmentId === null;
    }

    /**
     * Evaluates whether this role possesses a given permission.
     * Supports exact match, superuser wildcard '*', and prefix wildcards 'prefix.*'.
     */
    public function hasPermission(string $permission): bool
    {
        $target = trim($permission);
        if ($target === '') {
            return false;
        }

        // 1. Superuser wildcard
        if (in_array('*', $this->permissions, true)) {
            return true;
        }

        // 2. Exact match
        if (in_array($target, $this->permissions, true)) {
            return true;
        }

        // 3. Prefix wildcard matching (e.g., 'timelog.*' matches 'timelog.view')
        foreach ($this->permissions as $p) {
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                if (str_starts_with($target, $prefix . '.') || $target === $prefix) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determines whether this role has superior authority over another role.
     */
    public function canManage(Role $otherRole): bool
    {
        return $this->hierarchyLevel > $otherRole->getHierarchyLevel();
    }

    public function setName(string $name): void
    {
        $clean = trim($name);
        if ($clean === '') {
            throw ValidationException::forField('name', 'Role name cannot be empty.');
        }
        $this->name = $clean;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setHierarchyLevel(int $level): void
    {
        if ($level < 1 || $level > 100) {
            throw ValidationException::forField('hierarchy_level', "Role hierarchy level must be between 1 and 100, got: {$level}.");
        }
        $this->hierarchyLevel = $level;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setLevel(int $level): void
    {
        $this->setHierarchyLevel($level);
    }

    public function setDescription(string $description): void
    {
        $this->description = trim($description);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setDepartmentId(?string $departmentId): void
    {
        $this->departmentId = $departmentId !== null ? trim($departmentId) : null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addPermission(string $permission): void
    {
        $clean = trim($permission);
        if ($clean === '') {
            throw ValidationException::forField('permission', 'Permission slug cannot be empty.');
        }

        if (!in_array($clean, $this->permissions, true)) {
            $this->permissions[] = $clean;
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function removePermission(string $permission): void
    {
        $clean = trim($permission);
        $key = array_search($clean, $this->permissions, true);
        if ($key !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    /**
     * @param array<int, string> $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions = $this->normalizePermissions($permissions);
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = [];

        if ($this->id === '') {
            $errors['id'][] = 'Role ID cannot be empty.';
        }

        if ($this->name === '') {
            $errors['name'][] = 'Role name cannot be empty.';
        } elseif (mb_strlen($this->name) < 2) {
            $errors['name'][] = 'Role name must contain at least 2 characters.';
        }

        if ($this->hierarchyLevel < 1 || $this->hierarchyLevel > 100) {
            $errors['hierarchy_level'][] = "Role hierarchy level must be between 1 and 100, {$this->hierarchyLevel} given.";
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'Role validation failed.');
        }
    }

    public function isValid(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'hierarchy_level' => $this->hierarchyLevel,
            'level' => $this->hierarchyLevel,
            'permissions' => $this->permissions,
            'department_id' => $this->departmentId,
            'is_department_scoped' => $this->isDepartmentScoped(),
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(DateTimeInterface::ATOM),
        ];
    }

    public function toJson(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getAuditIdentifier(): string
    {
        return $this->id;
    }

    public function getAuditCategory(): string
    {
        return 'Role';
    }

    public function toAuditArray(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return sprintf(
            'Role[%s] %s (Level: %d, Perms: %d)',
            $this->id,
            $this->name,
            $this->hierarchyLevel,
            count($this->permissions)
        );
    }

    /**
     * @param array<int, mixed> $permissions
     * @return array<int, string>
     */
    private function normalizePermissions(array $permissions): array
    {
        $normalized = [];
        foreach ($permissions as $p) {
            $clean = trim((string)$p);
            if ($clean !== '') {
                $normalized[$clean] = $clean;
            }
        }
        return array_values($normalized);
    }
}
