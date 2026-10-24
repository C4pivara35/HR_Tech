<?php
declare(strict_types=1);

namespace App\Services\External;

/**
 * AieMteCaService — AIE_CATALOGO_CAEPI_MTE (Nicolas)
 * 
 * Simula a consulta à base oficial de Certificados de Aprovação (CAEPI)
 * do Ministério do Trabalho e Emprego (MTE), atestando a validade jurídica
 * e as especificações técnicas de proteção dos EPIs exigidos pela NR-06.
 */
class AieMteCaService extends BaseAieService
{
    private const ENDPOINT = 'https://caepi.mte.gov.br/api/v1/certificados/consulta';

    /**
     * Base de conhecimento simulada de CAs do MTE.
     */
    private const CA_CATALOG = [
        '31469' => [
            'equipment' => 'Capacete de Segurança Tipo Aba Frontal Classe B com Jugular',
            'manufacturer' => 'MSA do Brasil Equipamentos e Instrumentos de Segurança Ltda',
            'valid_until' => '2028-11-20',
            'status' => 'VALIDO',
            'protection_level' => 'Impacto de objetos e choques elétricos até 20.000V (NBR 8221)'
        ],
        '28511' => [
            'equipment' => 'Calçado de Segurança Tipo Botina com Bico de Composite e Palmilha Antiperfuro',
            'manufacturer' => 'Marluvas Calçados de Segurança Ltda',
            'valid_until' => '2027-06-15',
            'status' => 'VALIDO',
            'protection_level' => 'Impacto 200J e compressão 15kN (ISO 20345)'
        ],
        '15624' => [
            'equipment' => 'Protetor Auditivo de Inserção Pré-moldado de Silicone (Plug)',
            'manufacturer' => '3M do Brasil Ltda',
            'valid_until' => '2024-03-10', // VENCIDO para teste de barreira
            'status' => 'VENCIDO',
            'protection_level' => 'Atenuação de Ruído NRRsf 16 dB (ANSI S12.6 - Método B)'
        ],
        '11268' => [
            'equipment' => 'Óculos de Segurança Modelo Spectra com Lente Antirrisco e Antiembaçante',
            'manufacturer' => 'Danny Equipamentos de Proteção Ltda',
            'valid_until' => '2029-01-30',
            'status' => 'VALIDO',
            'protection_level' => 'Proteção contra radiação UVA/UVB e partículas volantes multidirecionais'
        ]
    ];

    /**
     * Consulta a situação de um CA junto à base oficial do MTE.
     *
     * @param string $caNumber Número do CA do EPI
     * @param string $tenantId
     * @return array<string, mixed> Ficha técnica e situação legal do CA
     */
    public function verifyCertificateOfApproval(string $caNumber, string $tenantId = 'tenant-ind'): array
    {
        $startTime = hrtime(true);
        $cleanCa = preg_replace('/\D/', '', $caNumber) ?? '';

        $requestPayload = [
            'ca_number' => $cleanCa,
            'queried_at' => date('c'),
            'source_registry' => 'MTE_CAEPI_PUBLIC_PORTAL',
            'tenant_id' => $tenantId
        ];

        if (isset(self::CA_CATALOG[$cleanCa])) {
            $item = self::CA_CATALOG[$cleanCa];
            $isExpired = strtotime($item['valid_until']) < time();
            $status = $isExpired ? 'VENCIDO' : $item['status'];

            $responsePayload = [
                'ca_number' => $cleanCa,
                'status' => $status,
                'is_valid_for_delivery' => !$isExpired,
                'equipment_name' => $item['equipment'],
                'manufacturer_name' => $item['manufacturer'],
                'expiration_date' => $item['valid_until'],
                'protection_level' => $item['protection_level'],
                'nr06_compliance' => !$isExpired ? 'CONFORME_NR06' : 'IMPEDIMENTO_LEGAL_CA_VENCIDO',
                'query_protocol' => 'MTE-CA-' . strtoupper(substr(hash('sha256', $cleanCa . $item['valid_until']), 0, 12))
            ];
            $logStatus = !$isExpired ? 'SUCCESS' : 'WARNING';
        } else {
            // CA genérico válido simulado para números não cadastrados no catálogo mock
            $futureDate = date('Y-m-d', strtotime('+2 years'));
            $responsePayload = [
                'ca_number' => $cleanCa,
                'status' => 'VALIDO',
                'is_valid_for_delivery' => true,
                'equipment_name' => "Equipamento de Proteção Individual Regulamentado CA $cleanCa",
                'manufacturer_name' => 'Fabricante Certificado Homologado MTE',
                'expiration_date' => $futureDate,
                'protection_level' => 'Atestado de conformidade técnica emitido por laboratório acreditado pelo Inmetro',
                'nr06_compliance' => 'CONFORME_NR06',
                'query_protocol' => 'MTE-CA-' . strtoupper(substr(hash('sha256', $cleanCa), 0, 12))
            ];
            $logStatus = 'SUCCESS';
        }

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 41;

        $this->logIntegration(
            $tenantId,
            'AIE_CATALOGO_CAEPI_MTE',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            $logStatus,
            $durationMs
        );

        return $responsePayload;
    }
}
