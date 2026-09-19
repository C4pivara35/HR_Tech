<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\StringableInterface;
use HrTech\Contracts\TenantScopedInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class Employee
 *
 * Core employment record encapsulating personal data, labor contract,
 * salary structure, time bank, and vacation rights under Brazilian labor regulations.
 */
class Employee implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    ArrayableInterface,
    JsonableInterface,
    AuditableInterface,
    StringableInterface,
    JsonSerializable
{
    private string $id;
    private string $tenantId;
    private Cpf $cpf;
    private string $fullName;
    private string $email;
    private string $phone;
    private DateTimeImmutable $birthDate;
    private DateTimeImmutable $admissionDate;
    private ?DateTimeImmutable $terminationDate;
    private string $departmentId;
    private string $roleId;
    private Money $baseSalary;
    private EmploymentType $employmentType;
    private bool $isActive;
    private int $vacationDaysBalance;
    private int $bankHoursBalance; // in minutes (signed)
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    /**
     * @param string $id
     * @param string $tenantId
     * @param Cpf|string $cpf
     * @param string $fullName
     * @param string $email
     * @param string $phone
     * @param DateTimeImmutable|string $birthDate
     * @param DateTimeImmutable|string $admissionDate
     * @param string $departmentId
     * @param string $roleId
     * @param Money|float|int $baseSalary
     * @param EmploymentType|string $employmentType
     * @param bool $isActive
     * @param int $vacationBalanceDays Default 30 days.
     * @param int $bankHoursMinutes Default 0 minutes.
     * @param DateTimeImmutable|string|null $terminationDate
     * @param DateTimeImmutable|string|null $createdAt
     * @param int|null $vacationDaysBalance Alias for $vacationBalanceDays.
     * @param int|null $bankHoursBalance Alias for $bankHoursMinutes.
     * @throws ValidationException
     */
    public function __construct(
        string $id,
        string $tenantId,
        Cpf|string $cpf,
        string $fullName,
        string $email,
        string $phone,
        DateTimeImmutable|string $birthDate,
        DateTimeImmutable|string $admissionDate,
        string $departmentId,
        string $roleId,
        Money|float|int $baseSalary,
        EmploymentType|string $employmentType,
        bool $isActive = true,
        int $vacationBalanceDays = 30,
        int $bankHoursMinutes = 0,
        DateTimeImmutable|string|null $terminationDate = null,
        DateTimeImmutable|string|null $createdAt = null,
        ?int $vacationDaysBalance = null,
        ?int $bankHoursBalance = null
    ) {
        $this->id = trim($id);
        $this->tenantId = trim($tenantId);
        $this->cpf = is_string($cpf) ? new Cpf($cpf) : $cpf;
        $this->fullName = trim($fullName);
        $this->email = strtolower(trim($email));
        $this->phone = trim($phone);

        $this->birthDate = is_string($birthDate) ? new DateTimeImmutable($birthDate) : $birthDate;
        $this->admissionDate = is_string($admissionDate) ? new DateTimeImmutable($admissionDate) : $admissionDate;
        $this->terminationDate = is_string($terminationDate) ? new DateTimeImmutable($terminationDate) : $terminationDate;

        $this->departmentId = trim($departmentId);
        $this->roleId = trim($roleId);

        if ($baseSalary instanceof Money) {
            $this->baseSalary = $baseSalary;
        } elseif (is_int($baseSalary)) {
            $this->baseSalary = Money::fromCents($baseSalary);
        } else {
            $this->baseSalary = Money::fromFloat((float)$baseSalary);
        }

        if (is_string($employmentType)) {
            $resolvedType = EmploymentType::fromCaseInsensitive($employmentType);
            if ($resolvedType === null) {
                throw ValidationException::forField('employment_type', "Invalid employment type '{$employmentType}'.");
            }
            $this->employmentType = $resolvedType;
        } else {
            $this->employmentType = $employmentType;
        }

        $this->isActive = $isActive;
        $this->vacationDaysBalance = $vacationDaysBalance ?? $vacationBalanceDays;
        $this->bankHoursBalance = $bankHoursBalance ?? $bankHoursMinutes;

        if ($createdAt === null) {
            $this->createdAt = new DateTimeImmutable();
        } elseif (is_string($createdAt)) {
            $this->createdAt = new DateTimeImmutable($createdAt);
        } else {
            $this->createdAt = $createdAt;
        }

        $this->updatedAt = $this->createdAt;
        $this->validate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenantId === trim($tenantId);
    }

    public function getCpf(): Cpf
    {
        return $this->cpf;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Alias for getFullName() ensuring contract compatibility with PayrollCalculatorTemplate payslip assembly.
     */
    public function getName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getBirthDate(): DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function getAdmissionDate(): DateTimeImmutable
    {
        return $this->admissionDate;
    }

    public function getTerminationDate(): ?DateTimeImmutable
    {
        return $this->terminationDate;
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId;
    }

    public function getRoleId(): string
    {
        return $this->roleId;
    }

    public function getBaseSalary(): Money
    {
        return $this->baseSalary;
    }

    public function getEmploymentType(): EmploymentType
    {
        return $this->employmentType;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getVacationBalanceDays(): int
    {
        return $this->vacationDaysBalance;
    }

    public function getVacationDaysBalance(): int
    {
        return $this->vacationDaysBalance;
    }

    public function getBankHoursMinutes(): int
    {
        return $this->bankHoursBalance;
    }

    public function getBankHoursBalance(): int
    {
        return $this->bankHoursBalance;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // --------------------------------------------------------------------------
    // Salary Adjustments
    // --------------------------------------------------------------------------

    /**
     * Adjusts the employee's base salary.
     */
    public function adjustSalary(Money $newSalary, string $reason = ''): void
    {
        if (!$newSalary->isPositive()) {
            throw ValidationException::forField('base_salary', 'Salary must be strictly positive.');
        }

        $this->baseSalary = $newSalary;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Raises base salary by a given percentage (e.g., 10.0 for 10% raise).
     */
    public function raiseSalaryByPercentage(float $percentage, string $reason = ''): void
    {
        if ($percentage <= 0.0) {
            throw ValidationException::forField('percentage', 'Raise percentage must be greater than zero.');
        }

        $increment = $this->baseSalary->percentage($percentage);
        $newSalary = $this->baseSalary->add($increment);
        $this->adjustSalary($newSalary, $reason);
    }

    // --------------------------------------------------------------------------
    // Time Bank (Banco de Horas) Accounting
    // --------------------------------------------------------------------------

    /**
     * Records delta minutes into the time bank (positive for credit, negative for debit).
     */
    public function recordBankHours(int $minutes, string $reason = ''): void
    {
        $this->bankHoursBalance += $minutes;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Credits overtime minutes into the time bank.
     */
    public function creditBankHours(int $minutes, string $reason = ''): void
    {
        if ($minutes <= 0) {
            throw ValidationException::forField('minutes', 'Credit minutes must be strictly positive.');
        }

        $this->recordBankHours($minutes, $reason);
    }

    /**
     * Debits compensation minutes from the time bank.
     */
    public function debitBankHours(int $minutes, string $reason = ''): void
    {
        if ($minutes <= 0) {
            throw ValidationException::forField('minutes', 'Debit minutes must be strictly positive.');
        }

        $this->recordBankHours(-$minutes, $reason);
    }

    /**
     * Formats the time bank balance as signed '+HH:MM' or '-HH:MM'.
     */
    public function getBankHoursBalanceFormatted(): string
    {
        $sign = $this->bankHoursBalance < 0 ? '-' : '+';
        $absMinutes = abs($this->bankHoursBalance);
        $hours = intdiv($absMinutes, 60);
        $mins = $absMinutes % 60;

        return sprintf('%s%02d:%02d', $sign, $hours, $mins);
    }

    /**
     * Returns time bank balance in decimal hours.
     */
    public function getBankHoursInHours(): float
    {
        return round($this->bankHoursBalance / 60.0, 2);
    }

    // --------------------------------------------------------------------------
    // Vacation Balance (Férias) Management
    // --------------------------------------------------------------------------

    /**
     * Updates vacation balance. If $days is negative, applies as delta deduction.
     */
    public function updateVacationBalance(int $days, string $reason = ''): void
    {
        if ($days < 0) {
            $newBalance = $this->vacationDaysBalance + $days;
            if ($newBalance < 0) {
                throw ValidationException::forField('vacation_days_balance', 'Vacation days balance cannot become negative.');
            }
            $this->vacationDaysBalance = $newBalance;
        } else {
            $this->vacationDaysBalance = $days;
        }

        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Deducts consumed vacation days from balance.
     *
     * @throws InvalidOperationException If balance is insufficient.
     */
    public function deductVacationDays(int $days, string $reason = ''): void
    {
        if ($days <= 0) {
            throw ValidationException::forField('days', 'Deducted vacation days must be strictly positive.');
        }

        if ($days > $this->vacationDaysBalance) {
            throw InvalidOperationException::businessRule(
                'insufficient_vacation_balance',
                "Cannot deduct {$days} vacation days from current balance of {$this->vacationDaysBalance} days."
            );
        }

        $this->vacationDaysBalance -= $days;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Accrues annual vacation days (default 30 days per acquisitive period).
     */
    public function accrueVacationDays(int $days = 30, string $reason = ''): void
    {
        if ($days <= 0) {
            throw ValidationException::forField('days', 'Accrued vacation days must be strictly positive.');
        }

        $this->vacationDaysBalance += $days;
        $this->updatedAt = new DateTimeImmutable();
    }

    // --------------------------------------------------------------------------
    // Labor Calculations (CLT Divisors & Hourly Rates)
    // --------------------------------------------------------------------------

    /**
     * Returns standard monthly working hours based on Brazilian labor regulation:
     * 44 weekly hours -> 220 monthly divisor (CLT Art. 64)
     * 30 weekly hours -> 150 monthly divisor
     */
    public function getMonthlyWorkingHours(): int
    {
        $weeklyHours = $this->employmentType->maxWeeklyHours();
        return $weeklyHours * 5;
    }

    /**
     * Calculates the employee's regular hourly rate in BRL float.
     */
    public function getHourlyRate(): float
    {
        $monthlyHours = $this->getMonthlyWorkingHours();
        return round($this->baseSalary->toFloat() / $monthlyHours, 4);
    }

    public function getHourlyRateMoney(): Money
    {
        return Money::fromFloat($this->getHourlyRate(), $this->baseSalary->getCurrency());
    }

    // --------------------------------------------------------------------------
    // Demographic & Status Queries
    // --------------------------------------------------------------------------

    /**
     * Calculates employee age in completed years.
     */
    public function getAge(?DateTimeImmutable $referenceDate = null): int
    {
        $ref = $referenceDate ?? new DateTimeImmutable();
        return $this->birthDate->diff($ref)->y;
    }

    /**
     * Calculates tenure at the company in full months.
     */
    public function getTenureInMonths(?DateTimeImmutable $referenceDate = null): int
    {
        $ref = $referenceDate ?? new DateTimeImmutable();
        $diff = $this->admissionDate->diff($ref);
        return ($diff->y * 12) + $diff->m;
    }

    public function isClt(): bool
    {
        return $this->employmentType === EmploymentType::CLT;
    }

    public function isPj(): bool
    {
        return $this->employmentType === EmploymentType::PJ;
    }

    public function isIntern(): bool
    {
        return $this->employmentType === EmploymentType::INTERN;
    }

    public function transferDepartment(string $newDepartmentId): void
    {
        $clean = trim($newDepartmentId);
        if ($clean === '') {
            throw ValidationException::forField('department_id', 'Department ID cannot be empty.');
        }
        $this->departmentId = $clean;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function promote(string $newRoleId, Money $newSalary): void
    {
        $cleanRole = trim($newRoleId);
        if ($cleanRole === '') {
            throw ValidationException::forField('role_id', 'Role ID cannot be empty.');
        }
        $this->roleId = $cleanRole;
        $this->adjustSalary($newSalary);
    }

    public function terminate(DateTimeImmutable|string $terminationDate, string $reason = ''): void
    {
        $date = is_string($terminationDate) ? new DateTimeImmutable($terminationDate) : $terminationDate;
        if ($date < $this->admissionDate) {
            throw ValidationException::forField('termination_date', 'Termination date cannot be earlier than admission date.');
        }

        $this->terminationDate = $date;
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reactivate(): void
    {
        $this->isActive = true;
        $this->terminationDate = null;
        $this->updatedAt = new DateTimeImmutable();
    }

    // --------------------------------------------------------------------------
    // Invariants Validation
    // --------------------------------------------------------------------------

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = [];

        if ($this->id === '') {
            $errors['id'][] = 'Employee ID cannot be empty.';
        }

        if ($this->tenantId === '') {
            $errors['tenant_id'][] = 'Tenant ID cannot be empty.';
        }

        if ($this->fullName === '') {
            $errors['full_name'][] = 'Full name cannot be empty.';
        } elseif (mb_strlen($this->fullName) < 3) {
            $errors['full_name'][] = 'Full name must contain at least 3 characters.';
        }

        if ($this->email === '') {
            $errors['email'][] = 'Email cannot be empty.';
        } elseif (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = "Email format '{$this->email}' is invalid.";
        }

        if ($this->phone === '') {
            $errors['phone'][] = 'Phone number cannot be empty.';
        }

        if ($this->departmentId === '') {
            $errors['department_id'][] = 'Department ID cannot be empty.';
        }

        if ($this->roleId === '') {
            $errors['role_id'][] = 'Role ID cannot be empty.';
        }

        if (!$this->baseSalary->isPositive()) {
            $errors['base_salary'][] = 'Base salary must be greater than zero.';
        }

        if ($this->getAge() < 14) {
            $errors['birth_date'][] = 'Employee must be at least 14 years of age (CLT Young Apprentice minimum).';
        }

        if ($this->admissionDate < $this->birthDate) {
            $errors['admission_date'][] = 'Admission date cannot be earlier than birth date.';
        }

        if ($this->terminationDate !== null && $this->terminationDate < $this->admissionDate) {
            $errors['termination_date'][] = 'Termination date cannot be earlier than admission date.';
        }

        if ($this->vacationDaysBalance < 0) {
            $errors['vacation_days_balance'][] = 'Vacation days balance cannot be negative.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'Employee validation failed.');
        }
    }

    public function isValid(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'cpf' => $this->cpf->getValue(),
            'cpf_formatted' => $this->cpf->getFormatted(),
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birthDate->format('Y-m-d'),
            'admission_date' => $this->admissionDate->format('Y-m-d'),
            'termination_date' => $this->terminationDate?->format('Y-m-d'),
            'department_id' => $this->departmentId,
            'role_id' => $this->roleId,
            'base_salary' => $this->baseSalary->jsonSerialize(),
            'employment_type' => $this->employmentType->value,
            'employment_type_label' => $this->employmentType->label(),
            'is_active' => $this->isActive,
            'vacation_days_balance' => $this->vacationDaysBalance,
            'vacation_balance_days' => $this->vacationDaysBalance,
            'bank_hours_balance_minutes' => $this->bankHoursBalance,
            'bank_hours_minutes' => $this->bankHoursBalance,
            'bank_hours_balance_formatted' => $this->getBankHoursBalanceFormatted(),
            'hourly_rate' => $this->getHourlyRate(),
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(DateTimeInterface::ATOM),
        ];
    }

    public function toJson(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getAuditIdentifier(): string
    {
        return $this->id;
    }

    public function getAuditCategory(): string
    {
        return 'Employee';
    }

    /**
     * LGPD compliance: Mask CPF in audit trails.
     *
     * @return array<string, mixed>
     */
    public function toAuditArray(): array
    {
        $audit = $this->toArray();
        $audit['cpf'] = $this->cpf->getMasked();
        return $audit;
    }

    public function __toString(): string
    {
        return sprintf(
            'Employee[%s] %s (%s, %s)',
            $this->id,
            $this->fullName,
            $this->employmentType->value,
            $this->baseSalary->getFormatted()
        );
    }
}
