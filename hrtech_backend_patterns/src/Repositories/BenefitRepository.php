<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Enums\BenefitType;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Repositories\Contracts\BenefitRepositoryInterface;
use PDO;

/**
 * Class BenefitRepository
 *
 * SQLite PDO implementation for persistence and retrieval of Benefit catalog packages.
 *
 * @package HrTech\Repositories
 * @author Valentin (Membro 4 — CRUD 8: Gestão de Benefícios)
 */
class BenefitRepository implements BenefitRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?Benefit
    {
        $stmt = $this->pdo->prepare('SELECT * FROM benefits WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, Benefit>
     */
    public function findByType(BenefitType|string $type, string $tenantId): array
    {
        $typeVal = $type instanceof BenefitType ? $type->value : (string)$type;
        $stmt = $this->pdo->prepare(
            'SELECT * FROM benefits WHERE tenant_id = :tenant_id AND type = :type ORDER BY name ASC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':type' => $typeVal]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, Benefit>
     */
    public function findAllByTenant(string $tenantId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM benefits WHERE tenant_id = :tenant_id ORDER BY name ASC');
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(Benefit $benefit): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO benefits (
                id, tenant_id, type, name, provider, value_cents, employee_cost_share_percentage, is_deductible
            ) VALUES (
                :id, :tenant_id, :type, :name, :provider, :value_cents, :employee_cost_share_percentage, :is_deductible
            )'
        );

        return $stmt->execute([
            ':id' => $benefit->getId(),
            ':tenant_id' => $benefit->getTenantId(),
            ':type' => $benefit->getType()->value,
            ':name' => $benefit->getName(),
            ':provider' => $benefit->getProvider(),
            ':value_cents' => $benefit->getValue()->getCents(),
            ':employee_cost_share_percentage' => $benefit->getEmployeeCostSharePercentage(),
            ':is_deductible' => $benefit->isDeductible() ? 1 : 0,
        ]);
    }

    public function update(Benefit $benefit): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE benefits
             SET type = :type,
                 name = :name,
                 provider = :provider,
                 value_cents = :value_cents,
                 employee_cost_share_percentage = :employee_cost_share_percentage,
                 is_deductible = :is_deductible
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $benefit->getId(),
            ':tenant_id' => $benefit->getTenantId(),
            ':type' => $benefit->getType()->value,
            ':name' => $benefit->getName(),
            ':provider' => $benefit->getProvider(),
            ':value_cents' => $benefit->getValue()->getCents(),
            ':employee_cost_share_percentage' => $benefit->getEmployeeCostSharePercentage(),
            ':is_deductible' => $benefit->isDeductible() ? 1 : 0,
        ]);
    }

    public function delete(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM benefits WHERE id = :id AND tenant_id = :tenant_id');
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    private function hydrate(array $row): Benefit
    {
        return new Benefit(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            type: BenefitType::from((string)$row['type']),
            name: (string)$row['name'],
            provider: (string)$row['provider'],
            value: Money::fromCents((int)$row['value_cents']),
            employeeCostSharePercentage: (float)$row['employee_cost_share_percentage'],
            isDeductible: (bool)$row['is_deductible']
        );
    }
}
