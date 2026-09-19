<?php

declare(strict_types=1);

namespace HrTech\Services;

use DateTimeImmutable;
use HrTech\Domain\Entities\VacationRequest;
use HrTech\Domain\Enums\VacationStatus;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\EmployeeRepositoryInterface;
use HrTech\Repositories\Contracts\VacationRepositoryInterface;

/**
 * Class VacationService
 *
 * Domain service managing CLT vacation requests, 30-day notice verification,
 * abono pecuniário, and manager/HR approval workflows.
 *
 * @package HrTech\Services
 * @author Valentin (Membro 4 — CRUD 7: Gestão de Férias)
 */
class VacationService
{
    public function __construct(
        private readonly VacationRepositoryInterface $repository,
        private readonly ?EmployeeRepositoryInterface $employeeRepository = null
    ) {
    }

    /**
     * Submits a new vacation request conforming to CLT rules.
     *
     * @throws ValidationException
     */
    public function requestVacation(
        string $id,
        string $tenantId,
        string $employeeId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        int $durationDays,
        bool $abonoPecuniario = false,
        bool $advanceThirteenthSalary = false,
        ?int $abonoDays = null
    ): VacationRequest {
        if ($this->employeeRepository !== null) {
            $employee = $this->employeeRepository->findById($employeeId, $tenantId);
            if ($employee !== null && $employee->getVacationBalanceDays() < $durationDays) {
                throw ValidationException::forField(
                    'duration_days',
                    "Insufficient vacation balance: employee has {$employee->getVacationBalanceDays()} days available."
                );
            }
        }

        $request = new VacationRequest(
            id: $id,
            tenantId: $tenantId,
            employeeId: $employeeId,
            startDate: $startDate,
            endDate: $endDate,
            durationDays: $durationDays,
            abonoPecuniario: $abonoPecuniario,
            advanceThirteenthSalary: $advanceThirteenthSalary,
            status: VacationStatus::REQUESTED,
            abonoDays: $abonoDays
        );

        $this->repository->save($request);

        return $request;
    }

    public function getVacationRequest(string $id, string $tenantId): ?VacationRequest
    {
        return $this->repository->findById($id, $tenantId);
    }

    /**
     * @return array<int, VacationRequest>
     */
    public function listVacationsByEmployee(string $employeeId, string $tenantId): array
    {
        return $this->repository->findByEmployee($employeeId, $tenantId);
    }

    /**
     * @return array<int, VacationRequest>
     */
    public function listPendingVacations(string $tenantId): array
    {
        return $this->repository->findByStatus(VacationStatus::REQUESTED, $tenantId);
    }

    /**
     * Approves vacation request and deducts vacation balance if employee repository is configured.
     *
     * @throws InvalidOperationException
     */
    public function approveVacation(string $id, string $tenantId, string $approverId): VacationRequest
    {
        $request = $this->getVacationRequest($id, $tenantId);
        if ($request === null) {
            throw new InvalidOperationException("Vacation request '{$id}' not found in tenant '{$tenantId}'.");
        }

        $request->approve($approverId);
        $this->repository->update($request);

        if ($this->employeeRepository !== null) {
            $employee = $this->employeeRepository->findById($request->employeeId, $tenantId);
            if ($employee !== null) {
                $employee->deductVacationDays($request->durationDays);
                $this->employeeRepository->update($employee);
            }
        }

        return $request;
    }

    /**
     * Rejects vacation request.
     *
     * @throws InvalidOperationException
     */
    public function rejectVacation(
        string $id,
        string $tenantId,
        string $approverId,
        string $reason
    ): VacationRequest {
        $request = $this->getVacationRequest($id, $tenantId);
        if ($request === null) {
            throw new InvalidOperationException("Vacation request '{$id}' not found in tenant '{$tenantId}'.");
        }

        $request->reject($approverId, $reason);
        $this->repository->update($request);

        return $request;
    }

    /**
     * Cancels vacation request (requester only).
     *
     * @throws InvalidOperationException
     */
    public function cancelVacation(string $id, string $tenantId, string $requesterId): VacationRequest
    {
        $request = $this->getVacationRequest($id, $tenantId);
        if ($request === null) {
            throw new InvalidOperationException("Vacation request '{$id}' not found in tenant '{$tenantId}'.");
        }

        $request->cancel($requesterId);
        $this->repository->update($request);

        return $request;
    }

    public function deleteVacationRequest(string $id, string $tenantId): bool
    {
        return $this->repository->delete($id, $tenantId);
    }
}
