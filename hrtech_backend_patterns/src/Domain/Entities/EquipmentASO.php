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
use HrTech\Domain\Enums\ExamType;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class EquipmentASO
 *
 * Manages personal protective equipment (EPI - NR-6) and occupational health medical
 * examinations (ASO / PCMSO - NR-7) with CA number and medical certificate expiration tracking.
 */
class EquipmentASO implements
    IdentifiableInterface,
    TenantScopedInterface,
    ValidatableInterface,
    AuditableInterface,
    ArrayableInterface,
    JsonableInterface,
    JsonSerializable
{
    private ?DateTimeImmutable $returnDate;
    public readonly DateTimeImmutable $createdAt;

    /**
     * @param string $id Unique record ID
     * @param string $tenantId Tenant identifier
     * @param string $employeeId Colaborador identifier
     * @param string $equipmentName Name/model of PPE (EPI)
     * @param string $caNumber Certificado de Aprovação issued by MTE
     * @param DateTimeImmutable|null $caExpirationDate Expiration date of CA certification
     * @param DateTimeImmutable|null $deliveryDate Date PPE was delivered to employee
     * @param DateTimeImmutable|null $returnDate Date PPE was returned or decommissioned
     * @param ExamType $examType Type of ASO exam (ADMISSION, PERIODIC, etc.)
     * @param DateTimeImmutable|null $examDate Date examination was performed
     * @param DateTimeImmutable|null $expirationDate Date ASO certificate expires
     * @param string|null $physicianName Name of examining occupational physician
     * @param string|null $physicianCrm CRM number and state of physician
     * @param bool $isFit Whether employee was deemed clinically fit (Apto/Inapto)
     * @param DateTimeImmutable|null $createdAt Record creation timestamp
     * @throws ValidationException
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly string $equipmentName,
        public readonly string $caNumber,
        public readonly ?DateTimeImmutable $caExpirationDate = null,
        public readonly ?DateTimeImmutable $deliveryDate = null,
        ?DateTimeImmutable $returnDate = null,
        public readonly ExamType $examType = ExamType::PERIODIC,
        public readonly ?DateTimeImmutable $examDate = null,
        public readonly ?DateTimeImmutable $expirationDate = null,
        public readonly ?string $physicianName = null,
        public readonly ?string $physicianCrm = null,
        public readonly bool $isFit = true,
        ?DateTimeImmutable $createdAt = null,
    ) {
        $this->returnDate = $returnDate;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->validate();
    }

    /**
     * Checks whether the PPE Certificado de Aprovação (CA) is expired relative to reference date.
     */
    public function isCaExpired(?DateTimeImmutable $referenceDate = null): bool
    {
        if ($this->caExpirationDate === null) {
            return false;
        }

        $ref = ($referenceDate ?? new DateTimeImmutable('today'))->setTime(0, 0, 0);
        $exp = $this->caExpirationDate->setTime(0, 0, 0);

        return $exp < $ref;
    }

    /**
     * Checks whether the ASO medical certificate is expired relative to reference date.
     */
    public function isExamExpired(?DateTimeImmutable $referenceDate = null): bool
    {
        if ($this->expirationDate === null) {
            return false;
        }

        $ref = ($referenceDate ?? new DateTimeImmutable('today'))->setTime(0, 0, 0);
        $exp = $this->expirationDate->setTime(0, 0, 0);

        return $exp < $ref;
    }

    /**
     * Calculates signed integer of days remaining until ASO medical certificate expires.
     * Returns negative value if already expired.
     */
    public function daysUntilExamExpiration(?DateTimeImmutable $referenceDate = null): int
    {
        if ($this->expirationDate === null) {
            return PHP_INT_MAX;
        }

        $ref = ($referenceDate ?? new DateTimeImmutable('today'))->setTime(0, 0, 0);
        $exp = $this->expirationDate->setTime(0, 0, 0);

        $diff = $ref->diff($exp);
        return $diff->invert === 1 ? -((int)$diff->days) : (int)$diff->days;
    }

    /**
     * Calculates signed integer of days remaining until CA certification expires.
     * Returns negative value if already expired.
     */
    public function daysUntilCaExpiration(?DateTimeImmutable $referenceDate = null): int
    {
        if ($this->caExpirationDate === null) {
            return PHP_INT_MAX;
        }

        $ref = ($referenceDate ?? new DateTimeImmutable('today'))->setTime(0, 0, 0);
        $exp = $this->caExpirationDate->setTime(0, 0, 0);

        $diff = $ref->diff($exp);
        return $diff->invert === 1 ? -((int)$diff->days) : (int)$diff->days;
    }

    /**
     * Records the return or decommissioning of the equipment.
     *
     * @param DateTimeImmutable $returnDate
     * @throws ValidationException
     */
    public function recordReturn(DateTimeImmutable $returnDate): void
    {
        if ($this->deliveryDate !== null && $returnDate < $this->deliveryDate) {
            throw ValidationException::forField(
                'return_date',
                sprintf(
                    'Return date (%s) cannot precede delivery date (%s).',
                    $returnDate->format('Y-m-d'),
                    $this->deliveryDate->format('Y-m-d')
                )
            );
        }

        $this->returnDate = $returnDate;
    }

    public function getReturnDate(): ?DateTimeImmutable
    {
        return $this->returnDate;
    }

    /**
     * Checks if equipment is currently in the custody of the employee (delivered and not returned).
     */
    public function isEquipmentActive(): bool
    {
        return $this->deliveryDate !== null && $this->returnDate === null;
    }

    /**
     * Returns clinical fitness status.
     */
    public function isFit(): bool
    {
        return $this->isFit;
    }

    /**
     * Determines whether employee is medically certified fit and within exam validity.
     */
    public function isFitForWork(?DateTimeImmutable $referenceDate = null): bool
    {
        return $this->isFit && !$this->isExamExpired($referenceDate);
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

        if (trim($this->equipmentName) === '') {
            $errors['equipment_name'][] = 'Equipment name cannot be empty.';
        }

        if (trim($this->caNumber) === '') {
            $errors['ca_number'][] = 'CA number cannot be empty.';
        }

        if ($this->deliveryDate !== null && $this->returnDate !== null && $this->returnDate < $this->deliveryDate) {
            $errors['return_date'][] = 'Return date cannot precede delivery date.';
        }

        if ($this->examDate !== null && $this->expirationDate !== null && $this->expirationDate < $this->examDate) {
            $errors['expiration_date'][] = 'Exam expiration date cannot precede examination date.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'EquipmentASO validation failed.');
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
        return 'EquipmentASO';
    }

    public function toAuditArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'equipment_name' => $this->equipmentName,
            'ca_number' => $this->caNumber,
            'exam_type' => $this->examType->value,
            'is_fit' => $this->isFit,
            'is_exam_expired' => $this->isExamExpired(),
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'equipment_name' => $this->equipmentName,
            'ca_number' => $this->caNumber,
            'ca_expiration_date' => $this->caExpirationDate?->format('Y-m-d'),
            'delivery_date' => $this->deliveryDate?->format('Y-m-d'),
            'return_date' => $this->returnDate?->format('Y-m-d'),
            'exam_type' => $this->examType->value,
            'exam_type_label' => $this->examType->label(),
            'exam_date' => $this->examDate?->format('Y-m-d'),
            'expiration_date' => $this->expirationDate?->format('Y-m-d'),
            'physician_name' => $this->physicianName,
            'physician_crm' => $this->physicianCrm,
            'is_fit' => $this->isFit,
            'is_ca_expired' => $this->isCaExpired(),
            'is_exam_expired' => $this->isExamExpired(),
            'days_until_exam_expiration' => $this->daysUntilExamExpiration(),
            'days_until_ca_expiration' => $this->daysUntilCaExpiration(),
            'is_equipment_active' => $this->isEquipmentActive(),
            'is_fit_for_work' => $this->isFitForWork(),
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
