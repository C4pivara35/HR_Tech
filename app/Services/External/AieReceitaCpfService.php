<?php
declare(strict_types=1);

namespace App\Services\External;

use HrTech\Domain\ValueObjects\Cpf;

/**
 * AieReceitaCpfService — AIE_RECEITA_FEDERAL_CPF (Andryus Zolet)
 * 
 * Simula a consulta e validação cadastral de CPF junto à API da Receita Federal do Brasil / SERPRO.
 * Registra o tráfego de requisição/resposta na tabela `external_integrations`.
 */
class AieReceitaCpfService extends BaseAieService
{
    private const ENDPOINT = 'https://api.receitafederal.gov.br/v1/cpf/validate';

    /**
     * Valida um CPF na base simulada da Receita Federal.
     *
     * @param string $cpf Número do CPF com ou sem máscara
     * @param string $tenantId Identificador do tenant
     * @return array<string, mixed> Dados cadastrais e situação na Receita
     */
    public function validateCpf(string $cpf, string $tenantId = 'tenant-tech'): array
    {
        $startTime = hrtime(true);
        $cleanCpf = preg_replace('/\D/', '', $cpf) ?? '';
        $isValidAlgorithmic = Cpf::isValid($cleanCpf);

        $requestPayload = [
            'consulted_cpf' => $cleanCpf,
            'requester_tenant' => $tenantId,
            'channel' => 'API_REST_SERPRO_V2',
            'timestamp' => date('c')
        ];

        if ($isValidAlgorithmic) {
            $protocol = 'RF-2026-CPF-' . strtoupper(substr(hash('sha256', $cleanCpf . 'SALT_RF'), 0, 12));
            $responsePayload = [
                'status' => 'REGULAR',
                'cpf' => substr($cleanCpf, 0, 3) . '.' . substr($cleanCpf, 3, 3) . '.' . substr($cleanCpf, 6, 3) . '-' . substr($cleanCpf, 9, 2),
                'situation_code' => '00',
                'situation_description' => 'Comprovante de Situação Cadastral Regular no CPF',
                'protocol_number' => $protocol,
                'birth_date_verified' => true,
                'death_indicative' => false,
                'origin_database' => 'RECEITA_FEDERAL_SERPRO_SP',
                'consulted_at' => date('Y-m-d H:i:s')
            ];
            $status = 'SUCCESS';
        } else {
            $responsePayload = [
                'status' => 'IRREGULAR',
                'cpf' => $cleanCpf,
                'situation_code' => '04',
                'situation_description' => 'Dígito verificador incompatível ou registro cancelado na base RFB',
                'protocol_number' => 'RF-ERR-' . strtoupper(substr(hash('sha256', $cleanCpf), 0, 8)),
                'origin_database' => 'RECEITA_FEDERAL_SERPRO_SP',
                'consulted_at' => date('Y-m-d H:i:s')
            ];
            $status = 'WARNING';
        }

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) {
            $durationMs = 38; // latência média simulada
        }

        $this->logIntegration(
            $tenantId,
            'AIE_RECEITA_FEDERAL_CPF',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            $status,
            $durationMs
        );

        return $responsePayload;
    }
}
