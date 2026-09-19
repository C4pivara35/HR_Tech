<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Corporate benefits managed by HRTech Core.
 */
enum BenefitType: string
{
    case MEAL_VOUCHER = 'MEAL_VOUCHER';
    case FOOD_VOUCHER = 'FOOD_VOUCHER';
    case TRANSPORTATION = 'TRANSPORTATION';
    case TRANSPORTATION_VOUCHER = 'VALE_TRANSPORTE';
    case HEALTH_PLAN = 'HEALTH_PLAN';
    case DENTAL_PLAN = 'DENTAL_PLAN';
    case LIFE_INSURANCE = 'LIFE_INSURANCE';
    case GYMPASS = 'GYMPASS';

    public function label(): string
    {
        return match ($this) {
            self::MEAL_VOUCHER => 'Vale Refeição (VR)',
            self::FOOD_VOUCHER => 'Vale Alimentação (VA)',
            self::TRANSPORTATION, self::TRANSPORTATION_VOUCHER => 'Vale Transporte (VT)',
            self::HEALTH_PLAN => 'Plano de Saúde Médico',
            self::DENTAL_PLAN => 'Plano Odontológico',
            self::LIFE_INSURANCE => 'Seguro de Vida em Grupo',
            self::GYMPASS => 'Auxílio Academia / Bem-estar',
        };
    }

    /**
     * Short corporate abbreviation code.
     */
    public function code(): string
    {
        return match ($this) {
            self::MEAL_VOUCHER => 'VR',
            self::FOOD_VOUCHER => 'VA',
            self::TRANSPORTATION, self::TRANSPORTATION_VOUCHER => 'VT',
            self::HEALTH_PLAN => 'SAUDE',
            self::DENTAL_PLAN => 'ODONTO',
            self::LIFE_INSURANCE => 'VIDA',
            self::GYMPASS => 'GYM',
        };
    }

    /**
     * Whether the benefit allows payroll salary deduction (coparticipação / desconto legal).
     */
    public function isDeductible(): bool
    {
        return in_array($this, [
            self::MEAL_VOUCHER,
            self::FOOD_VOUCHER,
            self::TRANSPORTATION,
            self::TRANSPORTATION_VOUCHER,
            self::HEALTH_PLAN,
            self::DENTAL_PLAN,
            self::GYMPASS,
        ], true);
    }

    /**
     * Maximum legal payroll deduction percentage (e.g. VT = 6% under Lei 7.418/1985; PAT VR/VA = 20%).
     */
    public function maxLegalDeductionPercentage(): ?float
    {
        return match ($this) {
            self::TRANSPORTATION, self::TRANSPORTATION_VOUCHER => 6.0,
            self::MEAL_VOUCHER, self::FOOD_VOUCHER => 20.0,
            default => null,
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
