<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\BenefitDiscount;

use DateTimeImmutable;
use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\BenefitType;

/**
 * Class HealthPlanStrategy
 *
 * Implements corporate medical/health insurance copayment calculation.
 * Combines a fixed base copayment fee with an age-bracket progressive percentage discount/surcharge
 * adhering to ANS (Agência Nacional de Saúde Suplementar) age tiers.
 */
class HealthPlanStrategy implements BenefitDiscountStrategyInterface
{
    /**
     * Standard ANS age tiers and corresponding copayment percentage rates.
     *
     * @var array<int, array{max_age: int, rate: float}>
     */
    public const array DEFAULT_AGE_BRACKETS = [
        ['max_age' => 18, 'rate' => 0.00], // 0 - 18: 0%
        ['max_age' => 28, 'rate' => 0.05], // 19 - 28: 5%
        ['max_age' => 38, 'rate' => 0.10], // 29 - 38: 10%
        ['max_age' => 48, 'rate' => 0.15], // 39 - 48: 15%
        ['max_age' => 58, 'rate' => 0.20], // 49 - 58: 20%
        ['max_age' => 999, 'rate' => 0.30], // 59+: 30%
    ];

    /**
     * @param float $fixedBaseCopay Nominal fixed base copayment per employee (if 0.0, uses benefit entity copay)
     * @param array<int, array{max_age: int, rate: float}> $ageBrackets Custom age brackets
     */
    public function __construct(
        private readonly float $fixedBaseCopay = 0.0,
        private readonly array $ageBrackets = self::DEFAULT_AGE_BRACKETS
    ) {
    }

    /**
     * Calculates employee health plan copayment: base copay + (benefitValue * ageBracketRate).
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float
    {
        if (!$benefit->isDeductible()) {
            return 0.0;
        }

        $now = new DateTimeImmutable('now');
        $age = $employee->getBirthDate()->diff($now)->y;

        $baseCopay = $this->fixedBaseCopay > 0.0
            ? $this->fixedBaseCopay
            : $benefit->calculateEmployeeContribution()->getAmount();

        $ageRate = $this->resolveAgeRate($age);
        $ageComponent = $benefit->getValue()->getAmount() * $ageRate;

        $totalDiscount = $baseCopay + $ageComponent;

        return round(max(0.0, $totalDiscount), 2);
    }

    /**
     * Resolves the age-tier copayment rate based on collaborator age.
     */
    public function resolveAgeRate(int $age): float
    {
        foreach ($this->ageBrackets as $bracket) {
            if ($age <= $bracket['max_age']) {
                return (float)$bracket['rate'];
            }
        }

        return 0.30;
    }

    public function getFixedBaseCopay(): float
    {
        return $this->fixedBaseCopay;
    }

    /**
     * Returns the canonical benefit type identifier.
     */
    public function getBenefitType(): string
    {
        return BenefitType::HEALTH_PLAN->value;
    }
}
