<?php
declare(strict_types=1);

namespace App\Services\External;

use PDO;
use HrTech\Database\DatabaseManager;

/**
 * BaseAieService — Classe Base para os 10 Serviços Simulados de Arquivo de Interface Externa (AIE)
 * 
 * Registra formalmente todas as chamadas no banco de dados SQLite (`external_integrations`)
 * garantindo rastreabilidade e auditabilidade para a contagem de Pontos de Função (IFPUG CPM 4.3.1).
 */
abstract class BaseAieService
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
        } else {
            $dbPath = defined('ROOT_DIR') ? ROOT_DIR . '/hrtech_db.sqlite' : dirname(__DIR__, 3) . '/hrtech_db.sqlite';
            $this->pdo = DatabaseManager::getInstance($dbPath)->getConnection();
        }
    }

    /**
     * Registra chamada externa na tabela external_integrations.
     *
     * @param string $tenantId
     * @param string $serviceName Nome da interface externa (AIE)
     * @param string $endpointUrl Endpoint simulado da API
     * @param array<string, mixed> $requestPayload
     * @param array<string, mixed> $responsePayload
     * @param string $status SUCCESS | ERROR | WARNING
     * @param int $durationMs Tempo simulado de resposta em milissegundos
     * @return string ID do registro inserido
     */
    protected function logIntegration(
        string $tenantId,
        string $serviceName,
        string $endpointUrl,
        array $requestPayload,
        array $responsePayload,
        string $status = 'SUCCESS',
        int $durationMs = 45
    ): string {
        $id = 'ext-' . bin2hex(random_bytes(8));
        $now = date('Y-m-d\TH:i:sP');

        $reqJson = json_encode($requestPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $resJson = json_encode($responsePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $stmt = $this->pdo->prepare(
            "INSERT INTO external_integrations 
             (id, tenant_id, service_name, endpoint_url, request_payload, response_payload, status, duration_ms, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $tenantId,
            $serviceName,
            $endpointUrl,
            $reqJson,
            $resJson,
            $status,
            $durationMs,
            $now
        ]);

        if (function_exists('logQuery')) {
            logQuery(
                "INSERT INTO external_integrations (service='$serviceName', status='$status')",
                [$id, $serviceName],
                "AIE Service Client: $serviceName",
                "AIE Client ($serviceName) ➔ API Gateway ($endpointUrl) ➔ Mock Response ➔ SQLite: external_integrations"
            );
        }

        return $id;
    }
}
