<?php

declare(strict_types=1);

namespace HrTech\Services;

use DateTimeImmutable;
use HrTech\Domain\Entities\TimeAdjustmentRequest;
use HrTech\Domain\Enums\AdjustmentStatus;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\TimeAdjustmentRepositoryInterface;

/**
 * Class TimeAdjustmentService
 *
 * Domain service managing the lifecycle, supervisor review, approval workflow,
 * and status transitions of manual time adjustment requests.
 *
 * @package HrTech\Services
 * @author Felipe (Membro 3 — CRUD 6: Ajustes de Ponto)
 */
class TimeAdjustmentService
{
    public function __construct(
        private readonly TimeAdjustmentRepositoryInterface $repository
    ) {
    }

    /**
     * Submits a new time adjustment request for managerial approval.
     *
     * @throws ValidationException
     */
    public function requestAdjustment(
        string $id,
        string $tenantId,
        string $employeeId,
        DateTimeImmutable $requestedDate,
        ?DateTimeImmutable $originalTime,
        DateTimeImmutable $requestedTime,
        string $reason,
        ?string $attachmentPath = null
    ): TimeAdjustmentRequest {
        $request = new TimeAdjustmentRequest(
            id: $id,
            tenantId: $tenantId,
            employeeId: $employeeId,
            requestedDate: $requestedDate,
            originalTime: $originalTime,
            requestedTime: $requestedTime,
            reason: $reason,
            attachmentPath: $attachmentPath,
            status: AdjustmentStatus::PENDING
        );

        $this->repository->save($request);

        return $request;
    }

    public function getAdjustment(string $id, string $tenantId): ?TimeAdjustmentRequest
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @return array<int, TimeAdjustmentRequest>
     */
    public function listPendingAdjustments(string $tenantId): array
    {
        return $this->repository->findByStatus(AdjustmentStatus::PENDING, $tenantId);
    }

    /**
     * @return array<int, TimeAdjustmentRequest>
     */
    public function listByEmployee(string $employeeId, string $tenantId): array
    {
        return $this->repository->findByEmployee($employeeId, $tenantId);
    }

    /**
     * Approves an adjustment request.
     *
     * @throws InvalidOperationException
     */
    public function approveAdjustment(
        string $id,
        string $tenantId,
        string $approverId,
        ?string $comment = null
    ): TimeAdjustmentRequest {
        $request = $this->getAdjustment($id, $tenantId);
        if ($request === null) {
            throw new InvalidOperationException("Adjustment request '{$id}' not found in tenant '{$tenantId}'.");
        }

        $request->approve($approverId, $comment);
        $this->repository->update($request);

        return $request;
    }

    /**
     * Rejects an adjustment request.
     *
     * @throws InvalidOperationException
     */
    public function rejectAdjustment(
        string $id,
        string $tenantId,
        string $approverId,
        string $reason
    ): TimeAdjustmentRequest {
        $request = $this->getAdjustment($id, $tenantId);
        if ($request === null) {
            throw new InvalidOperationException("Adjustment request '{$id}' not found in tenant '{$tenantId}'.");
        }

        $request->reject($approverId, $reason);
        $this->repository->update($request);

        return $request;
    }

    /**
     * Cancels an adjustment request (requester only).
     *
     * @throws InvalidOperationException
     */
    public function cancelAdjustment(string $id, string $tenantId, string $requesterId): TimeAdjustmentRequest
    {
        $request = $this->getAdjustment($id, $tenantId);
        if ($request === null) {
            throw new InvalidOperationException("Adjustment request '{$id}' not found in tenant '{$tenantId}'.");
        }

        $request->cancel($requesterId);
        $this->repository->update($request);

        return $request;
    }

    public function deleteAdjustment(string $id, string $tenantId): bool
    {
        return $this->repository->delete($id, $tenantId);
    }
}
