<?php

declare(strict_types=1);

namespace HrTech\Lps\Enums;

/**
 * Enum TenantSegment
 *
 * Market segment classification for tenants in the Software Product Line (LPS).
 * Determines default feature toggles, compliance workflows, and strategy profiles.
 *
 * @package HrTech\Lps\Enums
 * @author Fernando Lopes Duarte (LPS Architecture Lead)
 */
enum TenantSegment: string
{
    case TECH = 'tech';
    case INDUSTRIA = 'industria';
    case FINANCEIRO = 'financeiro';

    /**
     * Human-readable label for the market segment.
     */
    public function label(): string
    {
        return match ($this) {
            self::TECH => 'Tecnologia & Startups',
            self::INDUSTRIA => 'Indústria & Manufatura',
            self::FINANCEIRO => 'Setor Financeiro & Mercado de Capitais',
        };
    }

    /**
     * Default LPS feature configuration for this market segment.
     *
     * @return array<string, bool>
     */
    public function defaultFeatures(): array
    {
        return match ($this) {
            self::TECH => [
                'bank_of_hours' => true,
                'overtime_payout' => false,
                'risk_ppe_required' => false,
                'flexible_benefits' => true,
                'd_and_o_insurance' => true,
                'biometric_punch_mandatory' => false,
                'chartered_transport' => false,
                'executive_health_plan' => false,
                'aggressive_bonus' => false,
                'strict_lgpd_audit' => false,
                'fincorp_life_policy' => false,
            ],
            self::INDUSTRIA => [
                'bank_of_hours' => false,
                'overtime_payout' => true,
                'risk_ppe_required' => true,
                'flexible_benefits' => false,
                'd_and_o_insurance' => false,
                'biometric_punch_mandatory' => false,
                'chartered_transport' => true,
                'executive_health_plan' => false,
                'aggressive_bonus' => false,
                'strict_lgpd_audit' => false,
                'fincorp_life_policy' => false,
            ],
            self::FINANCEIRO => [
                'bank_of_hours' => false,
                'overtime_payout' => true,
                'risk_ppe_required' => false,
                'flexible_benefits' => false,
                'd_and_o_insurance' => false,
                'biometric_punch_mandatory' => true,
                'chartered_transport' => false,
                'executive_health_plan' => true,
                'aggressive_bonus' => true,
                'strict_lgpd_audit' => true,
                'fincorp_life_policy' => true,
            ],
        };
    }

    public function isTech(): bool
    {
        return $this === self::TECH;
    }

    public function isIndustria(): bool
    {
        return $this === self::INDUSTRIA;
    }

    public function isFinanceiro(): bool
    {
        return $this === self::FINANCEIRO;
    }

    /**
     * Tolerant parser that maps aliases to canonical enum cases.
     */
    public static function fromValue(string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $normalized = strtolower(trim($value));
        return match ($normalized) {
            'tech', 'tecnologia', 'startup' => self::TECH,
            'industria', 'industry', 'manufatura', 'fabril' => self::INDUSTRIA,
            'financeiro', 'financial', 'financas', 'banco' => self::FINANCEIRO,
            default => self::from($normalized),
        };
    }
}
