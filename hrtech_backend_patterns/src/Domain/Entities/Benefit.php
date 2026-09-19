<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\TenantScopedInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Domain\Enums\BenefitType;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class Benefit
 *
 * Corporate benefit package entity.
 * Represents meal vouchers (VR), food vouchers (VA), transport vouchers (VT),
 * medical plans, dental plans, life insurance, and gym allowances.
 *
 * Implements employee copay / cost share calculations adhering to Brazilian labor
 * law and corporate policies (e.g. PAT 20% cap, VT 6% base salary cap).
 */
class Benefit implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    AuditableInterface,
    ArrayableInterface,
    JsonableInterface,
    JsonSerializable
{
    /**
     * @param string $id Unique benefit identifier (UUID / alphanumeric)
     * @param string $tenantId Tenant multi-tenant isolation scope
     * @param BenefitType $type Categorization enum
     * @param string $name Commercial / plan name
     * @param string $provider Operator / supplier company (e.g., Sodexo, Ticket, Bradesco Saúde)
     * @param Money $value Total monthly monetary value per beneficiary
     * @param float $employeeCostSharePercentage Copay percentage deducted from employee (0.0 to 100.0)
     * @param bool $isDeductible Whether this benefit can be legally deducted from payroll
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private BenefitType $type,
        private string $name,
        private string $provider,
        private Money $value,
        private float $employeeCostSharePercentage = 0.0,
        private bool $isDeductible = true
    ) {
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

    public function getType(): BenefitType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getValue(): Money
    {
        return $this->value;
    }

    public function getEmployeeCostSharePercentage(): float
    {
        return $this->employeeCostSharePercentage;
    }

    public function isDeductible(): bool
    {
        return $this->isDeductible;
    }

    /**
     * Updates plan details.
     */
    public function updatePlan(
        string $name,
        string $provider,
        Money $value,
        float $employeeCostSharePercentage,
        bool $isDeductible
    ): void {
        $this->name = trim($name);
        $this->provider = trim($provider);
        $this->value = $value;
        $this->employeeCostSharePercentage = $employeeCostSharePercentage;
        $this->isDeductible = $isDeductible;
        $this->validate();
    }

    /**
     * Calculates the monthly employee copay / contribution.
     * Guarantees exact integer-cent rounding via Money value object.
     */
    public function calculateEmployeeContribution(): Money
    {
        if (!$this->isDeductible || $this->employeeCostSharePercentage <= 0.0) {
            return Money::zero($this->value->getCurrency());
        }

        return $this->value->percentage($this->employeeCostSharePercentage);
    }

    /**
     * Calculates the monthly employer subsidy / contribution.
     * Preserves exact zero-penny-loss invariant:
     * employerContribution = totalValue - employeeContribution.
     */
    public function calculateEmployerContribution(): Money
    {
        $employeeShare = $this->calculateEmployeeContribution();
        return $this->value->subtract($employeeShare);
    }

    /**
     * Calculates effective payroll deduction for a specific employee salary,
     * applying statutory caps (e.g. VT 6% salary cap under Lei 7.418/1985).
     *
     * @param Money $baseSalary Base gross monthly salary of the collaborator
     * @return Money Actual payroll deduction amount
     */
    public function calculateDeductionForSalary(Money $baseSalary): Money
    {
        if (!$this->isDeductible || $this->employeeCostSharePercentage <= 0.0) {
            return Money::zero($this->value->getCurrency());
        }

        $standardCopay = $this->calculateEmployeeContribution();

        // Check statutory deduction limit from BenefitType enum
        $legalCapPercentage = $this->type->maxLegalDeductionPercentage();
        if ($legalCapPercentage !== null && in_array($this->type, [BenefitType::TRANSPORTATION, BenefitType::TRANSPORTATION_VOUCHER], true)) {
            $salaryCap = $baseSalary->percentage($legalCapPercentage);
            // Employee pays whichever is lower: statutory salary cap (6%) or actual voucher cost
            return $salaryCap->lessThan($standardCopay) ? $salaryCap : $standardCopay;
        }

        return $standardCopay;
    }

    /**
     * Validates domain invariants.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = [];

        if (trim($this->id) === '') {
            $errors['id'][] = 'Benefit ID cannot be empty.';
        }

        if (trim($this->tenantId) === '') {
            $errors['tenant_id'][] = 'Tenant ID cannot be empty.';
        }

        if (trim($this->name) === '') {
            $errors['name'][] = 'Benefit name cannot be empty.';
        }

        if (trim($this->provider) === '') {
            $errors['provider'][] = 'Benefit provider cannot be empty.';
        }

        if (!$this->value->isPositive()) {
            $errors['value'][] = 'Benefit monetary value must be strictly positive.';
        }

        if ($this->employeeCostSharePercentage < 0.0 || $this->employeeCostSharePercentage > 100.0) {
            $errors['employee_cost_share_percentage'][] = 'Employee cost share percentage must be between 0.0 and 100.0.';
        }

        if (!$this->isDeductible && $this->employeeCostSharePercentage > 0.0) {
            $errors['is_deductible'][] = 'Non-deductible benefits cannot assign an employee cost share greater than 0%.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'Benefit validation failed.');
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

    public function getAuditIdentifier(): string
    {
        return $this->id;
    }

    public function getAuditCategory(): string
    {
        return 'Benefit';
    }

    public function toAuditArray(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'name' => $this->name,
            'provider' => $this->provider,
            'value' => $this->value->jsonSerialize(),
            'employee_cost_share_percentage' => $this->employeeCostSharePercentage,
            'employee_contribution' => $this->calculateEmployeeContribution()->jsonSerialize(),
            'employer_contribution' => $this->calculateEmployerContribution()->jsonSerialize(),
            'is_deductible' => $this->isDeductible,
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
}
