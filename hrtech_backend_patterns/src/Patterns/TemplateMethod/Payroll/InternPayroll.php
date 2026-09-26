<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Payroll;

// ============================================================
// PADRÃO DE PROJETO: TEMPLATE METHOD (Exemplo 1/3 — Subclasse Estagiário)
// ============================================================
// Subclasse concreta que implementa os passos específicos do
// contrato de Estágio (Lei 11.788/2008 — Lei do Estágio).
//
// O que muda em relação ao CLT:
//   - Bruto = Bolsa-auxílio + Auxílio-transporte
//   - INSS = R$ 0,00 (estagiário não tem vínculo empregatício)
//   - IRRF = R$ 0,00 (isento por lei)
//   - Benefícios = geralmente zero (lei proíbe descontos compulsórios)
//   - Holerite tem campos extras: bolsa_auxilio e transport_allowance
// ============================================================

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Exceptions\ValidationException;

/**
 * Classe InternPayroll — TEMPLATE METHOD (subclasse concreta Estagiário)
 *
 * Calcula a folha de estagiários conforme a Lei 11.788/2008.
 * Isenta de INSS e IRRF, sem descontos de benefícios obrigatórios.
 */
class InternPayroll extends PayrollCalculatorTemplate
{
    /**
     * Dados da folha atual (salvo pelo gancho beforeCalculation).
     *
     * @var array<string, mixed>
     */
    private array $currentPayrollData = [];

    /**
     * Gancho pré-cálculo: salva os dados da folha no contexto da instância.
     */
    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        $this->currentPayrollData = $payrollData;
    }

    /**
     * PASSO PRIMITIVO: Valida se o funcionário está ativo e é contrato de Estágio.
     *
     * @throws ValidationException Se não for INTERN ou estiver inativo
     */
    protected function validateEmployee(Employee $employee): void
    {
        if (!$employee->isActive()) {
            throw ValidationException::forField('is_active', 'Não é possível processar folha de estagiário inativo.');
        }

        // InternPayroll só aceita contratos do tipo INTERN
        if ($employee->getEmploymentType() !== EmploymentType::INTERN) {
            throw ValidationException::forField(
                'employment_type',
                sprintf(
                    "InternPayroll requer contrato INTERN, mas recebeu '%s'.",
                    $employee->getEmploymentType()->value
                )
            );
        }
    }

    /**
     * PASSO PRIMITIVO: Calcula o valor bruto do estagiário.
     * Composição: Bolsa-auxílio + Auxílio-transporte (se houver).
     */
    protected function calculateGrossSalary(Employee $employee, array $payrollData): float
    {
        // Bolsa-auxílio mensal (nomenclatura aceita: grant_stipend, bolsa_auxilio, gross_salary)
        $stipend = (float)($payrollData['grant_stipend']
            ?? $payrollData['bolsa_auxilio']
            ?? $payrollData['gross_salary']
            ?? $employee->getBaseSalary()->getAmount());

        // Auxílio-transporte (não é desconto — é adicionado ao bruto do estagiário)
        $transportAllowance = (float)($payrollData['transport_allowance']
            ?? $payrollData['auxilio_transporte']
            ?? 0.0);

        $gross = $stipend + $transportAllowance;

        return round(max(0.0, $gross), 2);
    }

    /**
     * PASSO PRIMITIVO: INSS = R$ 0,00 para estagiários.
     * Fundamento legal: Art. 12 da Lei 11.788/2008 (sem vínculo empregatício).
     */
    protected function calculateInss(float $grossSalary): float
    {
        return 0.0; // Estagiário é isento de INSS por lei
    }

    /**
     * PASSO PRIMITIVO: IRRF = R$ 0,00 para estagiários.
     * Fundamento legal: isenção prevista na Lei do Estágio (Lei 11.788/2008).
     */
    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float
    {
        return 0.0; // Estagiário é isento de IRRF por lei
    }

    /**
     * PASSO PRIMITIVO: Descontos de benefícios para estagiário.
     * O auxílio-transporte não pode ser descontado (Art. 12 da Lei 11.788/2008).
     * Retorna apenas desconto explicitamente informado (casos excepcionais).
     */
    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float
    {
        return (float)($this->currentPayrollData['benefits_discount'] ?? 0.0);
    }

    /**
     * PASSO PADRÃO SOBRESCRITO: Adiciona detalhes do estágio ao holerite.
     * Inclui bolsa-auxílio, auxílio-transporte e o embasamento legal.
     */
    protected function generatePayslip(Employee $employee, array $breakdown): array
    {
        // Adiciona detalhamento específico do estágio ao holerite
        $breakdown['bolsa_auxilio'] = round(
            (float)($this->currentPayrollData['grant_stipend']
                ?? $this->currentPayrollData['bolsa_auxilio']
                ?? $employee->getBaseSalary()->getAmount()),
            2
        );
        $breakdown['transport_allowance'] = round(
            (float)($this->currentPayrollData['transport_allowance']
                ?? $this->currentPayrollData['auxilio_transporte']
                ?? 0.0),
            2
        );
        // Referência legal para auditoria e compliance
        $breakdown['legal_framework'] = 'Lei 11.788/2008 (Lei do Estágio)';

        // Chama o holerite base com as informações enriquecidas
        return parent::generatePayslip($employee, $breakdown);
    }

    /**
     * PASSO PRIMITIVO: Retorna o identificador do tipo de contrato para o holerite.
     */
    protected function getContractTypeName(): string
    {
        return 'INTERN'; // Contrato de Estágio (Lei 11.788/2008)
    }
}
