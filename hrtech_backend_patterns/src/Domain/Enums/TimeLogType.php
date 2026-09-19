<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Punch/clock types for time tracking (Portaria 671/2021 MTE).
 */
enum TimeLogType: string
{
    case ENTRY = 'entry';
    case INTERVAL_START = 'interval_start';
    case INTERVAL_END = 'interval_end';
    case EXIT = 'exit';
    case OVERTIME_START = 'overtime_start';
    case OVERTIME_END = 'overtime_end';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entrada da Jornada',
            self::INTERVAL_START => 'Saída para Intervalo (Almoço)',
            self::INTERVAL_END => 'Retorno de Intervalo',
            self::EXIT => 'Saída da Jornada',
            self::OVERTIME_START => 'Início de Hora Extra',
            self::OVERTIME_END => 'Fim de Hora Extra',
        };
    }

    /**
     * Whether the punch represents beginning a period of work.
     */
    public function isEntry(): bool
    {
        return in_array($this, [self::ENTRY, self::INTERVAL_END, self::OVERTIME_START], true);
    }

    /**
     * Whether the punch represents ending a period of work.
     */
    public function isExit(): bool
    {
        return in_array($this, [self::INTERVAL_START, self::EXIT, self::OVERTIME_END], true);
    }

    /**
     * Standard sequential order of punches within a single work shift.
     */
    public function sequenceOrder(): int
    {
        return match ($this) {
            self::ENTRY => 1,
            self::INTERVAL_START => 2,
            self::INTERVAL_END => 3,
            self::EXIT => 4,
            self::OVERTIME_START => 5,
            self::OVERTIME_END => 6,
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
