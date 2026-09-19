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
use HrTech\Domain\Enums\VacationStatus;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class VacationRequest
 *
 * Implements vacation scheduling and lifecycle compliance under CLT Articles 129–145,
 * including 30-day notice periods, 3-period split rules, and abono pecuniário cashout.
 */
class VacationRequest implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    AuditableInterface,
    ArrayableInterface,
    JsonableInterface,
    JsonSerializable
{
    public const int MAX_ANNUAL_VACATION_DAYS = 30;
    public const int MAX_ABONO_DAYS = 10;
    public const int MIN_NOTICE_DAYS = 30;
    public const int MIN_SPLIT_PERIOD_DAYS = 5;
    public const int MIN_MAJOR_PERIOD_DAYS = 14;

    public readonly int $abonoDays;
    public readonly DateTimeImmutable $createdAt;
    private VacationStatus $status;
    private ?string $approverId;
    private ?DateTimeImmutable $approvedAt;
    private ?string $rejectionReason;

    /**
     * @param string $id
     * @param string $tenantId
     * @param string $employeeId
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @param int $durationDays
     * @param bool $abonoPecuniario Whether employee sells 1/3 vacation (up to 10 days)
     * @param bool $advanceThirteenthSalary Whether employee requests 50% 13th salary advance
     * @param VacationStatus $status
     * @param string|null $approverId
     * @param DateTimeImmutable|null $approvedAt
     * @param string|null $rejectionReason
     * @param int|null $abonoDays Explicit abono days
     * @param DateTimeImmutable|null $createdAt
     * @throws ValidationException
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly DateTimeImmutable $startDate,
        public readonly DateTimeImmutable $endDate,
        public readonly int $durationDays,
        public readonly bool $abonoPecuniario = false,
        public readonly bool $advanceThirteenthSalary = false,
        VacationStatus $status = VacationStatus::REQUESTED,
        ?string $approverId = null,
        ?DateTimeImmutable $approvedAt = null,
        ?string $rejectionReason = null,
        ?int $abonoDays = null,
        ?DateTimeImmutable $createdAt = null,
    ) {
        if ($abonoDays !== null) {
            $this->abonoDays = $abonoDays;
        } elseif ($abonoPecuniario) {
            // Statutory 1/3 of requested vacation period under CLT Art. 143 (max 10 days)
            $calculated = (int)round($durationDays / 3);
            $this->abonoDays = min(self::MAX_ABONO_DAYS, max(1, $calculated));
        } else {
            $this->abonoDays = 0;
        }

        $this->status = $status;
        $this->approverId = $approverId !== null && trim($approverId) !== '' ? trim($approverId) : null;
        $this->approvedAt = $approvedAt;
        $this->rejectionReason = $rejectionReason !== null && trim($rejectionReason) !== '' ? trim($rejectionReason) : null;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->validate();
    }

    /**
     * Calculates days of abono pecuniário (cashout) under Art. 143 CLT.
     */
    public function calculateAbonoDays(): int
    {
        return $this->abonoPecuniario ? $this->abonoDays : 0;
    }

    public function getDurationDays(): int
    {
        return $this->durationDays;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function isAbonoPecuniario(): bool
    {
        return $this->abonoPecuniario;
    }

    public function isAdvanceThirteenthSalary(): bool
    {
        return $this->advanceThirteenthSalary;
    }

    /**
     * Validates that the vacation start date satisfies the minimum 30-day notice rule (Art. 135 CLT).
     *
     * @param DateTimeImmutable|null $referenceDate
     * @throws ValidationException
     */
    public function validateNoticePeriod(?DateTimeImmutable $referenceDate = null): void
    {
        $ref = ($referenceDate ?? $this->createdAt)->setTime(0, 0, 0);
        $start = $this->startDate->setTime(0, 0, 0);

        $diff = $ref->diff($start);
        if ($diff->invert === 1 || $diff->days < self::MIN_NOTICE_DAYS) {
            throw ValidationException::forField(
                'start_date',
                sprintf(
                    'Vacation start date requires at least %d days notice under Art. 135 CLT (%d days provided).',
                    self::MIN_NOTICE_DAYS,
                    $diff->invert === 1 ? -((int)$diff->days) : (int)$diff->days
                )
            );
        }
    }

    /**
     * Validates a complete split schedule of up to 3 vacation periods under Art. 134 §1 CLT.
     *
     * @param int[] $periodDurations Days per period (e.g. [14, 8, 8], [15, 15], or [20] with abono)
     * @param bool $hasAbono Whether 10 days of abono are taken
     * @throws ValidationException
     */
    public static function validatePeriodSplit(array $periodDurations, bool $hasAbono = false): void
    {
        $count = count($periodDurations);
        if ($count < 1 || $count > 3) {
            throw ValidationException::forField(
                'periods',
                "Vacations can be split into a maximum of 3 periods under Art. 134 §1 CLT ({$count} provided)."
            );
        }

        $abonoDays = $hasAbono ? self::MAX_ABONO_DAYS : 0;
        $totalDays = array_sum($periodDurations) + $abonoDays;

        if ($totalDays !== self::MAX_ANNUAL_VACATION_DAYS) {
            throw ValidationException::forField(
                'periods',
                sprintf(
                    'Total vacation period days plus abono must equal exactly %d days (%d days provided).',
                    self::MAX_ANNUAL_VACATION_DAYS,
                    $totalDays
                )
            );
        }

        if ($count > 1) {
            $hasMajorPeriod = false;
            foreach ($periodDurations as $index => $days) {
                if ($days < self::MIN_SPLIT_PERIOD_DAYS) {
                    throw ValidationException::forField(
                        "period_{$index}",
                        sprintf(
                            'Split vacation period cannot be less than %d days under Art. 134 §1 CLT (%d days provided).',
                            self::MIN_SPLIT_PERIOD_DAYS,
                            $days
                        )
                    );
                }
                if ($days >= self::MIN_MAJOR_PERIOD_DAYS) {
                    $hasMajorPeriod = true;
                }
            }

            if (!$hasMajorPeriod) {
                throw ValidationException::forField(
                    'periods',
                    sprintf(
                        'At least one vacation period must be >= %d calendar days under Art. 134 §1 CLT.',
                        self::MIN_MAJOR_PERIOD_DAYS
                    )
                );
            }
        }
    }

    /**
     * Checks if vacation start date falls on Friday (5) or Saturday (6) prior to Sunday rest (Art. 134 §3 CLT).
     */
    public function startsOnRestDayEve(): bool
    {
        $dayOfWeek = (int)$this->startDate->format('N'); // 1 = Monday, 7 = Sunday
        return $dayOfWeek === 5 || $dayOfWeek === 6;
    }

    // --------------------------------------------------------------------------
    // Workflow State Transitions
    // --------------------------------------------------------------------------

    /**
     * Approves the vacation request. Transitions REQUESTED -> APPROVED_BY_MANAGER,
     * or APPROVED_BY_MANAGER -> APPROVED_BY_HR.
     *
     * @param string $approverId
     * @throws InvalidOperationException
     * @throws ValidationException
     */
    public function approve(string $approverId): void
    {
        $targetStatus = ($this->status === VacationStatus::REQUESTED)
            ? VacationStatus::APPROVED_BY_MANAGER
            : VacationStatus::APPROVED_BY_HR;

        if (!$this->status->canTransitionTo($targetStatus)) {
            throw InvalidOperationException::invalidState(
                'VacationRequest',
                $this->status->value,
                'approve'
            );
        }

        $cleanApprover = trim($approverId);
        if ($cleanApprover === '') {
            throw ValidationException::forField('approver_id', 'Approver ID cannot be empty.');
        }

        if ($cleanApprover === $this->employeeId) {
            throw InvalidOperationException::businessRule(
                'SelfApprovalProhibited',
                'An employee cannot approve their own vacation request.'
            );
        }

        $this->status = $targetStatus;
        $this->approverId = $cleanApprover;
        $this->approvedAt = new DateTimeImmutable();
    }

    /**
     * Rejects the vacation request with justification.
     */
    public function reject(string $approverId, string $reason): void
    {
        if (!$this->status->canTransitionTo(VacationStatus::REJECTED)) {
            throw InvalidOperationException::invalidState(
                'VacationRequest',
                $this->status->value,
                'reject'
            );
        }

        $cleanApprover = trim($approverId);
        if ($cleanApprover === '') {
            throw ValidationException::forField('approver_id', 'Approver ID cannot be empty.');
        }

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::forField('reason', 'Rejection reason cannot be empty.');
        }

        $this->status = VacationStatus::REJECTED;
        $this->approverId = $cleanApprover;
        $this->approvedAt = new DateTimeImmutable();
        $this->rejectionReason = $cleanReason;
    }

    public function startVacation(): void
    {
        if (!$this->status->canTransitionTo(VacationStatus::IN_PROGRESS)) {
            throw InvalidOperationException::invalidState('VacationRequest', $this->status->value, 'startVacation');
        }
        $this->status = VacationStatus::IN_PROGRESS;
    }

    public function completeVacation(): void
    {
        if (!$this->status->canTransitionTo(VacationStatus::COMPLETED)) {
            throw InvalidOperationException::invalidState('VacationRequest', $this->status->value, 'completeVacation');
        }
        $this->status = VacationStatus::COMPLETED;
    }

    // --------------------------------------------------------------------------
    // Query & Inspection Methods
    // --------------------------------------------------------------------------

    public function getStatus(): VacationStatus
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

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function isPendingApproval(): bool
    {
        return $this->status->isPendingApproval();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
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
            $errors['id'][] = 'ID cannot be empty.';
        }

        if (trim($this->tenantId) === '') {
            $errors['tenant_id'][] = 'Tenant ID cannot be empty.';
        }

        if (trim($this->employeeId) === '') {
            $errors['employee_id'][] = 'Employee ID cannot be empty.';
        }

        if ($this->startDate > $this->endDate) {
            $errors['date_range'][] = 'Start date cannot be after end date.';
        }

        // Inclusive calendar days check
        $expectedDays = (int)$this->startDate->diff($this->endDate)->days + 1;
        if ($this->durationDays !== $expectedDays) {
            $errors['duration_days'][] = "Duration ({$this->durationDays} days) does not match inclusive calendar dates ({$expectedDays} days).";
        }

        if ($this->durationDays < self::MIN_SPLIT_PERIOD_DAYS) {
            $errors['duration_days'][] = sprintf('Vacation duration cannot be less than %d days under Art. 134 §1 CLT.', self::MIN_SPLIT_PERIOD_DAYS);
        }

        if ($this->durationDays > self::MAX_ANNUAL_VACATION_DAYS) {
            $errors['duration_days'][] = sprintf('Vacation duration cannot exceed %d days.', self::MAX_ANNUAL_VACATION_DAYS);
        }

        // Statutory notice check under Art. 135 CLT
        $ref = $this->createdAt->setTime(0, 0, 0);
        $start = $this->startDate->setTime(0, 0, 0);
        $diff = $ref->diff($start);
        if ($diff->invert === 1 || $diff->days < self::MIN_NOTICE_DAYS) {
            $errors['start_date'][] = sprintf(
                'Vacation start date requires at least %d days notice under Art. 135 CLT (%d days provided).',
                self::MIN_NOTICE_DAYS,
                $diff->invert === 1 ? -((int)$diff->days) : (int)$diff->days
            );
        }

        if ($this->abonoPecuniario) {
            if ($this->abonoDays <= 0 || $this->abonoDays > self::MAX_ABONO_DAYS) {
                $errors['abono_days'][] = sprintf('Abono pecuniário days must be between 1 and %d days under Art. 143 CLT.', self::MAX_ABONO_DAYS);
            }
            if ($this->durationDays + $this->abonoDays > self::MAX_ANNUAL_VACATION_DAYS) {
                $errors['abono_days'][] = sprintf('Duration plus abono (%d days) cannot exceed %d days.', $this->durationDays + $this->abonoDays, self::MAX_ANNUAL_VACATION_DAYS);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'VacationRequest validation failed.');
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
        return 'VacationRequest';
    }

    public function toAuditArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'duration_days' => $this->durationDays,
            'abono_days' => $this->calculateAbonoDays(),
            'advance_13th' => $this->advanceThirteenthSalary,
            'status' => $this->status->value,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'duration_days' => $this->durationDays,
            'abono_pecuniario' => $this->abonoPecuniario,
            'abono_days' => $this->calculateAbonoDays(),
            'advance_thirteenth_salary' => $this->advanceThirteenthSalary,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approver_id' => $this->approverId,
            'approved_at' => $this->approvedAt?->format(DateTimeInterface::ATOM),
            'rejection_reason' => $this->rejectionReason,
            'starts_on_rest_day_eve' => $this->startsOnRestDayEve(),
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
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
