<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

// ============================================================
// PADRÃO DE PROJETO: TEMPLATE METHOD (Exemplo 1 de 3)
// ============================================================
// Intenção: Definir o ESQUELETO de um algoritmo na classe base,
// delegando apenas os passos específicos às subclasses, sem
// permitir que elas alterem a estrutura geral do algoritmo.
//
// Como identificar o Template Method neste arquivo:
//   1. Método "calculatePayroll()" é FINAL — nenhuma subclasse
//      pode sobrescrever a sequência de passos.
//   2. Métodos "abstract protected" são os PASSOS PRIMITIVOS —
//      cada subclasse (CLT, PJ, Estagiário) implementa à sua forma.
//   3. Métodos "protected" com implementação são PASSOS PADRÃO —
//      a subclasse pode ou não sobrescrever.
//   4. beforeCalculation() e afterCalculation() são GANCHOS (hooks)
//      opcionais que as subclasses podem usar sem obrigatoriedade.
// ============================================================

use HrTech\Contracts\PayrollCalculatorInterface;
use HrTech\Domain\Entities\Employee;
use HrTech\Exceptions\ValidationException;

/**
 * Classe abstrata PayrollCalculatorTemplate — PADRÃO TEMPLATE METHOD
 *
 * Define o esqueleto imutável do algoritmo de cálculo de folha de pagamento.
 * As subclasses (CltPayroll, PjPayroll, InternPayroll) implementam os passos
 * específicos de cada tipo de contrato, sem alterar a ordem de execução.
 */
abstract class PayrollCalculatorTemplate implements PayrollCalculatorInterface
{
    // -------------------------------------------------------
    // MÉTODO TEMPLATE — núcleo do padrão.
    //
    // "final" garante que NENHUMA subclasse possa alterar
    // a sequência dos passos abaixo. O algoritmo é fixo;
    // apenas os detalhes de cada passo variam por contrato.
    // -------------------------------------------------------
    /**
     * MÉTODO TEMPLATE: Define a sequência imutável do cálculo de folha.
     *
     * Ordem dos passos (não pode ser alterada pelas subclasses):
     *   1. Hook pré-cálculo (opcional)
     *   2. Validação do funcionário
     *   3. Cálculo do salário bruto
     *   4. Cálculo do INSS
     *   5. Cálculo do IRRF
     *   6. Aplicação de descontos de benefícios
     *   7. Cálculo do salário líquido
     *   8. Geração do holerite
     *   9. Hook pós-cálculo (opcional)
     *
     * @param Employee $employee Funcionário a ter a folha calculada
     * @param array<string, mixed> $payrollData Dados adicionais da folha
     * @return array<string, mixed> Holerite completo com todos os valores
     */
    final public function calculatePayroll(Employee $employee, array $payrollData): array
    {
        // PASSO 1: Hook opcional antes do cálculo (subclasses podem usar para salvar contexto)
        $this->beforeCalculation($employee, $payrollData);

        // PASSO 2: Validação do funcionário (implementação varia por tipo de contrato)
        $this->validateEmployee($employee);

        // PASSO 3: Cálculo do salário bruto (varia por contrato: CLT, PJ, Estagiário)
        $grossSalary = $this->calculateGrossSalary($employee, $payrollData);

        // PASSO 4: Cálculo do INSS (CLT=progressivo, PJ=zero, Estagiário=zero)
        $inssDeduction = $this->calculateInss($grossSalary);

        // PASSO 5: Cálculo do IRRF com dependentes (varia por tipo de contrato)
        $dependents    = (int)($payrollData['dependents'] ?? 0);
        $irrfDeduction = $this->calculateIrrf($grossSalary, $inssDeduction, $dependents);

        // PASSO 6: Descontos de benefícios (plano de saúde, VT, VR etc.)
        $benefitsDiscount = $this->applyBenefitsDiscounts($employee, $grossSalary);
        $otherDeductions  = (array)($payrollData['other_deductions'] ?? []);

        // PASSO 7: Cálculo do salário líquido final
        $netSalary = $this->calculateNetSalary(
            $grossSalary,
            $inssDeduction,
            $irrfDeduction,
            $benefitsDiscount,
            $otherDeductions
        );

        // Monta o detalhamento completo dos valores calculados
        $breakdown = [
            'gross_salary'     => $grossSalary,
            'inss_deduction'   => $inssDeduction,
            'irrf_deduction'   => $irrfDeduction,
            'benefits_discount'=> $benefitsDiscount,
            'other_deductions' => $otherDeductions,
            'net_salary'       => $netSalary,
            'calculation_date' => date('Y-m-d H:i:s'),
        ];

        // PASSO 8: Geração do holerite estruturado
        $payslip = $this->generatePayslip($employee, $breakdown);

        // PASSO 9: Hook opcional após o cálculo (subclasses podem usar para auditoria)
        $this->afterCalculation($employee, $payslip);

        return $payslip;
    }

