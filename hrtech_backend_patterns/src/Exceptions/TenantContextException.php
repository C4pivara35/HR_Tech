<?php

declare(strict_types=1);

namespace HrTech\Exceptions;

use Throwable;

/**
 * Thrown when multi-tenant boundaries or isolation contexts are violated.
 */
class TenantContextException extends HrTechException
{
    public function __construct(
        string $message = 'Tenant context violation',
        int $code = 403,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public static function missingContext(string $operation = ''): static
    {
        $action = $operation !== '' ? " while executing: '{$operation}'" : '';
        return new static(
            "No active tenant context in TenantContextManager{$action}. Multi-tenant isolation violation.",
            403,
            null,
            ['operation' => $operation]
        );
    }

    public static function mismatch(string $expectedTenantId, string $actualTenantId, string $resource = ''): static
    {
        return new static(
            "Cross-tenant isolation violation: resource '{$resource}' belongs to tenant '{$actualTenantId}', but active session is tenant '{$expectedTenantId}'.",
            403,
            null,
            [
                'expected_tenant_id' => $expectedTenantId,
                'actual_tenant_id' => $actualTenantId,
                'resource' => $resource,
            ]
        );
    }

    public static function alreadySet(string $currentTenantId): static
    {
        return new static(
            "Tenant context is already active for tenant '{$currentTenantId}'. Context switching requires explicit reset or scoped execution.",
            409,
            null,
            ['current_tenant_id' => $currentTenantId]
        );
    }
}
