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
     * Retorna o identificador úúnico da entidade (ex.: UUID, ULID ou ID alfanumérico).
     *
     * @return string
     */
    public function getId(): string;
}
