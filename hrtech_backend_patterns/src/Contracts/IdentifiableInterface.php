<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface IdentifiableInterface
 *
 * Enforces unique identification across all domain entities.
 */
interface IdentifiableInterface
{
    /**
     * Returns the unique string identifier for the entity (e.g. UUID, ULID, or string ID).
     *
     * @return string
     */
    public function getId(): string;
}
