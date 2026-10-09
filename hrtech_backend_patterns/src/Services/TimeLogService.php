<?php

declare(strict_types=1);

namespace HrTech\Services;

use DateTimeImmutable;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\TimeLogRepositoryInterface;

/**
 * Class TimeLogService
 *
 * Domain service orchestrating electronic time punches, NSR sequential counter increments,
 * SHA-256 cryptographic chain integrity, and Portaria 671/2021 MTE compliance.
 *
 * @package HrTech\Services
 * @author Felipe (Membro 3 — CRUD 5: Marcação de Ponto)
 */
class TimeLogService
{
    public function __construct(
        private readonly TimeLogRepositoryInterface $repository
    ) {
    }

    /**
     * Registra uma marcação de ponto eletrôúnico imutável conforme a Portaria 671/2021 MTE.
     *
     * @throws ValidationException
     */
    public function recordPunch(
        string $id,
        string $tenantId,
        string $employeeId,
        DateTimeImmutable $timestamp,
        TimeLogType|string $type,
        GeoLocation $location
    ): TimeLog {
        // [Trilha Arquitetural: 1. Validação de Invariantes de Enum e Tipos de Batida]
        $logType = $type instanceof TimeLogType ? $type : TimeLogType::from($type);

        // [Trilha Arquitetural: 2. Sequenciamento Atômico de NSR (Número Sequencial de Registro - Portaria 671)]
        $nextNsr = $this->repository->getLatestNsr($tenantId) + 1;

        // [Trilha Arquitetural: 3. Encadeamento Criptográfico SHA-256 (Ledger com a Batida Anterior)]
        $lastPunch = $this->repository->findLastByEmployee($employeeId, $tenantId);
        $previousHash = $lastPunch?->signatureHash ?? TimeLog::GENESIS_PREVIOUS_HASH;

        // [Trilha Arquitetural: 4. Construção da Entidade Imutável TimeLog com Hash SHA-256]
        $timeLog = new TimeLog(
            id: $id,
            tenantId: $tenantId,
            employeeId: $employeeId,
            timestamp: $timestamp,
            type: $logType,
            location: $location,
            nsr: $nextNsr,
            previousHash: $previousHash
        );

        // [Trilha Arquitetural: 5. Persistência no Repositório Relacional (Tabela time_logs)]
        $this->repository->save($timeLog);

        return $timeLog;
    }

    public function getTimeLog(string $id, string $tenantId): ?TimeLog
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @return array<int, TimeLog>
     */
    public function listEmployeePunches(
        string $employeeId,
        string $tenantId,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        return $this->repository->findByEmployeeAndPeriod($employeeId, $tenantId, $start, $end);
    }

    /**
     * Verifica a integridade da cadeia criptográfica de todas as batidas de ponto da empresa.
     */
    public function verifyTamperProofChain(string $tenantId, ?string $employeeId = null): bool
    {
        return $this->repository->verifyChainIntegrity($tenantId, $employeeId);
    }

    /**
     * Strict legal prohibition: time marks cannot be modified under Portaria 671/2021 MTE.
     *
     * @throws InvalidOperationException
     */
    public function updatePunch(): never
    {
        throw new InvalidOperationException(
            'Direct modification of time marks is strictly prohibited by Portaria 671/2021 MTE. Use TimeAdjustmentRequest instead.'
        );
    }

    /**
     * Strict legal prohibition: time marks cannot be deleted under Portaria 671/2021 MTE.
     *
     * @throws InvalidOperationException
     */
    public function deletePunch(): never
    {
        throw new InvalidOperationException(
            'Direct deletion of time marks is strictly prohibited by Portaria 671/2021 MTE.'
        );
    }
}
