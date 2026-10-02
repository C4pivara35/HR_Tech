<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

// ============================================================
// PADRÃO DE PROJETO: TEMPLATE METHOD (Exemplo 1/3 — Subclasse PJ)
// ============================================================
// Subclasse concreta que implementa os passos específicos do
// contrato Pessoa Jurídica (PJ).
//
// O que muda em relação ao CLT:
//   - Bruto = valor da nota fiscal (invoice_amount)
//   - INSS = R$ 0,00 (responsabilidade da empresa do prestador)
//   - IRRF = 1,5% de retenção na fonte (IN RFB 1.234/2012)
//   - Benefícios = geralmente zero (pode haver exceções)
//   - CSRF (PIS+COFINS+CSLL) = 4,65% adicional para notas > R$ 215,05
// ============================================================

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Exceptions\ValidationException;

/**
 * Classe PjPayroll — TEMPLATE METHOD (subclasse concreta PJ)
 *
 * Calcula a folha de pagamento para prestadores de serviço PJ.
 * Aplica retenções fiscais corporativas (IRRF 1,5% e CSRF 4,65%)
 * e isenta completamente INSS e descontos CLT.
 */
class PjPayroll extends PayrollCalculatorTemplate
{
    /** Alíquota padrão de retenção do IRRF para PJ: 1,5% (IN RFB 1.234/2012) */
    public const STANDARD_IRRF_WITHHOLDING_RATE = 0.015;

    /** Alíquota CSRF = PIS (0,65%) + COFINS (3,0%) + CSLL (1,0%) = 4,65% */
    public const STANDARD_CSRF_WITHHOLDING_RATE = 0.0465;

    /**
     * Dados da folha atual (salvo pelo gancho beforeCalculation).
     *
     * @var array<string, mixed>
     */
    private array $currentPayrollData = [];

    /** Valor da CSRF calculada para o holerite atual */
    private float $lastCsrfDeduction = 0.0;

    /**
     * Gancho pré-cálculo: salva os dados da folha e zera a CSRF anterior.
     */
    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        $this->currentPayrollData = $payrollData;
        $this->lastCsrfDeduction  = 0.0; // Reseta para cada novo cálculo
    }

    /**
     * PASSO PRIMITIVO: Valida se o funcionário está ativo e é contrato PJ.
     *
     * @throws ValidationException Se não for PJ ou estiver inativo
     */
    protected function validateEmployee(Employee $employee): void
    {
        if (!$employee->isActive()) {
            throw ValidationException::forField('is_active', 'Não é possível processar folha de prestador PJ inativo.');
        }

        // PjPayroll só aceita contratos do tipo PJ
        if ($employee->getEmploymentType() !== EmploymentType::PJ) {
            throw ValidationException::forField(
                'employment_type',
                sprintf(
                    "PjPayroll requer contrato PJ, mas recebeu '%s'.",
                    $employee->getEmploymentType()->value
                )
            );
        }
    }

    /**
     * PASSO PRIMITIVO: Calcula o valor bruto PJ.
     * Fonte: valor da nota fiscal (invoice_amount) ou salário base cadastrado.
     */
    protected function calculateGrossSalary(Employee $employee, array $payrollData): float
    {
        // Prioridade: nota fiscal > gross_salary informado > salário base cadastrado
        $invoiceAmount = $payrollData['invoice_amount']
            ?? $payrollData['gross_salary']
            ?? $employee->getBaseSalary()->getAmount();

        return round(max(0.0, (float)$invoiceAmount), 2);
    }

    /**
     * PASSO PRIMITIVO: INSS = R$ 0,00 para PJ.
     * O prestador PJ recolhe via Pró-labore/DAS na própria empresa.
     */
    protected function calculateInss(float $grossSalary): float
    {
        return 0.0; // PJ não tem desconto de INSS na folha
    }

    /**
     * PASSO PRIMITIVO: Calcula o IRRF retido na fonte para PJ.
     * Alíquota padrão: 1,5% sobre o valor bruto da nota fiscal.
     */
    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float
    {
        if ($grossSalary <= 0.0) {
            return 0.0;
        }

        // Verifica se a retenção do IRRF está habilitada para este pagamento
        $applyIrrf = (bool)($this->currentPayrollData['withhold_irrf'] ?? true);
        if (!$applyIrrf) {
            return 0.0; // IRRF dispensado por contrato ou isenção legal
        }

        // Usa alíquota informada ou a padrão de 1,5%
        $rate = (float)($this->currentPayrollData['irrf_rate'] ?? self::STANDARD_IRRF_WITHHOLDING_RATE);

        return round($grossSalary * $rate, 2);
    }

    /**
     * PASSO PRIMITIVO: Descontos de benefícios para PJ.
     * Geralmente zero; pode haver exceção se informado diretamente.
     */
    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float
    {
        return (float)($this->currentPayrollData['benefits_discount'] ?? 0.0);
    }

    /**
     * Calcula o CSRF (PIS + COFINS + CSLL) retido na fonte.
     * Alíquota padrão: 4,65% — aplica-se a notas acima do teto legal.
     */
    public function calculateCsrf(float $grossSalary): float
    {
        // Verifica se a retenção CSRF está habilitada
        $applyCsrf = (bool)($this->currentPayrollData['withhold_csrf'] ?? true);
        if (!$applyCsrf || $grossSalary <= 0.0) {
            return 0.0; // CSRF dispensado ou valor zerado
        }

        // Usa alíquota informada ou a padrão de 4,65%
        $rate = (float)($this->currentPayrollData['csrf_rate'] ?? self::STANDARD_CSRF_WITHHOLDING_RATE);

        return round($grossSalary * $rate, 2);
    }

    /**
     * PASSO PADRÃO SOBRESCRITO: Calcula o líquido PJ com CSRF adicional.
     * Fórmula PJ: Bruto - IRRF (1,5%) - CSRF (4,65%) - Outras Deduções
     */
    protected function calculateNetSalary(
        float $grossSalary,
        float $inss,       // Sempre 0,0 para PJ
        float $irrf,
        float $benefitsDiscount,
        array $otherDeductions = []
    ): float {
        // Calcula e armazena a CSRF para incluir no holerite
        $this->lastCsrfDeduction = $this->calculateCsrf($grossSalary);

        $totalOther = array_sum(array_map('floatval', $otherDeductions));

        // Líquido PJ = Bruto - IRRF - CSRF - Outras Deduções (sem INSS)
        $net = $grossSalary - $inss - $irrf - $benefitsDiscount - $totalOther - $this->lastCsrfDeduction;

        return round(max(0.0, $net), 2);
    }

    /**
     * PASSO PADRÃO SOBRESCRITO: Adiciona detalhes fiscais PJ ao holerite.
     * Inclui CSRF e total de retenções no breakdown do holerite.
     */
    protected function generatePayslip(Employee $employee, array $breakdown): array
    {
        // Adiciona informações fiscais específicas do PJ ao holerite
        $breakdown['csrf_deduction']    = $this->lastCsrfDeduction; // PIS+COFINS+CSLL retidos
        $breakdown['total_withholdings'] = round(
            $breakdown['irrf_deduction'] + $this->lastCsrfDeduction,
            2
        ); // Total de impostos retidos na fonte

        // Chama o holerite base com as informações enriquecidas
        return parent::generatePayslip($employee, $breakdown);
    }

    /**
     * PASSO PRIMITIVO: Retorna o identificador do tipo de contrato para o holerite.
     */
    protected function getContractTypeName(): string
    {
        return 'PJ'; // Pessoa Jurídica
    }
}
