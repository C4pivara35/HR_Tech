<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Report;

/**
 * Class ExcelReportGenerator
 *
 * Gera conjuntos de dados tabulares em CSV/TSV com cabeçalhos delimitados,
 * linhas de dados sanitizadas e linhas de totalizadores calculados para importação em planilhas.
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
     * Formata cabeçalhos de coluna delimitados para importação em planilhas.
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
     * Formata registros de dados em linhas delimitadas, calculando somas numéricas para os totais.
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
     * Formata a linha de resumo com totais calculados para o rodapé da planilha.
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
     * Monta o cabeçalho, os registros de dados e a linha de totais calculados.
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
     * Formata um array de valores em uma linha CSV padronizada conforme a RFC 4180.
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
