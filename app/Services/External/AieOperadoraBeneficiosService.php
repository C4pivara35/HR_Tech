<?php
declare(strict_types=1);

namespace App\Services\External;

/**
 * AieOperadoraBeneficiosService — AIE_OPERADORA_BENEFICIOS (Valentin)
 * 
 * Simula a integração com operadoras de benefícios flexíveis (Caju, Ticket, Flash)
 * para liberação de créditos em cartões multibenefícios (VR, VA, Mobilidade, Cultura)
 * com protocolo de autorização e consulta de saldo.
 */
class AieOperadoraBeneficiosService extends BaseAieService
{
    private const ENDPOINT = 'https://api.caju.com.br/v2/cards/recharge';

    /**
     * Executa a recarga de saldo do benefício no cartão do colaborador.
     *
     * @param string $employeeId
     * @param string $category MEAL | FOOD | MOBILITY | HEALTH
     * @param int $amountCents Valor em centavos
     * @param string $tenantId
     * @return array<string, mixed> Resposta do gateway da operadora
     */
    public function allocateBenefitCredits(
        string $employeeId,
        string $category = 'MEAL',
        int $amountCents = 85000,
        string $tenantId = 'tenant-tech'
    ): array {
        $startTime = hrtime(true);
        $orderId = 'ORD-' . strtoupper(bin2hex(random_bytes(6)));
        $authCode = 'CAJU-AUTH-' . date('Ym') . '-' . rand(100000, 999999);

        $requestPayload = [
            'order_id' => $orderId,
            'employee_id' => $employeeId,
            'benefit_category' => $category,
            'amount_cents' => $amountCents,
            'amount_reais' => number_format($amountCents / 100, 2, ',', '.'),
            'recharge_type' => 'MONTHLY_PAYROLL_CREDIT',
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'status' => 'APPROVED',
            'order_id' => $orderId,
            'authorization_code' => $authCode,
            'category' => $category,
            'credited_cents' => $amountCents,
            'new_balance_cents' => $amountCents + 12450, // saldo prévio acumulado simulado
            'effective_date' => date('Y-m-d'),
            'network_brand' => 'Elo / Caju Multi',
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 64;

        $this->logIntegration(
            $tenantId,
            'AIE_OPERADORA_BENEFICIOS',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }

    /**
     * Consulta o saldo ativo do colaborador na operadora.
     */
    public function queryCardBalance(string $employeeId, string $tenantId = 'tenant-tech'): array
    {
        return [
            'employee_id' => $employeeId,
            'status' => 'ACTIVE',
            'card_last4' => '9042',
            'meal_cents' => 95000,
            'food_cents' => 60000,
            'mobility_cents' => 22000,
            'total_available_cents' => 177000,
            'last_statement_sync' => date('Y-m-d H:i:s')
        ];
    }
}
