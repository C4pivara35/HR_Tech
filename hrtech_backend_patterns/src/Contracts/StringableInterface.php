<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface StringableInterface
 *
 * Contract for objects capable of being cast to string.
 */
interface StringableInterface extends \Stringable
{
    /**
     * Formats object as string.
     *
     * @return string
     */
    public function __toString(): string;
}
