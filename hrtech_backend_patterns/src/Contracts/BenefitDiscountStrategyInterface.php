<?php

declare(strict_types=1);

namespace HrTech\Contracts;

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Entities\Benefit;

/**
 * Interface BenefitDiscountStrategyInterface
 *
 * Define o contrato para estratégias de cálculo de desconto e coparticipação de benefícios.
 * Suporta limites legais (VT 6%, PAT 20%) e regras de benefícios corporativos.
 */
interface BenefitDiscountStrategyInterface
{
    /**
     * Calcula a contribuição descontada do colaborador para um benefício específico.
     *
     * @param Employee $employee The employee receiving the benefit.
     * @param Benefit $benefit The benefit configuration and values.
     * @return float The discount amount to be deducted from payroll (in BRL).
     */
    public function calculateDiscount(Employee $employee, Benefit $benefit): float;

    /**
     * Retorna o identificador do tipo de benefício ao qual esta estratégia se aplica.
     *
     * @return string Benefit type identifier (e.g. 'TRANSPORTATION', 'HEALTH_PLAN', 'MEAL_VOUCHER').
     */
    public function getBenefitType(): string;
}
