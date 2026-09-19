<?php

declare(strict_types=1);

namespace HrTech\Contracts;

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Entities\Benefit;

/**
 * Interface BenefitDiscountStrategyInterface
 *
 * Defines the contract for benefit discount and copayment calculation strategies.
 * Supports statutory caps (VT 6%, PAT 20%) and corporate benefit rules.
 */
interface BenefitDiscountStrategyInterface
{
    /**
     * Calculates the deductible employee contribution for a specific benefit.
     *
     * @param Employee $employee The employee receiving the benefit.
     * @param Benefit $benefit The benefit configuration and values.
     * @return float The discount amount to be deducted from payroll (in BRL).
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float;

    /**
     * Returns the benefit type identifier this strategy applies to.
     *
     * @return string Benefit type identifier (e.g. 'TRANSPORTATION', 'HEALTH_PLAN', 'MEAL_VOUCHER').
     */
    public function getBenefitType(): string;
}
