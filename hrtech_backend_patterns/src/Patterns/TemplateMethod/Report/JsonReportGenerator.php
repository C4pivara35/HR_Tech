<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Report;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Class JsonReportGenerator
 *
 * Gera relatórios analíticos e de conformidade em JSON estruturado para integrações
 * contendo metadados de execução, carimbos ISO-8601, análises estatísticas agregadas e registros.
 */
class JsonReportGenerator extends ReportGeneratorTemplate
{
    private int $recordCount = 0;

    /**
     * Registros armazenados para agregação de totais no rodapé do relatório.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $currentRecords = [];

    /**
     * Formata os metadados do relatório incluindo título, carimbo de execução e filtros aplicados.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function formatHeaders(array $options): mixed
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return [
            'report_name'  => (string)($options['title'] ?? 'HRTECH_ANALYTICS_REPORT'),
            'version'      => '1.0.0',
            'generated_at' => $now->format(DATE_ATOM),
            'tenant_id'    => $options['tenant_id'] ?? $options['tenant'] ?? null,
            'filters'      => [
                'key'   => $options['filter_key'] ?? null,
                'value' => $options['filter_value'] ?? null,
            ],
        ];
    }

    /**
     * Formata e estrutura os registros para a carga útil principal.
     *
     * @param array<int, array<string, mixed>> $filteredData
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    protected function formatBody(array $filteredData, array $options): mixed
    {
        $this->recordCount = count($filteredData);
        $this->currentRecords = $filteredData;

        return $filteredData;
    }

    /**
     * Formata totais estatísticos e agregações para o rodapé do relatório.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function formatFooter(array $options): mixed
    {
        $numericSums = [];

        foreach ($this->currentRecords as $row) {
            foreach ($row as $k => $v) {
                if (is_numeric($v)) {
                    $numericSums[$k] = ($numericSums[$k] ?? 0.0) + (float)$v;
                }
            }
        }

        $formattedSums = [];
        foreach ($numericSums as $col => $sum) {
            $formattedSums[$col] = round($sum, 2);
        }

        return [
            'total_records' => $this->recordCount,
            'aggregations'  => $formattedSums,
            'status'        => 'SUCCESS',
        ];
    }

    /**
     * Encodes assembled sections into canonical formatted JSON string.
     *
     * @param mixed $headers
     * @param mixed $body
     * @param mixed $footer
     * @param array<string, mixed> $options
     * @return string
     */
    protected function renderOutput(mixed $headers, mixed $body, mixed $footer, array $options): string
    {
        $reportStructure = [
            'metadata'  => (array)$headers,
            'records'   => (array)$body,
            'analytics' => (array)$footer,
        ];

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (!empty($options['pretty_print'])) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($reportStructure, $flags | JSON_THROW_ON_ERROR);
    }
}
