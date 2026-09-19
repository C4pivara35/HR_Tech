<?php

declare(strict_types=1);

namespace HrTech\Exceptions;

use Throwable;

/**
 * Thrown when a tenant cannot be located by ID, CNPJ, or domain.
 */
class TenantNotFoundException extends HrTechException
{
    private readonly string $tenantId;

    public function __construct(
        string $tenantIdentifierOrMessage,
        string|int $tenantIdOrCode = 404,
        ?Throwable $previous = null,
        array $context = []
    ) {
        if (is_string($tenantIdOrCode) && !is_numeric($tenantIdOrCode)) {
            // Called like: new TenantNotFoundException('Tenant not found', 'tenant-123')
            $message = $tenantIdentifierOrMessage;
            $this->tenantId = $tenantIdOrCode;
            $code = 404;
        } else {
            // Called like: new TenantNotFoundException('tenant-123', 404) or new TenantNotFoundException('tenant-123')
            $this->tenantId = $tenantIdentifierOrMessage;
            $code = is_numeric($tenantIdOrCode) ? (int)$tenantIdOrCode : 404;
            $message = "Tenant '{$tenantIdentifierOrMessage}' was not found.";
        }

        parent::__construct($message, $code, $previous, array_merge($context, ['tenant_id' => $this->tenantId]));
    }

    public static function forId(string $id): static
    {
        return new static("Tenant with ID '{$id}' was not found.", $id);
    }

    public static function forCnpj(string $cnpj): static
    {
        return new static("Tenant with CNPJ '{$cnpj}' was not found.", $cnpj);
    }

    public static function forSubdomain(string $subdomain): static
    {
        return new static("Tenant with subdomain '{$subdomain}' was not found.", $subdomain);
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getTenantIdentifier(): string
    {
        return $this->tenantId;
    }
}
