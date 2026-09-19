<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\InsurancePolicy;
use HrTech\Domain\Enums\PolicyStatus;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Repositories\Contracts\InsurancePolicyRepositoryInterface;
use PDO;

/**
 * Class InsurancePolicyRepository
 *
 * SQLite PDO implementation for persistence and management of FinCorp Broker insurance policies.
 *
 * @package HrTech\Repositories
 * @author Nicholas (Membro 5 — CRUD 10: Apólices FinCorp)
 */
class InsurancePolicyRepository implements InsurancePolicyRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?InsurancePolicy
    {
        $stmt = $this->pdo->prepare('SELECT * FROM insurance_policies WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByPolicyNumber(string $policyNumber, string $tenantId): ?InsurancePolicy
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM insurance_policies WHERE policy_number = :num AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute([':num' => $policyNumber, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, InsurancePolicy>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM insurance_policies WHERE tenant_id = :tenant_id AND employee_id = :employee_id ORDER BY start_date DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':employee_id' => $employeeId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, InsurancePolicy>
     */
    public function findByStatus(PolicyStatus|string $status, string $tenantId): array
    {
        $statusVal = $status instanceof PolicyStatus ? $status->value : (string)$status;
        $stmt = $this->pdo->prepare(
            'SELECT * FROM insurance_policies WHERE tenant_id = :tenant_id AND status = :status ORDER BY start_date DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':status' => $statusVal]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, InsurancePolicy>
     */
    public function findAllByTenant(string $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM insurance_policies WHERE tenant_id = :tenant_id ORDER BY start_date DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(InsurancePolicy $policy): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO insurance_policies (
                id, tenant_id, policy_number, broker_code, insurer_name, employee_id,
                insured_capital_cents, monthly_premium_cents, status, start_date, end_date,
                coverage_details, cancellation_reason, cancelled_at
            ) VALUES (
                :id, :tenant_id, :policy_number, :broker_code, :insurer_name, :employee_id,
                :insured_capital_cents, :monthly_premium_cents, :status, :start_date, :end_date,
                :coverage_details, :cancellation_reason, :cancelled_at
            )'
        );

        return $stmt->execute([
            ':id' => $policy->getId(),
            ':tenant_id' => $policy->getTenantId(),
            ':policy_number' => $policy->getPolicyNumber(),
            ':broker_code' => $policy->getBrokerCode(),
            ':insurer_name' => $policy->getInsurerName(),
            ':employee_id' => $policy->getEmployeeId(),
            ':insured_capital_cents' => $policy->getInsuredCapital()->getCents(),
            ':monthly_premium_cents' => $policy->getMonthlyPremium()->getCents(),
            ':status' => $policy->getStatus()->value,
            ':start_date' => $policy->getStartDate()->format('Y-m-d'),
            ':end_date' => $policy->getEndDate()->format('Y-m-d'),
            ':coverage_details' => json_encode($policy->getCoverageDetails(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':cancellation_reason' => $policy->getCancellationReason(),
            ':cancelled_at' => $policy->getCancelledAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    public function update(InsurancePolicy $policy): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE insurance_policies
             SET policy_number = :policy_number,
                 broker_code = :broker_code,
                 insurer_name = :insurer_name,
                 insured_capital_cents = :insured_capital_cents,
                 monthly_premium_cents = :monthly_premium_cents,
                 status = :status,
                 start_date = :start_date,
                 end_date = :end_date,
                 coverage_details = :coverage_details,
                 cancellation_reason = :cancellation_reason,
                 cancelled_at = :cancelled_at
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $policy->getId(),
            ':tenant_id' => $policy->getTenantId(),
            ':policy_number' => $policy->getPolicyNumber(),
            ':broker_code' => $policy->getBrokerCode(),
            ':insurer_name' => $policy->getInsurerName(),
            ':insured_capital_cents' => $policy->getInsuredCapital()->getCents(),
            ':monthly_premium_cents' => $policy->getMonthlyPremium()->getCents(),
            ':status' => $policy->getStatus()->value,
            ':start_date' => $policy->getStartDate()->format('Y-m-d'),
            ':end_date' => $policy->getEndDate()->format('Y-m-d'),
            ':coverage_details' => json_encode($policy->getCoverageDetails(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':cancellation_reason' => $policy->getCancellationReason(),
            ':cancelled_at' => $policy->getCancelledAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    public function delete(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM insurance_policies WHERE id = :id AND tenant_id = :tenant_id');
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    private function hydrate(array $row): InsurancePolicy
    {
        $coverages = json_decode((string)($row['coverage_details'] ?? '{}'), true) ?? [];
        return new InsurancePolicy(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            policyNumber: (string)$row['policy_number'],
            brokerCode: (string)$row['broker_code'],
            insurerName: (string)$row['insurer_name'],
            employeeId: (string)$row['employee_id'],
            insuredCapital: Money::fromCents((int)$row['insured_capital_cents']),
            monthlyPremium: Money::fromCents((int)$row['monthly_premium_cents']),
            status: PolicyStatus::from((string)$row['status']),
            startDate: new DateTimeImmutable((string)$row['start_date']),
            endDate: new DateTimeImmutable((string)$row['end_date']),
            coverageDetails: $coverages,
            cancellationReason: $row['cancellation_reason'] !== null ? (string)$row['cancellation_reason'] : null,
            cancelledAt: $row['cancelled_at'] !== null ? new DateTimeImmutable((string)$row['cancelled_at']) : null
        );
    }
}
