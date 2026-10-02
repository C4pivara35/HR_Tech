<?php

declare(strict_types=1);

namespace HrTech\Contracts;

use HrTech\Exceptions\ValidationException;

/**
 * Interface ValidatableInterface
 *
 * Enforces self-validating invariant contracts on domain models and value objects.
 */
interface ValidatableInterface
{
    /**
     * Valida o estado interno de domínio e regras de invariância.
     *
     * @throws ValidationException When domain validation rules are violated.
     * @return void
     */
    public function validate(): void;

    /**
     * Determines whether the entity is currently in a valid state without throwing.
     *
     * @return bool
     */
    public function isValid(): bool;
}
