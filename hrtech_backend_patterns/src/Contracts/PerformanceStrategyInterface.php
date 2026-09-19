<?php

declare(strict_types=1);

namespace HrTech\Contracts;

use HrTech\Domain\Entities\Employee;

/**
 * Interface PerformanceStrategyInterface
 *
 * Defines the contract for employee performance evaluation scoring strategies.
 * Standardizes appraisal scoring across OKRs, 360-degree reviews, and KPIs.
 */
interface PerformanceStrategyInterface
{
    /**
     * Calculates the normalized performance score for an employee based on metrics.
     *
     * @param Employee $employee The employee being evaluated.
     * @param array<string, mixed> $metrics Strategy-specific metrics data.
     * @return float Normalized performance score (0.0 to 100.0 scale).
     */
    public function calculateScore(Employee $employee, array $metrics): float;

    /**
     * Returns the scoring model name/identifier.
     *
     * @return string Scoring model name (e.g. 'OKR', 'EVALUATION_360', 'KPI').
     */
    public function getScoringModel(): string;
}
