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
use HrTech\Domain\Enums\UserRole;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class User
 *
 * Authentication principal and RBAC identity within a tenant.
 */
class User implements
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
    private string $username;
    private string $email;
    private string $passwordHash;
    private UserRole $role;
    private bool $isActive;
    private bool $mfaEnabled;
    private ?string $employeeId;
    private ?DateTimeImmutable $lastLoginAt;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    /**
     * @param string $id
     * @param string $tenantId
     * @param string $username
     * @param string $email
     * @param string $passwordHash Existing hash or plain password to hash.
     * @param UserRole|string $role
     * @param bool $isActive
     * @param bool $mfaEnabled
     * @param ?string $employeeId Optional link to an Employee record.
     * @param DateTimeImmutable|string|null $lastLoginAt
     * @param DateTimeImmutable|string|null $createdAt
     * @throws ValidationException
     */
    public function __construct(
        string $id,
        string $tenantId,
        string $username,
        string $email,
        string $passwordHash,
        UserRole|string $role,
        bool $isActive = true,
        bool $mfaEnabled = false,
        ?string $employeeId = null,
        DateTimeImmutable|string|null $lastLoginAt = null,
        DateTimeImmutable|string|null $createdAt = null
    ) {
        $this->id = trim($id);
        $this->tenantId = trim($tenantId);
        $this->username = trim($username);
        $this->email = strtolower(trim($email));

        // Intelligent password hash detection
        $info = password_get_info($passwordHash);
        if ($info['algo'] !== null && $info['algo'] !== 0) {
            $this->passwordHash = $passwordHash;
        } else {
            $this->passwordHash = password_hash($passwordHash, PASSWORD_DEFAULT);
        }

        if (is_string($role)) {
            $resolvedRole = UserRole::tryFrom($role);
            if ($resolvedRole === null) {
                throw ValidationException::forField('role', "Invalid user role '{$role}'.");
            }
            $this->role = $resolvedRole;
        } else {
            $this->role = $role;
        }

        $this->isActive = $isActive;
        $this->mfaEnabled = $mfaEnabled;
        $this->employeeId = $employeeId !== null ? trim($employeeId) : null;

        if ($lastLoginAt === null) {
            $this->lastLoginAt = null;
        } elseif (is_string($lastLoginAt)) {
            $this->lastLoginAt = new DateTimeImmutable($lastLoginAt);
        } else {
            $this->lastLoginAt = $lastLoginAt;
        }

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

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isMfaEnabled(): bool
    {
        return $this->mfaEnabled;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Verifies plain-text password against stored bcrypt/argon2 hash.
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * Authenticates user: requires active status and valid password.
     */
    public function authenticate(string $plainPassword): bool
    {
        if (!$this->isActive) {
            return false;
        }
        return $this->verifyPassword($plainPassword);
    }

    /**
     * Updates password to a new plain value (minimum 8 characters).
     */
    public function updatePassword(string $newPlainPassword): void
    {
        if (mb_strlen($newPlainPassword) < 8) {
            throw ValidationException::forField('password', 'Password must contain at least 8 characters.');
        }

        $this->passwordHash = password_hash($newPlainPassword, PASSWORD_DEFAULT);
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Changes password requiring current password verification.
     */
    public function changePassword(string $currentPassword, string $newPassword): bool
    {
        if (!$this->verifyPassword($currentPassword)) {
            return false;
        }

        $this->updatePassword($newPassword);
        return true;
    }

    /**
     * Records a successful login event.
     */
    public function recordLogin(?DateTimeImmutable $at = null): void
    {
        $this->lastLoginAt = $at ?? new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function enableMfa(): void
    {
        $this->mfaEnabled = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function disableMfa(): void
    {
        $this->mfaEnabled = false;
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

    public function changeRole(UserRole $newRole): void
    {
        $this->role = $newRole;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function linkEmployee(string $employeeId): void
    {
        $cleaned = trim($employeeId);
        if ($cleaned === '') {
            throw ValidationException::forField('employee_id', 'Employee ID cannot be empty.');
        }
        $this->employeeId = $cleaned;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function unlinkEmployee(): void
    {
        $this->employeeId = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Checks if this user's role has authority to manage another user.
     */
    public function canManage(User $otherUser): bool
    {
        return $this->role->canManage($otherUser->getRole());
    }

    /**
     * Checks if this user has HR or administrative role.
     */
    public function isHr(): bool
    {
        return $this->role->isHr();
    }

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = [];

        if ($this->id === '') {
            $errors['id'][] = 'User ID cannot be empty.';
        }

        if ($this->tenantId === '') {
            $errors['tenant_id'][] = 'Tenant ID cannot be empty.';
        }

        if ($this->username === '') {
            $errors['username'][] = 'Username cannot be empty.';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $this->username)) {
            $errors['username'][] = "Username '{$this->username}' is invalid. Must be 3-50 alphanumeric characters.";
        }

        if ($this->email === '') {
            $errors['email'][] = 'Email cannot be empty.';
        } elseif (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = "Email format '{$this->email}' is invalid.";
        }

        if ($this->passwordHash === '') {
            $errors['password'][] = 'Password hash cannot be empty.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'User validation failed.');
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
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'role_hierarchy' => $this->role->hierarchyLevel(),
            'is_active' => $this->isActive,
            'mfa_enabled' => $this->mfaEnabled,
            'employee_id' => $this->employeeId,
            'last_login_at' => $this->lastLoginAt?->format(DateTimeInterface::ATOM),
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
        return 'User';
    }

    public function toAuditArray(): array
    {
        $audit = $this->toArray();
        $audit['password_hash'] = '[REDACTED]';
        return $audit;
    }

    public function __toString(): string
    {
        return sprintf(
            'User[%s] %s <%s> (%s)',
            $this->id,
            $this->username,
            $this->email,
            $this->role->value
        );
    }
}
