<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Types of occupational health medical exams (NR-7 / PCMSO / ASO).
 */
enum ExamType: string
{
    case ADMISSION = 'admission';
    case PERIODIC = 'periodic';
    case RETURN_TO_WORK = 'return_to_work';
    case ROLE_CHANGE = 'role_change';
    case DEMISSION = 'demission';

    public function label(): string
    {
        return match ($this) {
            self::ADMISSION => 'Exame Médico Admissional',
            self::PERIODIC => 'Exame Médico Periódico',
            self::RETURN_TO_WORK => 'Exame de Retorno ao Trabalho',
            self::ROLE_CHANGE => 'Exame de Mudança de Risco Ocupacional',
            self::DEMISSION => 'Exame Médico Demissional',
        };
    }

    /**
     * Default validity duration in months under NR-7 guidelines.
     */
    public function defaultValidityMonths(int $riskGrade = 1): int
    {
        if ($this === self::PERIODIC) {
            return ($riskGrade >= 3) ? 12 : 24;
        }

        return 12;
    }

    /**
     * Whether exam is legally required prior to beginning employment.
     */
    public function isMandatoryOnHiring(): bool
    {
        return $this === self::ADMISSION;
    }

    /**
     * Whether exam is legally required upon termination.
     */
    public function isMandatoryOnDismissal(): bool
    {
        return $this === self::DEMISSION;
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
