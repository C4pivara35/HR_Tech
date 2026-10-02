<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

// ============================================================
// PADRÃO DE PROJETO: TEMPLATE METHOD (Exemplo 1/3 — Subclasse CLT)
// ============================================================
// Esta classe é uma SUBCLASSE CONCRETA do Template Method.
// Implementa os passos primitivos (abstract) definidos em
// PayrollCalculatorTemplate com as regras específicas da CLT.
//
// O esqueleto do algoritmo (a ordem dos passos) está em
// PayrollCalculatorTemplate::calculatePayroll() — imutável.
// Aqui definimos APENAS o que muda: as regras do contrato CLT.
// ============================================================

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\ValidationException;

/**
 * Classe CltPayroll — TEMPLATE METHOD (subclasse concreta CLT)
 *
 * Implementa o cálculo de folha para funcionários CLT com:
 * - INSS progressivo (tabela 2024-2026)
 * - IRRF progressivo com dedução por dependentes
 * - Descontos de benefícios (plano de saúde, VT, VR)
 */
class CltPayroll extends PayrollCalculatorTemplate
{
    // -------------------------------------------------------
    // Tabela INSS progressiva 2024-2026 (Art. 198 da CLT).
    // Cada faixa tem um teto e uma alíquota própria.
    // -------------------------------------------------------
    /**
     * Faixas progressivas do INSS (tabela brasileira 2024-2026).
     *
     * @var array<int, array{limit: float, rate: float}>
     */
    public const INSS_BRACKETS = [
        ['limit' => 1412.00, 'rate' => 0.075], // Até R$ 1.412,00 → 7,5%
        ['limit' => 2666.68, 'rate' => 0.09],  // Até R$ 2.666,68 → 9,0%
        ['limit' => 4000.03, 'rate' => 0.12],  // Até R$ 4.000,03 → 12,0%
        ['limit' => 7786.02, 'rate' => 0.14],  // Até R$ 7.786,02 → 14,0%
    ];

    // -------------------------------------------------------
    // Tabela IRRF progressiva com parcelas de dedução.
    // -------------------------------------------------------
    /**
     * Faixas progressivas do IRRF com parcelas dedutíveis.
     *
     * @var array<int, array{limit: float, rate: float, deduction: float}>
     */
    public const IRRF_BRACKETS = [
        ['limit' => 2259.20, 'rate' => 0.0,   'deduction' => 0.0],      // Isento
        ['limit' => 2826.65, 'rate' => 0.075, 'deduction' => 169.44],   // 7,5%
        ['limit' => 3751.05, 'rate' => 0.15,  'deduction' => 381.44],   // 15%
        ['limit' => 4664.68, 'rate' => 0.225, 'deduction' => 662.77],   // 22,5%
        ['limit' => INF,     'rate' => 0.275, 'deduction' => 896.00],   // 27,5%
    ];

    /** Valor de dedução por dependente no IRRF (R$ 189,59 por dependente) */
    public const DEPENDENT_DEDUCTION_AMOUNT = 189.59;

    /**
     * Dados da folha atual, salvo pelo gancho beforeCalculation().
     *
     * @var array<string, mixed>
     */
    private array $currentPayrollData = [];

    /**
     * Estratégias de desconto de benefícios (padrão Strategy) injetadas.
     * Permite trocar o algoritmo de desconto de cada benefício sem alterar esta classe.
     *
     * @var array<string, BenefitDiscountStrategyInterface>
     */
    private array $benefitStrategies = [];

    /**
     * Construtor: recebe opcionalmente estratégias de benefício (padrão Strategy).
     *
     * @param array<string, BenefitDiscountStrategyInterface> $benefitStrategies
     */
    public function __construct(array $benefitStrategies = [])
    {
        foreach ($benefitStrategies as $strategy) {
            $this->addBenefitStrategy($strategy);
        }
    }

    /**
     * Registra uma estratégia de desconto para um tipo de benefício.
     * (Integração com o padrão Strategy de benefícios)
     */
    public function addBenefitStrategy(BenefitDiscountStrategyInterface $strategy): self
    {
        // Chave = tipo do benefício (ex: 'HEALTH_PLAN', 'MEAL_VOUCHER')
        $this->benefitStrategies[$strategy->getBenefitType()] = $strategy;
        return $this;
    }

