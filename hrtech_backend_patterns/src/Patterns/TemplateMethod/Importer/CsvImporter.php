<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Importer;

use DateTimeImmutable;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Exceptions\ValidationException;

/**
 * Class CsvImporter
 *
 * Importer for comma- and semicolon-delimited CSV biometric time clock files.
 * Normalizes punch headers (employee_id, punch_time, type, lat, lon) and maps to domain records.
 */
class CsvImporter extends TimeLogImporterTemplate
{
    /**
     * Common CSV header aliases mapped to canonical schema keys.
     *
     * @var array<string, string>
     */
    private const array HEADER_MAP = [
        'employee_id'     => 'employee_id',
        'employeeid'      => 'employee_id',
        'emp_id'          => 'employee_id',
        'colaborador_id'  => 'employee_id',
        'punch_time'      => 'timestamp',
        'timestamp'       => 'timestamp',
        'time'            => 'timestamp',
        'punch_date'      => 'timestamp',
        'data_hora'       => 'timestamp',
        'type'            => 'type',
        'punch_type'      => 'type',
        'tipo'            => 'type',
        'lat'             => 'latitude',
        'latitude'        => 'latitude',
        'lon'             => 'longitude',
        'lng'             => 'longitude',
        'longitude'       => 'longitude',
        'nsr'             => 'nsr',
    ];

    private ?string $detectedDelimiter = null;

    /**
     * Opens file path or creates an in-memory stream from string content.
     *
     * @param string $source
     * @return resource
     * @throws ValidationException
     */
    protected function openSource(string $source): mixed
    {
        $this->detectedDelimiter = null;

        if (file_exists($source) && is_readable($source)) {
            $stream = fopen($source, 'rb');
            if ($stream === false) {
                throw new ValidationException("Failed to open CSV source file '{$source}'.");
            }
            return $stream;
        }

        $stream = fopen('php://memory', 'r+b');
        if ($stream === false) {
            throw new ValidationException('Failed to allocate in-memory stream for CSV data.');
        }

        fwrite($stream, $source);
        rewind($stream);

        return $stream;
    }

    /**
     * Parses CSV records into associative array rows with mapped column headers.
     *
     * @param mixed $handle
     * @return array<int, array<string, mixed>>
     * @throws ValidationException
     */
    protected function parseRecords(mixed $handle): array
    {
        if (!is_resource($handle)) {
            throw new ValidationException('Invalid stream handle passed to CsvImporter.');
        }

        // Detect delimiter from first line
        $firstLine = fgets($handle);
        if ($firstLine === false || trim($firstLine) === '') {
            return [];
        }

        $commaCount = substr_count($firstLine, ',');
        $semiCount = substr_count($firstLine, ';');
        $delimiter = $semiCount > $commaCount ? ';' : ',';
        $this->detectedDelimiter = $delimiter;

        // Parse header row
        $rawHeaders = str_getcsv(trim($firstLine), $delimiter);
        $mappedHeaders = [];

        foreach ($rawHeaders as $index => $header) {
            $normalized = strtolower(trim((string)$header));
            $mappedHeaders[$index] = self::HEADER_MAP[$normalized] ?? $normalized;
        }

        $records = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Skip empty rows
            if ($row === [null] || empty(array_filter($row, fn($v) => $v !== null && trim((string)$v) !== ''))) {
                continue;
            }

            $record = [];
            foreach ($row as $colIdx => $value) {
                $headerKey = $mappedHeaders[$colIdx] ?? (string)$colIdx;
                $record[$headerKey] = $value !== null ? trim((string)$value) : null;
            }

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Closes the stream handle and releases resources.
     *
     * @param mixed $handle
     */
    protected function closeSource(mixed $handle): void
    {
        if (is_resource($handle)) {
            fclose($handle);
        }
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

            $id = sprintf('TL-CSV-%s-%04d', date('Ymd'), $index + 1);
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
