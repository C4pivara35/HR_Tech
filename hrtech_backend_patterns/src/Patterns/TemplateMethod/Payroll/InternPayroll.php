<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Exceptions\ValidationException;

/**
 * Class InternPayroll
 *
 * Payroll calculator for academic internship contracts governed by Lei do Estágio (Lei 11.788/2008).
 * Processes Bolsa-auxílio and mandatory auxiliary transportation grant while enforcing
 * strict statutory exemption from INSS, IRRF, and mandatory CLT benefit discounts.
 */
class InternPayroll extends PayrollCalculatorTemplate
{
    /**
     * @var array<string, mixed>
     */
    private array $currentPayrollData = [];

    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        $this->currentPayrollData = $payrollData;
    }

    /**
     * Validates that employee is active and enrolled under an Internship contract.
     *
     * @throws ValidationException
     */
    protected function validateEmployee(Employee $employee): void
    {
        if (!$employee->isActive()) {
            throw ValidationException::forField('is_active', 'Cannot process payroll for inactive intern.');
        }

        if ($employee->getEmploymentType() !== EmploymentType::INTERN) {
            throw ValidationException::forField(
                'employment_type',
                sprintf(
                    "InternPayroll requires INTERN contract, got '%s'.",
                    $employee->getEmploymentType()->value
                )
            );
        }
    }

    /**
     * Calculates gross internship remuneration: Bolsa-auxílio plus auxiliary transportation allowance.
     */
    protected function calculateGrossSalary(Employee $employee, array $payrollData): float
    {
        $stipend = (float)($payrollData['grant_stipend']
            ?? $payrollData['bolsa_auxilio']
            ?? $payrollData['gross_salary']
            ?? $employee->getBaseSalary()->getAmount());

        $transportAllowance = (float)($payrollData['transport_allowance']
            ?? $payrollData['auxilio_transporte']
            ?? 0.0);

        $gross = $stipend + $transportAllowance;

        return round(max(0.0, $gross), 2);
    }

    /**
     * INSS is strictly zero under Art. 12 of Lei 11.788/2008 (no employment relationship).
     */
    protected function calculateInss(float $grossSalary): float
    {
        return 0.0;
    }

    /**
     * IRRF is strictly zero/exempt under Lei do Estágio provisions.
     */
    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float
    {
        return 0.0;
    }

    /**
     * Auxiliary transport allowance cannot be deducted from intern stipend (Lei 11.788/2008 Art. 12).
     */
    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float
    {
        return (float)($this->currentPayrollData['benefits_discount'] ?? 0.0);
    }

    /**
     * Augments payslip receipt with stipend and transport allowance breakdown details.
     */
    protected function generatePayslip(Employee $employee, array $breakdown): array
    {
        $breakdown['bolsa_auxilio'] = round(
            (float)($this->currentPayrollData['grant_stipend']
                ?? $this->currentPayrollData['bolsa_auxilio']
                ?? $employee->getBaseSalary()->getAmount()),
            2
        );
        $breakdown['transport_allowance'] = round(
            (float)($this->currentPayrollData['transport_allowance']
                ?? $this->currentPayrollData['auxilio_transporte']
                ?? 0.0),
            2
        );
        $breakdown['legal_framework'] = 'Lei 11.788/2008 (Lei do Estágio)';

        return parent::generatePayslip($employee, $breakdown);
    }

    /**
     * Returns human-readable contract type identifier for payslip generation.
     */
    protected function getContractTypeName(): string
    {
        return 'INTERN';
    }
}
