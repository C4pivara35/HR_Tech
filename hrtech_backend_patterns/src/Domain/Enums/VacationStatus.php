<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Status of employee vacation scheduling and lifecycle (Art. 129 a 138 CLT).
 */
enum VacationStatus: string
{
    case REQUESTED = 'requested';
    case APPROVED_BY_MANAGER = 'approved_by_manager';
    case APPROVED_BY_HR = 'approved_by_hr';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => 'Solicitado pelo Colaborador',
            self::APPROVED_BY_MANAGER => 'Aprovado pelo Gestor Imediato',
            self::APPROVED_BY_HR => 'Aprovado pelo Recursos Humanos',
            self::IN_PROGRESS => 'Em Gozo de Férias',
            self::COMPLETED => 'Férias Concluídas',
            self::REJECTED => 'Solicitação Rejeitada',
            self::CANCELLED => 'Solicitação Cancelada',
        };
    }

    public function isPendingApproval(): bool
    {
        return in_array($this, [self::REQUESTED, self::APPROVED_BY_MANAGER], true);
    }

    public function isApproved(): bool
    {
        return in_array($this, [self::APPROVED_BY_HR, self::IN_PROGRESS, self::COMPLETED], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::REJECTED, self::CANCELLED], true);
    }

    /**
     * Validates legal workflow transitions for vacation requests.
     */
    public function canTransitionTo(VacationStatus $next): bool
    {
        return match ($this) {
            self::REQUESTED => in_array($next, [self::APPROVED_BY_MANAGER, self::REJECTED, self::CANCELLED], true),
            self::APPROVED_BY_MANAGER => in_array($next, [self::APPROVED_BY_HR, self::REJECTED, self::CANCELLED], true),
            self::APPROVED_BY_HR => in_array($next, [self::IN_PROGRESS, self::CANCELLED], true),
            self::IN_PROGRESS => in_array($next, [self::COMPLETED], true),
            self::COMPLETED, self::REJECTED, self::CANCELLED => false,
        };
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
