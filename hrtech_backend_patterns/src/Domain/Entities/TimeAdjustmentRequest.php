<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\TenantScopedInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Domain\Enums\AdjustmentStatus;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class TimeAdjustmentRequest
 *
 * Represents an employee request for punch correction, missed punch inclusion,
 * or interval adjustment under Portaria 671/2021 MTE and internal HR compliance.
 */
class TimeAdjustmentRequest implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    AuditableInterface,
    ArrayableInterface,
    JsonableInterface,
    JsonSerializable
{
    private AdjustmentStatus $status;
    private ?string $approverId;
    private ?DateTimeImmutable $approvedAt;
    private ?string $reviewComment;
    public readonly DateTimeImmutable $createdAt;

    /**
     * @param string $id Unique request ID
     * @param string $tenantId Tenant identifier
     * @param string $employeeId Requesting employee ID
     * @param DateTimeImmutable $requestedDate Calendar date of punch occurrence
     * @param DateTimeImmutable|null $originalTime Original recorded time, or null if missed punch
     * @param DateTimeImmutable $requestedTime Intended / corrected punch time
     * @param string $reason Detailed business justification
     * @param string|null $attachmentPath Optional file path / URI for proof document
     * @param AdjustmentStatus $status Current workflow status (defaults to PENDING)
     * @param string|null $approverId ID of approving manager
     * @param DateTimeImmutable|null $approvedAt Timestamp of review decision
     * @param string|null $reviewComment Manager's review notes or rejection reason
     * @param DateTimeImmutable|null $createdAt Submission timestamp
     * @throws ValidationException
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly DateTimeImmutable $requestedDate,
        public readonly ?DateTimeImmutable $originalTime,
        public readonly DateTimeImmutable $requestedTime,
        public readonly string $reason,
        public readonly ?string $attachmentPath = null,
        AdjustmentStatus $status = AdjustmentStatus::PENDING,
        ?string $approverId = null,
        ?DateTimeImmutable $approvedAt = null,
        ?string $reviewComment = null,
        ?DateTimeImmutable $createdAt = null,
    ) {
        $this->status = $status;
        $this->approverId = $approverId !== null && trim($approverId) !== '' ? trim($approverId) : null;
        $this->approvedAt = $approvedAt;
        $this->reviewComment = $reviewComment !== null && trim($reviewComment) !== '' ? trim($reviewComment) : null;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->validate();
    }

    /**
     * Approves the adjustment request.
     *
     * @param string $approverId Manager or HR authority ID
     * @param string|null $comment Optional approval notes
     * @throws InvalidOperationException
     * @throws ValidationException
     */
    public function approve(string $approverId, ?string $comment = null): void
    {
        if (!$this->status->canTransitionTo(AdjustmentStatus::APPROVED)) {
            throw InvalidOperationException::invalidState(
                'TimeAdjustmentRequest',
                $this->status->value,
                'approve'
            );
        }

        $cleanApprover = trim($approverId);
        if ($cleanApprover === '') {
            throw ValidationException::forField('approverId', 'Approver ID cannot be empty.');
        }

        if ($cleanApprover === $this->employeeId) {
            throw InvalidOperationException::businessRule(
                'SelfApprovalProhibited',
                'An employee cannot approve their own time adjustment request.'
            );
        }

        $this->status = AdjustmentStatus::APPROVED;
        $this->approverId = $cleanApprover;
        $this->approvedAt = new DateTimeImmutable();
        $this->reviewComment = $comment !== null ? trim($comment) : null;
    }

    /**
     * Rejects the adjustment request with mandatory justification.
     *
     * @param string $approverId Manager or HR authority ID
     * @param string $reason Rejection explanation
     * @throws InvalidOperationException
     * @throws ValidationException
     */
    public function reject(string $approverId, string $reason): void
    {
        if (!$this->status->canTransitionTo(AdjustmentStatus::REJECTED)) {
            throw InvalidOperationException::invalidState(
                'TimeAdjustmentRequest',
                $this->status->value,
                'reject'
            );
        }

        $cleanApprover = trim($approverId);
        if ($cleanApprover === '') {
            throw ValidationException::forField('approverId', 'Approver ID cannot be empty.');
        }

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::forField('reason', 'Rejection reason cannot be empty.');
        }

        $this->status = AdjustmentStatus::REJECTED;
        $this->approverId = $cleanApprover;
        $this->approvedAt = new DateTimeImmutable();
        $this->reviewComment = $cleanReason;
    }

    /**
     * Cancels the adjustment request. Only permitted for the requesting employee while pending.
     *
     * @param string $requesterId
     * @throws InvalidOperationException
     */
    public function cancel(string $requesterId): void
    {
        if (!$this->status->canTransitionTo(AdjustmentStatus::CANCELLED)) {
            throw InvalidOperationException::invalidState(
                'TimeAdjustmentRequest',
                $this->status->value,
                'cancel'
            );
        }

        if (trim($requesterId) !== $this->employeeId) {
            throw InvalidOperationException::businessRule(
                'UnauthorizedCancellation',
                'Only the requesting employee can cancel their adjustment request.'
            );
        }

        $this->status = AdjustmentStatus::CANCELLED;
    }

    // --------------------------------------------------------------------------
    // Query & Inspection Methods
    // --------------------------------------------------------------------------

    public function getStatus(): AdjustmentStatus
    {
        return $this->status;
    }

    public function getApproverId(): ?string
    {
        return $this->approverId;
    }

    public function getApprovedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getReviewComment(): ?string
    {
        return $this->reviewComment;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejected();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function hasAttachment(): bool
    {
        return $this->attachmentPath !== null && trim($this->attachmentPath) !== '';
    }

    public function getRequestedDate(): DateTimeImmutable
    {
        return $this->requestedDate;
    }

    public function getOriginalTime(): ?DateTimeImmutable
    {
        return $this->originalTime;
    }

    public function getRequestedTime(): DateTimeImmutable
    {
        return $this->requestedTime;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getAttachmentPath(): ?string
    {
        return $this->attachmentPath;
    }

    /**
     * Calculates time adjustment delta in minutes, or null if missed punch.
     */
    public function getTimeDeltaMinutes(): ?int
    {
        if ($this->originalTime === null) {
            return null;
        }

        $diff = $this->originalTime->diff($this->requestedTime);
        $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

        return $diff->invert === 1 ? -$minutes : $minutes;
    }

    // --------------------------------------------------------------------------
    // Contract Implementations
    // --------------------------------------------------------------------------

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

    public function validate(): void
    {
        $errors = [];

        if (trim($this->id) === '') {
            $errors['id'] = ['ID cannot be empty.'];
        }

        if (trim($this->tenantId) === '') {
            $errors['tenant_id'] = ['Tenant ID cannot be empty.'];
        }

        if (trim($this->employeeId) === '') {
            $errors['employee_id'] = ['Employee ID cannot be empty.'];
        }

        if (mb_strlen(trim($this->reason)) < 5) {
            $errors['reason'] = ['Reason must contain at least 5 characters explaining the adjustment.'];
        }

        if ($this->status === AdjustmentStatus::APPROVED) {
            if ($this->approverId === null || trim($this->approverId) === '') {
                $errors['approver_id'] = ['Approved request must specify an approver ID.'];
            }
            if ($this->approvedAt === null) {
                $errors['approved_at'] = ['Approved request must record approval timestamp.'];
            }
        }

        if ($this->status === AdjustmentStatus::REJECTED) {
            if ($this->approverId === null || trim($this->approverId) === '') {
                $errors['approver_id'] = ['Rejected request must specify an approver ID.'];
            }
            if ($this->reviewComment === null || trim($this->reviewComment) === '') {
                $errors['review_comment'] = ['Rejected request must record rejection reason.'];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'TimeAdjustmentRequest validation failed.');
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
        return 'TimeAdjustmentRequest';
    }

    public function toAuditArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'requested_date' => $this->requestedDate->format('Y-m-d'),
            'requested_time' => $this->requestedTime->format(DateTimeInterface::ATOM),
            'status' => $this->status->value,
            'approver_id' => $this->approverId,
            'approved_at' => $this->approvedAt?->format(DateTimeInterface::ATOM),
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'requested_date' => $this->requestedDate->format('Y-m-d'),
            'original_time' => $this->originalTime?->format(DateTimeInterface::ATOM),
            'requested_time' => $this->requestedTime->format(DateTimeInterface::ATOM),
            'reason' => $this->reason,
            'attachment_path' => $this->attachmentPath,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approver_id' => $this->approverId,
            'approved_at' => $this->approvedAt?->format(DateTimeInterface::ATOM),
            'review_comment' => $this->reviewComment,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'time_delta_minutes' => $this->getTimeDeltaMinutes(),
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
