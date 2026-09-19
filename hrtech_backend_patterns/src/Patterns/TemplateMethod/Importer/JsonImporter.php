<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Importer;

use DateTimeImmutable;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Exceptions\ValidationException;

/**
 * Class JsonImporter
 *
 * Importer for JSON-formatted biometric punch payloads and API dump files.
 * Validates payload schema, normalizes field aliases, and converts to domain TimeLog structures.
 */
class JsonImporter extends TimeLogImporterTemplate
{
    /**
     * Opens and decodes JSON file or string payload.
     *
     * @param string $source
     * @return array<string, mixed>|array<int, mixed>
     * @throws ValidationException
     */
    protected function openSource(string $source): mixed
    {
        $jsonContent = $source;

        if (file_exists($source) && is_readable($source)) {
            $fileData = file_get_contents($source);
            if ($fileData === false) {
                throw new ValidationException("Failed to read JSON file '{$source}'.");
            }
            $jsonContent = $fileData;
        }

        $decoded = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new ValidationException(
                sprintf('Invalid JSON payload: %s', json_last_error_msg())
            );
        }

        return $decoded;
    }

    /**
     * Extracts and normalizes punch records from decoded JSON payload.
     *
     * @param mixed $handle
     * @return array<int, array<string, mixed>>
     */
    protected function parseRecords(mixed $handle): array
    {
        if (!is_array($handle)) {
            return [];
        }

        // Support wrapped JSON collections or direct array
        $rawRecords = $handle['records']
            ?? $handle['punches']
            ?? $handle['items']
            ?? $handle['data']
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
                'timestamp'   => $item['timestamp'] ?? $item['punch_time'] ?? $item['time'] ?? $item['data_hora'] ?? null,
                'type'        => $item['type'] ?? $item['punch_type'] ?? $item['tipo'] ?? null,
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
     * Releases parsed in-memory JSON data.
     *
     * @param mixed $handle
     */
    protected function closeSource(mixed $handle): void
    {
        // In-memory data released by PHP garbage collection
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

            $id = sprintf('TL-JSON-%s-%04d', date('Ymd'), $index + 1);
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
