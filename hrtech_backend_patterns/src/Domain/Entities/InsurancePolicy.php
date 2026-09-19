<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use DateTimeImmutable;
use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\TenantScopedInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Domain\Enums\PolicyStatus;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class InsurancePolicy
 *
 * FinCorp Broker Portal Integration Entity.
 * Represents corporate insurance policies (Life, Accidental Disability, Critical Illness, Dental/Health).
 */
class InsurancePolicy implements
    IdentifiableInterface,
    TenantScopedInterface,
    AuditableInterface,
    ValidatableInterface,
    ArrayableInterface,
    JsonableInterface,
    JsonSerializable
{
    /**
     * @param string $id Unique policy ID
     * @param string $tenantId Tenant isolation scope
     * @param string $policyNumber FinCorp or insurer policy reference number
     * @param string $brokerCode FinCorp broker credential / SUSEP code
     * @param string $insurerName Name of underwriting insurance company
     * @param string $employeeId Collaborator beneficiary identifier
     * @param Money $insuredCapital Total coverage / indemnity limit
     * @param Money $monthlyPremium Monthly cost paid to insurer
     * @param PolicyStatus $status Current lifecycle status
     * @param DateTimeImmutable $startDate Inception date of policy coverage
     * @param DateTimeImmutable $endDate Expiration date of policy coverage
     * @param array<string, mixed> $coverageDetails Itemized coverage breakdown
     * @param string|null $cancellationReason Justification if cancelled
     * @param DateTimeImmutable|null $cancelledAt Cancellation timestamp
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private string $policyNumber,
        private string $brokerCode,
        private string $insurerName,
        private readonly string $employeeId,
        private Money $insuredCapital,
        private Money $monthlyPremium,
        private PolicyStatus $status,
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate,
        private array $coverageDetails = [],
        private ?string $cancellationReason = null,
        private ?DateTimeImmutable $cancelledAt = null
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

    public function getPolicyNumber(): string
    {
        return $this->policyNumber;
    }

    public function getBrokerCode(): string
    {
        return $this->brokerCode;
    }

    public function getInsurerName(): string
    {
        return $this->insurerName;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getInsuredCapital(): Money
    {
        return $this->insuredCapital;
    }

    public function getMonthlyPremium(): Money
    {
        return $this->monthlyPremium;
    }

    public function getStatus(): PolicyStatus
    {
        return $this->status;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getCoverageDetails(): array
    {
        return $this->coverageDetails;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    /**
     * Calculates the projected annual premium cost (12 monthly premiums).
     */
    public function calculateAnnualPremium(): Money
    {
        return $this->monthlyPremium->multiply(12);
    }

    /**
     * Checks if coverage is actively in force at a given timestamp.
     */
    public function isActive(?DateTimeImmutable $atDate = null): bool
    {
        $date = $atDate ?? new DateTimeImmutable('now');

        if (!$this->status->isActive()) {
            return false;
        }

        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * Activates a drafted or proposed policy.
     *
     * @throws InvalidOperationException
     */
    public function activate(): void
    {
        if ($this->status === PolicyStatus::ACTIVE) {
            return;
        }

        if (!in_array($this->status, [PolicyStatus::DRAFT, PolicyStatus::PROPOSAL_SUBMITTED], true)) {
            throw InvalidOperationException::invalidState(
                'InsurancePolicy',
                $this->status->value,
                'activate'
            );
        }

        $this->status = PolicyStatus::ACTIVE;
    }

    /**
     * Cancels an active or pending policy with reason recording.
     *
     * @throws InvalidOperationException
     * @throws ValidationException
     */
    public function cancel(string $reason, ?DateTimeImmutable $cancelledAt = null): void
    {
        if (in_array($this->status, [PolicyStatus::CANCELLED, PolicyStatus::EXPIRED], true)) {
            throw InvalidOperationException::invalidState(
                'InsurancePolicy',
                $this->status->value,
                'cancel'
            );
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::forField('reason', 'Cancellation reason cannot be empty.');
        }

        $this->status = PolicyStatus::CANCELLED;
        $this->cancellationReason = $trimmedReason;
        $this->cancelledAt = $cancelledAt ?? new DateTimeImmutable('now');
    }

    /**
     * Suspends policy due to underwriting review or payment default.
     */
    public function suspend(string $reason): void
    {
        if ($this->status !== PolicyStatus::ACTIVE) {
            throw InvalidOperationException::invalidState(
                'InsurancePolicy',
                $this->status->value,
                'suspend'
            );
        }

        $this->status = PolicyStatus::SUSPENDED;
    }

    /**
     * Renews the policy coverage until a new end date.
     */
    public function renew(DateTimeImmutable $newEndDate, ?Money $newMonthlyPremium = null): void
    {
        if ($newEndDate <= $this->endDate) {
            throw new ValidationException(
                "Renewal end date ({$newEndDate->format('Y-m-d')}) must be strictly after current end date ({$this->endDate->format('Y-m-d')})."
            );
        }

        $this->endDate = $newEndDate;
        if ($newMonthlyPremium !== null) {
            if (!$newMonthlyPremium->isPositive()) {
                throw new ValidationException('Renewed monthly premium must be strictly positive.');
            }
            $this->monthlyPremium = $newMonthlyPremium;
        }

        $this->status = PolicyStatus::ACTIVE;
    }

    public function validate(): void
    {
        $errors = [];

        if (trim($this->id) === '') {
            $errors['id'][] = 'Policy ID cannot be empty.';
        }

        if (trim($this->tenantId) === '') {
            $errors['tenant_id'][] = 'Tenant ID cannot be empty.';
        }

        if (trim($this->policyNumber) === '') {
            $errors['policy_number'][] = 'Policy number cannot be empty.';
        }

        if (trim($this->brokerCode) === '') {
            $errors['broker_code'][] = 'Broker code cannot be empty.';
        }

        if (trim($this->insurerName) === '') {
            $errors['insurer_name'][] = 'Insurer name cannot be empty.';
        }

        if (trim($this->employeeId) === '') {
            $errors['employee_id'][] = 'Employee ID cannot be empty.';
        }

        if (!$this->insuredCapital->isPositive()) {
            $errors['insured_capital'][] = 'Insured capital must be strictly positive.';
        }

        if (!$this->monthlyPremium->isPositive()) {
            $errors['monthly_premium'][] = 'Monthly premium must be strictly positive.';
        }

        if ($this->endDate <= $this->startDate) {
            $errors['dates'][] = 'Policy end date must be strictly after start date.';
        }

        if ($this->status === PolicyStatus::CANCELLED && ($this->cancellationReason === null || trim($this->cancellationReason) === '')) {
            $errors['cancellation_reason'][] = 'Cancelled policy must possess a valid cancellation reason.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'InsurancePolicy validation failed.');
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
        return 'InsurancePolicy';
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
            'policy_number' => $this->policyNumber,
            'broker_code' => $this->brokerCode,
            'insurer_name' => $this->insurerName,
            'employee_id' => $this->employeeId,
            'insured_capital' => $this->insuredCapital->jsonSerialize(),
            'monthly_premium' => $this->monthlyPremium->jsonSerialize(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'coverage_details' => $this->coverageDetails,
            'is_active' => $this->isActive(),
            'annual_premium' => $this->calculateAnnualPremium()->jsonSerialize(),
            'cancellation_reason' => $this->cancellationReason,
            'cancelled_at' => $this->cancelledAt?->format('c'),
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
