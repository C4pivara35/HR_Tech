<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\BenefitDiscount;

// ============================================================
// PADRÃO DE PROJETO: STRATEGY (Exemplo 2 de 3 — Plano de Saúde)
// ============================================================
// Intenção: Encapsular o algoritmo de cálculo do copagamento
// de plano de saúde, tornando-o intercambiável com as outras
// estratégias de benefícios (VT, VR).
//
// Regras específicas desta estratégia:
//   - Copagamento base fixo (se definido) ou percentual do benefício
//   - Acréscimo por faixa etária conforme tabela ANS (agência reguladora)
//   - Faixas: até 18 anos = 0%, 19-28 = 5%, 29-38 = 10%, etc.
//
// A interface BenefitDiscountStrategyInterface garante que o cliente
// (CltPayroll) possa trocar por MealVoucherStrategy sem mudar seu código.
// ============================================================

use DateTimeImmutable;
use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\BenefitType;

/**
 * Classe HealthPlanStrategy — PADRÃO STRATEGY (desconto plano de saúde)
 *
 * Calcula o copagamento do empregado para o plano de saúde corporativo.
 * Combina um valor fixo de copagamento com um percentual por faixa etária
 * conforme as diretrizes da ANS (Agência Nacional de Saúde Suplementar).
 */
class HealthPlanStrategy implements BenefitDiscountStrategyInterface
{
    // -------------------------------------------------------
    // TABELA DE FAIXAS ETÁRIAS DA ANS:
    // Define o percentual adicional de copagamento por idade.
    // Quanto mais velho o funcionário, maior o percentual.
    // -------------------------------------------------------
    /**
     * Faixas etárias ANS com alíquotas de copagamento.
     *
     * @var array<int, array{max_age: int, rate: float}>
     */
    public const DEFAULT_AGE_BRACKETS = [
        ['max_age' =>  18, 'rate' => 0.00], //  0 a 18 anos: sem adicional por idade
        ['max_age' =>  28, 'rate' => 0.05], // 19 a 28 anos: +5% sobre o valor do benefício
        ['max_age' =>  38, 'rate' => 0.10], // 29 a 38 anos: +10%
        ['max_age' =>  48, 'rate' => 0.15], // 39 a 48 anos: +15%
        ['max_age' =>  58, 'rate' => 0.20], // 49 a 58 anos: +20%
        ['max_age' => 999, 'rate' => 0.30], // 59 anos ou mais: +30%
    ];

    /**
     * @param float $fixedBaseCopay Valor fixo de copagamento (0,0 = usa o da entidade Benefit)
     * @param array<int, array{max_age: int, rate: float}> $ageBrackets Faixas etárias personalizadas
     */
    public function __construct(
        private readonly float $fixedBaseCopay = 0.0,
        private readonly array $ageBrackets = self::DEFAULT_AGE_BRACKETS
    ) {
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Calcula o copagamento do plano de saúde.
     *
     * Fórmula: copagamentoBase + (valorBenefício × taxaFaixaEtária)
     *
     * Exemplo: funcionário com 35 anos, benefício de R$ 500,00, copagamento base R$ 50,00
     *   → 50,00 + (500,00 × 0,10) = R$ 100,00 de desconto na folha
     *
     * @param Employee $employee Funcionário (para calcular a idade)
     * @param Benefit $benefit Benefício de plano de saúde
     * @return float Valor do desconto a ser descontado na folha (em R$)
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float
    {
        // Benefícios não dedutíveis não geram desconto na folha
        if (!$benefit->isDeductible()) {
            return 0.0;
        }

        // Calcula a idade atual do funcionário para determinar a faixa etária ANS
        $now = new DateTimeImmutable('now');
        $age = $employee->getBirthDate()->diff($now)->y;

        // Define o copagamento base: usa o valor fixo configurado ou o da entidade Benefit
        $baseCopay = $this->fixedBaseCopay > 0.0
            ? $this->fixedBaseCopay                               // Copagamento fixo corporativo
            : $benefit->calculateEmployeeContribution()->getAmount(); // Calculado pelo benefício

        // Busca a alíquota correspondente à faixa etária do funcionário
        $ageRate = $this->resolveAgeRate($age);

        // Componente etário: valor do benefício × percentual da faixa ANS
        $ageComponent = $benefit->getValue()->getAmount() * $ageRate;

        // Desconto total = copagamento base + acréscimo por idade
        $totalDiscount = $baseCopay + $ageComponent;

        return round(max(0.0, $totalDiscount), 2);
    }

    /**
     * Resolve a alíquota da faixa etária ANS conforme a idade do funcionário.
     *
     * @param int $age Idade em anos completos
     * @return float Alíquota da faixa etária (ex: 0.10 = 10%)
     */
    public function resolveAgeRate(int $age): float
    {
        foreach ($this->ageBrackets as $bracket) {
            if ($age <= $bracket['max_age']) {
                // Encontrou a faixa correta para esta idade
                return (float)$bracket['rate'];
            }
        }

        // Fallback: faixa máxima para idades extremas não mapeadas
        return 0.30;
    }

    /**
     * Retorna o copagamento base fixo configurado.
     */
    public function getFixedBaseCopay(): float
    {
        return $this->fixedBaseCopay;
    }

    /**
     * MÉTODO DA INTERFACE (Strategy): Retorna o tipo de benefício que esta estratégia trata.
     * O cliente (CltPayroll) usa este valor como chave para selecionar a estratégia correta.
     */
    public function getBenefitType(): string
    {
        return BenefitType::HEALTH_PLAN->value; // Ex: 'HEALTH_PLAN'
    }
}
