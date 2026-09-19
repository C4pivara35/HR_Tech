<?php

declare(strict_types=1);

namespace HrTech\Exceptions;

use Throwable;

/**
 * Thrown when authentication or authorization permissions are insufficient.
 */
class UnauthorizedException extends HrTechException
{
    public function __construct(
        string $message = 'Unauthorized access',
        private readonly ?string $requiredRole = null,
        private readonly ?string $action = null,
        int $code = 403,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct(
            $message,
            $code,
            $previous,
            array_merge($context, [
                'required_role' => $requiredRole,
                'action' => $action,
            ])
        );
    }

    public static function forRole(string $requiredRole, string $action = ''): static
    {
        $actionSuffix = $action !== '' ? " for action '{$action}'" : '';
        return new static(
            "Access denied: role '{$requiredRole}' is required{$actionSuffix}.",
            $requiredRole,
            $action,
            403
        );
    }

    public static function unauthenticated(string $message = 'Authentication required'): static
    {
        return new static($message, null, null, 401);
    }

    public function getRequiredRole(): ?string
    {
        return $this->requiredRole;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }
}
