<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\BenefitDiscount;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 2 de 3 — Vale Refeição)
// ============================================================
// Estratégia para cálculo do desconto de Vale Refeição/Alimentação.
//
// Regras específicas desta estratégia:
//   - O desconto não pode exceder 20% do valor mensal do benefício
//     (limite legal do PAT — Programa de Alimentação do Trabalhador, Lei 6.321/1976)
//   - Se houver copagamento fixo configurado, usa-o; senão, usa
//     o copagamento calculado pelo próprio benefício
//   - O resultado final é sempre limitado ao teto de 20%
// ============================================================

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\BenefitType;

/**
 * Classe MealVoucherStrategy — PADRÃO STRATEGY (desconto vale refeição)
 *
 * Implementa as regras de desconto do Vale Refeição/Alimentação
 * conforme o PAT (Lei Federal 6.321/1976), garantindo que o desconto
 * nunca ultrapasse 20% do valor mensal do benefício.
 */
class MealVoucherStrategy implements BenefitDiscountStrategyInterface
{
    // -------------------------------------------------------
    // ALGORITMO ESPECÍFICO DESTA ESTRATÉGIA:
    // Teto legal de 20% do valor do benefício (Lei PAT).
    // Diferente do plano de saúde (faixas etárias) e do VT (6% do salário).
    // -------------------------------------------------------

    /** Percentual máximo legal de desconto do PAT: 20% do valor do benefício */
    public const float PAT_MAX_LEGAL_DEDUCTION_PERCENTAGE = 0.20;

    /**
     * @param float $fixedNominalCopay Valor fixo de copagamento em R$ (0,0 = usa o da entidade Benefit)
     */
    public function __construct(private readonly float $fixedNominalCopay = 0.0)
    {
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o desconto do Vale Refeição.
     *
     * Regra: o desconto é o menor valor entre o copagamento nominal
     * e o teto legal de 20% do valor do benefício (PAT).
     *
     * Exemplo: benefício R$ 500,00 → teto PAT = R$ 100,00 (20%)
     *   Se copagamento for R$ 80,00 → desconta R$ 80,00
     *   Se copagamento for R$ 120,00 → desconta só R$ 100,00 (limitado)
     *
     * @param Employee $employee Funcionário (não usado nesta estratégia)
     * @param Benefit $benefit Benefício de vale refeição/alimentação
     * @return float Valor do desconto respeitando o teto PAT (em R$)
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float
    {
        // Benefícios não dedutíveis não geram desconto na folha
        if (!$benefit->isDeductible()) {
            return 0.0;
        }

        $benefitValue = $benefit->getValue()->getAmount();

        // Calcula o teto legal PAT: 20% do valor mensal do benefício
        $patCap = $benefitValue * self::PAT_MAX_LEGAL_DEDUCTION_PERCENTAGE;

        // Define o copagamento nominal (fixo configurado ou calculado pelo benefício)
        $nominalCopay = $this->fixedNominalCopay > 0.0
            ? $this->fixedNominalCopay
            : $benefit->calculateEmployeeContribution()->getAmount();

        // Aplica o teto legal: desconto = mínimo entre o copagamento e o teto de 20% do PAT
        $discount = min($nominalCopay, $patCap);

        return round(max(0.0, $discount), 2);
    }

    /**
     * Retorna o valor fixo de copagamento configurado.
     */
    public function getFixedNominalCopay(): float
    {
        return $this->fixedNominalCopay;
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Retorna o tipo de benefício desta estratégia.
     * Usado pelo CltPayroll para selecionar a estratégia correta por tipo de benefício.
     */
    public function getBenefitType(): string
    {
        return BenefitType::MEAL_VOUCHER->value; // Ex: 'MEAL_VOUCHER'
    }
}
