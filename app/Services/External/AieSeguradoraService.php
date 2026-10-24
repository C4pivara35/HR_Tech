<?php
declare(strict_types=1);

namespace App\Services\External;

/**
 * AieSeguradoraService — AIE_FINCORP_SEGURADORA (Fernando Lopes Duarte)
 * 
 * Simula a subscrição atuarial de riscos junto ao Gateway da Seguradora FinCorp
 * (regulada pela SUSEP), calculando o score de risco, tarifação de prêmios mensais
 * e emissão formal de apólices coletivas de seguro de vida e invalidez.
 */
class AieSeguradoraService extends BaseAieService
{
    private const ENDPOINT = 'https://underwriting.fincorp.com.br/api/v3/policies/quote';

    /**
     * Realiza a cotação e subscrição atuarial da apólice coletiva junto à Seguradora FinCorp.
     *
     * @param array<string, mixed> $employee Colaborador segurado
     * @param int $capitalCents Capital segurado total em centavos (ex: R$ 500.000,00 = 50000000)
     * @param string $coverageType LIFE_GROUP | D_AND_O | ACCIDENTS
     * @param string $tenantId
     * @return array<string, mixed> Parecer atuarial e proposta de seguro
     */
    public function underwriteLifePolicy(
        array $employee,
        int $capitalCents = 50000000,
        string $coverageType = 'LIFE_GROUP',
        string $tenantId = 'tenant-fin'
    ): array {
        $startTime = hrtime(true);
        $empName = (string)($employee['full_name'] ?? 'Colaborador Segurado');
        $cpf = (string)($employee['cpf'] ?? '000.000.000-00');
        $birthDate = (string)($employee['birth_date'] ?? '1990-01-01');

        // Cálculo da idade para tarifação atuarial
        $age = date_diff(date_create($birthDate), date_create('today'))->y;
        
        // Alíquota atuarial progressiva por faixa etária (tabela SUSEP)
        $baseRate = 0.00025; // 0,025% base
        if ($age >= 50) {
            $baseRate = 0.00055;
        } elseif ($age >= 40) {
            $baseRate = 0.00038;
        } elseif ($age >= 30) {
            $baseRate = 0.00028;
        }

        $monthlyPremiumCents = (int)round($capitalCents * $baseRate);
        $proposalId = 'FIN-PROP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));
        $policyNumber = 'FINCORP-2026-' . rand(10000, 99999);

        $requestPayload = [
            'insurer' => 'FinCorp Seguradora S.A. (Código SUSEP: 05412)',
            'proposal_type' => $coverageType,
            'insured_name' => $empName,
            'insured_cpf' => $cpf,
            'insured_age' => $age,
            'insured_capital_cents' => $capitalCents,
            'capital_reais' => number_format($capitalCents / 100, 2, ',', '.'),
            'underwriting_rules' => 'SUSEP_CIRCULAR_600_2020',
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'status' => 'APPROVED_UNDERWRITING',
            'proposal_number' => $proposalId,
            'policy_number' => $policyNumber,
            'actuarial_risk_score' => round(1.0 + ($age / 100), 2),
            'monthly_premium_cents' => $monthlyPremiumCents,
            'monthly_premium_reais' => number_format($monthlyPremiumCents / 100, 2, ',', '.'),
            'coverages' => [
                'Morte Básica (Qualquer Causa)' => '100% do Capital Segurado',
                'Invalidez Permanente Total/Parcial por Acidente (IPA)' => 'Até 100% do Capital Segurado',
                'Assistência Funeral Individual' => 'R$ 7.000,00'
            ],
            'susep_process_code' => 'SUSEP-15414.901842/2026-11',
            'validity_start' => date('Y-m-d'),
            'validity_end' => date('Y-m-d', strtotime('+1 year')),
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 92;

        $this->logIntegration(
            $tenantId,
            'AIE_FINCORP_SEGURADORA',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }
}
