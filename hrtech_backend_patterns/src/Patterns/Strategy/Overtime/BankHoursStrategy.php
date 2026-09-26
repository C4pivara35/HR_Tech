<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Overtime;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 1 de 3 — Banco de Horas)
// ============================================================
// Esta estratégia representa o regime de Banco de Horas (Art. 59 §2 CLT).
// O contrato monetário é R$ 0,00 (sem pagamento imediato),
// mas os minutos são creditados no banco interno do funcionário.
//
// Por ser Strategy, o cliente usa a mesma interface OvertimeStrategyInterface
// e recebe R$ 0,00 como retorno. Os créditos de banco de horas são
// tratados separadamente via calculateBankMinutes().
// ============================================================

use HrTech\Contracts\OvertimeStrategyInterface;

/**
 * Classe BankHoursStrategy — PADRÃO STRATEGY (banco de horas)
 *
 * Implementa o regime de compensação de horas (Banco de Horas) onde
 * o pagamento monetário é zero e as horas são creditadas para compensação
 * futura, conforme Art. 59 §2 da CLT.
 */
class BankHoursStrategy implements OvertimeStrategyInterface
{
    // -------------------------------------------------------
    // ALGORITMO ENCAPSULADO NESTA ESTRATÉGIA:
    // Pagamento monetário = R$ 0,00 (diferente das outras estratégias).
    // Compensação em minutos = horasExtras × 60 × fatorDeCredito.
    // -------------------------------------------------------

    /**
     * Fator de crédito aplicado ao banco de horas.
     * 1,0 = 1 hora extra vira 1 hora no banco.
     * 1,5 = 1 hora extra vira 1h30 no banco (mais benéfico ao funcionário).
     *
     * @param float $creditFactor Fator de crédito (padrão 1,0 = proporcional)
     */
    public function __construct(private readonly float $creditFactor = 1.0)
    {
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Retorna R$ 0,00.
     *
     * No banco de horas, não há pagamento monetário de hora extra.
     * O cliente (CltPayroll) recebe zero e trata o crédito de minutos
     * via calculateBankMinutes() separadamente.
     *
     * @param float $hourlyRate Ignorado nesta estratégia (sem pagamento)
     * @param float $overtimeHours Quantidade de horas (usada só em calculateBankMinutes)
     * @return float Sempre R$ 0,00 — sem pagamento monetário imediato
     */
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float
    {
        // Banco de horas: custo monetário imediato é sempre zero
        return 0.0;
    }

    /**
     * Calcula os minutos a serem creditados no banco de horas do funcionário.
     * Minutos = horasExtras × 60 × fatorDeCredito
     *
     * @param float $overtimeHours Quantidade de horas extras trabalhadas
     * @return int Minutos a creditar no banco de horas
     */
    public function calculateBankMinutes(float $overtimeHours): int
    {
        if ($overtimeHours <= 0.0) {
            return 0; // Sem horas extras: sem crédito
        }

        // Converte horas em minutos e aplica o fator de crédito contratual
        return (int)round($overtimeHours * 60 * max(0.0, $this->creditFactor));
    }

    /**
     * Retorna o fator de crédito configurado para auditoria.
     */
    public function getCreditFactor(): float
    {
        return $this->creditFactor;
    }

    /**
     * Descrição legal e operacional desta estratégia.
     * Útil para registro em holerite e auditoria trabalhista.
     */
    public function getDescription(): string
    {
        return sprintf(
            'Banco de Horas conforme Art. 59 §2 da CLT — custo monetário: R$ 0,00; fator de crédito: %.2f×.',
            $this->creditFactor
        );
    }
}
