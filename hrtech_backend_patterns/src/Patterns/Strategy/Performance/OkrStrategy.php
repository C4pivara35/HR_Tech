<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Performance;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 3 de 3 — Avaliação OKR)
// ============================================================
// Estratégia de avaliação por Objectives and Key Results (OKR).
//
// Diferenças em relação ao KPI:
//   - Trabalha com Key Results em escala 0,0 a 1,0 (ou 0 a 100%)
//   - Não tem metas individuais ponderadas por alíquota
//   - Score = média dos Key Results × 100 (normalizado para 0-100)
//   - Pode calcular bônus monetário com base no score final
//
// O cliente usa a mesma interface PerformanceStrategyInterface,
// tornando KPI, OKR e 360° completamente intercambiáveis.
// ============================================================

use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Domain\Entities\Employee;

/**
 * Classe OkrStrategy — PADRÃO STRATEGY (avaliação por OKR)
 *
 * Implementa avaliação de performance por Objectives and Key Results.
 * Agrega o atingimento dos Key Results em uma pontuação normalizada
 * de 0,0 a 100,0 e pode calcular bônus monetário proporcional.
 */
class OkrStrategy implements PerformanceStrategyInterface
{
    // -------------------------------------------------------
    // ALGORITMO ENCAPSULADO NESTA ESTRATÉGIA:
    // Média ponderada dos Key Results com atingimento 0-1 (ou 0-100%).
    // Diferente do KPI (meta/realizado por indicador) e do 360° (avaliadores).
    // -------------------------------------------------------

    /**
     * @param bool $allowOverachievement Se true, scores acima de 100% são reconhecidos (até 120,0)
     */
    public function __construct(private readonly bool $allowOverachievement = false)
    {
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o score OKR normalizado.
     *
     * O array $metrics pode conter:
     *   'key_results' => [0.8, 0.6, 1.0]         → atingimento em 0-1
     *   'key_results' => [80, 60, 100]            → atingimento em 0-100
     *   'key_results' => [['current'=>8,'target'=>10,'weight'=>2]] → estruturado
     *   'achievement_ratio' => 0.75               → ratio direto
     *
     * @param Employee $employee Funcionário avaliado
     * @param array<string, mixed> $metrics Dados dos Key Results
     * @return float Score normalizado (0,0 a 100,0)
     */
    public function calculateScore(Employee $employee, array $metrics): float
    {
        // Calcula o ratio de atingimento médio dos Key Results (0,0 a 1,0)
        $achievement = $this->calculateAchievementRatio($metrics);

        // Define o teto: 100% ou 120% com superatingimento
        $maxScore = $this->allowOverachievement ? 120.0 : 100.0;

        // Normaliza para 0-100 e aplica o teto
        $score = $achievement * 100.0;

        return round(min($maxScore, max(0.0, $score)), 2);
    }

    /**
     * Calcula o ratio de atingimento médio dos Key Results (0,0 a 1,0).
     * Aceita múltiplos formatos de entrada para flexibilidade.
     *
     * @param array<string, mixed> $metrics
     * @return float Ratio de atingimento (0,0 = não atingiu, 1,0 = atingiu tudo)
     */
    public function calculateAchievementRatio(array $metrics): float
    {
        // Formato 1: ratio direto já calculado externamente
        if (isset($metrics['achievement_ratio'])) {
            return (float)$metrics['achievement_ratio'];
        }

        // Formato 2: percentual de atingimento (ex: 75 → 0.75)
        if (isset($metrics['achievement'])) {
            $val = (float)$metrics['achievement'];
            return $val > 1.0 ? $val / 100.0 : $val;
        }

        // Formato 3: array de Key Results para calcular a média
        $keyResults = (array)($metrics['key_results'] ?? $metrics['krs'] ?? []);
        if (empty($keyResults)) {
            return 0.0; // Sem KRs: atingimento zero
        }

        $totalRatio  = 0.0;
        $totalWeight = 0.0;

        foreach ($keyResults as $kr) {
            if (is_numeric($kr)) {
                // KR numérico simples: converte de 0-100 para 0-1 se necessário
                $ratio  = (float)$kr > 1.0 ? (float)$kr / 100.0 : (float)$kr;
                $weight = 1.0; // Peso unitário (todos KRs têm o mesmo peso)
            } elseif (is_array($kr)) {
                // KR estruturado: pode ter current/target ou achievement e weight
                $weight = (float)($kr['weight'] ?? 1.0);
                if (isset($kr['current'], $kr['target']) && (float)$kr['target'] > 0.0) {
                    // Calcula ratio a partir de current/target (ex: 8 de 10 = 0.80)
                    $ratio = (float)$kr['current'] / (float)$kr['target'];
                } elseif (isset($kr['achievement'])) {
                    // Usa o atingimento direto informado
                    $ratio = (float)$kr['achievement'] > 1.0
                        ? (float)$kr['achievement'] / 100.0
                        : (float)$kr['achievement'];
                } else {
                    $ratio = 0.0; // KR sem dados suficientes
                }
            } else {
                continue; // Elemento inválido: ignora
            }

            $totalRatio  += $ratio * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return 0.0; // Evita divisão por zero
        }

        // Retorna a média ponderada dos Key Results
        return $totalRatio / $totalWeight;
    }

    /**
     * Calcula o bônus monetário com base no score OKR e no salário base.
     *
     * Fórmula: salário × (score/100) × múltiploMáximo
     * Exemplo: salário R$ 5.000, score 80%, máximo 1,5 salários
     *   → bônus = 5.000 × 0,80 × 1,5 = R$ 6.000,00
     *
     * @param Employee $employee Funcionário (para obter o salário base)
     * @param float $score Score OKR (0,0 a 100,0)
     * @param float $maxBonusMonths Múltiplo máximo do salário (ex: 1,5 = 1,5 salários)
     * @return float Valor do bônus em R$
     */
    public function calculateBonus(Employee $employee, float $score, float $maxBonusMonths = 1.0): float
    {
        $base       = $employee->getBaseSalary()->getAmount();
        $multiplier = ($score / 100.0) * $maxBonusMonths;

        // Bônus proporcional ao score: quem atinge 100% recebe o máximo
        return round(max(0.0, $base * $multiplier), 2);
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Retorna o identificador do modelo de avaliação.
     */
    public function getScoringModel(): string
    {
        return 'OKR'; // Objectives and Key Results
    }
}
