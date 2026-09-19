<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Importer;

use HrTech\Contracts\TimeLogImporterInterface;
use HrTech\Exceptions\ValidationException;

/**
 * Class TimeLogImporterTemplate
 *
 * Abstract template method orchestrating ingestion of biometric time logs.
 */
abstract class TimeLogImporterTemplate implements TimeLogImporterInterface
{
    /**
     * The Template Method defining the immutable time log ingestion lifecycle.
     *
     * @param string $source
     * @return array<string, mixed>
     */
    final public function import(string $source): array
    {
        $this->beforeImport($source);

        $handle = $this->openSource($source);

        try {
            $rawRecords = $this->parseRecords($handle);
            $validRecords = $this->validateSchema($rawRecords);
            $domainLogs = $this->transformToDomain($validRecords);
            $persistedCount = $this->persistLogs($domainLogs);
        } finally {
            $this->closeSource($handle);
        }

        $summary = [
            'source' => $source,
            'total_raw' => count($rawRecords),
            'valid_count' => count($validRecords),
            'persisted_count' => $persistedCount,
            'records' => $domainLogs,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->afterImport($summary);

        return $summary;
    }

    /**
     * Optional pre-import lifecycle hook.
     */
    protected function beforeImport(string $source): void
    {
        // Default no-op
    }

    /**
     * Primitive step: Opens file, stream, or prepares payload.
     *
     * @param string $source
     * @return mixed Resource handle or parsed data.
     */
    abstract protected function openSource(string $source): mixed;

    /**
     * Primitive step: Parses raw source records into array.
     *
     * @param mixed $handle
     * @return array<int, array<string, mixed>>
     */
    abstract protected function parseRecords(mixed $handle): array;

    /**
     * Validates required schema keys on raw records.
     *
     * @param array<int, array<string, mixed>> $rawRecords
     * @return array<int, array<string, mixed>>
     * @throws ValidationException
     */
    protected function validateSchema(array $rawRecords): array
    {
        $requiredKeys = ['employee_id', 'timestamp', 'type'];
        $valid = [];

        foreach ($rawRecords as $index => $record) {
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $record) || $record[$key] === null || $record[$key] === '') {
                    throw new ValidationException("Record at index {$index} missing required field '{$key}'.");
                }
            }
            $valid[] = $record;
        }

        return $valid;
    }

    /**
     * Transforms validated records into domain-ready records with SHA-256 hashes.
     *
     * @param array<int, array<string, mixed>> $validRecords
     * @return array<int, array<string, mixed>>
     */
    protected function transformToDomain(array $validRecords): array
    {
        $transformed = [];
        foreach ($validRecords as $record) {
            // Portaria 671 MTE tamper-evident hash
            $hashPayload = sprintf(
                '%s|%s|%s|%s|%s',
                (string)($record['employee_id'] ?? ''),
                (string)($record['timestamp'] ?? ''),
                (string)($record['type'] ?? ''),
                (string)($record['latitude'] ?? '0.0'),
                (string)($record['longitude'] ?? '0.0')
            );
            $record['hash'] = hash('sha256', $hashPayload);
            $transformed[] = $record;
        }
        return $transformed;
    }

    /**
     * Persists transformed logs into memory store or database.
     *
     * @param array<int, array<string, mixed>> $domainLogs
     * @return int Number of successfully persisted records.
     */
    protected function persistLogs(array $domainLogs): int
    {
        return count($domainLogs);
    }

    /**
     * Primitive step: Closes stream, file pointer, or releases memory.
     *
     * @param mixed $handle
     */
    abstract protected function closeSource(mixed $handle): void;

    /**
     * Optional post-import lifecycle hook.
     *
     * @param array<string, mixed> $summary
     */
    protected function afterImport(array $summary): void
    {
        // Default no-op
    }
}
