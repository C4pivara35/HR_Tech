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
     * Retorna a instância única do Singleton.
     *
     * @return static
     */
    public static function getInstance(): static;

    /**
     * Reseta a instância Singleton para null (para isolamento em testes).
     *
     * @return void
     */
    public static function resetInstance(): void;
}
