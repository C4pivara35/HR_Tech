<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\VacationRequest;
use HrTech\Domain\Enums\VacationStatus;
use HrTech\Repositories\Contracts\VacationRepositoryInterface;
use PDO;

/**
 * Class VacationRepository
 *
 * SQLite PDO implementation for persistence and state updates of VacationRequest entities.
 *
 * @package HrTech\Repositories
 * @author Valentin (Membro 4 — CRUD 7: Gestão de Férias)
 */
class VacationRepository implements VacationRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?VacationRequest
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vacation_requests WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, VacationRequest>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vacation_requests
             WHERE tenant_id = :tenant_id AND employee_id = :employee_id
             ORDER BY created_at DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':employee_id' => $employeeId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, VacationRequest>
     */
    public function findByStatus(VacationStatus|string $status, string $tenantId): array
    {
        $statusVal = $status instanceof VacationStatus ? $status->value : (string)$status;
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vacation_requests
             WHERE tenant_id = :tenant_id AND status = :status
             ORDER BY created_at DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':status' => $statusVal]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, VacationRequest>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array
    {
        $sql = 'SELECT * FROM vacation_requests WHERE tenant_id = :tenant_id';
        $params = [':tenant_id' => $tenantId];

        if (isset($filters['employee_id'])) {
            $sql .= ' AND employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (isset($filters['status'])) {
            $sql .= ' AND status = :status';
            $params[':status'] = $filters['status'] instanceof VacationStatus
                ? $filters['status']->value
                : (string)$filters['status'];
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(VacationRequest $request): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vacation_requests (
                id, tenant_id, employee_id, start_date, end_date, duration_days,
                abono_pecuniario, advance_thirteenth_salary, abono_days, status,
                approver_id, approved_at, rejection_reason, created_at
            ) VALUES (
                :id, :tenant_id, :employee_id, :start_date, :end_date, :duration_days,
                :abono_pecuniario, :advance_thirteenth_salary, :abono_days, :status,
                :approver_id, :approved_at, :rejection_reason, :created_at
            )'
        );

        return $stmt->execute([
            ':id' => $request->id,
            ':tenant_id' => $request->tenantId,
            ':employee_id' => $request->employeeId,
            ':start_date' => $request->startDate->format('Y-m-d'),
            ':end_date' => $request->endDate->format('Y-m-d'),
            ':duration_days' => $request->durationDays,
            ':abono_pecuniario' => $request->abonoPecuniario ? 1 : 0,
            ':advance_thirteenth_salary' => $request->advanceThirteenthSalary ? 1 : 0,
            ':abono_days' => $request->abonoDays,
            ':status' => $request->getStatus()->value,
            ':approver_id' => $request->getApproverId(),
            ':approved_at' => $request->getApprovedAt()?->format(DateTimeInterface::ATOM),
            ':rejection_reason' => $request->getRejectionReason(),
            ':created_at' => $request->createdAt->format(DateTimeInterface::ATOM),
        ]);
    }

    public function update(VacationRequest $request): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE vacation_requests
             SET status = :status,
                 approver_id = :approver_id,
                 approved_at = :approved_at,
                 rejection_reason = :rejection_reason
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $request->id,
            ':tenant_id' => $request->tenantId,
            ':status' => $request->getStatus()->value,
            ':approver_id' => $request->getApproverId(),
            ':approved_at' => $request->getApprovedAt()?->format(DateTimeInterface::ATOM),
            ':rejection_reason' => $request->getRejectionReason(),
        ]);
    }

    public function delete(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM vacation_requests WHERE id = :id AND tenant_id = :tenant_id');
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    private function hydrate(array $row): VacationRequest
    {
        return new VacationRequest(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            employeeId: (string)$row['employee_id'],
            startDate: new DateTimeImmutable((string)$row['start_date']),
            endDate: new DateTimeImmutable((string)$row['end_date']),
            durationDays: (int)$row['duration_days'],
            abonoPecuniario: (bool)$row['abono_pecuniario'],
            advanceThirteenthSalary: (bool)$row['advance_thirteenth_salary'],
            status: VacationStatus::from((string)$row['status']),
            approverId: $row['approver_id'] !== null ? (string)$row['approver_id'] : null,
            approvedAt: $row['approved_at'] !== null ? new DateTimeImmutable((string)$row['approved_at']) : null,
            rejectionReason: $row['rejection_reason'] !== null ? (string)$row['rejection_reason'] : null,
            abonoDays: (int)$row['abono_days'],
            createdAt: new DateTimeImmutable((string)$row['created_at'])
        );
    }
}
