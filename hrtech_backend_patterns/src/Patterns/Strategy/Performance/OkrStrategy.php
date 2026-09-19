<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Performance;

use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Domain\Entities\Employee;

/**
 * Class OkrStrategy
 *
 * Implements Objectives and Key Results (OKR) performance scoring methodology.
 * Evaluates achievement percentages on a standard 0.0 - 1.0 scale across quantitative Key Results,
 * producing normalized 0.0 - 100.0 appraisal scores and bonus multipliers.
 */
class OkrStrategy implements PerformanceStrategyInterface
{
    /**
     * @param bool $allowOverachievement Whether scores can exceed 100.0 for exceeding targets (capped at 120.0).
     */
    public function __construct(private readonly bool $allowOverachievement = false)
    {
    }

    /**
     * Calculates normalized performance appraisal score (0.0 to 100.0) from Key Results metrics.
     *
     * @param Employee $employee
     * @param array<string, mixed> $metrics Expects 'key_results' array (floats 0.0-1.0 or target/current items)
     * @return float Normalized score (0.0 to 100.0)
     */
    public function calculateScore(Employee $employee, array $metrics): float
    {
        $achievement = $this->calculateAchievementRatio($metrics);

        $maxScore = $this->allowOverachievement ? 120.0 : 100.0;
        $score = $achievement * 100.0;

        return round(min($maxScore, max(0.0, $score)), 2);
    }

    /**
     * Calculates the aggregate Key Results achievement ratio on a 0.0 - 1.0 scale.
     *
     * @param array<string, mixed> $metrics
     * @return float Achievement ratio (0.0 - 1.0)
     */
    public function calculateAchievementRatio(array $metrics): float
    {
        if (isset($metrics['achievement_ratio'])) {
            return (float)$metrics['achievement_ratio'];
        }

        if (isset($metrics['achievement'])) {
            $val = (float)$metrics['achievement'];
            return $val > 1.0 ? $val / 100.0 : $val;
        }

        $keyResults = (array)($metrics['key_results'] ?? $metrics['krs'] ?? []);
        if (empty($keyResults)) {
            return 0.0;
        }

        $totalRatio = 0.0;
        $totalWeight = 0.0;

        foreach ($keyResults as $kr) {
            if (is_numeric($kr)) {
                $ratio = (float)$kr > 1.0 ? (float)$kr / 100.0 : (float)$kr;
                $weight = 1.0;
            } elseif (is_array($kr)) {
                $weight = (float)($kr['weight'] ?? 1.0);
                if (isset($kr['current'], $kr['target']) && (float)$kr['target'] > 0.0) {
                    $ratio = (float)$kr['current'] / (float)$kr['target'];
                } elseif (isset($kr['achievement'])) {
                    $ratio = (float)$kr['achievement'] > 1.0 ? (float)$kr['achievement'] / 100.0 : (float)$kr['achievement'];
                } else {
                    $ratio = 0.0;
                }
            } else {
                continue;
            }

            $totalRatio += $ratio * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return 0.0;
        }

        return $totalRatio / $totalWeight;
    }

    /**
     * Calculates performance-based monetary bonus from normalized score.
     *
     * @param Employee $employee
     * @param float $score (0.0 to 100.0)
     * @param float $maxBonusMonths Base salary multiplier (e.g. 1.5 = 1.5 salaries)
     * @return float Bonus amount in BRL
     */
    public function calculateBonus(Employee $employee, float $score, float $maxBonusMonths = 1.0): float
    {
        $base = $employee->getBaseSalary()->getAmount();
        $multiplier = ($score / 100.0) * $maxBonusMonths;

        return round(max(0.0, $base * $multiplier), 2);
    }

    /**
     * Returns scoring model identifier.
     */
    public function getScoringModel(): string
    {
        return 'OKR';
    }
}
