<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\User;
use HrTech\Repositories\Contracts\UserRepositoryInterface;
use PDO;

/**
 * Class UserRepository
 *
 * SQLite PDO implementation for persistence and retrieval of User entities with multi-tenant scoping.
 *
 * @package HrTech\Repositories
 * @author Fernando Lopes Duarte (Membro 1 — CRUD 2: Gestão de Usuários e RBAC)
 */
class UserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByUsername(string $username, string $tenantId): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':username' => $username, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email, string $tenantId): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email)), ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, User>
     */
    public function findByTenant(string $tenantId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM users WHERE tenant_id = :tenant_id';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, tenant_id, username, email, password_hash, role, is_active, mfa_enabled, employee_id, last_login_at, created_at, updated_at)
             VALUES (:id, :tenant_id, :username, :email, :password_hash, :role, :is_active, :mfa_enabled, :employee_id, :last_login_at, :created_at, :updated_at)'
        );

        return $stmt->execute([
            ':id' => $user->getId(),
            ':tenant_id' => $user->getTenantId(),
            ':username' => $user->getUsername(),
            ':email' => $user->getEmail(),
            ':password_hash' => $user->getPasswordHash(),
            ':role' => $user->getRole()->value,
            ':is_active' => $user->isActive() ? 1 : 0,
            ':mfa_enabled' => $user->isMfaEnabled() ? 1 : 0,
            ':employee_id' => $user->getEmployeeId(),
            ':last_login_at' => $user->getLastLoginAt()?->format(DateTimeInterface::ATOM),
            ':created_at' => $user->getCreatedAt()->format(DateTimeInterface::ATOM),
            ':updated_at' => $user->getUpdatedAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    public function update(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET username = :username,
                 email = :email,
                 password_hash = :password_hash,
                 role = :role,
                 is_active = :is_active,
                 mfa_enabled = :mfa_enabled,
                 employee_id = :employee_id,
                 last_login_at = :last_login_at,
                 updated_at = :updated_at
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $user->getId(),
            ':tenant_id' => $user->getTenantId(),
            ':username' => $user->getUsername(),
            ':email' => $user->getEmail(),
            ':password_hash' => $user->getPasswordHash(),
            ':role' => $user->getRole()->value,
            ':is_active' => $user->isActive() ? 1 : 0,
            ':mfa_enabled' => $user->isMfaEnabled() ? 1 : 0,
            ':employee_id' => $user->getEmployeeId(),
            ':last_login_at' => $user->getLastLoginAt()?->format(DateTimeInterface::ATOM),
            ':updated_at' => ($user->getUpdatedAt() ?? new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ]);
    }

    public function delete(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id AND tenant_id = :tenant_id');
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    public function existsUsername(string $username, string $tenantId, ?string $excludeUserId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE username = :username AND tenant_id = :tenant_id';
        $params = [':username' => $username, ':tenant_id' => $tenantId];

        if ($excludeUserId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeUserId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function existsEmail(string $email, string $tenantId, ?string $excludeUserId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email AND tenant_id = :tenant_id';
        $params = [':email' => strtolower(trim($email)), ':tenant_id' => $tenantId];

        if ($excludeUserId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeUserId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            username: (string)$row['username'],
            email: (string)$row['email'],
            passwordHash: (string)$row['password_hash'],
            role: (string)$row['role'],
            isActive: (bool)$row['is_active'],
            mfaEnabled: (bool)$row['mfa_enabled'],
            employeeId: $row['employee_id'] !== null ? (string)$row['employee_id'] : null,
            lastLoginAt: $row['last_login_at'] !== null ? new DateTimeImmutable((string)$row['last_login_at']) : null,
            createdAt: new DateTimeImmutable((string)$row['created_at'])
        );
    }
}
