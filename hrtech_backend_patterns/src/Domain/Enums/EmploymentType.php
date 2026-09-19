<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Employment contract types under Brazilian labor regulations.
 */
enum EmploymentType: string
{
    case CLT = 'CLT';
    case PJ = 'PJ';
    case INTERN = 'INTERN';
    case APPRENTICE = 'APPRENTICE';
    case TEMPORARY = 'TEMPORARY';

    /**
     * Human-readable label in Portuguese.
     */
    public function label(): string
    {
        return match ($this) {
            self::CLT => 'CLT - Contrato por Prazo Indeterminado',
            self::PJ => 'PJ - Prestador de Serviços (Pessoa Jurídica)',
            self::INTERN => 'Estágio (Lei 11.788/2008)',
            self::APPRENTICE => 'Jovem Aprendiz (Lei 10.097/2000)',
            self::TEMPORARY => 'Trabalho Temporário (Lei 6.019/1974)',
        };
    }

    /**
     * Whether contract is subject to standard CLT labor taxes (INSS, IRRF, FGTS).
     */
    public function hasLaborTaxes(): bool
    {
        return in_array($this, [self::CLT, self::APPRENTICE, self::TEMPORARY], true);
    }

    /**
     * Whether employee is entitled to 1/3 constitutional vacation bonus.
     */
    public function hasVacationBonus(): bool
    {
        return in_array($this, [self::CLT, self::APPRENTICE, self::TEMPORARY], true);
    }

    /**
     * Whether employee is entitled to 13th salary.
     */
    public function hasThirteenthSalary(): bool
    {
        return in_array($this, [self::CLT, self::APPRENTICE, self::TEMPORARY], true);
    }

    /**
     * Maximum weekly regular hours permitted by law.
     */
    public function maxWeeklyHours(): int
    {
        return match ($this) {
            self::CLT, self::TEMPORARY, self::PJ => 44,
            self::INTERN, self::APPRENTICE => 30,
        };
    }

    /**
     * Whether contract requires electronic time card tracking (Portaria 671).
     */
    public function requiresTimeTracking(): bool
    {
        return in_array($this, [self::CLT, self::APPRENTICE, self::TEMPORARY, self::INTERN], true);
    }

    /**
     * Resolve case-insensitively.
     */
    public static function fromCaseInsensitive(string $value): ?self
    {
        $upper = strtoupper(trim($value));
        return self::tryFrom($upper);
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
