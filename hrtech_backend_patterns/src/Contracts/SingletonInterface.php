<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface SingletonInterface
 *
 * Architectural contract for Singleton implementations, ensuring uniform
 * instance retrieval and test-isolation reset capabilities.
 */
interface SingletonInterface
{
    /**
     * Returns the unique singleton instance.
     *
     * @return static
     */
    public static function getInstance(): static;

    /**
     * Resets the singleton instance to null (for test isolation and context teardown).
     *
     * @return void
     */
    public static function resetInstance(): void;
}
