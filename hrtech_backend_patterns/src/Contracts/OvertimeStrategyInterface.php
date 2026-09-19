<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface OvertimeStrategyInterface
 *
 * Defines the contract for overtime compensation calculation strategies.
 * Supports standard CLT surcharges (50%, 100%) and compensatory bank hours.
 */
interface OvertimeStrategyInterface
{
    /**
     * Calculates the total overtime monetary compensation.
     *
     * @param float $hourlyRate The regular hourly wage of the employee.
     * @param float $overtimeHours Number of overtime hours worked.
     * @return float The calculated monetary overtime compensation (in BRL).
     */
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float;

    /**
     * Returns a human-readable legal and operational description of the strategy.
     *
     * @return string Description of the calculation rule and legal basis.
     */
    public function getDescription(): string;
}
