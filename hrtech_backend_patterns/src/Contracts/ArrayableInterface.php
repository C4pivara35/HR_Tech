<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface ArrayableInterface
 *
 * Contrato para objetos que podem ser representados como um array associativo.
 */
interface ArrayableInterface
{
    /**
     * Retorna a instância representada como um array associativo.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
