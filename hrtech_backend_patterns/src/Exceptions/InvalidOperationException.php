<?php

declare(strict_types=1);

namespace HrTech\Exceptions;

use Throwable;

/**
 * Lançada quando uma operação de negócio ilegal ou transição inválida de ciclo de vida é tentada.
 */
class InvalidOperationException extends HrTechException
{
    public function __construct(
        string $message = 'Invalid operation',
        int $code = 400,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public static function invalidState(string $entity, string $currentState, string $attemptedAction): static
    {
        return new static(
            "Cannot perform action '{$attemptedAction}' on {$entity} in current state '{$currentState}'.",
            400,
            null,
            [
                'entity' => $entity,
                'current_state' => $currentState,
                'attempted_action' => $attemptedAction,
            ]
        );
    }

    public static function businessRule(string $ruleName, string $reason): static
    {
        return new static(
            "Business rule '{$ruleName}' violated: {$reason}",
            422,
            null,
            ['rule' => $ruleName, 'reason' => $reason]
        );
    }
}
