<?php
declare(strict_types=1);

namespace App\Services\External;

/**
 * AiePixBacenService — AIE_PIX_BACEN_GATEWAY (Fernando Lopes Duarte)
 * 
 * Simula o Gateway de Pagamentos Instantâneos do Banco Central do Brasil (BACEN SPI / DICT),
 * gerando QR Code dinâmico no padrão EMV (Pix Copia-e-Cola) e simulando liquidação instantânea
 * em tempo real com gravação em `pix_transactions` e `external_integrations`.
 */
class AiePixBacenService extends BaseAieService
{
    private const ENDPOINT = 'https://spi.bcb.gov.br/api/v2/cobv';

    /**
     * Emite uma cobrança PIX dinâmica para liquidação de apólice ou benefícios.
     *
     * @param string|null $policyId ID da apólice vinculada
     * @param int $amountCents Valor da transação em centavos
     * @param string $description Descrição do pagamento
     * @param string $tenantId
     * @return array<string, mixed> Payload PIX Copia-e-Cola, QR Code e TxID
     */
    public function generateDynamicPix(
        ?string $policyId = null,
        int $amountCents = 14000,
        string $description = 'Fatura FinCorp Seguros Vida',
        string $tenantId = 'tenant-fin'
    ): array {
        $startTime = hrtime(true);
        $txid = 'TX' . date('YmdHis') . strtoupper(bin2hex(random_bytes(6)));
        $amountReais = number_format($amountCents / 100, 2, '.', '');

        // Construção do Payload EMV Pix Copia-e-Cola canônico
        $pixPayload = $this->buildEmvPayload($txid, $amountReais, $description);

        $now = date('Y-m-d\TH:i:sP');
        $transId = 'pix-' . bin2hex(random_bytes(6));

        // Persiste a transação na tabela pix_transactions
        $stmt = $this->pdo->prepare(
            "INSERT INTO pix_transactions (id, tenant_id, policy_id, txid, amount_cents, qr_code, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?)"
        );
        $stmt->execute([$transId, $tenantId, $policyId, $txid, $amountCents, $pixPayload, $now]);

        $requestPayload = [
            'calendario' => ['expiracao' => 3600],
            'devedor' => ['nome' => 'Colaborador Titular da Apólice', 'cpf' => '888.999.000-08'],
            'valor' => ['original' => $amountReais],
            'chave' => 'pix-financeiro@fincorp.com.br',
            'solicitacaoPagador' => $description,
            'txid' => $txid,
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'status' => 'PENDING',
            'transaction_id' => $transId,
            'txid' => $txid,
            'amount_cents' => $amountCents,
            'amount_reais' => $amountReais,
            'pix_copia_e_cola' => $pixPayload,
            'location' => "pix.fincorp.com.br/qr/v2/$txid",
            'bacen_spi_protocol' => 'SPI-' . strtoupper(substr(hash('sha256', $txid), 0, 16)),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'created_at' => $now
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 35;

        $this->logIntegration(
            $tenantId,
            'AIE_PIX_BACEN_GATEWAY',
            self::ENDPOINT,
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }

    /**
     * Simula a liquidação instantânea via webhook do BACEN SPI.
     */
    public function settleTransaction(string $txid, string $tenantId = 'tenant-fin'): array
    {
        $startTime = hrtime(true);
        $now = date('Y-m-d\TH:i:sP');
        $e2eid = 'E00038166' . date('YmdHi') . strtoupper(bin2hex(random_bytes(6)));

        $stmt = $this->pdo->prepare(
            "UPDATE pix_transactions 
             SET status = 'PAID', e2eid = ?, paid_at = ? 
             WHERE txid = ?"
        );
        $stmt->execute([$e2eid, $now, $txid]);

        $requestPayload = [
            'webhook_source' => 'BACEN_SPI_SETTLEMENT_CORE',
            'event' => 'PIX_RECEIVED',
            'txid' => $txid,
            'end_to_end_id' => $e2eid,
            'tenant_id' => $tenantId
        ];

        $responsePayload = [
            'status' => 'SETTLED',
            'txid' => $txid,
            'end_to_end_id' => $e2eid,
            'clearing_house' => 'BACEN SPI - STRATUM 0',
            'settlement_speed' => 'INSTANTANEOUS_SUB_SECOND',
            'paid_at' => $now
        ];

        $durationMs = (int)((hrtime(true) - $startTime) / 1e6);
        if ($durationMs <= 0) $durationMs = 28;

        $this->logIntegration(
            $tenantId,
            'AIE_PIX_BACEN_GATEWAY',
            'https://spi.bcb.gov.br/api/v2/webhook/pix',
            $requestPayload,
            $responsePayload,
            'SUCCESS',
            $durationMs
        );

        return $responsePayload;
    }

    /**
     * Constrói string EMV válida do PIX Copia-e-Cola com CRC-16 CCITT.
     */
    private function buildEmvPayload(string $txid, string $amount, string $description): string
    {
        $gui = '0014br.gov.bcb.pix';
        $key = '0129pix-financeiro@fincorp.com.br';
        $merchantAccount = $gui . $key;
        $tag26 = '26' . str_pad((string)strlen($merchantAccount), 2, '0', STR_PAD_LEFT) . $merchantAccount;

        $tag00 = '000201'; // Payload Format Indicator
        $tag52 = '52040000'; // Merchant Category Code
        $tag53 = '5303986';  // BRL Currency Code
        $tag54 = '54' . str_pad((string)strlen($amount), 2, '0', STR_PAD_LEFT) . $amount;
        $tag58 = '5802BR';   // Country Code
        $tag59 = '5914FINCORP SEGUROS'; // Merchant Name
        $tag60 = '6008CURITIBA';        // Merchant City
        
        $refField = '05' . str_pad((string)strlen($txid), 2, '0', STR_PAD_LEFT) . $txid;
        $tag62 = '62' . str_pad((string)strlen($refField), 2, '0', STR_PAD_LEFT) . $refField;

        $rawPayload = $tag00 . $tag26 . $tag52 . $tag53 . $tag54 . $tag58 . $tag59 . $tag60 . $tag62 . '6304';
        $crc16 = $this->calculateCrc16($rawPayload);

        return $rawPayload . $crc16;
    }

    /**
     * Calcula o CRC16-CCITT (Polinômio 0x1021) padrão Bacen para Pix.
     */
    private function calculateCrc16(string $payload): string
    {
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
