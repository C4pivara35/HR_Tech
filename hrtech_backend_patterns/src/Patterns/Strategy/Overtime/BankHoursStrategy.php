<?php

declare(strict_types=1);

namespace HrTech\Patterns\Strategy\Overtime;

use HrTech\Contracts\OvertimeStrategyInterface;

/**
 * Class BankHoursStrategy
 *
 * Implements compensatory time bank (Banco de Horas) regime under Article 59 §2 of CLT.
 * Overtime hours yield zero immediate monetary payroll payout (monetary cost = 0.0)
 * and are converted into credited compensatory bank minutes using a credit factor (e.g. 1.0 or 1.5).
 */
class BankHoursStrategy implements OvertimeStrategyInterface
{
    /**
     * @param float $creditFactor Ratio applied when crediting overtime to the time bank (default 1.0 = 1:1, 1.5 = 1:1.5).
     */
    public function __construct(private readonly float $creditFactor = 1.0)
    {
    }

    /**
     * Monetary overtime payout is strictly 0.0 under the time bank compensation agreement.
     */
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float
    {
        return 0.0;
    }

    /**
     * Calculates the compensatory minutes to be credited into the employee's time bank balance.
     *
     * @param float $overtimeHours
     * @return int Credited balance in minutes
     */
    public function calculateBankMinutes(float $overtimeHours): int
    {
        if ($overtimeHours <= 0.0) {
            return 0;
        }

        return (int)round($overtimeHours * 60 * max(0.0, $this->creditFactor));
    }

    public function getCreditFactor(): float
    {
        return $this->creditFactor;
    }

    /**
     * Returns legal and operational description of the strategy.
     */
    public function getDescription(): string
    {
        return sprintf(
            'Compensatory time bank (Banco de Horas) pursuant to Art. 59 §2 of CLT (monetary cost: R$ 0.00; credit factor: %.2fx).',
            $this->creditFactor
        );
    }
}
