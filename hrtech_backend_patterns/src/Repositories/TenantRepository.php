<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\Tenant;
use HrTech\Domain\ValueObjects\Cnpj;
use HrTech\Repositories\Contracts\TenantRepositoryInterface;
use PDO;

/**
 * Class TenantRepository
 *
 * SQLite PDO implementation for persistence and retrieval of Tenant entities.
 *
 * @package HrTech\Repositories
 * @author Fernando Lopes Duarte (Membro 1 — CRUD 1: Gestão de Tenants)
 */
class TenantRepository implements TenantRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id): ?Tenant
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCnpj(string|Cnpj $cnpj): ?Tenant
    {
        $rawCnpj = $cnpj instanceof Cnpj ? $cnpj->getValue() : preg_replace('/\D/', '', $cnpj);
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE cnpj = :cnpj LIMIT 1');
        $stmt->execute([':cnpj' => $rawCnpj]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, Tenant>
     */
    public function findAll(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM tenants';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(Tenant $tenant): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at, updated_at)
             VALUES (:id, :cnpj, :corporate_name, :trading_name, :segment, :is_active, :module_licenses, :created_at, :updated_at)'
        );

        return $stmt->execute([
            ':id' => $tenant->getId(),
            ':cnpj' => $tenant->getCnpj()->getValue(),
            ':corporate_name' => $tenant->getCorporateName(),
            ':trading_name' => $tenant->getTradingName(),
            ':segment' => $tenant->getSegment(),
            ':is_active' => $tenant->isActive() ? 1 : 0,
            ':module_licenses' => json_encode($tenant->getModuleLicenses(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':created_at' => $tenant->getCreatedAt()->format(DateTimeInterface::ATOM),
            ':updated_at' => $tenant->getUpdatedAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    public function update(Tenant $tenant): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tenants
             SET cnpj = :cnpj,
                 corporate_name = :corporate_name,
                 trading_name = :trading_name,
                 segment = :segment,
                 is_active = :is_active,
                 module_licenses = :module_licenses,
                 updated_at = :updated_at
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $tenant->getId(),
            ':cnpj' => $tenant->getCnpj()->getValue(),
            ':corporate_name' => $tenant->getCorporateName(),
            ':trading_name' => $tenant->getTradingName(),
            ':segment' => $tenant->getSegment(),
            ':is_active' => $tenant->isActive() ? 1 : 0,
            ':module_licenses' => json_encode($tenant->getModuleLicenses(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':updated_at' => ($tenant->getUpdatedAt() ?? new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ]);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tenants WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function exists(string $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    public function existsCnpj(string|Cnpj $cnpj, ?string $excludeTenantId = null): bool
    {
        $rawCnpj = $cnpj instanceof Cnpj ? $cnpj->getValue() : preg_replace('/\D/', '', $cnpj);
        $sql = 'SELECT 1 FROM tenants WHERE cnpj = :cnpj';
        $params = [':cnpj' => $rawCnpj];

        if ($excludeTenantId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeTenantId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    private function hydrate(array $row): Tenant
    {
        $modules = json_decode((string)($row['module_licenses'] ?? '[]'), true) ?? [];
        $tenant = new Tenant(
            id: (string)$row['id'],
            cnpj: (string)$row['cnpj'],
            corporateName: (string)$row['corporate_name'],
            tradingName: (string)$row['trading_name'],
            isActive: (bool)$row['is_active'],
            moduleLicenses: $modules,
            createdAt: new DateTimeImmutable((string)$row['created_at']),
            segment: (string)($row['segment'] ?? 'tech')
        );

        return $tenant;
    }
}
