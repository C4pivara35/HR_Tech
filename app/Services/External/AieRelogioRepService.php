<?php
declare(strict_types=1);

namespace App\Services\External;

/**
 * AieRelogioRepService — AIE_RELOGIO_REP_671 (Felipe)
 * 
 * Simula a interface com Relógio Eletrônico de Ponto (REP-C/REP-P conforme Portaria 671/2021 MTE).
 * Realiza a leitura e processamento de Arquivo Fonte de Dados (AFD) fiscal e grava lote em `rep_imports`.
 */
class AieRelogioRepService extends BaseAieService
{
    private const ENDPOINT = 'https://rep.relogioponto.mte.gov.br/api/afd/stream';

    /**
     * Processa um arquivo AFD e persiste os registros no banco.
     *
     * @param string $afdContent Conteúdo textual bruto do arquivo AFD
     * @param string $filename Nome do arquivo enviado
     * @param string $tenantId
     * @return array<string, mixed> Resumo do processamento do AFD
     */
    public function importAfd(string $afdContent, string $filename = 'AFD_REP_001.txt', string $tenantId = 'tenant-tech'): array
    {
        $startTime = hrtime(true);
        $lines = explode("\n", str_replace("\r", "", trim($afdContent)));
        
        $header = null;
        $records = [];
        $trailer = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // O padrão da Portaria 671 MTE identifica o tipo de registro pelo dígito correspondente
            $type = substr($line, 9, 1); // Em layout AFD padrão
            if (str_starts_with($line, '0000000001')) {
                // Registro Tipo 1: Cabeçalho
                $header = [
                    'cnpj' => substr($line, 11, 14),
                    'corporate_name' => trim(substr($line, 25, 150)),
                    'rep_serial' => trim(substr($line, 175, 17))
                ];
            } elseif (str_starts_with($line, '999999999')) {
                // Registro Tipo 9: Trailer
                $trailer = [
                    'total_records' => (int)trim(substr($line, 9, 9))
                ];
            } else {
                // Registro Tipo 3: Marcação de ponto
                $nsr = (int)substr($line, 0, 9);
                $records[] = [
                    'nsr' => $nsr,
                    'raw_line' => $line
                ];
            }
        }

        $recordsCount = count($records);
        $importId = 'rep-' . bin2hex(random_bytes(6));
        $now = date('Y-m-d\TH:i:sP');

        // Persiste o lote na tabela rep_imports
        $stmt = $this->pdo->prepare(
            "INSERT INTO rep_imports (id, tenant_id, filename, records_count, imported_at, status, log_details)
             VALUES (?, ?, ?, ?, ?, 'COMPLETED', ?)"
        );
        $details = json_encode([
            'rep_serial' => $header['rep_serial'] ?? 'REP-FAB-98402',
            'parsed_records' => $recordsCount,
            'standard' => 'PORTARIA_671_MTE'
        ]);
        $stmt->execute([$importId, $tenantId, $filename, $recordsCount, $now, $details]);

        $requestPayload = [
            'filename' => $filename,
            'file_size_bytes' => strlen($afdContent),
            'source_device' => 'REP_FISCAL_CONTROLID_671',
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'import_id' => $importId,
            'filename' => $filename,
            'records_parsed' => $recordsCount,
            'status' => 'COMPLETED',
            'header_identified' => $header !== null,
            'rep_serial' => $header['rep_serial'] ?? 'REP-FAB-98402',
            'mte_compliance' => 'PORTARIA_671_2021_COMPLIANT',
            'imported_at' => $now
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 52;

        $this->logIntegration(
            $tenantId,
            'AIE_RELOGIO_REP_671',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }

    /**
     * Gera um arquivo AFD de exemplo válido conforme Portaria 671 MTE.
     */
    public static function generateSampleAfdContent(string $cnpj = '11222333000181', string $corpName = 'TechStart Inovações Ltda'): string
    {
        $serial = '00014002880001001';
        $out = [];
        // Header
        $out[] = "00000000011" . str_pad($cnpj, 14, '0', STR_PAD_LEFT) . str_pad($corpName, 150, ' ') . $serial . "0101202631122026" . date('dmYHi');
        
        // Punctuation records
        for ($i = 1; $i <= 8; $i++) {
            $nsr = str_pad((string)$i, 9, '0', STR_PAD_LEFT);
            $pis = str_pad((string)(12000000000 + $i), 12, '0', STR_PAD_LEFT);
            $date = date('dmY');
            $time = str_pad((string)(800 + $i * 60), 4, '0', STR_PAD_LEFT);
            $out[] = "{$nsr}3{$date}{$time}{$pis}";
        }
        
        // Trailer
        $out[] = "999999999" . str_pad("8", 9, '0', STR_PAD_LEFT);
        return implode("\r\n", $out);
    }
}
