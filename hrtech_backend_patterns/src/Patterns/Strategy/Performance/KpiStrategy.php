<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Performance;

use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Domain\Entities\Employee;

/**
 * Class KpiStrategy
 *
 * Implements Key Performance Indicator (KPI) operational evaluation.
 * Assesses quantitative metric targets against minimum threshold gates,
 * calculating weighted achievement percentages scaled to 0.0 - 100.0.
 */
class KpiStrategy implements PerformanceStrategyInterface
{
    /**
     * @param bool $allowOverachievement Whether achievement above 100% is recognized (up to 120%).
     */
    public function __construct(private readonly bool $allowOverachievement = false)
    {
    }

    /**
     * Calculates composite KPI score based on individual indicator achievement and thresholds.
     *
     * @param Employee $employee
     * @param array<string, mixed> $metrics Expects 'kpis' array containing targets, actuals, and thresholds
     * @return float Normalized KPI performance score (0.0 to 100.0)
     */
    public function calculateScore(Employee $employee, array $metrics): float
    {
        $kpis = (array)($metrics['kpis'] ?? $metrics['indicators'] ?? []);
        if (empty($kpis)) {
            return 0.0;
        }

        $totalWeightedAchievement = 0.0;
        $totalWeight = 0.0;
        $maxCap = $this->allowOverachievement ? 1.20 : 1.00;

        foreach ($kpis as $kpi) {
            if (!is_array($kpi)) {
                continue;
            }

            $weight = max(0.01, (float)($kpi['weight'] ?? 1.0));
            $target = (float)($kpi['target'] ?? 100.0);
            $actual = (float)($kpi['actual'] ?? 0.0);
            $threshold = isset($kpi['threshold']) ? (float)$kpi['threshold'] : null;
            $isLowerBetter = (bool)($kpi['lower_is_better'] ?? false);

            $achievement = $this->evaluateSingleKpi($target, $actual, $threshold, $isLowerBetter, $maxCap);

            $totalWeightedAchievement += $achievement * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return 0.0;
        }

        $normalizedRatio = $totalWeightedAchievement / $totalWeight;
        $score = $normalizedRatio * 100.0;

        return round(min($maxCap * 100.0, max(0.0, $score)), 2);
    }

    /**
     * Evaluates a single KPI indicator against target and threshold.
     */
    private function evaluateSingleKpi(
        float $target,
        float $actual,
        ?float $threshold,
        bool $isLowerBetter,
        float $maxCap
    ): float {
        if ($isLowerBetter) {
            // Lower is better (e.g. error rate, incident count)
            if ($threshold !== null && $actual > $threshold) {
                return 0.0; // Breached maximum allowable error threshold
            }

            if ($target <= 0.0) {
                return $actual <= 0.0 ? 1.0 : 0.0;
            }

            $ratio = $target / max(0.001, $actual);
            return min($maxCap, max(0.0, $ratio));
        }

        // Higher is better (standard revenue, output, velocity)
        if ($threshold !== null && $actual < $threshold) {
            return 0.0; // Failed minimum qualifying gate
        }

        if ($target <= 0.0) {
            return $actual >= 0.0 ? 1.0 : 0.0;
        }

        $ratio = $actual / $target;

        return min($maxCap, max(0.0, $ratio));
    }

    /**
     * Returns scoring model identifier.
     */
    public function getScoringModel(): string
    {
        return 'KPI';
    }
}
