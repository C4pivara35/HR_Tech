<?php

declare(strict_types=1);

namespace HrTech\Patterns\Singleton;

use HrTech\Contracts\SingletonInterface;
use HrTech\Domain\Entities\Tenant;
use HrTech\Exceptions\TenantContextException;
use HrTech\Exceptions\ValidationException;

/**
 * Class TenantContextManager
 *
 * Singleton managing the active multi-tenant context boundary across the application.
 * Ensures strict cross-tenant isolation and prevents data leaks.
 */
class TenantContextManager implements SingletonInterface
{
    private static ?self $instance = null;

    private ?Tenant $activeTenant = null;
    private ?string $activeTenantId = null;

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization.
     *
     * @throws TenantContextException
     */
    public function __wakeup(): void
    {
        throw new TenantContextException('Cannot unserialize singleton TenantContextManager.');
    }

    /**
     * Returns the unique singleton instance.
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Resets the singleton instance and context to null (for test isolation and context teardown).
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->clearContext();
            self::$instance = null;
        }
    }

    /**
     * Sets the active tenant context using a Tenant entity or tenant identifier string.
     *
     * @param Tenant|string $tenant
     * @throws ValidationException
     */
    public function setActiveTenant(Tenant|string $tenant): void
    {
        if ($tenant instanceof Tenant) {
            $id = trim($tenant->getId());
            if ($id === '') {
                throw ValidationException::forField('tenant', 'Tenant entity ID cannot be empty.');
            }
            $this->activeTenant = $tenant;
            $this->activeTenantId = $id;
        } else {
            $id = trim($tenant);
            if ($id === '') {
                throw ValidationException::forField('tenant_id', 'Tenant ID cannot be empty.');
            }
            $this->activeTenant = null;
            $this->activeTenantId = $id;
        }
    }

    /**
     * Returns the active tenant identifier.
     *
     * @return string
     * @throws TenantContextException If no active tenant context is set.
     */
    public function getActiveTenantId(): string
    {
        $this->assertTenantActive();

        return (string)$this->activeTenantId;
    }

    /**
     * Returns the active Tenant entity if one was provided when setting context.
     *
     * @return Tenant|null
     */
    public function getActiveTenant(): ?Tenant
    {
        return $this->activeTenant;
    }

    /**
     * Checks if an active tenant context is currently set.
     *
     * @return bool
     */
    public function hasActiveTenant(): bool
    {
        return $this->activeTenantId !== null && $this->activeTenantId !== '';
    }

    /**
     * Clears the active tenant context.
     */
    public function clearContext(): void
    {
        $this->activeTenant = null;
        $this->activeTenantId = null;
    }

    /**
     * Asserts that an active tenant context is present.
     *
     * @throws TenantContextException If no tenant context is active.
     */
    public function assertTenantActive(): void
    {
        if (!$this->hasActiveTenant()) {
            throw TenantContextException::missingContext('assertTenantActive');
        }
    }

    /**
     * Asserts that the active tenant matches the given tenant identifier.
     *
     * @param string $tenantId
     * @throws TenantContextException If active tenant does not match or no tenant is active.
     */
    public function assertMatchesTenant(string $tenantId): void
    {
        $this->assertTenantActive();

        $cleanTenantId = trim($tenantId);
        if ($this->activeTenantId !== $cleanTenantId) {
            throw TenantContextException::mismatch((string)$this->activeTenantId, $cleanTenantId);
        }
    }

    /**
     * Executes a callback within a temporary scoped tenant context, restoring the
     * previous context after completion even if an exception is thrown.
     *
     * @template T
     * @param Tenant|string $tenant
     * @param callable(): T $callback
     * @return T
     */
    public function runInContext(Tenant|string $tenant, callable $callback): mixed
    {
        $previousTenant = $this->activeTenant;
        $previousTenantId = $this->activeTenantId;

        $this->setActiveTenant($tenant);

        try {
            return $callback();
        } finally {
            $this->activeTenant = $previousTenant;
            $this->activeTenantId = $previousTenantId;
        }
    }
}
