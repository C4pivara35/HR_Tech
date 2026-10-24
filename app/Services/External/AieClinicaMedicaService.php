<?php
declare(strict_types=1);

namespace App\Services\External;

/**
 * AieClinicaMedicaService — AIE_CLINICA_MEDICINA_OCUP (Nicolas)
 * 
 * Simula a integração eletrônica com clínicas médicas ocupacionais credenciadas
 * (sistemas SOC / Telemedicina Ocupacional), permitindo o agendamento de exames
 * clínicos e a recepção digital do Atestado de Saúde Ocupacional (ASO) conforme NR-07.
 */
class AieClinicaMedicaService extends BaseAieService
{
    private const ENDPOINT = 'https://api.soc.com.br/v1/aso/consult';

    /**
     * Agenda e simula a recepção de laudo ASO de clínica ocupacional.
     *
     * @param string $employeeId
     * @param string $examType ADMISSIONAL | PERIODICO | RETORNO | DEMISSIONAL
     * @param bool $isFit Colaborador apto ou inapto
     * @param string $tenantId
     * @return array<string, mixed> Dados do laudo médico ocupacional ASO
     */
    public function scheduleAndIssueAso(
        string $employeeId,
        string $examType = 'PERIODICO',
        bool $isFit = true,
        string $tenantId = 'tenant-ind'
    ): array {
        $startTime = hrtime(true);
        $examDate = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime('+1 year'));
        $asoProtocol = 'SOC-ASO-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));

        $requestPayload = [
            'clinic_network' => 'Clínica Paraná Medicina do Trabalho & SST (CNPJ: 12.345.678/0001-99)',
            'employee_id' => $employeeId,
            'exam_category' => $examType,
            'pcmso_reference' => 'PCMSO_ANUAL_NR07_2026',
            'occupational_risks' => [
                'Físico: Ruído contínuo e intermitente (88 dB(A))',
                'Químico: Poeiras metálicas e fumos de solda',
                'Ergonômico: Levantamento e transporte manual de peso'
            ],
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'status' => 'ISSUED',
            'protocol_number' => $asoProtocol,
            'exam_type' => $examType,
            'exam_date' => $examDate,
            'valid_until' => $validUntil,
            'is_fit' => $isFit,
            'conclusion' => $isFit ? 'APTO para o exercício da função com exposição aos riscos descritos' : 'INAPTO temporário com restrição para áreas industriais ruidosas',
            'physician_name' => 'Dr. Carlos Eduardo Mendonça',
            'physician_crm' => 'CRM-PR 28.941',
            'physician_specialty' => 'Médico do Trabalho (RQE 18492)',
            'complementary_exams' => [
                'Audiometria Tonal Ocupacional' => 'Limiares auditivos estáveis dentro dos limites da normalidade',
                'Espirometria Ocupacional' => 'Padrão ventilatório normal',
                'Acuidade Visual' => 'Apto para operação de máquinas industriais'
            ],
            'nr07_certified' => true,
            'issued_at' => date('Y-m-d H:i:s')
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 86;

        $this->logIntegration(
            $tenantId,
            'AIE_CLINICA_MEDICINA_OCUP',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }
}
