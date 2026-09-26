<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Overtime;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 1 de 3 — Hora Extra 50%)
// ============================================================
// Intenção: Encapsular o algoritmo de cálculo de hora extra
// dentro de uma classe intercambiável. O cliente (CltPayroll)
// não precisa saber QUE algoritmo está sendo usado — apenas
// chama calculateOvertime() e recebe o resultado.
//
// Por que Strategy aqui?
//   O sistema precisa calcular horas extras com regras diferentes:
//   - Dia útil → 50% adicional (esta classe)
//   - Domingo/feriado → 100% adicional (Sunday100Strategy)
//   - Banco de horas → sem pagamento (BankHoursStrategy)
//   Trocar a regra é só trocar o objeto injetado. Sem if/switch.
//
// Esta é a implementação da interface OvertimeStrategyInterface.
// ============================================================

use HrTech\Contracts\OvertimeStrategyInterface;

/**
 * Classe Standard50Strategy — PADRÃO STRATEGY (hora extra 50%)
 *
 * Calcula o valor monetário de horas extras em dias úteis
 * com adicional de 50%, conforme Art. 59 §1 da CLT.
 *
 * Fórmula: valor = valorHora × 1,5 × qtdHorasExtras
 */
class Standard50Strategy implements OvertimeStrategyInterface
{
    // -------------------------------------------------------
    // ALGORITMO ENCAPSULADO NESTA ESTRATÉGIA:
    // Multiplicador 1,5 = 100% do valor normal + 50% de adicional.
    // Este é o único parâmetro que diferencia esta estratégia das demais.
    // -------------------------------------------------------
    /** Multiplicador da hora extra em dia útil: 100% + 50% de adicional = 1,5× */
    public const float SURCHARGE_MULTIPLIER = 1.5;

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o valor monetário da hora extra.
     *
     * Esta é a implementação do contrato definido por OvertimeStrategyInterface.
     * Trocar esta classe por Sunday100Strategy ou BankHoursStrategy muda
     * completamente o comportamento sem alterar o código do cliente.
     *
     * Fórmula: valorHora × 1,5 × horasExtras
     *
     * @param float $hourlyRate Valor da hora normal do funcionário (em R$)
     * @param float $overtimeHours Quantidade de horas extras trabalhadas
     * @return float Valor total a pagar pelas horas extras (em R$)
     */
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float
    {
        // Valores inválidos (zero ou negativos) resultam em zero pagamento
        if ($hourlyRate <= 0.0 || $overtimeHours <= 0.0) {
            return 0.0;
        }

        // Aplica o multiplicador de 50%: valorHora × 1,5 × horas
        return round($hourlyRate * self::SURCHARGE_MULTIPLIER * $overtimeHours, 2);
    }

    /**
     * Descrição legal e operacional desta estratégia de hora extra.
     * Útil para registro em holerite e auditoria trabalhista.
     */
    public function getDescription(): string
    {
        return 'Hora extra em dia útil com adicional de 50% — Art. 59 §1 da CLT.';
    }
}