    // -------------------------------------------------------
    // IMPLEMENTAÇÃO DO GANCHO: salva os dados da folha para
    // uso nos passos primitivos durante o cálculo.
    // -------------------------------------------------------
    /**
     * Gancho pré-cálculo: salva os dados da folha no contexto da instância.
     */
    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        // Salva o contexto para uso nos passos de INSS, IRRF e benefícios
        $this->currentPayrollData = $payrollData;
    }

    // -------------------------------------------------------
    // IMPLEMENTAÇÃO DOS PASSOS PRIMITIVOS (abstract da base)
    // -------------------------------------------------------

    /**
     * PASSO PRIMITIVO: Valida se o funcionário está ativo e é contrato CLT.
     *
     * @throws ValidationException Se não for CLT ou estiver inativo
     */
    protected function validateEmployee(Employee $employee): void
    {
        // Funcionário inativo não pode ter folha processada
        if (!$employee->isActive()) {
            throw ValidationException::forField('is_active', 'Não é possível processar folha de funcionário inativo.');
        }

        // CltPayroll só aceita contratos do tipo CLT
        if ($employee->getEmploymentType() !== EmploymentType::CLT) {
            throw ValidationException::forField(
                'employment_type',
                sprintf(
                    "CltPayroll requer contrato CLT, mas recebeu '%s'.",
                    $employee->getEmploymentType()->value
                )
            );
        }
    }

    /**
     * PASSO PRIMITIVO: Calcula o salário bruto CLT.
     * Composição: salário base + horas extras + bônus + comissão + DSR + adicional de periculosidade.
     */
    protected function calculateGrossSalary(Employee $employee, array $payrollData): float
    {
        // Se já veio o bruto pré-calculado, usa diretamente
        if (isset($payrollData['gross_salary']) && (float)$payrollData['gross_salary'] > 0.0) {
            return round((float)$payrollData['gross_salary'], 2);
        }

        $base       = $employee->getBaseSalary()->getAmount(); // Salário base contratual
        $overtime   = (float)($payrollData['overtime_amount'] ?? $payrollData['overtime_pay'] ?? 0.0);
        $bonus      = (float)($payrollData['bonus']      ?? $payrollData['bonuses']     ?? 0.0);
        $commission = (float)($payrollData['commission'] ?? $payrollData['commissions'] ?? 0.0);
        $dsr        = (float)($payrollData['dsr']        ?? 0.0); // Descanso Semanal Remunerado
        $hazardPay  = (float)($payrollData['hazard_pay'] ?? 0.0); // Adicional de periculosidade

        $gross = $base + $overtime + $bonus + $commission + $dsr + $hazardPay;

        return round(max(0.0, $gross), 2);
    }

    /**
     * PASSO PRIMITIVO: Calcula o INSS progressivo CLT (tabela 2024-2026).
     * Cada faixa de salário tem uma alíquota própria (não cumulativa).
     */
    protected function calculateInss(float $grossSalary): float
    {
        if ($grossSalary <= 0.0) {
            return 0.0;
        }

        $totalInss = 0.0;
        $prevLimit = 0.0; // Teto da faixa anterior

        foreach (self::INSS_BRACKETS as $bracket) {
            if ($grossSalary > $prevLimit) {
                // Calcula o valor tributável nesta faixa
                $taxableInBracket = min($grossSalary, $bracket['limit']) - $prevLimit;
                $totalInss       += $taxableInBracket * $bracket['rate'];
                $prevLimit        = $bracket['limit'];
            } else {
                break; // Salário não atingiu esta faixa, encerra o loop
            }
        }

        return round($totalInss, 2);
    }

    /**
     * PASSO PRIMITIVO: Calcula o IRRF progressivo CLT com dedução por dependentes.
     * Base de cálculo = Bruto - INSS - (dependentes × R$ 189,59).
     */
    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float
    {
        if ($grossSalary <= 0.0) {
            return 0.0;
        }

        // Abatimento por dependentes legais
        $dependentAllowance = max(0, $dependents) * self::DEPENDENT_DEDUCTION_AMOUNT;

        // Base de cálculo do IRRF
        $baseIrrf = max(0.0, $grossSalary - $inssDeduction - $dependentAllowance);

        // Aplica a faixa da tabela progressiva do IRRF
        foreach (self::IRRF_BRACKETS as $bracket) {
            if ($baseIrrf <= $bracket['limit']) {
                $tax = ($baseIrrf * $bracket['rate']) - $bracket['deduction'];
                return round(max(0.0, $tax), 2);
            }
        }

        // Fallback: faixa máxima (> R$ 4.664,68)
        $topBracket = end(self::IRRF_BRACKETS);
        $tax        = ($baseIrrf * $topBracket['rate']) - $topBracket['deduction'];

        return round(max(0.0, $tax), 2);
    }

    /**
     * PASSO PRIMITIVO: Calcula os descontos de benefícios (copagamento do empregado).
     * Usa o padrão Strategy para calcular o desconto de cada benefício.
     */
    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float
    {
        // Sobrescrita direta nos dados da folha (para testes ou casos especiais)
        if (isset($this->currentPayrollData['benefits_discount'])) {
            return round((float)$this->currentPayrollData['benefits_discount'], 2);
        }

        // Processa entidades Benefit específicas (com estratégias de cálculo)
        $benefits = $this->currentPayrollData['benefits'] ?? [];
        if (!empty($benefits) && is_array($benefits)) {
            $totalDiscount    = 0.0;
            $baseSalaryMoney  = Money::fromFloat($grossSalary);

            foreach ($benefits as $benefit) {
                if ($benefit instanceof Benefit) {
                    $benefitType = $benefit->getType()->value;

                    if (isset($this->benefitStrategies[$benefitType])) {
                        // Usa a estratégia registrada para este tipo de benefício (padrão Strategy)
                        $totalDiscount += $this->benefitStrategies[$benefitType]->calculateDiscount($employee, $benefit);
                    } else {
                        // Sem estratégia registrada: usa cálculo padrão do próprio benefício
                        $totalDiscount += $benefit->calculateDeductionForSalary($baseSalaryMoney)->getAmount();
                    }
                }
            }

            return round($totalDiscount, 2);
        }

        return 0.0; // Sem benefícios: sem desconto
    }

    /**
     * PASSO PRIMITIVO: Retorna o identificador do tipo de contrato para o holerite.
     */
    protected function getContractTypeName(): string
    {
        return 'CLT'; // Consolidação das Leis do Trabalho
    }
}
