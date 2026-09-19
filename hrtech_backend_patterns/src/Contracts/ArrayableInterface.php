<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface ArrayableInterface
 *
 * Contract for objects that can be represented as an associative array.
 */
interface ArrayableInterface
{
    /**
     * Returns the instance represented as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
