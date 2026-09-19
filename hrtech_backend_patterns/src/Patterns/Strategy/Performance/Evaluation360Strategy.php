<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Performance;

use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Domain\Entities\Employee;

/**
 * Class Evaluation360Strategy
 *
 * Implements 360-degree multi-rater performance feedback appraisal methodology.
 * Aggregates and calculates a composite weighted score across self-evaluation,
 * peer feedback reviews, and direct manager assessments.
 */
class Evaluation360Strategy implements PerformanceStrategyInterface
{
    public const float DEFAULT_SELF_WEIGHT = 0.15;    // 15%
    public const float DEFAULT_PEER_WEIGHT = 0.35;    // 35%
    public const float DEFAULT_MANAGER_WEIGHT = 0.50; // 50%

    /**
     * @param float $weightSelf Weight allocated to self-review (default 0.15)
     * @param float $weightPeers Weight allocated to peer reviews (default 0.35)
     * @param float $weightManager Weight allocated to supervisor review (default 0.50)
     */
    public function __construct(
        private readonly float $weightSelf = self::DEFAULT_SELF_WEIGHT,
        private readonly float $weightPeers = self::DEFAULT_PEER_WEIGHT,
        private readonly float $weightManager = self::DEFAULT_MANAGER_WEIGHT
    ) {
    }

    /**
     * Calculates the composite 360-degree appraisal score (0.0 to 100.0).
     *
     * @param Employee $employee
     * @param array<string, mixed> $metrics Expects 'self', 'peers' (or 'peer'), and 'manager'
     * @return float Normalized composite score (0.0 to 100.0)
     */
    public function calculateScore(Employee $employee, array $metrics): float
    {
        $wSelf = (float)($metrics['weights']['self'] ?? $this->weightSelf);
        $wPeers = (float)($metrics['weights']['peers'] ?? $metrics['weights']['peer'] ?? $this->weightPeers);
        $wManager = (float)($metrics['weights']['manager'] ?? $this->weightManager);

        $totalWeight = $wSelf + $wPeers + $wManager;
        if ($totalWeight <= 0.0) {
            $totalWeight = 1.0;
        }

        $selfScore = $this->normalizeScore($metrics['self'] ?? 0.0);
        $peerScore = $this->resolvePeerScore($metrics['peers'] ?? $metrics['peer'] ?? []);
        $managerScore = $this->normalizeScore($metrics['manager'] ?? 0.0);

        $weightedTotal = ($selfScore * $wSelf) + ($peerScore * $wPeers) + ($managerScore * $wManager);
        $finalScore = $weightedTotal / $totalWeight;

        return round(min(100.0, max(0.0, $finalScore)), 2);
    }

    /**
     * Resolves and averages peer review ratings.
     *
     * @param mixed $peers Array of peer scores or single average
     * @return float Normalized peer score (0.0 to 100.0)
     */
    private function resolvePeerScore(mixed $peers): float
    {
        if (is_numeric($peers)) {
            return $this->normalizeScore((float)$peers);
        }

        if (is_array($peers) && !empty($peers)) {
            $normalizedList = array_map(fn($p) => $this->normalizeScore(is_numeric($p) ? (float)$p : 0.0), $peers);
            return array_sum($normalizedList) / count($normalizedList);
        }

        return 0.0;
    }

    /**
     * Normalizes a rating value to 0.0 - 100.0 scale (converts 1-5 scale if raw <= 5.0).
     */
    private function normalizeScore(float $rawScore): float
    {
        if ($rawScore <= 0.0) {
            return 0.0;
        }

        // Auto-detect 1 - 5 Likert rating scale
        if ($rawScore <= 5.0) {
            return ($rawScore / 5.0) * 100.0;
        }

        // Auto-detect 1 - 10 scale
        if ($rawScore <= 10.0) {
            return ($rawScore / 10.0) * 100.0;
        }

        return min(100.0, $rawScore);
    }

    /**
     * Returns scoring model identifier.
     */
    public function getScoringModel(): string
    {
        return 'EVALUATION_360';
    }
}
