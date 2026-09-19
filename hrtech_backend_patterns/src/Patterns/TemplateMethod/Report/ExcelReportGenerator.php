<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Report;

/**
 * Class ExcelReportGenerator
 *
 * Generates structured CSV/TSV spreadsheet datasets with delimited column headers,
 * sanitized data rows, and calculated summary/totals rows suitable for spreadsheet import.
 */
class ExcelReportGenerator extends ReportGeneratorTemplate
{
    /**
     * Calculated column sums across numeric records.
     *
     * @var array<string, float>
     */
    private array $calculatedColumnSums = [];

    /**
     * Stored column keys.
     *
     * @var array<int, string>
     */
    private array $activeColumns = [];

    private int $totalRowCount = 0;

    /**
     * Formats delimited column headers for spreadsheet import.
     *
     * @param array<string, mixed> $options
     * @return string
     */
    protected function formatHeaders(array $options): mixed
    {
        $delimiter = $this->resolveDelimiter($options);
        $columns = (array)($options['columns'] ?? []);
        $this->activeColumns = $columns;

        if (empty($columns)) {
            return '';
        }

        return $this->formatCsvLine($columns, $delimiter);
    }

    /**
     * Formats data records into delimited rows, calculating numeric sums for totals.
     *
     * @param array<int, array<string, mixed>> $filteredData
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    protected function formatBody(array $filteredData, array $options): mixed
    {
        $this->totalRowCount = count($filteredData);
        $this->calculatedColumnSums = [];
        $delimiter = $this->resolveDelimiter($options);

        if (empty($filteredData)) {
            return [];
        }

        // Infer columns if not explicitly provided in options
        if (empty($this->activeColumns)) {
            $this->activeColumns = array_keys($filteredData[0]);
        }

        $lines = [];

        foreach ($filteredData as $row) {
            $rowValues = [];
            foreach ($this->activeColumns as $col) {
                $val = $row[$col] ?? '';
                $rowValues[] = (string)$val;

                // Track numeric totals
                if (is_numeric($val)) {
                    $this->calculatedColumnSums[$col] = ($this->calculatedColumnSums[$col] ?? 0.0) + (float)$val;
                }
            }
            $lines[] = $this->formatCsvLine($rowValues, $delimiter);
        }

        return $lines;
    }

    /**
     * Formats calculated summary totals row for the bottom of the spreadsheet.
     *
     * @param array<string, mixed> $options
     * @return string
     */
    protected function formatFooter(array $options): mixed
    {
        $delimiter = $this->resolveDelimiter($options);

        if (empty($this->activeColumns)) {
            return sprintf('"TOTAL RECORDS: %d"', $this->totalRowCount);
        }

        $summaryValues = [];
        $first = true;

        foreach ($this->activeColumns as $col) {
            if ($first) {
                $summaryValues[] = sprintf('TOTAL (%d records)', $this->totalRowCount);
                $first = false;
            } elseif (isset($this->calculatedColumnSums[$col])) {
                $summaryValues[] = number_format($this->calculatedColumnSums[$col], 2, '.', '');
            } else {
                $summaryValues[] = '';
            }
        }

        return $this->formatCsvLine($summaryValues, $delimiter);
    }

    /**
     * Assembles headers, body records, and calculated summary row.
     *
     * @param mixed $headers
     * @param mixed $body
     * @param mixed $footer
     * @param array<string, mixed> $options
     * @return string
     */
    protected function renderOutput(mixed $headers, mixed $body, mixed $footer, array $options): string
    {
        $lines = [];

        $headerStr = (string)$headers;
        if ($headerStr !== '') {
            $lines[] = $headerStr;
        } elseif (!empty($this->activeColumns)) {
            $delimiter = $this->resolveDelimiter($options);
            $lines[] = $this->formatCsvLine($this->activeColumns, $delimiter);
        }

        foreach ((array)$body as $line) {
            $lines[] = (string)$line;
        }

        $footerStr = (string)$footer;
        if ($footerStr !== '') {
            $lines[] = $footerStr;
        }

        return implode("\r\n", $lines);
    }

    /**
     * Formats an array of values into a standard RFC 4180 CSV line.
     *
     * @param array<int, string> $fields
     * @param string $delimiter
     * @return string
     */
    private function formatCsvLine(array $fields, string $delimiter): string
    {
        $fp = fopen('php://memory', 'r+b');
        if ($fp === false) {
            return implode($delimiter, array_map(fn($f) => '"' . str_replace('"', '""', (string)$f) . '"', $fields));
        }

        fputcsv($fp, $fields, $delimiter, '"', '\\');
        rewind($fp);
        $line = stream_get_contents($fp);
        fclose($fp);

        return $line !== false ? rtrim($line, "\r\n") : '';
    }

    /**
     * Resolves separator delimiter (supports CSV commas, semicolons, and TSV tabs).
     *
     * @param array<string, mixed> $options
     * @return string
     */
    private function resolveDelimiter(array $options): string
    {
        if (isset($options['delimiter'])) {
            return (string)$options['delimiter'];
        }

        $format = strtolower((string)($options['format'] ?? 'csv'));
        return $format === 'tsv' ? "\t" : ',';
    }
}
