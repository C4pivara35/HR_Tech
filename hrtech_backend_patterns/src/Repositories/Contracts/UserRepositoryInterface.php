<?php

declare(strict_types=1);

namespace HrTech\Repositories\Contracts;

use HrTech\Domain\Entities\User;

/**
 * Interface UserRepositoryInterface
 *
 * Contract for relational persistence and retrieval of User entities under tenant isolation.
 *
 * @package HrTech\Repositories\Contracts
 * @author Fernando Lopes Duarte (Membro 1 — CRUD 2: Gestão de Usuários e RBAC)
 */
interface UserRepositoryInterface
{
    public function findById(string $id, string $tenantId): ?User;

    public function findByUsername(string $username, string $tenantId): ?User;

    public function findByEmail(string $email, string $tenantId): ?User;

    /**
     * @return array<int, User>
     */
    public function findByTenant(string $tenantId, bool $onlyActive = false): array;

    public function save(User $user): bool;

    public function update(User $user): bool;

    public function delete(string $id, string $tenantId): bool;

    public function existsUsername(string $username, string $tenantId, ?string $excludeUserId = null): bool;

    public function existsEmail(string $email, string $tenantId, ?string $excludeUserId = null): bool;
}
