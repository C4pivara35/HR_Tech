<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Overtime;

use HrTech\Contracts\OvertimeStrategyInterface;

/**
 * Class Sunday100Strategy
 *
 * Implements Sunday and legal holiday overtime compensation with 100% statutory surcharge.
 * Governed by Article 70 of CLT and TST Jurisprudence (Súmula 146 TST).
 */
class Sunday100Strategy implements OvertimeStrategyInterface
{
    public const float SURCHARGE_MULTIPLIER = 2.0; // 100% regular + 100% surcharge

    /**
     * Calculates overtime monetary compensation with 100% surcharge.
     * Formula: hourlyRate * 2.0 * overtimeHours
     */
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float
    {
        if ($hourlyRate <= 0.0 || $overtimeHours <= 0.0) {
            return 0.0;
        }

        return round($hourlyRate * self::SURCHARGE_MULTIPLIER * $overtimeHours, 2);
    }

    /**
     * Returns legal and operational description of the strategy.
     */
    public function getDescription(): string
    {
        return 'Sunday and holiday overtime with 100% surcharge pursuant to Art. 70 of CLT and Súmula 146 TST.';
    }
}
