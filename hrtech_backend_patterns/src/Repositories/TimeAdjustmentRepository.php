<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\TimeAdjustmentRequest;
use HrTech\Domain\Enums\AdjustmentStatus;
use HrTech\Repositories\Contracts\TimeAdjustmentRepositoryInterface;
use PDO;

/**
 * Class TimeAdjustmentRepository
 *
 * SQLite PDO implementation for persistence and state updates of TimeAdjustmentRequest entities.
 *
 * @package HrTech\Repositories
 * @author Felipe (Membro 3 — CRUD 6: Ajustes de Ponto)
 */
class TimeAdjustmentRepository implements TimeAdjustmentRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?TimeAdjustmentRequest
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM time_adjustment_requests WHERE id = :id AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, TimeAdjustmentRequest>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM time_adjustment_requests
             WHERE tenant_id = :tenant_id AND employee_id = :employee_id
             ORDER BY created_at DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':employee_id' => $employeeId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, TimeAdjustmentRequest>
     */
    public function findByStatus(AdjustmentStatus|string $status, string $tenantId): array
    {
        $statusVal = $status instanceof AdjustmentStatus ? $status->value : (string)$status;
        $stmt = $this->pdo->prepare(
            'SELECT * FROM time_adjustment_requests
             WHERE tenant_id = :tenant_id AND status = :status
             ORDER BY created_at DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':status' => $statusVal]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, TimeAdjustmentRequest>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array
    {
        $sql = 'SELECT * FROM time_adjustment_requests WHERE tenant_id = :tenant_id';
        $params = [':tenant_id' => $tenantId];

        if (isset($filters['employee_id'])) {
            $sql .= ' AND employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (isset($filters['status'])) {
            $sql .= ' AND status = :status';
            $params[':status'] = $filters['status'] instanceof AdjustmentStatus
                ? $filters['status']->value
                : (string)$filters['status'];
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(TimeAdjustmentRequest $request): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO time_adjustment_requests (
                id, tenant_id, employee_id, requested_date, original_time, requested_time,
                reason, attachment_path, status, approver_id, approved_at, review_comment, created_at
            ) VALUES (
                :id, :tenant_id, :employee_id, :requested_date, :original_time, :requested_time,
                :reason, :attachment_path, :status, :approver_id, :approved_at, :review_comment, :created_at
            )'
        );

        return $stmt->execute([
            ':id' => $request->id,
            ':tenant_id' => $request->tenantId,
            ':employee_id' => $request->employeeId,
            ':requested_date' => $request->requestedDate->format('Y-m-d'),
            ':original_time' => $request->originalTime?->format(DateTimeInterface::ATOM),
            ':requested_time' => $request->requestedTime->format(DateTimeInterface::ATOM),
            ':reason' => $request->reason,
            ':attachment_path' => $request->attachmentPath,
            ':status' => $request->getStatus()->value,
            ':approver_id' => $request->getApproverId(),
            ':approved_at' => $request->getApprovedAt()?->format(DateTimeInterface::ATOM),
            ':review_comment' => $request->getReviewComment(),
            ':created_at' => $request->createdAt->format(DateTimeInterface::ATOM),
        ]);
    }

    public function update(TimeAdjustmentRequest $request): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE time_adjustment_requests
             SET status = :status,
                 approver_id = :approver_id,
                 approved_at = :approved_at,
                 review_comment = :review_comment
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $request->id,
            ':tenant_id' => $request->tenantId,
            ':status' => $request->getStatus()->value,
            ':approver_id' => $request->getApproverId(),
            ':approved_at' => $request->getApprovedAt()?->format(DateTimeInterface::ATOM),
            ':review_comment' => $request->getReviewComment(),
        ]);
    }

    public function delete(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM time_adjustment_requests WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    private function hydrate(array $row): TimeAdjustmentRequest
    {
        return new TimeAdjustmentRequest(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            employeeId: (string)$row['employee_id'],
            requestedDate: new DateTimeImmutable((string)$row['requested_date']),
            originalTime: $row['original_time'] !== null ? new DateTimeImmutable((string)$row['original_time']) : null,
            requestedTime: new DateTimeImmutable((string)$row['requested_time']),
            reason: (string)$row['reason'],
            attachmentPath: $row['attachment_path'] !== null ? (string)$row['attachment_path'] : null,
            status: AdjustmentStatus::from((string)$row['status']),
            approverId: $row['approver_id'] !== null ? (string)$row['approver_id'] : null,
            approvedAt: $row['approved_at'] !== null ? new DateTimeImmutable((string)$row['approved_at']) : null,
            reviewComment: $row['review_comment'] !== null ? (string)$row['review_comment'] : null,
            createdAt: new DateTimeImmutable((string)$row['created_at'])
        );
    }
}
