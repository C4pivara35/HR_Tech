<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface TenantScopedInterface
 *
 * Enforces tenant boundary isolation. Implementers are explicitly bound
 * to a specific tenant and cannot be accessed across tenant boundaries.
 */
interface TenantScopedInterface
{
    /**
     * Returns the tenant identifier to which this entity belongs.
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Checks whether this entity belongs to the specified tenant.
     *
     * @param string $tenantId
     * @return bool
     */
    public function belongsToTenant(string $tenantId): bool;
}
