<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface OvertimeStrategyInterface
 *
 * Define o contrato para estratégias de cálculo de compensação de horas extras.
 * Suporta adicionais da CLT (50%, 100%) e compensação em banco de horas.
 */
interface OvertimeStrategyInterface
{
    /**
     * Calcula o valor monetário total da remuneração de horas extras.
     *
     * @param float $hourlyRate The regular hourly wage of the employee.
     * @param float $overtimeHours Quantidade de horas extras trabalhadas.
     * @return float The calculated monetary overtime compensation (in BRL).
     */
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float;

    /**
     * Retorna a descrição legal e operacional legível desta estratégia.
     *
     * @return string Description of the calculation rule and legal basis.
     */
    public function getDescription(): string;
}
