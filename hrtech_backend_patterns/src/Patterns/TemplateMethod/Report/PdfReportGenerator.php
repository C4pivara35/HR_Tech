<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Report;

/**
 * Class PdfReportGenerator
 *
 * Generates formatted text-based PDF/print layout compliance reports with formal title headers,
 * tabular data columns with box-drawing borders, pagination markers, and total summary sections.
 */
class PdfReportGenerator extends ReportGeneratorTemplate
{
    private const int DEFAULT_PAGE_SIZE = 25;

    /**
     * Stored record count for footer rendering.
     */
    private int $lastRecordCount = 0;

    /**
     * Formats official PDF document header banner and column headers.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function formatHeaders(array $options): mixed
    {
        $title = (string)($options['title'] ?? 'HRTECH OFFICIAL COMPLIANCE AUDIT REPORT');
        $company = (string)($options['company'] ?? $options['tenant'] ?? 'HRTech Core Enterprise Systems');
        $generatedAt = (string)($options['generated_at'] ?? date('Y-m-d H:i:s T'));

        return [
            'title'        => strtoupper($title),
            'company'      => $company,
            'generated_at' => $generatedAt,
            'columns'      => (array)($options['columns'] ?? []),
        ];
    }

    /**
     * Formats records into aligned tabular columns with pagination indicators.
     *
     * @param array<int, array<string, mixed>> $filteredData
     * @param array<string, mixed> $options
     * @return array<int, string>
     */
    protected function formatBody(array $filteredData, array $options): mixed
    {
        $this->lastRecordCount = count($filteredData);

        if (empty($filteredData)) {
            return ['  [No records match the requested report criteria]'];
        }

        // Determine column keys and dynamic widths
        $columns = (array)($options['columns'] ?? array_keys($filteredData[0]));
        $colWidths = [];

        foreach ($columns as $col) {
            $colWidths[$col] = max(mb_strlen((string)$col), 8);
        }

        foreach ($filteredData as $row) {
            foreach ($columns as $col) {
                $val = (string)($row[$col] ?? '');
                $colWidths[$col] = min(40, max($colWidths[$col], mb_strlen($val)));
            }
        }

        $pageSize = (int)($options['page_size'] ?? self::DEFAULT_PAGE_SIZE);
        $totalPages = (int)ceil(count($filteredData) / max(1, $pageSize));

        $lines = [];
        $border = $this->buildSeparatorLine($columns, $colWidths);
        $headerLine = $this->buildRowLine($columns, $columns, $colWidths);

        $currentPage = 1;
        $itemsInPage = 0;

        // Print first page header
        $lines[] = sprintf('--- Page %d of %d ---', $currentPage, $totalPages);
        $lines[] = $border;
        $lines[] = $headerLine;
        $lines[] = $border;

        foreach ($filteredData as $row) {
            if ($itemsInPage >= $pageSize) {
                $currentPage++;
                $itemsInPage = 0;
                $lines[] = '';
                $lines[] = sprintf('--- Page %d of %d ---', $currentPage, $totalPages);
                $lines[] = $border;
                $lines[] = $headerLine;
                $lines[] = $border;
            }

            $lines[] = $this->buildRowLine($columns, $row, $colWidths);
            $itemsInPage++;
        }

        $lines[] = $border;

        return $lines;
    }

    /**
     * Formats summary footer and compliance disclaimer.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function formatFooter(array $options): mixed
    {
        return [
            'total_records'   => $this->lastRecordCount,
            'summary_notes'   => (string)($options['summary_notes'] ?? 'Confidential - Generated for internal compliance auditing only.'),
            'hash_signature'  => hash('sha256', (string)microtime(true) . (string)$this->lastRecordCount),
        ];
    }

    /**
     * Renders assembled header, tabular body, and footer into fixed-width print layout string.
     *
     * @param mixed $headers
     * @param mixed $body
     * @param mixed $footer
     * @param array<string, mixed> $options
     * @return string
     */
    protected function renderOutput(mixed $headers, mixed $body, mixed $footer, array $options): string
    {
        $h = (array)$headers;
        $b = (array)$body;
        $f = (array)$footer;

        $width = 80;
        $divider = str_repeat('=', $width);

        $output = [];
        $output[] = $divider;
        $output[] = str_pad($h['company'] ?? '', $width, ' ', STR_PAD_BOTH);
        $output[] = str_pad($h['title'] ?? '', $width, ' ', STR_PAD_BOTH);
        $output[] = str_pad('Date: ' . ($h['generated_at'] ?? ''), $width, ' ', STR_PAD_BOTH);
        $output[] = $divider;
        $output[] = '';

        foreach ($b as $line) {
            $output[] = $line;
        }

        $output[] = '';
        $output[] = str_repeat('-', $width);
        $output[] = sprintf('SUMMARY TOTALS: %d Record(s) Processed', $f['total_records'] ?? 0);
        $output[] = 'Notice: ' . ($f['summary_notes'] ?? '');
        $output[] = 'Digital Certificate Seal: ' . ($f['hash_signature'] ?? '');
        $output[] = $divider;

        return implode("\n", $output);
    }

    /**
     * @param array<int, string> $columns
     * @param array<string, int> $widths
     */
    private function buildSeparatorLine(array $columns, array $widths): string
    {
        $parts = [];
        foreach ($columns as $col) {
            $w = $widths[$col] ?? 10;
            $parts[] = str_repeat('-', $w + 2);
        }
        return '+' . implode('+', $parts) . '+';
    }

    /**
     * @param array<int, string> $columns
     * @param array<string, mixed> $row
     * @param array<string, int> $widths
     */
    private function buildRowLine(array $columns, array $row, array $widths): string
    {
        $parts = [];
        foreach ($columns as $col) {
            $w = $widths[$col] ?? 10;
            $val = (string)($row[$col] ?? '');
            $parts[] = ' ' . str_pad($val, $w) . ' ';
        }
        return '|' . implode('|', $parts) . '|';
    }
}
