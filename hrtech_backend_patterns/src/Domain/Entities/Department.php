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
 * Class Department
 *
 * Represents an organizational department, cost center, and managerial hierarchy.
 */
class Department implements
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
    private string $tenantId;
    private string $code;
    private string $name;
    private string $costCenter;
    private ?string $managerId;
    private ?string $parentDepartmentId;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    /**
     * @param string $id Unique department ID.
     * @param string $tenantId Multi-tenant boundary.
     * @param string $code Alphanumeric code (e.g., 'TECH-01').
     * @param string $name Full departmental name.
     * @param string $costCenter Financial cost center (e.g., 'CC-1010').
     * @param ?string $managerId Optional Employee ID of the department manager.
     * @param ?string $parentDepartmentId Optional parent department ID for hierarchy trees.
     * @param bool $isActive Operational status.
     * @param DateTimeImmutable|string|null $createdAt
     * @throws ValidationException
     */
    public function __construct(
        string $id,
        string $tenantId,
        string $code,
        string $name,
        string $costCenter,
        ?string $managerId = null,
        ?string $parentDepartmentId = null,
        bool $isActive = true,
        DateTimeImmutable|string|null $createdAt = null
    ) {
        $this->id = trim($id);
        $this->tenantId = trim($tenantId);
        $this->code = strtoupper(trim($code));
        $this->name = trim($name);
        $this->costCenter = strtoupper(trim($costCenter));
        $this->managerId = $managerId !== null && trim($managerId) !== '' ? trim($managerId) : null;
        $this->parentDepartmentId = $parentDepartmentId !== null && trim($parentDepartmentId) !== '' ? trim($parentDepartmentId) : null;
        $this->isActive = $isActive;

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
        return $this->tenantId;
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenantId === trim($tenantId);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCostCenter(): string
    {
        return $this->costCenter;
    }

    public function getManagerId(): ?string
    {
        return $this->managerId;
    }

    public function getParentDepartmentId(): ?string
    {
        return $this->parentDepartmentId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasManager(): bool
    {
        return $this->managerId !== null && $this->managerId !== '';
    }

    public function isSubDepartment(): bool
    {
        return $this->parentDepartmentId !== null;
    }

    /**
     * Assigns a manager to this department.
     */
    public function assignManager(string $employeeId): void
    {
        $cleaned = trim($employeeId);
        if ($cleaned === '') {
            throw ValidationException::forField('manager_id', 'Manager Employee ID cannot be empty.');
        }

        $this->managerId = $cleaned;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Removes the assigned department manager.
     */
    public function removeManager(): void
    {
        $this->managerId = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setCode(string $code): void
    {
        $cleaned = strtoupper(trim($code));
        if ($cleaned === '') {
            throw ValidationException::forField('code', 'Department code cannot be empty.');
        }
        $this->code = $cleaned;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setName(string $name): void
    {
        $cleaned = trim($name);
        if ($cleaned === '') {
            throw ValidationException::forField('name', 'Department name cannot be empty.');
        }
        $this->name = $cleaned;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setCostCenter(string $costCenter): void
    {
        $cleaned = strtoupper(trim($costCenter));
        if ($cleaned === '') {
            throw ValidationException::forField('cost_center', 'Cost center cannot be empty.');
        }
        $this->costCenter = $cleaned;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setParentDepartmentId(?string $parentId): void
    {
        $this->parentDepartmentId = $parentId !== null ? trim($parentId) : null;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = [];

        if ($this->id === '') {
            $errors['id'][] = 'Department ID cannot be empty.';
        }

        if ($this->tenantId === '') {
            $errors['tenant_id'][] = 'Tenant ID cannot be empty.';
        }

        if ($this->code === '') {
            $errors['code'][] = 'Department code cannot be empty.';
        } elseif (!preg_match('/^[A-Z0-9._-]{2,30}$/', $this->code)) {
            $errors['code'][] = "Department code '{$this->code}' is invalid. Must be 2-30 uppercase alphanumeric characters.";
        }

        if ($this->name === '') {
            $errors['name'][] = 'Department name cannot be empty.';
        } elseif (mb_strlen($this->name) < 2) {
            $errors['name'][] = 'Department name must contain at least 2 characters.';
        }

        if ($this->costCenter === '') {
            $errors['cost_center'][] = 'Cost center cannot be empty.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'Department validation failed.');
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
            'code' => $this->code,
            'name' => $this->name,
            'cost_center' => $this->costCenter,
            'manager_id' => $this->managerId,
            'parent_department_id' => $this->parentDepartmentId,
            'is_active' => $this->isActive,
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
        return 'Department';
    }

    public function toAuditArray(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return sprintf(
            'Department[%s] %s (Cost Center: %s)',
            $this->code,
            $this->name,
            $this->costCenter
        );
    }
}
