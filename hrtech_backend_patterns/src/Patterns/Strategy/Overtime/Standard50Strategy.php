<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Overtime;

use HrTech\Contracts\OvertimeStrategyInterface;

/**
 * Class Standard50Strategy
 *
 * Implements standard weekday overtime compensation with 50% statutory surcharge.
 * Governed by Article 59 §1 of the Brazilian Consolidation of Labor Laws (CLT).
 */
class Standard50Strategy implements OvertimeStrategyInterface
{
    public const float SURCHARGE_MULTIPLIER = 1.5; // 100% regular + 50% surcharge

    /**
     * Calculates overtime monetary compensation with 50% surcharge.
     * Formula: hourlyRate * 1.5 * overtimeHours
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
        return 'Standard 50% weekday overtime surcharge pursuant to Art. 59 §1 of CLT.';
    }
}
