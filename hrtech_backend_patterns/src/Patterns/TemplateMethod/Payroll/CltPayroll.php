<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\ValidationException;

/**
 * Class CltPayroll
 *
 * Payroll calculator for standard CLT employees subject to Brazilian labor legislation.
 * Implements progressive INSS brackets, progressive IRRF with dependent deductions,
 * and benefits copayment discounts.
 */
class CltPayroll extends PayrollCalculatorTemplate
{
    /**
     * INSS progressive brackets (2024-2026 standard Brazilian social security table).
     *
     * @var array<int, array{limit: float, rate: float}>
     */
    public const array INSS_BRACKETS = [
        ['limit' => 1412.00, 'rate' => 0.075],
        ['limit' => 2666.68, 'rate' => 0.09],
        ['limit' => 4000.03, 'rate' => 0.12],
        ['limit' => 7786.02, 'rate' => 0.14],
    ];

    /**
     * IRRF progressive brackets with corresponding deduction parcels.
     *
     * @var array<int, array{limit: float, rate: float, deduction: float}>
     */
    public const array IRRF_BRACKETS = [
        ['limit' => 2259.20, 'rate' => 0.0, 'deduction' => 0.0],
        ['limit' => 2826.65, 'rate' => 0.075, 'deduction' => 169.44],
        ['limit' => 3751.05, 'rate' => 0.15, 'deduction' => 381.44],
        ['limit' => 4664.68, 'rate' => 0.225, 'deduction' => 662.77],
        ['limit' => INF,     'rate' => 0.275, 'deduction' => 896.00],
    ];

    public const float DEPENDENT_DEDUCTION_AMOUNT = 189.59;

    /**
     * Stored payroll data context for current execution.
     *
     * @var array<string, mixed>
     */
    private array $currentPayrollData = [];

    /**
     * Optional registered benefit discount strategies keyed by benefit type.
     *
     * @var array<string, BenefitDiscountStrategyInterface>
     */
    private array $benefitStrategies = [];

    /**
     * @param array<string, BenefitDiscountStrategyInterface> $benefitStrategies
     */
    public function __construct(array $benefitStrategies = [])
    {
        foreach ($benefitStrategies as $strategy) {
            $this->addBenefitStrategy($strategy);
        }
    }

    /**
     * Registers a benefit discount calculation strategy.
     */
    public function addBenefitStrategy(BenefitDiscountStrategyInterface $strategy): self
    {
        $this->benefitStrategies[$strategy->getBenefitType()] = $strategy;
        return $this;
    }

    /**
     * Lifecycle hook: records current payload for use during step executions.
     */
    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        $this->currentPayrollData = $payrollData;
    }

    /**
     * Validates that employee is active and bound to a CLT labor contract.
     *
     * @throws ValidationException
     */
    protected function validateEmployee(Employee $employee): void
    {
        if (!$employee->isActive()) {
            throw ValidationException::forField('is_active', 'Cannot process payroll for inactive employee.');
        }

        if ($employee->getEmploymentType() !== EmploymentType::CLT) {
            throw ValidationException::forField(
                'employment_type',
                sprintf(
                    "CltPayroll requires CLT contract, got '%s'.",
                    $employee->getEmploymentType()->value
                )
            );
        }
    }

    /**
     * Calculates total gross salary including base wage, overtime compensation, bonuses, and additions.
     */
    protected function calculateGrossSalary(Employee $employee, array $payrollData): float
    {
        if (isset($payrollData['gross_salary']) && (float)$payrollData['gross_salary'] > 0.0) {
            return round((float)$payrollData['gross_salary'], 2);
        }

        $base = $employee->getBaseSalary()->getAmount();
        $overtime = (float)($payrollData['overtime_amount'] ?? $payrollData['overtime_pay'] ?? 0.0);
        $bonus = (float)($payrollData['bonus'] ?? $payrollData['bonuses'] ?? 0.0);
        $commission = (float)($payrollData['commission'] ?? $payrollData['commissions'] ?? 0.0);
        $dsr = (float)($payrollData['dsr'] ?? 0.0);
        $hazardPay = (float)($payrollData['hazard_pay'] ?? 0.0);

        $gross = $base + $overtime + $bonus + $commission + $dsr + $hazardPay;

        return round(max(0.0, $gross), 2);
    }

    /**
     * Calculates progressive INSS social security deduction across all legal brackets.
     */
    protected function calculateInss(float $grossSalary): float
    {
        if ($grossSalary <= 0.0) {
            return 0.0;
        }

        $totalInss = 0.0;
        $prevLimit = 0.0;

        foreach (self::INSS_BRACKETS as $bracket) {
            if ($grossSalary > $prevLimit) {
                $taxableInBracket = min($grossSalary, $bracket['limit']) - $prevLimit;
                $totalInss += $taxableInBracket * $bracket['rate'];
                $prevLimit = $bracket['limit'];
            } else {
                break;
            }
        }

        return round($totalInss, 2);
    }

    /**
     * Calculates progressive IRRF withholding tax with legal dependent allowances.
     */
    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float
    {
        if ($grossSalary <= 0.0) {
            return 0.0;
        }

        $dependentAllowance = max(0, $dependents) * self::DEPENDENT_DEDUCTION_AMOUNT;
        $baseIrrf = max(0.0, $grossSalary - $inssDeduction - $dependentAllowance);

        foreach (self::IRRF_BRACKETS as $bracket) {
            if ($baseIrrf <= $bracket['limit']) {
                $tax = ($baseIrrf * $bracket['rate']) - $bracket['deduction'];
                return round(max(0.0, $tax), 2);
            }
        }

        // Top bracket fallback (> 4664.68)
        $topBracket = end(self::IRRF_BRACKETS);
        $tax = ($baseIrrf * $topBracket['rate']) - $topBracket['deduction'];

        return round(max(0.0, $tax), 2);
    }

    /**
     * Calculates employee benefits copay deductions.
     */
    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float
    {
        // Direct override in payroll data
        if (isset($this->currentPayrollData['benefits_discount'])) {
            return round((float)$this->currentPayrollData['benefits_discount'], 2);
        }

        // Process explicit Benefit entities if passed
        $benefits = $this->currentPayrollData['benefits'] ?? [];
        if (!empty($benefits) && is_array($benefits)) {
            $totalDiscount = 0.0;
            $baseSalaryMoney = Money::fromFloat($grossSalary);

            foreach ($benefits as $benefit) {
                if ($benefit instanceof Benefit) {
                    $benefitType = $benefit->getType()->value;
                    if (isset($this->benefitStrategies[$benefitType])) {
                        $totalDiscount += $this->benefitStrategies[$benefitType]->calculateDiscount($employee, $benefit);
                    } else {
                        $totalDiscount += $benefit->calculateDeductionForSalary($baseSalaryMoney)->getAmount();
                    }
                }
            }

            return round($totalDiscount, 2);
        }

        return 0.0;
    }

    /**
     * Returns human-readable contract type identifier for payslip generation.
     */
    protected function getContractTypeName(): string
    {
        return 'CLT';
    }
}
