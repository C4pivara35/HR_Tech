<?php

declare(strict_types=1);

namespace HrTech\Services;

use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Enums\BenefitType;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\BenefitRepositoryInterface;

/**
 * Class BenefitService
 *
 * Domain service managing corporate benefits packages, legal copay constraints (PAT / VT),
 * provider catalogs, and payroll deduction configurations.
 *
 * @package HrTech\Services
 * @author Valentin (Membro 4 — CRUD 8: Gestão de Benefícios)
 */
class BenefitService
{
    public function __construct(
        private readonly BenefitRepositoryInterface $repository
    ) {
    }

    /**
     * Registers a new corporate benefit into the tenant catalog.
     *
     * @throws ValidationException
     */
    public function createBenefit(
        string $id,
        string $tenantId,
        BenefitType|string $type,
        string $name,
        string $provider,
        Money|float|int $value,
        float $copayPercentage = 0.0,
        bool $isDeductible = true
    ): Benefit {
        $benefitType = $type instanceof BenefitType ? $type : BenefitType::from($type);
        $moneyVal = $value instanceof Money ? $value : Money::fromCents((int)round((float)$value * 100));

        $benefit = new Benefit(
            id: $id,
            tenantId: $tenantId,
            type: $benefitType,
            name: $name,
            provider: $provider,
            value: $moneyVal,
            employeeCostSharePercentage: $copayPercentage,
            isDeductible: $isDeductible
        );

        $this->repository->save($benefit);

        return $benefit;
    }

    public function getBenefit(string $id, string $tenantId): ?Benefit
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @return array<int, Benefit>
     */
    public function listBenefits(string $tenantId): array
    {
        return $this->repository->findAllByTenant($tenantId);
    }

    /**
     * @return array<int, Benefit>
     */
    public function listByType(string $tenantId, BenefitType|string $type): array
    {
        return $this->repository->findByType($type, $tenantId);
    }

    public function updatePricing(
        string $id,
        string $tenantId,
        Money|float|int $newValue,
        float $newCopayPercentage
    ): Benefit {
        $benefit = $this->getBenefit($id, $tenantId);
        if ($benefit === null) {
            throw new InvalidOperationException("Benefit '{$id}' not found in tenant '{$tenantId}'.");
        }

        $moneyVal = $newValue instanceof Money ? $newValue : Money::fromCents((int)round((float)$newValue * 100));
        $benefit->updatePricing($moneyVal, $newCopayPercentage);
        $this->repository->update($benefit);

        return $benefit;
    }

    public function deleteBenefit(string $id, string $tenantId): bool
    {
        return $this->repository->delete($id, $tenantId);
    }
}
