<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\BenefitDiscount;

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\BenefitType;

/**
 * Class MealVoucherStrategy
 *
 * Implements Vale-Refeição/Alimentação employee deduction rules under the
 * Programa de Alimentação do Trabalhador (PAT - Lei Federal 6.321/1976).
 * Enforces the strict legal maximum copayment ceiling of 20% of the monthly benefit face value.
 */
class MealVoucherStrategy implements BenefitDiscountStrategyInterface
{
    public const float PAT_MAX_LEGAL_DEDUCTION_PERCENTAGE = 0.20; // 20% statutory limit

    /**
     * @param float $fixedNominalCopay Optional fixed nominal copayment amount (in BRL)
     */
    public function __construct(private readonly float $fixedNominalCopay = 0.0)
    {
    }

    /**
     * Calculates the deductible employee meal voucher copayment.
     * Guaranteed to never exceed the 20% statutory cap under Lei 6.321/1976.
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float
    {
        if (!$benefit->isDeductible()) {
            return 0.0;
        }

        $benefitValue = $benefit->getValue()->getAmount();
        $patCap = $benefitValue * self::PAT_MAX_LEGAL_DEDUCTION_PERCENTAGE;

        $nominalCopay = $this->fixedNominalCopay > 0.0
            ? $this->fixedNominalCopay
            : $benefit->calculateEmployeeContribution()->getAmount();

        // Capped at 20% legal maximum under PAT
        $discount = min($nominalCopay, $patCap);

        return round(max(0.0, $discount), 2);
    }

    public function getFixedNominalCopay(): float
    {
        return $this->fixedNominalCopay;
    }

    /**
     * Returns the canonical benefit type identifier.
     */
    public function getBenefitType(): string
    {
        return BenefitType::MEAL_VOUCHER->value;
    }
}
