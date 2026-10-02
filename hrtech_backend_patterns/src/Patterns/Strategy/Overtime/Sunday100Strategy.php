<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Overtime;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 1 de 3 — Hora Extra 100%)
// ============================================================
// Mesma interface que Standard50Strategy, mas com lógica diferente:
// Domingo e feriado → adicional de 100% (multiplicador 2,0×).
//
// O cliente (ex: CltPayroll) injeta esta estratégia ou Standard50Strategy
// dependendo do dia. O código do cliente NÃO MUDA — apenas a estratégia.
//
// Fundamento legal: Art. 70 da CLT + Súmula 146 do TST.
// ============================================================

use HrTech\Contracts\OvertimeStrategyInterface;

/**
 * Classe Sunday100Strategy — PADRÃO STRATEGY (hora extra 100%)
 *
 * Calcula o valor monetário de horas extras em domingos e feriados
 * com adicional de 100%, conforme Art. 70 da CLT e Súmula 146 TST.
 *
 * Fórmula: valor = valorHora × 2,0 × qtdHorasExtras
 */
class Sunday100Strategy implements OvertimeStrategyInterface
{
    // -------------------------------------------------------
    // ALGORITMO ENCAPSULADO NESTA ESTRATÉGIA:
    // Multiplicador 2,0 = 100% normal + 100% de adicional.
    // Diferente do Standard50Strategy (1,5×) e BankHoursStrategy (0×).
    // -------------------------------------------------------
    /** Multiplicador de hora extra em domingo/feriado: 100% + 100% adicional = 2,0× */
    public const SURCHARGE_MULTIPLIER = 2.0;

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o valor da hora extra em domingo/feriado.
     *
     * Fórmula: valorHora × 2,0 × horasExtras
     *
     * @param float $hourlyRate Valor da hora normal do funcionário (em R$)
     * @param float $overtimeHours Quantidade de horas extras trabalhadas
     * @return float Valor total a pagar pelas horas extras (em R$)
     */
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float
    {
        // Valores inválidos resultam em zero pagamento
        if ($hourlyRate <= 0.0 || $overtimeHours <= 0.0) {
            return 0.0;
        }

        // Aplica o multiplicador de 100%: valorHora × 2,0 × horas
        return round($hourlyRate * self::SURCHARGE_MULTIPLIER * $overtimeHours, 2);
    }

    /**
     * Descrição legal e operacional desta estratégia.
     * Útil para registro em holerite e auditoria trabalhista.
     */
    public function getDescription(): string
    {
        return 'Hora extra em domingo/feriado com adicional de 100% — Art. 70 da CLT e Súmula 146 TST.';
    }
}
