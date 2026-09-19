<?php

declare(strict_types=1);

namespace HrTech\Services;

use DateTimeImmutable;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\EmployeeRepositoryInterface;

/**
 * Class EmployeeService
 *
 * Domain service orchestrating collaborator hiring, salary adjustments, departmental transfers,
 * bank of hours ledger updates, and labor termination.
 *
 * @package HrTech\Services
 * @author Andryus (Membro 2 — CRUD 3: Cadastro de Colaboradores)
 */
class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $repository
    ) {
    }

    /**
     * Hires a new collaborator under CLT or contractual regime.
     *
     * @throws ValidationException
     */
    public function hireEmployee(
        string $id,
        string $tenantId,
        string|Cpf $cpf,
        string $fullName,
        string $email,
        string $phone,
        DateTimeImmutable|string $birthDate,
        DateTimeImmutable|string $admissionDate,
        string $departmentId,
        string $roleId,
        Money|float|int $baseSalary,
        EmploymentType|string $employmentType
    ): Employee {
        $cpfVo = $cpf instanceof Cpf ? $cpf : new Cpf($cpf);

        if ($this->repository->existsCpf($cpfVo, $tenantId)) {
            throw ValidationException::forField('cpf', "Employee with CPF '{$cpfVo->getFormatted()}' already exists in this tenant.");
        }

        $employee = new Employee(
            id: $id,
            tenantId: $tenantId,
            cpf: $cpfVo,
            fullName: $fullName,
            email: $email,
            phone: $phone,
            birthDate: $birthDate,
            admissionDate: $admissionDate,
            departmentId: $departmentId,
            roleId: $roleId,
            baseSalary: $baseSalary,
            employmentType: $employmentType,
            isActive: true,
            vacationBalanceDays: 30,
            bankHoursMinutes: 0
        );

        $this->repository->save($employee);

        return $employee;
    }

    public function getEmployee(string $id, string $tenantId): ?Employee
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, Employee>
     */
    public function listEmployees(string $tenantId, array $filters = []): array
    {
        return $this->repository->findAllByTenant($tenantId, $filters);
    }

    /**
     * @return array<int, Employee>
     */
    public function getEmployeesByDepartment(string $departmentId, string $tenantId): array
    {
        return $this->repository->findByDepartment($departmentId, $tenantId);
    }

    public function adjustSalary(string $id, string $tenantId, Money|float|int $newSalary): Employee
    {
        $employee = $this->getEmployee($id, $tenantId);
        if ($employee === null) {
            throw new InvalidOperationException("Employee '{$id}' not found in tenant '{$tenantId}'.");
        }

        $employee->adjustSalary($newSalary);
        $this->repository->update($employee);

        return $employee;
    }

    public function promoteRole(string $id, string $tenantId, string $newRoleId): Employee
    {
        $employee = $this->getEmployee($id, $tenantId);
        if ($employee === null) {
            throw new InvalidOperationException("Employee '{$id}' not found in tenant '{$tenantId}'.");
        }

        $employee->assignRole($newRoleId);
        $this->repository->update($employee);

        return $employee;
    }

    public function transferDepartment(string $id, string $tenantId, string $newDepartmentId): Employee
    {
        $employee = $this->getEmployee($id, $tenantId);
        if ($employee === null) {
            throw new InvalidOperationException("Employee '{$id}' not found in tenant '{$tenantId}'.");
        }

        $employee->transferDepartment($newDepartmentId);
        $this->repository->update($employee);

        return $employee;
    }

    public function recordBankHours(string $id, string $tenantId, int $deltaMinutes): Employee
    {
        $employee = $this->getEmployee($id, $tenantId);
        if ($employee === null) {
            throw new InvalidOperationException("Employee '{$id}' not found in tenant '{$tenantId}'.");
        }

        if ($deltaMinutes >= 0) {
            $employee->creditBankHours($deltaMinutes);
        } else {
            $employee->debitBankHours(abs($deltaMinutes));
        }

        $this->repository->update($employee);

        return $employee;
    }

    public function terminateEmployee(
        string $id,
        string $tenantId,
        DateTimeImmutable|string|null $terminationDate = null
    ): Employee {
        $employee = $this->getEmployee($id, $tenantId);
        if ($employee === null) {
            throw new InvalidOperationException("Employee '{$id}' not found in tenant '{$tenantId}'.");
        }

        $date = $terminationDate instanceof DateTimeImmutable
            ? $terminationDate
            : new DateTimeImmutable($terminationDate ?? 'now');

        $employee->terminate($date);
        $this->repository->update($employee);

        return $employee;
    }

    public function reactivateEmployee(string $id, string $tenantId): Employee
    {
        $employee = $this->getEmployee($id, $tenantId);
        if ($employee === null) {
            throw new InvalidOperationException("Employee '{$id}' not found in tenant '{$tenantId}'.");
        }

        $employee->reactivate();
        $this->repository->update($employee);

        return $employee;
    }

    public function deleteEmployee(string $id, string $tenantId): bool
    {
        return $this->repository->delete($id, $tenantId);
    }
}
