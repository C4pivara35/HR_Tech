<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface TimeLogImporterInterface
 *
 * Contract for batch importing time clock records from disparate data sources.
 */
interface TimeLogImporterInterface
{
    /**
     * Imports time clock records from the given source identifier/payload.
     *
     * @param string $source File path, JSON string, or API endpoint identifier.
     * @return array<string, mixed> Summary of import operation including processed records.
     */
    public function import(string $source): array;
}
