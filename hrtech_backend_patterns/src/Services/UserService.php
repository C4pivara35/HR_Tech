<?php

declare(strict_types=1);

namespace HrTech\Services;

use HrTech\Domain\Entities\User;
use HrTech\Domain\Enums\UserRole;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\UserRepositoryInterface;

/**
 * Class UserService
 *
 * Domain service orchestrating user credentials, RBAC roles, multi-factor authentication,
 * and tenant-isolated user accounts.
 *
 * @package HrTech\Services
 * @author Fernando Lopes Duarte (Membro 1 — CRUD 2: Gestão de Usuários e RBAC)
 */
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository
    ) {
    }

    /**
     * Creates and persists a new User under a specific tenant.
     *
     * @throws ValidationException
     */
    public function createUser(
        string $id,
        string $tenantId,
        string $username,
        string $email,
        string $plainPassword,
        UserRole|string $role,
        bool $mfaEnabled = false,
        ?string $employeeId = null
    ): User {
        $cleanUsername = trim($username);
        $cleanEmail = strtolower(trim($email));

        if ($this->repository->existsUsername($cleanUsername, $tenantId)) {
            throw ValidationException::forField('username', "Username '{$cleanUsername}' is already taken in this tenant.");
        }

        if ($this->repository->existsEmail($cleanEmail, $tenantId)) {
            throw ValidationException::forField('email', "Email '{$cleanEmail}' is already registered in this tenant.");
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $user = new User(
            id: $id,
            tenantId: $tenantId,
            username: $cleanUsername,
            email: $cleanEmail,
            passwordHash: $passwordHash,
            role: $role,
            isActive: true,
            mfaEnabled: $mfaEnabled,
            employeeId: $employeeId
        );

        $this->repository->save($user);

        return $user;
    }

    /**
     * Authenticates user credentials within a specific tenant context.
     */
    public function authenticate(string $username, string $plainPassword, string $tenantId): ?User
    {
        $user = $this->repository->findByUsername(trim($username), $tenantId);
        if ($user === null || !$user->isActive()) {
            return null;
        }

        if (!$user->verifyPassword($plainPassword)) {
            return null;
        }

        $user->recordLogin();
        $this->repository->update($user);

        return $user;
    }

    public function getUserById(string $id, string $tenantId): ?User
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @return array<int, User>
     */
    public function listUsersByTenant(string $tenantId, bool $onlyActive = false): array
    {
        return $this->repository->findByTenant($tenantId, $onlyActive);
    }

    /**
     * Changes user password verifying the previous password.
     *
     * @throws InvalidOperationException
     */
    public function changePassword(string $id, string $tenantId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->getUserById($id, $tenantId);
        if ($user === null) {
            throw new InvalidOperationException("User '{$id}' not found in tenant '{$tenantId}'.");
        }

        if (!$user->verifyPassword($currentPassword)) {
            throw new InvalidOperationException('Current password does not match.');
        }

        $user->setPassword($newPassword);
        return $this->repository->update($user);
    }

    public function updateRole(string $id, string $tenantId, UserRole|string $newRole): User
    {
        $user = $this->getUserById($id, $tenantId);
        if ($user === null) {
            throw new InvalidOperationException("User '{$id}' not found in tenant '{$tenantId}'.");
        }

        $user->setRole($newRole);
        $this->repository->update($user);

        return $user;
    }

    public function toggleMfa(string $id, string $tenantId, bool $enabled): User
    {
        $user = $this->getUserById($id, $tenantId);
        if ($user === null) {
            throw new InvalidOperationException("User '{$id}' not found in tenant '{$tenantId}'.");
        }

        $user->toggleMfa($enabled);
        $this->repository->update($user);

        return $user;
    }

    public function linkEmployee(string $id, string $tenantId, ?string $employeeId): User
    {
        $user = $this->getUserById($id, $tenantId);
        if ($user === null) {
            throw new InvalidOperationException("User '{$id}' not found in tenant '{$tenantId}'.");
        }

        $user->linkEmployee($employeeId);
        $this->repository->update($user);

        return $user;
    }

    public function toggleActive(string $id, string $tenantId, bool $active): User
    {
        $user = $this->getUserById($id, $tenantId);
        if ($user === null) {
            throw new InvalidOperationException("User '{$id}' not found in tenant '{$tenantId}'.");
        }

        if ($active) {
            $user->activate();
        } else {
            $user->deactivate();
        }
        $this->repository->update($user);

        return $user;
    }

    public function deleteUser(string $id, string $tenantId): bool
    {
        return $this->repository->delete($id, $tenantId);
    }
}
