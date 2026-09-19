<?php

declare(strict_types=1);

namespace HrTech\Services;

use HrTech\Domain\Entities\Tenant;
use HrTech\Domain\ValueObjects\Cnpj;
use HrTech\Exceptions\TenantNotFoundException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\Contracts\TenantRepositoryInterface;

/**
 * Class TenantService
 *
 * Domain service orchestrating tenant lifecycle, CNPJ validation, licensing,
 * and multi-tenant organization management.
 *
 * @package HrTech\Services
 * @author Fernando Lopes Duarte (Membro 1 — CRUD 1: Gestão de Tenants)
 */
class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository
    ) {
    }

    /**
     * Creates and persists a new tenant enterprise.
     *
     * @param string $id
     * @param string|Cnpj $cnpj
     * @param string $corporateName Razão Social
     * @param string $tradingName Nome Fantasia
     * @param string $segment Market segment (tech, industria, financeiro)
     * @param array<int, string> $modules
     * @throws ValidationException
     */
    public function createTenant(
        string $id,
        string|Cnpj $cnpj,
        string $corporateName,
        string $tradingName,
        string $segment = 'tech',
        array $modules = []
    ): Tenant {
        $cnpjVo = $cnpj instanceof Cnpj ? $cnpj : new Cnpj($cnpj);

        if ($this->repository->exists($id)) {
            throw ValidationException::forField('id', "Tenant with ID '{$id}' already exists.");
        }

        if ($this->repository->existsCnpj($cnpjVo)) {
            throw ValidationException::forField('cnpj', "Tenant with CNPJ '{$cnpjVo->getFormatted()}' already exists.");
        }

        $tenant = new Tenant(
            id: $id,
            cnpj: $cnpjVo,
            corporateName: $corporateName,
            tradingName: $tradingName,
            isActive: true,
            moduleLicenses: $modules,
            createdAt: null,
            segment: $segment
        );

        $this->repository->save($tenant);

        return $tenant;
    }

    /**
     * Finds a tenant by ID or throws TenantNotFoundException.
     *
     * @throws TenantNotFoundException
     */
    public function getTenantById(string $id): Tenant
    {
        $tenant = $this->repository->findById($id);
        if ($tenant === null) {
            throw TenantNotFoundException::forId($id);
        }

        return $tenant;
    }

    /**
     * Finds a tenant by CNPJ.
     */
    public function getTenantByCnpj(string|Cnpj $cnpj): ?Tenant
    {
        return $this->repository->findByCnpj($cnpj);
    }

    /**
     * Lists all registered tenants, optionally filtering active only.
     *
     * @return array<int, Tenant>
     */
    public function listTenants(bool $onlyActive = false): array
    {
        return $this->repository->findAll($onlyActive);
    }

    /**
     * Updates corporate and trading names.
     */
    public function updateTenantNames(string $id, string $corporateName, string $tradingName): Tenant
    {
        $tenant = $this->getTenantById($id);
        $tenant->updateNames($corporateName, $tradingName);
        $this->repository->update($tenant);

        return $tenant;
    }

    /**
     * Updates tenant market segment.
     */
    public function updateSegment(string $id, string $segment): Tenant
    {
        $tenant = $this->getTenantById($id);
        $tenant->setSegment($segment);
        $this->repository->update($tenant);

        return $tenant;
    }

    /**
     * Toggles tenant operational activation status.
     */
    public function toggleActive(string $id, bool $active): Tenant
    {
        $tenant = $this->getTenantById($id);
        if ($active) {
            $tenant->activate();
        } else {
            $tenant->deactivate();
        }
        $this->repository->update($tenant);

        return $tenant;
    }

    /**
     * Replaces licensed module portfolio.
     *
     * @param array<int, string> $modules
     */
    public function setModules(string $id, array $modules): Tenant
    {
        $tenant = $this->getTenantById($id);
        $tenant->setModuleLicenses($modules);
        $this->repository->update($tenant);

        return $tenant;
    }

    /**
     * Deletes a tenant and cascades to all child relations.
     */
    public function deleteTenant(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
