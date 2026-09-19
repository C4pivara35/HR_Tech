<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Exceptions\ValidationException;

/**
 * Class PjPayroll
 *
 * Payroll calculator for legal entity contractors (Pessoa Jurídica - PJ).
 * Implements fixed contract/invoice rate settlement, corporate withholding taxes
 * (IRRF 1.5% and PIS/COFINS/CSLL 4.65% when applicable), and strictly zero CLT labor deductions.
 */
class PjPayroll extends PayrollCalculatorTemplate
{
    public const float STANDARD_IRRF_WITHHOLDING_RATE = 0.015; // 1.5%
    public const float STANDARD_CSRF_WITHHOLDING_RATE = 0.0465; // 4.65% (PIS 0.65% + COFINS 3.0% + CSLL 1.0%)

    /**
     * @var array<string, mixed>
     */
    private array $currentPayrollData = [];

    /**
     * Stored CSRF deduction for current calculation.
     */
    private float $lastCsrfDeduction = 0.0;

    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        $this->currentPayrollData = $payrollData;
        $this->lastCsrfDeduction = 0.0;
    }

    /**
     * Validates that employee is active and bound to a PJ contract.
     *
     * @throws ValidationException
     */
    protected function validateEmployee(Employee $employee): void
    {
        if (!$employee->isActive()) {
            throw ValidationException::forField('is_active', 'Cannot process payroll for inactive PJ contractor.');
        }

        if ($employee->getEmploymentType() !== EmploymentType::PJ) {
            throw ValidationException::forField(
                'employment_type',
                sprintf(
                    "PjPayroll requires PJ contract, got '%s'.",
                    $employee->getEmploymentType()->value
                )
            );
        }
    }

    /**
     * Calculates gross contractor remuneration based on invoice amount or fixed contract fee.
     */
    protected function calculateGrossSalary(Employee $employee, array $payrollData): float
    {
        $invoiceAmount = $payrollData['invoice_amount']
            ?? $payrollData['gross_salary']
            ?? $employee->getBaseSalary()->getAmount();

        return round(max(0.0, (float)$invoiceAmount), 2);
    }

    /**
     * INSS is strictly zero for corporate PJ service contracts (recolhimento via Pro-labore/DAS).
     */
    protected function calculateInss(float $grossSalary): float
    {
        return 0.0;
    }

    /**
     * Calculates corporate withholding IRRF (standard 1.5% for legal entity service provisions).
     */
    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float
    {
        if ($grossSalary <= 0.0) {
            return 0.0;
        }

        $applyIrrf = (bool)($this->currentPayrollData['withhold_irrf'] ?? true);
        if (!$applyIrrf) {
            return 0.0;
        }

        $rate = (float)($this->currentPayrollData['irrf_rate'] ?? self::STANDARD_IRRF_WITHHOLDING_RATE);

        return round($grossSalary * $rate, 2);
    }

    /**
     * Benefits discounts are 0.0 for PJ contractors.
     */
    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float
    {
        return (float)($this->currentPayrollData['benefits_discount'] ?? 0.0);
    }

    /**
     * Calculates PIS/COFINS/CSLL withholding tax (CSRF 4.65%) when applicable.
     */
    public function calculateCsrf(float $grossSalary): float
    {
        $applyCsrf = (bool)($this->currentPayrollData['withhold_csrf'] ?? true);
        if (!$applyCsrf || $grossSalary <= 0.0) {
            return 0.0;
        }

        $rate = (float)($this->currentPayrollData['csrf_rate'] ?? self::STANDARD_CSRF_WITHHOLDING_RATE);

        return round($grossSalary * $rate, 2);
    }

    /**
     * Calculates net contractor payment after IRRF, CSRF (4.65%), and other invoice deductions.
     */
    protected function calculateNetSalary(
        float $grossSalary,
        float $inss,
        float $irrf,
        float $benefitsDiscount,
        array $otherDeductions = []
    ): float {
        $this->lastCsrfDeduction = $this->calculateCsrf($grossSalary);
        $totalOther = array_sum(array_map('floatval', $otherDeductions));

        $net = $grossSalary - $inss - $irrf - $benefitsDiscount - $totalOther - $this->lastCsrfDeduction;

        return round(max(0.0, $net), 2);
    }

    /**
     * Augments standard payslip breakdown with CSRF corporate tax details.
     */
    protected function generatePayslip(Employee $employee, array $breakdown): array
    {
        $breakdown['csrf_deduction'] = $this->lastCsrfDeduction;
        $breakdown['total_withholdings'] = round(
            $breakdown['irrf_deduction'] + $this->lastCsrfDeduction,
            2
        );

        return parent::generatePayslip($employee, $breakdown);
    }

    /**
     * Returns human-readable contract type identifier for payslip generation.
     */
    protected function getContractTypeName(): string
    {
        return 'PJ';
    }
}
