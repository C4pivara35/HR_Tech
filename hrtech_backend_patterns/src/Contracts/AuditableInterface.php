<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface AuditableInterface
 *
 * Contract for entities subject to immutable audit logging and compliance tracking.
 */
interface AuditableInterface
{
    /**
     * Returns the unique subject identifier for audit indexing.
     *
     * @return string
     */
    public function getAuditIdentifier(): string;

    /**
     * Returns the category or classification of the auditable subject (e.g., 'Employee', 'TimeLog').
     *
     * @return string
     */
    public function getAuditCategory(): string;

    /**
     * Returns a sanitized array representation suitable for audit log retention.
     * Sensitive attributes (passwords, tokens, salts) must be redacted.
     *
     * @return array<string, mixed>
     */
    public function toAuditArray(): array;
}