    // -------------------------------------------------------
    // GANCHO (Hook) PRÉ-CÁLCULO — implementação padrão vazia.
    // Subclasses PODEM sobrescrever se precisarem preparar algo.
    // -------------------------------------------------------
    /**
     * Gancho opcional executado antes do início do cálculo.
     * Padrão: não faz nada. Subclasses podem sobrescrever.
     *
     * @param Employee $employee
     * @param array<string, mixed> $payrollData
     */
    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        // Implementação padrão: sem ação (gancho vazio)
    }

    // -------------------------------------------------------
    // PASSOS PRIMITIVOS (abstract) — obrigatórios nas subclasses.
    // Cada subclasse implementa conforme as regras do contrato.
    // -------------------------------------------------------

    /**
     * PASSO PRIMITIVO: Valida elegibilidade e tipo de contrato do funcionário.
     * Cada subclasse verifica o tipo correto (CLT, PJ, INTERN).
     *
     * @param Employee $employee
     * @throws ValidationException Se o funcionário não for elegível
     */
    abstract protected function validateEmployee(Employee $employee): void;

    /**
     * PASSO PRIMITIVO: Calcula o salário bruto total.
     * CLT: salário base + horas extras + bônus.
     * PJ: valor da nota fiscal.
     * Estagiário: bolsa-auxílio + auxílio transporte.
     *
     * @param Employee $employee
     * @param array<string, mixed> $payrollData
     * @return float Salário bruto calculado
     */
    abstract protected function calculateGrossSalary(Employee $employee, array $payrollData): float;

    /**
     * PASSO PRIMITIVO: Calcula a dedução do INSS (Previdência Social).
     * CLT: tabela progressiva 2024-2026.
     * PJ e Estagiário: retornam 0.0.
     *
     * @param float $grossSalary Salário bruto
     * @return float Valor do INSS a descontar
     */
    abstract protected function calculateInss(float $grossSalary): float;

    /**
     * PASSO PRIMITIVO: Calcula a retenção do IRRF (Imposto de Renda).
     * CLT: tabela progressiva com dedução por dependentes.
     * PJ: alíquota fixa de 1,5%.
     * Estagiário: retorna 0.0.
     *
     * @param float $grossSalary Salário bruto
     * @param float $inssDeduction Dedução do INSS já calculada
     * @param int $dependents Número de dependentes do funcionário
     * @return float Valor do IRRF a descontar
     */
    abstract protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float;

    /**
     * PASSO PRIMITIVO: Calcula os descontos de benefícios.
     * CLT: plano de saúde, VT, VR etc. (via Strategy de benefícios).
     * PJ e Estagiário: geralmente retornam 0.0.
     *
     * @param Employee $employee
     * @param float $grossSalary Salário bruto
     * @return float Total de descontos de benefícios
     */
    abstract protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float;

    // -------------------------------------------------------
    // PASSOS PADRÃO — implementados na base, subclasses podem sobrescrever.
    // -------------------------------------------------------

    /**
     * PASSO PADRÃO: Calcula o salário líquido final.
     * Fórmula: Bruto - INSS - IRRF - Benefícios - Outras Deduções
     * Subclasses (ex: PJ) podem sobrescrever para incluir CSRF.
     *
     * @param float $grossSalary Salário bruto
     * @param float $inss Dedução do INSS
     * @param float $irrf Dedução do IRRF
     * @param float $benefitsDiscount Total de descontos de benefícios
     * @param array<int|string, mixed> $otherDeductions Outras deduções variáveis
     * @return float Salário líquido (mínimo de R$ 0,00)
     */
    protected function calculateNetSalary(
        float $grossSalary,
        float $inss,
        float $irrf,
        float $benefitsDiscount,
        array $otherDeductions = []
    ): float {
        // Soma todas as outras deduções variáveis
        $totalOther = array_sum(array_map('floatval', $otherDeductions));

        // Salário líquido não pode ser negativo (mínimo zero)
        $net = $grossSalary - $inss - $irrf - $benefitsDiscount - $totalOther;
        return round(max(0.0, $net), 2);
    }

    /**
     * PASSO PADRÃO: Monta o holerite estruturado com dados do funcionário e breakdown.
     * Subclasses (ex: PJ) podem sobrescrever para adicionar campos específicos.
     *
     * @param Employee $employee
     * @param array<string, mixed> $breakdown Detalhamento de todos os valores
     * @return array<string, mixed> Holerite completo
     */
    protected function generatePayslip(Employee $employee, array $breakdown): array
    {
        // Obtém ID e nome do funcionário de forma compatível com diferentes implementações
        $id   = method_exists($employee, 'getId')  ? $employee->getId()  : (string)($employee->id   ?? '');
        $name = method_exists($employee, 'getName') ? $employee->getName() : (string)($employee->name ?? '');

        return [
            'employee_id'   => $id,
            'employee_name' => $name,
            'contract_type' => $this->getContractTypeName(), // CLT, PJ ou INTERN
            'breakdown'     => $breakdown,
        ];
    }

    /**
     * PASSO PRIMITIVO: Retorna o nome legível do tipo de contrato para o holerite.
     * Cada subclasse retorna o nome adequado: 'CLT', 'PJ' ou 'INTERN'.
     *
     * @return string Nome do tipo de contrato
     */
    abstract protected function getContractTypeName(): string;

    // -------------------------------------------------------
    // GANCHO (Hook) PÓS-CÁLCULO — implementação padrão vazia.
    // Subclasses PODEM sobrescrever para registrar auditoria.
    // -------------------------------------------------------
    /**
     * Gancho opcional executado após a geração do holerite.
     * Padrão: não faz nada. Subclasses podem sobrescrever para auditoria.
     *
     * @param Employee $employee
     * @param array<string, mixed> $payslip Holerite gerado
     */
    protected function afterCalculation(Employee $employee, array $payslip): void
    {
        // Implementação padrão: sem ação (gancho vazio — use para integrar com AuditLogger)
    }
}
