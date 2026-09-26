<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\BenefitDiscount;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 2 de 3 — Vale Transporte)
// ============================================================
// Estratégia para cálculo do desconto de Vale Transporte.
//
// Regras específicas desta estratégia:
//   - O desconto é limitado a 6% do salário base bruto mensal
//     (Lei Federal 7.418/1985 + Decreto 95.247/1987)
//   - O desconto também é limitado ao valor real do benefício
//   - Aplica-se o menor valor entre as duas limitações
//
// O cliente (CltPayroll) usa a mesma interface BenefitDiscountStrategyInterface,
// podendo trocar esta estratégia por HealthPlanStrategy ou MealVoucherStrategy.
// ============================================================

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\BenefitType;

/**
 * Classe TransportationVoucherStrategy — PADRÃO STRATEGY (desconto vale transporte)
 *
 * Implementa as regras de desconto do Vale Transporte conforme
 * a Lei Federal 7.418/1985: o desconto é limitado a 6% do salário
 * base bruto mensal ou ao valor real do benefício, o que for menor.
 */
class TransportationVoucherStrategy implements BenefitDiscountStrategyInterface
{
    // -------------------------------------------------------
    // ALGORITMO ESPECÍFICO DESTA ESTRATÉGIA:
    // Teto de 6% do salário base (diferente das outras estratégias).
    // Plano de saúde usa faixas etárias. Vale refeição usa 20% do PAT.
    // Vale transporte usa 6% do salário.
    // -------------------------------------------------------

    /** Percentual máximo de desconto do vale transporte: 6% do salário base mensal (Lei 7.418/85) */
    public const float STATUTORY_SALARY_CAP_PERCENTAGE = 0.06;

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o desconto do Vale Transporte.
     *
     * Regra: o desconto é o menor valor entre:
     *   1. 6% do salário base mensal bruto do funcionário
     *   2. O valor real do vale transporte concedido
     *
     * Exemplo: salário R$ 3.000,00 → 6% = R$ 180,00
     *   Se VT for R$ 150,00 → desconta R$ 150,00 (menor valor)
     *   Se VT for R$ 250,00 → desconta R$ 180,00 (limitado a 6%)
     *
     * @param Employee $employee Funcionário (para obter o salário base)
     * @param Benefit $benefit Benefício de vale transporte
     * @return float Valor do desconto dentro do limite legal (em R$)
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float
    {
        // Benefícios não dedutíveis não geram desconto na folha
        if (!$benefit->isDeductible()) {
            return 0.0;
        }

        // Calcula o teto legal: 6% do salário base mensal bruto
        $baseSalary = $employee->getBaseSalary()->getAmount();
        $salaryCap  = $baseSalary * self::STATUTORY_SALARY_CAP_PERCENTAGE;

        // Valor real do vale transporte concedido pela empresa
        $actualVoucherValue = $benefit->getValue()->getAmount();

        // Aplica o menor valor entre o teto salarial (6%) e o valor real do VT
        $deduction = min($salaryCap, $actualVoucherValue);

        return round(max(0.0, $deduction), 2);
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Retorna o tipo de benefício desta estratégia.
     * Usado pelo CltPayroll para selecionar automaticamente a estratégia correta.
     */
    public function getBenefitType(): string
    {
        return BenefitType::TRANSPORTATION->value; // Ex: 'TRANSPORTATION'
    }
}
