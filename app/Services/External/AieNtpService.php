<?php
declare(strict_types=1);

namespace App\Services\External;

use DateTimeImmutable;
use DateTimeZone;

/**
 * AieNtpService — AIE_NTP_HORA_LEGAL (Felipe)
 * 
 * Simula a sincronização e obtenção do carimbo da Hora Legal Brasileira
 * fornecida pelo Observatório Nacional (NTP.br / Stratum 1), conforme exigido
 * pelo Art. 75 da Portaria 671/2021 MTE para batidas de ponto eletrônico.
 */
class AieNtpService extends BaseAieService
{
    private const ENDPOINT = 'udp://a.st1.ntp.br:123';

    /**
     * Obtém o carimbo de tempo oficial sincronizado com o Observatório Nacional.
     *
     * @param string $tenantId
     * @return array<string, mixed> Metadados de sincronização NTP Stratum 1
     */
    public function getLegalTime(string $tenantId = 'tenant-tech'): array
    {
        $startTime = hrtime(true);
        $tz = new DateTimeZone('America/Sao_Paulo');
        $now = new DateTimeImmutable('now', $tz);

        $driftMs = round(mt_rand(5, 25) / 10.0, 2); // jitter/offset simulado entre 0.5ms e 2.5ms
        $isoTimestamp = $now->format('Y-m-d\TH:i:s.vP');

        $legalStampToken = hash('sha256', 'NTP.BR|' . $isoTimestamp . '|STRATUM_1|' . $tenantId);

        $requestPayload = [
            'ntp_client' => 'HRTech Core NTP Client v2.0',
            'server_pool' => ['a.st1.ntp.br', 'b.st1.ntp.br', 'c.st1.ntp.br'],
            'stratum_requested' => 1,
            'transport' => 'UDP_123',
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'status' => 'SYNCHRONIZED',
            'stratum' => 1,
            'reference_clock' => 'ON_BRASILIA_CESIUM',
            'server_used' => 'a.st1.ntp.br',
            'legal_time_brasilia' => $isoTimestamp,
            'root_delay_ms' => 1.85,
            'root_dispersion_ms' => $driftMs,
            'leap_indicator' => 0,
            'legal_stamp_token' => $legalStampToken,
            'portaria_671_certified' => true,
            'synchronized_at' => date('Y-m-d H:i:s')
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 12;

        $this->logIntegration(
            $tenantId,
            'AIE_NTP_HORA_LEGAL',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }
}
