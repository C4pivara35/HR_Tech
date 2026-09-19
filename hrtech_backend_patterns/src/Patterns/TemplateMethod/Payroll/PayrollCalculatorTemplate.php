<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

use HrTech\Contracts\PayrollCalculatorInterface;
use HrTech\Domain\Entities\Employee;
use HrTech\Exceptions\ValidationException;

/**
 * Class PayrollCalculatorTemplate
 *
 * Abstract template method orchestrating the complete payroll calculation lifecycle.
 */
abstract class PayrollCalculatorTemplate implements PayrollCalculatorInterface
{
    /**
     * The Template Method defining the immutable payroll calculation skeleton.
     *
     * @param Employee $employee
     * @param array<string, mixed> $payrollData
     * @return array<string, mixed>
     */
    final public function calculatePayroll(Employee $employee, array $payrollData): array
    {
        $this->beforeCalculation($employee, $payrollData);

        $this->validateEmployee($employee);

        $grossSalary = $this->calculateGrossSalary($employee, $payrollData);
        $inssDeduction = $this->calculateInss($grossSalary);

        $dependents = (int)($payrollData['dependents'] ?? 0);
        $irrfDeduction = $this->calculateIrrf($grossSalary, $inssDeduction, $dependents);

        $benefitsDiscount = $this->applyBenefitsDiscounts($employee, $grossSalary);
        $otherDeductions = (array)($payrollData['other_deductions'] ?? []);

        $netSalary = $this->calculateNetSalary(
            $grossSalary,
            $inssDeduction,
            $irrfDeduction,
            $benefitsDiscount,
            $otherDeductions
        );

        $breakdown = [
            'gross_salary' => $grossSalary,
            'inss_deduction' => $inssDeduction,
            'irrf_deduction' => $irrfDeduction,
            'benefits_discount' => $benefitsDiscount,
            'other_deductions' => $otherDeductions,
            'net_salary' => $netSalary,
            'calculation_date' => date('Y-m-d H:i:s'),
        ];

        $payslip = $this->generatePayslip($employee, $breakdown);

        $this->afterCalculation($employee, $payslip);

        return $payslip;
    }

    /**
     * Optional pre-execution lifecycle hook.
     *
     * @param Employee $employee
     * @param array<string, mixed> $payrollData
     */
    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        // Default no-op
    }

    /**
     * Primitive step: Validates contract and employee eligibility.
     *
     * @param Employee $employee
     * @throws ValidationException
     */
    abstract protected function validateEmployee(Employee $employee): void;

    /**
     * Primitive step: Calculates total gross remuneration.
     *
     * @param Employee $employee
     * @param array<string, mixed> $payrollData
     * @return float
     */
    abstract protected function calculateGrossSalary(Employee $employee, array $payrollData): float;

    /**
     * Primitive step: Calculates social security (INSS) deduction.
     *
     * @param float $grossSalary
     * @return float
     */
    abstract protected function calculateInss(float $grossSalary): float;

    /**
     * Primitive step: Calculates withholding income tax (IRRF) deduction.
     *
     * @param float $grossSalary
     * @param float $inssDeduction
     * @param int $dependents
     * @return float
     */
    abstract protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float;

    /**
     * Primitive step: Calculates benefits copayments and deductions.
     *
     * @param Employee $employee
     * @param float $grossSalary
     * @return float
     */
    abstract protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float;

    /**
     * Default step: Calculates final net salary.
     *
     * @param float $grossSalary
     * @param float $inss
     * @param float $irrf
     * @param float $benefitsDiscount
     * @param array<int|string, mixed> $otherDeductions
     * @return float
     */
    protected function calculateNetSalary(
        float $grossSalary,
        float $inss,
        float $irrf,
        float $benefitsDiscount,
        array $otherDeductions = []
    ): float {
        $totalOther = array_sum(array_map('floatval', $otherDeductions));
        $net = $grossSalary - $inss - $irrf - $benefitsDiscount - $totalOther;
        return round(max(0.0, $net), 2);
    }

    /**
     * Default step: Assembles structured payslip receipt array.
     *
     * @param Employee $employee
     * @param array<string, mixed> $breakdown
     * @return array<string, mixed>
     */
    protected function generatePayslip(Employee $employee, array $breakdown): array
    {
        $id = method_exists($employee, 'getId') ? $employee->getId() : (string)($employee->id ?? '');
        $name = method_exists($employee, 'getName') ? $employee->getName() : (string)($employee->name ?? '');

        return [
            'employee_id' => $id,
            'employee_name' => $name,
            'contract_type' => $this->getContractTypeName(),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Returns the human-readable contract type name for the payslip.
     *
     * @return string
     */
    abstract protected function getContractTypeName(): string;

    /**
     * Optional post-execution lifecycle hook.
     *
     * @param Employee $employee
     * @param array<string, mixed> $payslip
     */
    protected function afterCalculation(Employee $employee, array $payslip): void
    {
        // Default no-op (e.g. AuditLogger integration)
    }
}
