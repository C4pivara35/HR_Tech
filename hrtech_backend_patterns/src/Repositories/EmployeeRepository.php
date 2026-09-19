<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Repositories\Contracts\EmployeeRepositoryInterface;
use PDO;

/**
 * Class EmployeeRepository
 *
 * SQLite PDO implementation for persistence and retrieval of Employee entities.
 *
 * @package HrTech\Repositories
 * @author Andryus (Membro 2 — CRUD 3: Cadastro de Colaboradores)
 */
class EmployeeRepository implements EmployeeRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?Employee
    {
        $stmt = $this->pdo->prepare('SELECT * FROM employees WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCpf(string|Cpf $cpf, string $tenantId): ?Employee
    {
        $rawCpf = $cpf instanceof Cpf ? $cpf->getValue() : preg_replace('/\D/', '', $cpf);
        $stmt = $this->pdo->prepare('SELECT * FROM employees WHERE cpf = :cpf AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':cpf' => $rawCpf, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email, string $tenantId): ?Employee
    {
        $stmt = $this->pdo->prepare('SELECT * FROM employees WHERE email = :email AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email)), ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, Employee>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array
    {
        $sql = 'SELECT * FROM employees WHERE tenant_id = :tenant_id';
        $params = [':tenant_id' => $tenantId];

        if (isset($filters['department_id'])) {
            $sql .= ' AND department_id = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }

        if (isset($filters['role_id'])) {
            $sql .= ' AND role_id = :role_id';
            $params[':role_id'] = $filters['role_id'];
        }

        if (isset($filters['is_active'])) {
            $sql .= ' AND is_active = :is_active';
            $params[':is_active'] = $filters['is_active'] ? 1 : 0;
        }

        if (isset($filters['employment_type'])) {
            $sql .= ' AND employment_type = :employment_type';
            $params[':employment_type'] = (string)$filters['employment_type'];
        }

        $sql .= ' ORDER BY full_name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, Employee>
     */
    public function findByDepartment(string $departmentId, string $tenantId): array
    {
        return $this->findAllByTenant($tenantId, ['department_id' => $departmentId]);
    }

    public function save(Employee $employee): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO employees (
                id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date,
                termination_date, department_id, role_id, base_salary_cents, employment_type,
                is_active, vacation_days_balance, bank_hours_balance, created_at, updated_at
            ) VALUES (
                :id, :tenant_id, :cpf, :full_name, :email, :phone, :birth_date, :admission_date,
                :termination_date, :department_id, :role_id, :base_salary_cents, :employment_type,
                :is_active, :vacation_days_balance, :bank_hours_balance, :created_at, :updated_at
            )'
        );

        return $stmt->execute([
            ':id' => $employee->getId(),
            ':tenant_id' => $employee->getTenantId(),
            ':cpf' => $employee->getCpf()->getValue(),
            ':full_name' => $employee->getFullName(),
            ':email' => $employee->getEmail(),
            ':phone' => $employee->getPhone(),
            ':birth_date' => $employee->getBirthDate()->format('Y-m-d'),
            ':admission_date' => $employee->getAdmissionDate()->format('Y-m-d'),
            ':termination_date' => $employee->getTerminationDate()?->format('Y-m-d'),
            ':department_id' => $employee->getDepartmentId(),
            ':role_id' => $employee->getRoleId(),
            ':base_salary_cents' => $employee->getBaseSalary()->getCents(),
            ':employment_type' => $employee->getEmploymentType()->value,
            ':is_active' => $employee->isActive() ? 1 : 0,
            ':vacation_days_balance' => $employee->getVacationBalanceDays(),
            ':bank_hours_balance' => $employee->getBankHoursMinutes(),
            ':created_at' => $employee->getCreatedAt()->format(DateTimeInterface::ATOM),
            ':updated_at' => $employee->getUpdatedAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    public function update(Employee $employee): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE employees
             SET full_name = :full_name,
                 email = :email,
                 phone = :phone,
                 birth_date = :birth_date,
                 admission_date = :admission_date,
                 termination_date = :termination_date,
                 department_id = :department_id,
                 role_id = :role_id,
                 base_salary_cents = :base_salary_cents,
                 employment_type = :employment_type,
                 is_active = :is_active,
                 vacation_days_balance = :vacation_days_balance,
                 bank_hours_balance = :bank_hours_balance,
                 updated_at = :updated_at
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $employee->getId(),
            ':tenant_id' => $employee->getTenantId(),
            ':full_name' => $employee->getFullName(),
            ':email' => $employee->getEmail(),
            ':phone' => $employee->getPhone(),
            ':birth_date' => $employee->getBirthDate()->format('Y-m-d'),
            ':admission_date' => $employee->getAdmissionDate()->format('Y-m-d'),
            ':termination_date' => $employee->getTerminationDate()?->format('Y-m-d'),
            ':department_id' => $employee->getDepartmentId(),
            ':role_id' => $employee->getRoleId(),
            ':base_salary_cents' => $employee->getBaseSalary()->getCents(),
            ':employment_type' => $employee->getEmploymentType()->value,
            ':is_active' => $employee->isActive() ? 1 : 0,
            ':vacation_days_balance' => $employee->getVacationBalanceDays(),
            ':bank_hours_balance' => $employee->getBankHoursMinutes(),
            ':updated_at' => ($employee->getUpdatedAt() ?? new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ]);
    }

    public function delete(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM employees WHERE id = :id AND tenant_id = :tenant_id');
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    public function existsCpf(string|Cpf $cpf, string $tenantId, ?string $excludeEmployeeId = null): bool
    {
        $rawCpf = $cpf instanceof Cpf ? $cpf->getValue() : preg_replace('/\D/', '', $cpf);
        $sql = 'SELECT 1 FROM employees WHERE cpf = :cpf AND tenant_id = :tenant_id';
        $params = [':cpf' => $rawCpf, ':tenant_id' => $tenantId];

        if ($excludeEmployeeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeEmployeeId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    private function hydrate(array $row): Employee
    {
        return new Employee(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            cpf: (string)$row['cpf'],
            fullName: (string)$row['full_name'],
            email: (string)$row['email'],
            phone: (string)$row['phone'],
            birthDate: new DateTimeImmutable((string)$row['birth_date']),
            admissionDate: new DateTimeImmutable((string)$row['admission_date']),
            departmentId: (string)$row['department_id'],
            roleId: (string)$row['role_id'],
            baseSalary: Money::fromCents((int)$row['base_salary_cents']),
            employmentType: (string)$row['employment_type'],
            isActive: (bool)$row['is_active'],
            vacationBalanceDays: (int)$row['vacation_days_balance'],
            bankHoursMinutes: (int)$row['bank_hours_balance'],
            terminationDate: $row['termination_date'] !== null ? new DateTimeImmutable((string)$row['termination_date']) : null,
            createdAt: new DateTimeImmutable((string)$row['created_at'])
        );
    }
}
