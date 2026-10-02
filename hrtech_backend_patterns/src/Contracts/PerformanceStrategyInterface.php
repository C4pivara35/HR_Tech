<?php

declare(strict_types=1);

namespace HrTech\Contracts;

use HrTech\Domain\Entities\Employee;

/**
 * Interface PerformanceStrategyInterface
 *
 * Define o contrato para estratégias de cálculo e pontuação de avaliação de desempenho.
 * Standardizes appraisal scoring across OKRs, 360-degree reviews, and KPIs.
 */
interface PerformanceStrategyInterface
{
    /**
     * Calcula a pontuação de desempenho normalizada para o colaborador com base nas métricas.
     *
     * @param Employee $employee The employee being evaluated.
     * @param array<string, mixed> $metrics Strategy-specific metrics data.
     * @return float Nota de desempenho normalizada (0.0 to 100.0 scale).
     */
    public function calculateScore(Employee $employee, array $metrics): float;

    /**
     * Retorna o nome ou identificador do modelo de avaliação.
     *
     * @return string Scoring model name (e.g. 'OKR', 'EVALUATION_360', 'KPI').
     */
    public function getScoringModel(): string;
}
