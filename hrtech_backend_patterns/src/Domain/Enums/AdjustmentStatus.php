<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Status workflow for time log adjustment requests.
 */
enum AdjustmentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente de Análise',
            self::APPROVED => 'Aprovado pelo Gestor',
            self::REJECTED => 'Reprovado pelo Gestor',
            self::CANCELLED => 'Cancelado pelo Colaborador',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * Indicates whether the status is terminal (cannot be transitioned further).
     */
    public function isFinal(): bool
    {
        return $this !== self::PENDING;
    }

    /**
     * Validates whether transition to target status is permitted.
     */
    public function canTransitionTo(AdjustmentStatus $next): bool
    {
        if ($this === self::PENDING) {
            return in_array($next, [self::APPROVED, self::REJECTED, self::CANCELLED], true);
        }

        return false;
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
