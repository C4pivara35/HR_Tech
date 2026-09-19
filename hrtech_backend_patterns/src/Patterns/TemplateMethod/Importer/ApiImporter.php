<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Importer;

use DateTimeImmutable;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Exceptions\UnauthorizedException;
use HrTech\Exceptions\ValidationException;

/**
 * Class ApiImporter
 *
 * Importer for API ingest endpoints receiving structured electronic punch payloads.
 * Enforces mandatory Bearer token or API key authentication checks before ingesting records.
 */
class ApiImporter extends TimeLogImporterTemplate
{
    /**
     * @param string|null $expectedBearerToken If specified, incoming request must supply this Bearer token.
     * @param string|null $expectedApiKey If specified, incoming request must supply this X-API-Key.
     */
    public function __construct(
        private ?string $expectedBearerToken = null,
        private ?string $expectedApiKey = null
    ) {
    }

    public function setBearerToken(?string $token): self
    {
        $this->expectedBearerToken = $token;
        return $this;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->expectedApiKey = $apiKey;
        return $this;
    }

    /**
     * Opens and authenticates the incoming API payload.
     *
     * @param string $source JSON-encoded payload containing auth credentials and punch data.
     * @return array<string, mixed>
     * @throws UnauthorizedException If authentication fails.
     * @throws ValidationException If JSON is malformed.
     */
    protected function openSource(string $source): mixed
    {
        $payload = json_decode($source, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            throw new ValidationException(
                sprintf('Malformed API punch payload: %s', json_last_error_msg())
            );
        }

        // Authenticate credentials if constraints are active
        $this->authenticateRequest($payload);

        return $payload;
    }

    /**
     * Verifies provided Bearer token or API key against configured secrets.
     *
     * @param array<string, mixed> $payload
     * @throws UnauthorizedException
     */
    private function authenticateRequest(array $payload): void
    {
        if ($this->expectedBearerToken !== null) {
            $providedToken = $this->extractBearerToken($payload);
            if ($providedToken === null || !hash_equals($this->expectedBearerToken, $providedToken)) {
                throw UnauthorizedException::unauthenticated('API punch ingestion rejected: invalid or missing Bearer token.');
            }
        }

        if ($this->expectedApiKey !== null) {
            $providedApiKey = $this->extractApiKey($payload);
            if ($providedApiKey === null || !hash_equals($this->expectedApiKey, $providedApiKey)) {
                throw UnauthorizedException::unauthenticated('API punch ingestion rejected: invalid or missing API key.');
            }
        }
    }

    /**
     * Extracts Bearer token from header array, auth object, or root payload.
     *
     * @param array<string, mixed> $payload
     * @return string|null
     */
    private function extractBearerToken(array $payload): ?string
    {
        // 1. Check HTTP headers structure: Authorization: Bearer <token>
        $headers = $payload['headers'] ?? [];
        if (is_array($headers)) {
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            if (is_string($authHeader) && preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $matches)) {
                return trim($matches[1]);
            }
        }

        // 2. Check auth object
        $auth = $payload['auth'] ?? [];
        if (is_array($auth) && isset($auth['bearer']) && is_string($auth['bearer'])) {
            return trim($auth['bearer']);
        }

        // 3. Direct property
        if (isset($payload['token']) && is_string($payload['token'])) {
            return trim($payload['token']);
        }

        return null;
    }

    /**
     * Extracts API key from headers or payload.
     *
     * @param array<string, mixed> $payload
     * @return string|null
     */
    private function extractApiKey(array $payload): ?string
    {
        $headers = $payload['headers'] ?? [];
        if (is_array($headers)) {
            $apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? $headers['X-Api-Key'] ?? null;
            if (is_string($apiKey) && trim($apiKey) !== '') {
                return trim($apiKey);
            }
        }

        $auth = $payload['auth'] ?? [];
        if (is_array($auth) && isset($auth['api_key']) && is_string($auth['api_key'])) {
            return trim($auth['api_key']);
        }

        if (isset($payload['api_key']) && is_string($payload['api_key'])) {
            return trim($payload['api_key']);
        }

        return null;
    }

    /**
     * Parses biometric punch records from authenticated API payload.
     *
     * @param mixed $handle
     * @return array<int, array<string, mixed>>
     */
    protected function parseRecords(mixed $handle): array
    {
        if (!is_array($handle)) {
            return [];
        }

        // Unpack nested payload structures
        $rawRecords = $handle['records']
            ?? $handle['data']
            ?? $handle['punches']
            ?? $handle['body']
            ?? $handle;

        if (!is_array($rawRecords)) {
            return [];
        }

        $normalized = [];

        foreach ($rawRecords as $item) {
            if (!is_array($item)) {
                continue;
            }

            $record = [
                'employee_id' => $item['employee_id'] ?? $item['emp_id'] ?? $item['colaborador_id'] ?? null,
                'timestamp'   => $item['timestamp'] ?? $item['punch_time'] ?? $item['time'] ?? null,
                'type'        => $item['type'] ?? $item['punch_type'] ?? null,
                'latitude'    => $item['latitude'] ?? $item['lat'] ?? 0.0,
                'longitude'   => $item['longitude'] ?? $item['lon'] ?? $item['lng'] ?? 0.0,
            ];

            if (isset($item['nsr'])) {
                $record['nsr'] = $item['nsr'];
            }

            $normalized[] = $record;
        }

        return $normalized;
    }

    /**
     * Closes API session handle.
     *
     * @param mixed $handle
     */
    protected function closeSource(mixed $handle): void
    {
        // No persistent connection stream to close
    }

    /**
     * Converts imported summary records into domain TimeLog entity instances.
     *
     * @param array<string, mixed> $importSummary
     * @param string $tenantId
     * @return array<int, TimeLog>
     */
    public function convertToTimeLogs(array $importSummary, string $tenantId = 'default'): array
    {
        $entities = [];
        $records = (array)($importSummary['records'] ?? []);
        $nsrCounter = 1;
        $prevHash = null;

        foreach ($records as $index => $record) {
            $typeStr = strtoupper((string)($record['type'] ?? 'ENTRY'));
            $type = TimeLogType::tryFrom($typeStr) ?? match ($typeStr) {
                'ENTRY', 'ENTRADA', 'IN' => TimeLogType::ENTRY,
                'INTERVAL_START', 'INTERVALO_INICIO', 'LUNCH_OUT' => TimeLogType::INTERVAL_START,
                'INTERVAL_END', 'INTERVALO_FIM', 'LUNCH_IN' => TimeLogType::INTERVAL_END,
                'EXIT', 'SAIDA', 'OUT' => TimeLogType::EXIT,
                default => TimeLogType::ENTRY,
            };

            $lat = (float)($record['latitude'] ?? 0.0);
            $lon = (float)($record['longitude'] ?? 0.0);
            $location = new GeoLocation($lat, $lon);

            $id = sprintf('TL-API-%s-%04d', date('Ymd'), $index + 1);
            $timestamp = new DateTimeImmutable((string)($record['timestamp'] ?? 'now'));
            $nsr = isset($record['nsr']) ? (int)$record['nsr'] : $nsrCounter++;

            $timeLog = TimeLog::record(
                id: $id,
                tenantId: $tenantId,
                employeeId: (string)$record['employee_id'],
                timestamp: $timestamp,
                type: $type,
                location: $location,
                nsr: $nsr,
                previousHash: $prevHash
            );

            $prevHash = $timeLog->signatureHash;
            $entities[] = $timeLog;
        }

        return $entities;
    }
}
