<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Report;

use HrTech\Contracts\ReportGeneratorInterface;

/**
 * Class ReportGeneratorTemplate
 *
 * Abstract template method orchestrating the generation and rendering of HR reports.
 */
abstract class ReportGeneratorTemplate implements ReportGeneratorInterface
{
    /**
     * The Template Method defining the report generation algorithm.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     * @return string
     */
    final public function generate(array $data, array $options = []): string
    {
        $rawRecords = $this->fetchData($data);
        $filteredRecords = $this->applyFilters($rawRecords, $options);

        $headers = $this->formatHeaders($options);
        $body = $this->formatBody($filteredRecords, $options);
        $footer = $this->formatFooter($options);

        return $this->renderOutput($headers, $body, $footer, $options);
    }

    /**
     * Ingests and normalizes raw report data.
     *
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    protected function fetchData(array $data): array
    {
        return (array)($data['items'] ?? $data['records'] ?? $data);
    }

    /**
     * Applies criteria filtering (e.g. status, department, date range).
     *
     * @param array<int, array<string, mixed>> $data
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    protected function applyFilters(array $data, array $options): array
    {
        if (isset($options['filter_key'], $options['filter_value'])) {
            $key = $options['filter_key'];
            $val = $options['filter_value'];
            return array_values(array_filter($data, fn($item) => isset($item[$key]) && $item[$key] === $val));
        }

        return $data;
    }

    /**
     * Primitive step: Formats document header.
     *
     * @param array<string, mixed> $options
     * @return mixed
     */
    abstract protected function formatHeaders(array $options): mixed;

    /**
     * Primitive step: Formats document body content.
     *
     * @param array<int, array<string, mixed>> $filteredData
     * @param array<string, mixed> $options
     * @return mixed
     */
    abstract protected function formatBody(array $filteredData, array $options): mixed;

    /**
     * Primitive step: Formats document footer and totals.
     *
     * @param array<string, mixed> $options
     * @return mixed
     */
    abstract protected function formatFooter(array $options): mixed;

    /**
     * Primitive step: Assembles and renders headers, body, and footer into target format.
     *
     * @param mixed $headers
     * @param mixed $body
     * @param mixed $footer
     * @param array<string, mixed> $options
     * @return string
     */
    abstract protected function renderOutput(mixed $headers, mixed $body, mixed $footer, array $options): string;
}
