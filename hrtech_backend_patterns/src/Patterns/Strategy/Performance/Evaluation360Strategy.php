<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Performance;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 3 de 3 — Avaliação 360°)
// ============================================================
// Estratégia de avaliação por múltiplos avaliadores (Avaliação 360°).
//
// Diferenças em relação ao KPI e OKR:
//   - Agrega notas de 3 fontes: autoavaliação, pares e gestor
//   - Cada fonte tem um peso configurável (padrão: self=15%, pares=35%, gestor=50%)
//   - Aceita escalas diferentes: 1-5 (Likert), 1-10 ou 0-100 (auto-detectado)
//   - Score final = média ponderada normalizada para 0-100
//
// Como trocar: basta substituir a instância de PerformanceStrategyInterface
// no módulo de RH. Nenhum outro código muda.
// ============================================================

use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Domain\Entities\Employee;

/**
 * Classe Evaluation360Strategy — PADRÃO STRATEGY (avaliação 360°)
 *
 * Implementa avaliação multi-avaliador com autoavaliação, feedback
 * de pares e avaliação do gestor direto, produzindo um score
 * composto ponderado normalizado de 0,0 a 100,0.
 */
class Evaluation360Strategy implements PerformanceStrategyInterface
{
    // -------------------------------------------------------
    // PESOS PADRÃO POR AVALIADOR:
    // Gestor tem maior peso (50%) por ter visão mais abrangente.
    // Pares têm 35% por avaliar competências colaborativas.
    // Autoavaliação tem 15% por tendência de viés pessoal.
    // -------------------------------------------------------

    /** Peso padrão da autoavaliação: 15% */
    public const float DEFAULT_SELF_WEIGHT    = 0.15;
    /** Peso padrão da avaliação de pares: 35% */
    public const float DEFAULT_PEER_WEIGHT    = 0.35;
    /** Peso padrão da avaliação do gestor: 50% */
    public const float DEFAULT_MANAGER_WEIGHT = 0.50;

    /**
     * @param float $weightSelf    Peso da autoavaliação (padrão 0,15 = 15%)
     * @param float $weightPeers   Peso da avaliação de pares (padrão 0,35 = 35%)
     * @param float $weightManager Peso da avaliação do gestor (padrão 0,50 = 50%)
     */
    public function __construct(
        private readonly float $weightSelf    = self::DEFAULT_SELF_WEIGHT,
        private readonly float $weightPeers   = self::DEFAULT_PEER_WEIGHT,
        private readonly float $weightManager = self::DEFAULT_MANAGER_WEIGHT
    ) {
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o score 360° composto ponderado.
     *
     * O array $metrics deve conter:
     *   'self'    => 4.2             → autoavaliação (escala Likert 1-5, 1-10 ou 0-100)
     *   'peers'   => [4.0, 3.5, 4.5] → notas dos pares (média calculada aqui)
     *   'manager' => 85              → nota do gestor (escala 0-100)
     *   'weights' => ['self' => 0.2, 'peers' => 0.3, 'manager' => 0.5] (opcional)
     *
     * @param Employee $employee Funcionário avaliado
     * @param array<string, mixed> $metrics Dados de avaliação das 3 fontes
     * @return float Score composto normalizado (0,0 a 100,0)
     */
    public function calculateScore(Employee $employee, array $metrics): float
    {
        // Usa pesos informados nos dados ou os pesos padrão do construtor
        $wSelf    = (float)($metrics['weights']['self']    ?? $this->weightSelf);
        $wPeers   = (float)($metrics['weights']['peers']   ?? $metrics['weights']['peer'] ?? $this->weightPeers);
        $wManager = (float)($metrics['weights']['manager'] ?? $this->weightManager);

        // Normaliza os pesos para garantir que somam 1,0 (100%)
        $totalWeight = $wSelf + $wPeers + $wManager;
        if ($totalWeight <= 0.0) {
            $totalWeight = 1.0; // Evita divisão por zero
        }

        // Normaliza cada nota para a escala 0-100 (detecta 1-5, 1-10 ou 0-100)
        $selfScore    = $this->normalizeScore($metrics['self']    ?? 0.0);
        $peerScore    = $this->resolvePeerScore($metrics['peers'] ?? $metrics['peer'] ?? []);
        $managerScore = $this->normalizeScore($metrics['manager'] ?? 0.0);

        // Calcula o score composto ponderado
        $weightedTotal = ($selfScore * $wSelf) + ($peerScore * $wPeers) + ($managerScore * $wManager);
        $finalScore    = $weightedTotal / $totalWeight;

        return round(min(100.0, max(0.0, $finalScore)), 2);
    }

    /**
     * Calcula a média das notas dos pares avaliadores.
     * Aceita: número único, ou array de notas individuais de cada par.
     *
     * @param mixed $peers Nota única ou array de notas dos pares
     * @return float Score médio dos pares normalizado (0,0 a 100,0)
     */
    private function resolvePeerScore(mixed $peers): float
    {
        if (is_numeric($peers)) {
            // Nota única de par: normaliza diretamente
            return $this->normalizeScore((float)$peers);
        }

        if (is_array($peers) && !empty($peers)) {
            // Múltiplas notas de pares: normaliza cada uma e calcula a média
            $normalizedList = array_map(
                fn($p) => $this->normalizeScore(is_numeric($p) ? (float)$p : 0.0),
                $peers
            );
            return array_sum($normalizedList) / count($normalizedList);
        }

        return 0.0; // Sem avaliação de pares: zero
    }

    /**
     * Normaliza qualquer nota de avaliação para a escala 0-100.
     * Auto-detecta a escala usada:
     *   - 0 a 5   → escala Likert (1-5) → multiplica por 20
     *   - 0 a 10  → escala de notas     → multiplica por 10
     *   - 0 a 100 → já normalizada      → usa diretamente
     *
     * @param float $rawScore Nota bruta da avaliação
     * @return float Nota normalizada para escala 0-100
     */
    private function normalizeScore(float $rawScore): float
    {
        if ($rawScore <= 0.0) {
            return 0.0; // Nota zero ou negativa
        }

        // Detecta escala Likert 1-5 (ex: avaliações de satisfação)
        if ($rawScore <= 5.0) {
            return ($rawScore / 5.0) * 100.0; // Ex: 4,0 → 80,0
        }

        // Detecta escala 1-10 (ex: notas escolares)
        if ($rawScore <= 10.0) {
            return ($rawScore / 10.0) * 100.0; // Ex: 8,5 → 85,0
        }

        // Já está na escala 0-100: usa diretamente, limitando ao máximo
        return min(100.0, $rawScore);
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Retorna o identificador do modelo de avaliação.
     */
    public function getScoringModel(): string
    {
        return 'EVALUATION_360'; // Avaliação 360 graus — multi-avaliador
    }
}
