<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\EquipmentASO;
use HrTech\Domain\Enums\ExamType;
use HrTech\Repositories\Contracts\EquipmentASORepositoryInterface;
use PDO;

/**
 * Class EquipmentASORepository
 *
 * SQLite PDO implementation for persistence and compliance tracking of Equipment (NR-6)
 * and ASO medical exams (NR-7).
 *
 * @package HrTech\Repositories
 * @author Nicholas (Membro 5 — CRUD 9: Controle de EPIs e ASO)
 */
class EquipmentASORepository implements EquipmentASORepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?EquipmentASO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM equipment_aso WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, EquipmentASO>
     */
    public function findByEmployee(string $employeeId, string $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM equipment_aso WHERE tenant_id = :tenant_id AND employee_id = :employee_id ORDER BY created_at DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':employee_id' => $employeeId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, EquipmentASO>
     */
    public function findExpiredExams(string $tenantId, ?DateTimeImmutable $referenceDate = null): array
    {
        $ref = ($referenceDate ?? new DateTimeImmutable())->format('Y-m-d');
        $stmt = $this->pdo->prepare(
            'SELECT * FROM equipment_aso
             WHERE tenant_id = :tenant_id
               AND expiration_date IS NOT NULL
               AND expiration_date < :ref_date
             ORDER BY expiration_date ASC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':ref_date' => $ref]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array<int, EquipmentASO>
     */
    public function findExpiredEquipment(string $tenantId, ?DateTimeImmutable $referenceDate = null): array
    {
        $ref = ($referenceDate ?? new DateTimeImmutable())->format('Y-m-d');
        $stmt = $this->pdo->prepare(
            'SELECT * FROM equipment_aso
             WHERE tenant_id = :tenant_id
               AND ca_expiration_date IS NOT NULL
               AND ca_expiration_date < :ref_date
             ORDER BY ca_expiration_date ASC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':ref_date' => $ref]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, EquipmentASO>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array
    {
        $sql = 'SELECT * FROM equipment_aso WHERE tenant_id = :tenant_id';
        $params = [':tenant_id' => $tenantId];

        if (isset($filters['employee_id'])) {
            $sql .= ' AND employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (isset($filters['is_fit'])) {
            $sql .= ' AND is_fit = :is_fit';
            $params[':is_fit'] = $filters['is_fit'] ? 1 : 0;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(EquipmentASO $record): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO equipment_aso (
                id, tenant_id, employee_id, equipment_name, ca_number, ca_expiration_date,
                delivery_date, return_date, exam_type, exam_date, expiration_date,
                physician_name, physician_crm, is_fit, created_at
            ) VALUES (
                :id, :tenant_id, :employee_id, :equipment_name, :ca_number, :ca_expiration_date,
                :delivery_date, :return_date, :exam_type, :exam_date, :expiration_date,
                :physician_name, :physician_crm, :is_fit, :created_at
            )'
        );

        return $stmt->execute([
            ':id' => $record->id,
            ':tenant_id' => $record->tenantId,
            ':employee_id' => $record->employeeId,
            ':equipment_name' => $record->equipmentName,
            ':ca_number' => $record->caNumber,
            ':ca_expiration_date' => $record->caExpirationDate?->format('Y-m-d'),
            ':delivery_date' => $record->deliveryDate?->format('Y-m-d'),
            ':return_date' => $record->getReturnDate()?->format('Y-m-d'),
            ':exam_type' => $record->examType->value,
            ':exam_date' => $record->examDate?->format('Y-m-d'),
            ':expiration_date' => $record->expirationDate?->format('Y-m-d'),
            ':physician_name' => $record->physicianName,
            ':physician_crm' => $record->physicianCrm,
            ':is_fit' => $record->isFit ? 1 : 0,
            ':created_at' => $record->createdAt->format(DateTimeInterface::ATOM),
        ]);
    }

    public function update(EquipmentASO $record): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE equipment_aso
             SET equipment_name = :equipment_name,
                 ca_number = :ca_number,
                 ca_expiration_date = :ca_expiration_date,
                 delivery_date = :delivery_date,
                 return_date = :return_date,
                 exam_type = :exam_type,
                 exam_date = :exam_date,
                 expiration_date = :expiration_date,
                 physician_name = :physician_name,
                 physician_crm = :physician_crm,
                 is_fit = :is_fit
             WHERE id = :id AND tenant_id = :tenant_id'
        );

        return $stmt->execute([
            ':id' => $record->id,
            ':tenant_id' => $record->tenantId,
            ':equipment_name' => $record->equipmentName,
            ':ca_number' => $record->caNumber,
            ':ca_expiration_date' => $record->caExpirationDate?->format('Y-m-d'),
            ':delivery_date' => $record->deliveryDate?->format('Y-m-d'),
            ':return_date' => $record->getReturnDate()?->format('Y-m-d'),
            ':exam_type' => $record->examType->value,
            ':exam_date' => $record->examDate?->format('Y-m-d'),
            ':expiration_date' => $record->expirationDate?->format('Y-m-d'),
            ':physician_name' => $record->physicianName,
            ':physician_crm' => $record->physicianCrm,
            ':is_fit' => $record->isFit ? 1 : 0,
        ]);
    }

    public function delete(string $id, string $tenantId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM equipment_aso WHERE id = :id AND tenant_id = :tenant_id');
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    private function hydrate(array $row): EquipmentASO
    {
        return new EquipmentASO(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            employeeId: (string)$row['employee_id'],
            equipmentName: (string)$row['equipment_name'],
            caNumber: (string)$row['ca_number'],
            caExpirationDate: $row['ca_expiration_date'] !== null ? new DateTimeImmutable((string)$row['ca_expiration_date']) : null,
            deliveryDate: $row['delivery_date'] !== null ? new DateTimeImmutable((string)$row['delivery_date']) : null,
            returnDate: $row['return_date'] !== null ? new DateTimeImmutable((string)$row['return_date']) : null,
            examType: ExamType::from((string)$row['exam_type']),
            examDate: $row['exam_date'] !== null ? new DateTimeImmutable((string)$row['exam_date']) : null,
            expirationDate: $row['expiration_date'] !== null ? new DateTimeImmutable((string)$row['expiration_date']) : null,
            physicianName: $row['physician_name'] !== null ? (string)$row['physician_name'] : null,
            physicianCrm: $row['physician_crm'] !== null ? (string)$row['physician_crm'] : null,
            isFit: (bool)$row['is_fit'],
            createdAt: new DateTimeImmutable((string)$row['created_at'])
        );
    }
}
