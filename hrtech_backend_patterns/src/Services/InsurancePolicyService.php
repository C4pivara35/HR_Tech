<?php

declare(strict_types=1);

namespace HrTech\Services;

use DateTimeImmutable;
use HrTech\Domain\Entities\InsurancePolicy;
use HrTech\Domain\Enums\PolicyStatus;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\InsurancePolicyRepositoryInterface;

/**
 * Class InsurancePolicyService
 *
 * Domain service managing FinCorp broker portal insurance policies,
 * endorsements, renewals, cancellations, and coverage lifecycle.
 *
 * @package HrTech\Services
 * @author Nicholas (Membro 5 — CRUD 10: Apólices FinCorp)
 */
class InsurancePolicyService
{
    public function __construct(
        private readonly InsurancePolicyRepositoryInterface $repository
    ) {
    }

    /**
     * Issues a new FinCorp insurance policy for an employee.
     *
     * @param array<string, mixed> $coverageDetails
     * @throws ValidationException
     */
    public function issuePolicy(
        string $id,
        string $tenantId,
        string $policyNumber,
        string $brokerCode,
        string $insurerName,
        string $employeeId,
        Money|float|int $insuredCapital,
        Money|float|int $monthlyPremium,
        PolicyStatus|string $status,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $coverageDetails = []
    ): InsurancePolicy {
        $policyStatus = $status instanceof PolicyStatus ? $status : PolicyStatus::from($status);
        $capital = $insuredCapital instanceof Money
            ? $insuredCapital
            : Money::fromCents((int)round((float)$insuredCapital * 100));
        $premium = $monthlyPremium instanceof Money
            ? $monthlyPremium
            : Money::fromCents((int)round((float)$monthlyPremium * 100));

        $policy = new InsurancePolicy(
            id: $id,
            tenantId: $tenantId,
            policyNumber: $policyNumber,
            brokerCode: $brokerCode,
            insurerName: $insurerName,
            employeeId: $employeeId,
            insuredCapital: $capital,
            monthlyPremium: $premium,
            status: $policyStatus,
            startDate: $startDate,
            endDate: $endDate,
            coverageDetails: $coverageDetails
        );

        $this->repository->save($policy);

        return $policy;
    }

    public function getPolicy(string $id, string $tenantId): ?InsurancePolicy
    {
        return $this->repository->findById($id, $tenantId);
    }

    public function getPolicyByNumber(string $policyNumber, string $tenantId): ?InsurancePolicy
    {
        return $this->repository->findByPolicyNumber($policyNumber, $tenantId);
    }

    /**
     * @return array<int, InsurancePolicy>
     */
    public function listPolicies(string $tenantId): array
    {
        return $this->repository->findAllByTenant($tenantId);
    }

    /**
     * @return array<int, InsurancePolicy>
     */
    public function listByEmployee(string $employeeId, string $tenantId): array
    {
        return $this->repository->findByEmployee($employeeId, $tenantId);
    }

    /**
     * Cancels an active or pending policy with reason recording.
     *
     * @throws InvalidOperationException
     */
    public function cancelPolicy(
        string $id,
        string $tenantId,
        string $cancellationReason,
        ?DateTimeImmutable $cancelledAt = null
    ): InsurancePolicy {
        $policy = $this->getPolicy($id, $tenantId);
        if ($policy === null) {
            throw new InvalidOperationException("Policy '{$id}' not found in tenant '{$tenantId}'.");
        }

        $policy->cancel($cancellationReason, $cancelledAt);
        $this->repository->update($policy);

        return $policy;
    }

    /**
     * Renews the policy coverage until a new end date.
     *
     * @throws InvalidOperationException
     */
    public function renewPolicy(
        string $id,
        string $tenantId,
        DateTimeImmutable $newEndDate,
        Money|float|int|null $newMonthlyPremium = null
    ): InsurancePolicy {
        $policy = $this->getPolicy($id, $tenantId);
        if ($policy === null) {
            throw new InvalidOperationException("Policy '{$id}' not found in tenant '{$tenantId}'.");
        }

        $premium = null;
        if ($newMonthlyPremium !== null) {
            $premium = $newMonthlyPremium instanceof Money
                ? $newMonthlyPremium
                : Money::fromCents((int)round((float)$newMonthlyPremium * 100));
        }

        $policy->renew($newEndDate, $premium);
        $this->repository->update($policy);

        return $policy;
    }

    public function deletePolicy(string $id, string $tenantId): bool
    {
        return $this->repository->delete($id, $tenantId);
    }
}
