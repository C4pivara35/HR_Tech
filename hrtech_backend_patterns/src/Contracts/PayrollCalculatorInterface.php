<?php

declare(strict_types=1);

namespace HrTech\Contracts;

use HrTech\Domain\Entities\Employee;

/**
 * Interface PayrollCalculatorInterface
 *
 * Contract for processing payroll for employees across different contract types.
 */
interface PayrollCalculatorInterface
{
    /**
     * Executes the end-to-end payroll calculation algorithm for the given employee.
     *
     * @param Employee $employee The employee whose payroll is being calculated.
     * @param array<string, mixed> $payrollData Input data (worked hours, overtime, bonuses, deductions).
     * @return array<string, mixed> Detailed payslip data structure.
     */
    public function calculatePayroll(Employee $employee, array $payrollData): array;
}
