<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Performance;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 3 de 3 — Avaliação KPI)
// ============================================================
// Intenção: Encapsular o algoritmo de avaliação por KPI,
// tornando-o intercambiável com OKR e Avaliação 360°.
//
// Regras específicas do KPI:
//   - Calcula atingimento individual por indicador (meta vs. realizado)
//   - Pondera cada KPI por seu peso relativo (weight)
//   - Aplica gatilho mínimo de qualificação (threshold)
//   - Pode ou não aceitar superatingimento acima de 100%
//   - Score final: 0,0 a 100,0 (ou até 120,0 com superatingimento)
// ============================================================

use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Domain\Entities\Employee;

/**
 * Classe KpiStrategy — PADRÃO STRATEGY (avaliação por KPI)
 *
 * Implementa avaliação de performance por Indicadores-Chave de Desempenho (KPI).
 * Calcula atingimento percentual ponderado, com gatilhos mínimos por indicador.
 * Score normalizado: 0,0 a 100,0 (ou até 120,0 se superatingimento habilitado).
 */
class KpiStrategy implements PerformanceStrategyInterface
{
    // -------------------------------------------------------
    // ALGORITMO ENCAPSULADO NESTA ESTRATÉGIA:
    // Atingimento individual ponderado por KPI, com gatilhos mínimos.
    // Diferente do OKR (Key Results 0-1) e 360° (auto/pares/gestor).
    // -------------------------------------------------------

    /**
     * @param bool $allowOverachievement Se true, scores acima de 100% são reconhecidos (até 120%)
     */
    public function __construct(private readonly bool $allowOverachievement = false)
    {
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o score de performance por KPIs.
     *
     * O array $metrics deve conter:
     *   'kpis' => [
     *     ['target' => 100, 'actual' => 85, 'weight' => 2.0, 'threshold' => 70],
     *     ['target' => 50,  'actual' => 60, 'weight' => 1.0, 'lower_is_better' => false],
     *   ]
     *
     * @param Employee $employee Funcionário avaliado (para contexto)
     * @param array<string, mixed> $metrics Dados dos KPIs com metas, realizados e pesos
     * @return float Score normalizado (0,0 a 100,0)
     */
    public function calculateScore(Employee $employee, array $metrics): float
    {
        // Aceita 'kpis' ou 'indicators' como chave do array
        $kpis = (array)($metrics['kpis'] ?? $metrics['indicators'] ?? []);
        if (empty($kpis)) {
            return 0.0; // Sem KPIs definidos: score zero
        }

        $totalWeightedAchievement = 0.0;
        $totalWeight = 0.0;

        // Define o teto do atingimento: 100% ou 120% se superatingimento habilitado
        $maxCap = $this->allowOverachievement ? 1.20 : 1.00;

        foreach ($kpis as $kpi) {
            if (!is_array($kpi)) {
                continue; // Ignora elementos inválidos
            }

            $weight        = max(0.01, (float)($kpi['weight']    ?? 1.0));   // Peso do KPI
            $target        = (float)($kpi['target']              ?? 100.0);  // Meta estabelecida
            $actual        = (float)($kpi['actual']              ?? 0.0);    // Resultado realizado
            $threshold     = isset($kpi['threshold']) ? (float)$kpi['threshold'] : null; // Gatilho mínimo
            $isLowerBetter = (bool)($kpi['lower_is_better']      ?? false);  // Ex: taxa de erros

            // Calcula o atingimento individual deste KPI
            $achievement = $this->evaluateSingleKpi($target, $actual, $threshold, $isLowerBetter, $maxCap);

            // Acumula ponderado: atingimento × peso deste KPI
            $totalWeightedAchievement += $achievement * $weight;
            $totalWeight              += $weight;
        }

        if ($totalWeight <= 0.0) {
            return 0.0; // Evita divisão por zero
        }

        // Score final = média ponderada × 100 (normalizado para 0-100)
        $normalizedRatio = $totalWeightedAchievement / $totalWeight;
        $score           = $normalizedRatio * 100.0;

        return round(min($maxCap * 100.0, max(0.0, $score)), 2);
    }

    /**
     * Avalia o atingimento de um único KPI (retorna ratio de 0,0 a maxCap).
     *
     * Para indicadores onde "menor é melhor" (ex: taxa de defeitos):
     *   - Se ultrapassou o gatilho máximo: score zero (falhou)
     *   - Caso contrário: meta/realizado (quanto menor o realizado, melhor)
     *
     * Para indicadores normais (maior é melhor):
     *   - Se não atingiu o mínimo qualificador: score zero
     *   - Caso contrário: realizado/meta (quanto maior o realizado, melhor)
     */
    private function evaluateSingleKpi(
        float $target,
        float $actual,
        ?float $threshold,
        bool $isLowerBetter,
        float $maxCap
    ): float {
        if ($isLowerBetter) {
            // Para KPIs como "taxa de erros" ou "absenteísmo" — menor é melhor
            if ($threshold !== null && $actual > $threshold) {
                return 0.0; // Ultrapassou o limite máximo tolerado: score zero
            }

            if ($target <= 0.0) {
                return $actual <= 0.0 ? 1.0 : 0.0;
            }

            // Atingimento inverso: quanto menor o realizado, maior o score
            $ratio = $target / max(0.001, $actual);
            return min($maxCap, max(0.0, $ratio));
        }

        // Para KPIs normais — maior é melhor (vendas, produção, velocidade)
        if ($threshold !== null && $actual < $threshold) {
            return 0.0; // Não atingiu o gatilho mínimo qualificador
        }

        if ($target <= 0.0) {
            return $actual >= 0.0 ? 1.0 : 0.0;
        }

        // Atingimento direto: realizado ÷ meta
        $ratio = $actual / $target;

        return min($maxCap, max(0.0, $ratio));
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Retorna o identificador do modelo de avaliação.
     * Usado para registro em avaliações e relatórios de RH.
     */
    public function getScoringModel(): string
    {
        return 'KPI'; // Indicadores-Chave de Desempenho
    }
}
