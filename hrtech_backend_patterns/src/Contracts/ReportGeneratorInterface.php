<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface ReportGeneratorInterface
 *
 * Contract for generating standardized HR and compliance reports in various formats.
 */
interface ReportGeneratorInterface
{
    /**
     * Generates a formatted report output from input data and formatting options.
     *
     * @param array<string, mixed> $data Raw report dataset.
     * @param array<string, mixed> $options Formatting and filtering options.
     * @return string Formatted report output.
     */
    public function generate(array $data, array $options = []): string;
}
