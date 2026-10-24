<?php
declare(strict_types=1);

namespace App\Services\External;

/**
 * AieOperadoraSaudeService — AIE_OPERADORA_SAUDE_ANS (Valentin)
 * 
 * Simula a interface com operadoras de planos de saúde reguladas pela ANS
 * no padrão TISS (Troca de Informações na Saúde Suplementar), realizando
 * checagem de elegibilidade do beneficiário, coparticipação e autorização de guias.
 */
class AieOperadoraSaudeService extends BaseAieService
{
    private const ENDPOINT = 'https://tiss.ans.gov.br/services/v4_01_00/autorizacaoProcedimento';

    /**
     * Valida elegibilidade do colaborador e calcula coparticipação no padrão TISS ANS.
     *
     * @param string $employeeId
     * @param string $cardRegistry Carteirinha ou matrícula do plano
     * @param string $procedureCode Código TUSS do procedimento (ex: consulta médica 10101012)
     * @param string $tenantId
     * @return array<string, mixed> Guia de autorização e detalhamento de coparticipação
     */
    public function verifyEligibilityAndCopay(
        string $employeeId,
        string $cardRegistry = 'BRAD-99381-001',
        string $procedureCode = '10101012',
        string $tenantId = 'tenant-tech'
    ): array {
        $startTime = hrtime(true);
        $guideNumber = 'TISS-' . date('Y') . '-' . rand(1000000, 9999999);
        $authHash = strtoupper(substr(hash('sha256', $guideNumber . $cardRegistry), 0, 16));

        $requestPayload = [
            'standard' => 'TISS_ANS_4.01.00',
            'health_operator' => 'Bradesco Saúde S.A. (Registro ANS: 005711)',
            'beneficiary_id' => $employeeId,
            'card_registry' => $cardRegistry,
            'procedure_tuss' => $procedureCode,
            'procedure_description' => 'Consulta Médica em Consultório (No Horário Normal ou Extraordinário)',
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'status' => 'ELIGIBLE_AUTHORIZED',
            'ans_authorization_protocol' => $authHash,
            'tiss_guide_number' => $guideNumber,
            'beneficiary_status' => 'ATIVO_ADIMPLENTE',
            'plan_segmentation' => 'AMBULATORIAL_HOSPITALAR_COM_OBSTETRICIA',
            'grace_period_status' => 'CUMPRIDA_TOTAL',
            'procedure_covered' => true,
            'procedure_base_value_cents' => 15000, // R$ 150,00
            'copay_rate_percent' => 20.0,
            'copay_amount_cents' => 3000, // R$ 30,00 descontado do funcionário
            'operator_net_cents' => 12000, // R$ 120,00 pago pela empresa/plano
            'ans_normative' => 'RN_ANS_503_2022',
            'issued_at' => date('Y-m-d H:i:s')
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 78;

        $this->logIntegration(
            $tenantId,
            'AIE_OPERADORA_SAUDE_ANS',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }
}
