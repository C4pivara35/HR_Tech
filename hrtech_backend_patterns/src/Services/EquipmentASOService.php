<?php

declare(strict_types=1);

namespace HrTech\Services;

use DateTimeImmutable;
use HrTech\Domain\Entities\EquipmentASO;
use HrTech\Domain\Enums\ExamType;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\EquipmentASORepositoryInterface;

/**
 * Class EquipmentASOService
 *
 * Domain service managing occupational safety gear (NR-6 CA validation),
 * medical health certificates (NR-7 ASO), expiration alerts, and equipment returns.
 *
 * @package HrTech\Services
 * @author Nicholas (Membro 5 — CRUD 9: Controle de EPIs e ASO)
 */
class EquipmentASOService
{
    public function __construct(
        private readonly EquipmentASORepositoryInterface $repository
    ) {
    }

    /**
     * Delivers PPE equipment to an employee with NR-6 CA certification details.
     *
     * @throws ValidationException
     */
    public function deliverEquipment(
        string $id,
        string $tenantId,
        string $employeeId,
        string $equipmentName,
        string $caNumber,
        ?DateTimeImmutable $caExpirationDate = null,
        ?DateTimeImmutable $deliveryDate = null
    ): EquipmentASO {
        $record = new EquipmentASO(
            id: $id,
            tenantId: $tenantId,
            employeeId: $employeeId,
            equipmentName: $equipmentName,
            caNumber: $caNumber,
            caExpirationDate: $caExpirationDate,
            deliveryDate: $deliveryDate ?? new DateTimeImmutable(),
            examType: ExamType::PERIODIC,
            isFit: true
        );

        $this->repository->save($record);

        return $record;
    }

    /**
     * Records an occupational medical examination certificate (ASO) under NR-7 PCMSO.
     *
     * @throws ValidationException
     */
    public function recordMedicalExam(
        string $id,
        string $tenantId,
        string $employeeId,
        ExamType|string $examType,
        DateTimeImmutable $examDate,
        DateTimeImmutable $expirationDate,
        string $physicianName,
        string $physicianCrm,
        bool $isFit = true,
        string $equipmentName = 'ASO Examination',
        string $caNumber = 'N/A'
    ): EquipmentASO {
        $type = $examType instanceof ExamType ? $examType : ExamType::from($examType);

        $record = new EquipmentASO(
            id: $id,
            tenantId: $tenantId,
            employeeId: $employeeId,
            equipmentName: $equipmentName,
            caNumber: $caNumber,
            examType: $type,
            examDate: $examDate,
            expirationDate: $expirationDate,
            physicianName: $physicianName,
            physicianCrm: $physicianCrm,
            isFit: $isFit
        );

        $this->repository->save($record);

        return $record;
    }

    public function getRecord(string $id, string $tenantId): ?EquipmentASO
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @return array<int, EquipmentASO>
     */
    public function listByEmployee(string $employeeId, string $tenantId): array
    {
        return $this->repository->findByEmployee($employeeId, $tenantId);
    }

    /**
     * @return array<int, EquipmentASO>
     */
    public function listExpiredExams(string $tenantId, ?DateTimeImmutable $referenceDate = null): array
    {
        return $this->repository->findExpiredExams($tenantId, $referenceDate);
    }

    /**
     * @return array<int, EquipmentASO>
     */
    public function listExpiredEquipment(string $tenantId, ?DateTimeImmutable $referenceDate = null): array
    {
        return $this->repository->findExpiredEquipment($tenantId, $referenceDate);
    }

    public function recordEquipmentReturn(
        string $id,
        string $tenantId,
        ?DateTimeImmutable $returnDate = null
    ): EquipmentASO {
        $record = $this->getRecord($id, $tenantId);
        if ($record === null) {
            throw new InvalidOperationException("Record '{$id}' not found in tenant '{$tenantId}'.");
        }

        $record->recordReturn($returnDate ?? new DateTimeImmutable());
        $this->repository->update($record);

        return $record;
    }

    public function deleteRecord(string $id, string $tenantId): bool
    {
        return $this->repository->delete($id, $tenantId);
    }
}
