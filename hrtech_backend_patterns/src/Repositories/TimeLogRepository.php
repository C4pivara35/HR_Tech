<?php

declare(strict_types=1);

namespace HrTech\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Database\DatabaseManager;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Repositories\Contracts\TimeLogRepositoryInterface;
use PDO;

/**
 * Class TimeLogRepository
 *
 * SQLite PDO implementation for append-only electronic time mark persistence
 * and SHA-256 chain verification conforming to Portaria 671/2021 MTE.
 *
 * @package HrTech\Repositories
 * @author Felipe (Membro 3 — CRUD 5: Marcação de Ponto)
 */
class TimeLogRepository implements TimeLogRepositoryInterface
{
    private PDO $pdo;

    public function __construct(?DatabaseManager $dbManager = null)
    {
        $this->pdo = ($dbManager ?? DatabaseManager::getInstance())->getConnection();
    }

    public function findById(string $id, string $tenantId): ?TimeLog
    {
        $stmt = $this->pdo->prepare('SELECT * FROM time_logs WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findLastByEmployee(string $employeeId, string $tenantId): ?TimeLog
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM time_logs
             WHERE tenant_id = :tenant_id AND employee_id = :employee_id
             ORDER BY nsr DESC LIMIT 1'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':employee_id' => $employeeId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array<int, TimeLog>
     */
    public function findByEmployeeAndPeriod(
        string $employeeId,
        string $tenantId,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM time_logs
             WHERE tenant_id = :tenant_id
               AND employee_id = :employee_id
               AND timestamp >= :start_time
               AND timestamp <= :end_time
             ORDER BY timestamp ASC, nsr ASC'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':employee_id' => $employeeId,
            ':start_time' => $start->format(DateTimeInterface::ATOM),
            ':end_time' => $end->format(DateTimeInterface::ATOM),
        ]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, TimeLog>
     */
    public function findAllByTenant(string $tenantId, array $filters = []): array
    {
        $sql = 'SELECT * FROM time_logs WHERE tenant_id = :tenant_id';
        $params = [':tenant_id' => $tenantId];

        if (isset($filters['employee_id'])) {
            $sql .= ' AND employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (isset($filters['type'])) {
            $sql .= ' AND type = :type';
            $params[':type'] = $filters['type'] instanceof TimeLogType ? $filters['type']->value : (string)$filters['type'];
        }

        $sql .= ' ORDER BY timestamp DESC, nsr DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function getLatestNsr(string $tenantId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(nsr), 0) FROM time_logs WHERE tenant_id = :tenant_id');
        $stmt->execute([':tenant_id' => $tenantId]);
        return (int)$stmt->fetchColumn();
    }

    public function save(TimeLog $timeLog): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO time_logs (
                id, tenant_id, employee_id, timestamp, type, latitude, longitude, accuracy, nsr, previous_hash, signature_hash
            ) VALUES (
                :id, :tenant_id, :employee_id, :timestamp, :type, :latitude, :longitude, :accuracy, :nsr, :previous_hash, :signature_hash
            )'
        );

        return $stmt->execute([
            ':id' => $timeLog->id,
            ':tenant_id' => $timeLog->tenantId,
            ':employee_id' => $timeLog->employeeId,
            ':timestamp' => $timeLog->timestamp->format(DateTimeInterface::ATOM),
            ':type' => $timeLog->type->value,
            ':latitude' => $timeLog->location->getLatitude(),
            ':longitude' => $timeLog->location->getLongitude(),
            ':accuracy' => $timeLog->location->getAccuracy(),
            ':nsr' => $timeLog->nsr,
            ':previous_hash' => $timeLog->previousHash,
            ':signature_hash' => $timeLog->signatureHash,
        ]);
    }

    public function verifyChainIntegrity(string $tenantId, ?string $employeeId = null): bool
    {
        $sql = 'SELECT * FROM time_logs WHERE tenant_id = :tenant_id';
        $params = [':tenant_id' => $tenantId];

        if ($employeeId !== null) {
            $sql .= ' AND employee_id = :employee_id';
            $params[':employee_id'] = $employeeId;
        }

        $sql .= ' ORDER BY nsr ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return true;
        }

        $prevHash = null;
        foreach ($rows as $row) {
            $log = $this->hydrate($row);
            if (!$log->verifyIntegrity($prevHash)) {
                return false;
            }
            $prevHash = $log->signatureHash;
        }

        return true;
    }

    private function hydrate(array $row): TimeLog
    {
        $location = new GeoLocation(
            latitude: (float)$row['latitude'],
            longitude: (float)$row['longitude'],
            accuracy: $row['accuracy'] !== null ? (float)$row['accuracy'] : null
        );

        return new TimeLog(
            id: (string)$row['id'],
            tenantId: (string)$row['tenant_id'],
            employeeId: (string)$row['employee_id'],
            timestamp: new DateTimeImmutable((string)$row['timestamp']),
            type: TimeLogType::from((string)$row['type']),
            location: $location,
            nsr: (int)$row['nsr'],
            previousHash: $row['previous_hash'] !== null ? (string)$row['previous_hash'] : null,
            signatureHash: (string)$row['signature_hash']
        );
    }
}
