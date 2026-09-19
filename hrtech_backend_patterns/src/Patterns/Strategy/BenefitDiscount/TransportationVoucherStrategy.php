<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\BenefitDiscount;

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\BenefitType;

/**
 * Class TransportationVoucherStrategy
 *
 * Implements Vale-Transporte statutory deduction under Brazilian Federal Law 7.418/1985.
 * Deducts the employee's statutory salary cap (strictly 6% of base gross monthly wage)
 * or the actual monthly voucher disbursement value, whichever amount is lower.
 */
class TransportationVoucherStrategy implements BenefitDiscountStrategyInterface
{
    public const float STATUTORY_SALARY_CAP_PERCENTAGE = 0.06; // 6%

    /**
     * Calculates the deductible employee contribution for transportation vouchers.
     * Guarantees that employee never pays more than 6% of basic salary or the actual benefit cost.
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float
    {
        if (!$benefit->isDeductible()) {
            return 0.0;
        }

        $baseSalary = $employee->getBaseSalary()->getAmount();
        $salaryCap = $baseSalary * self::STATUTORY_SALARY_CAP_PERCENTAGE;
        $actualVoucherValue = $benefit->getValue()->getAmount();

        // Whichever is lower: 6% salary cap or actual voucher value
        $deduction = min($salaryCap, $actualVoucherValue);

        return round(max(0.0, $deduction), 2);
    }

    /**
     * Returns the canonical benefit type identifier.
     */
    public function getBenefitType(): string
    {
        return BenefitType::TRANSPORTATION->value;
    }
}
