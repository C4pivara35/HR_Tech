<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Report;

// ============================================================
// PADRÃO DE PROJETO: TEMPLATE METHOD (Exemplo 2 de 3)
// ============================================================
// Intenção: Definir o esqueleto do algoritmo de geração de
// relatórios na classe base, deixando os passos de formatação
// e renderização para as subclasses (PDF, Excel, JSON).
//
// Elementos do Template Method neste arquivo:
//   1. generate() é FINAL — a sequência de passos não muda.
//   2. fetchData() e applyFilters() são PASSOS PADRÃO com impl. base.
//   3. formatHeaders(), formatBody(), formatFooter(), renderOutput()
//      são PASSOS PRIMITIVOS (abstract) — cada formato os implementa.
// ============================================================

use HrTech\Contracts\ReportGeneratorInterface;

/**
 * Classe abstrata ReportGeneratorTemplate — PADRÃO TEMPLATE METHOD
 *
 * Define o esqueleto imutável do algoritmo de geração de relatórios RH.
 * As subclasses (PdfReportGenerator, ExcelReportGenerator, JsonReportGenerator)
 * implementam apenas a formatação e renderização específicas de cada formato.
 */
abstract class ReportGeneratorTemplate implements ReportGeneratorInterface
{
    // -------------------------------------------------------
    // MÉTODO TEMPLATE — núcleo do padrão.
    // "final" garante que a sequência de geração do relatório
    // nunca seja alterada pelas subclasses.
    // -------------------------------------------------------
    /**
     * MÉTODO TEMPLATE: Define a sequência imutável de geração de relatório.
     *
     * Ordem dos passos (fixa para todos os formatos):
     *   1. Coleta dos dados brutos
     *   2. Aplicação de filtros
     *   3. Formatação do cabeçalho
     *   4. Formatação do corpo
     *   5. Formatação do rodapé
     *   6. Renderização da saída final
     *
     * @param array<string, mixed> $data Dados de entrada do relatório
     * @param array<string, mixed> $options Opções de geração (título, filtros, colunas etc.)
     * @return string Relatório gerado no formato específico da subclasse
     */
    final public function generate(array $data, array $options = []): string
    {
        // PASSO 1: Coleta e normaliza os dados brutos
        $rawRecords = $this->fetchData($data);

        // PASSO 2: Aplica filtros de critério (departamento, status, data etc.)
        $filteredRecords = $this->applyFilters($rawRecords, $options);

        // PASSO 3: Formata o cabeçalho (cada subclasse define o formato)
        $headers = $this->formatHeaders($options);

        // PASSO 4: Formata o corpo com os registros filtrados
        $body = $this->formatBody($filteredRecords, $options);

        // PASSO 5: Formata o rodapé com totais e notas
        $footer = $this->formatFooter($options);

        // PASSO 6: Renderiza a saída final no formato da subclasse (PDF, Excel ou JSON)
        return $this->renderOutput($headers, $body, $footer, $options);
    }

    // -------------------------------------------------------
    // PASSOS PADRÃO — implementados aqui, subclasses podem sobrescrever.
    // -------------------------------------------------------

    /**
     * PASSO PADRÃO: Extrai e normaliza os dados brutos de entrada.
     * Subclasses podem sobrescrever para buscar de fontes específicas.
     *
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    protected function fetchData(array $data): array
    {
        // Aceita arrays no formato ['items' => [...]], ['records' => [...]] ou diretamente [...]
        return (array)($data['items'] ?? $data['records'] ?? $data);
    }

    /**
     * PASSO PADRÃO: Filtra os registros por critério (chave-valor).
     * Subclasses podem sobrescrever para aplicar filtros mais complexos.
     *
     * @param array<int, array<string, mixed>> $data Dados normalizados
     * @param array<string, mixed> $options Opções com 'filter_key' e 'filter_value'
     * @return array<int, array<string, mixed>> Registros filtrados
     */
    protected function applyFilters(array $data, array $options): array
    {
        if (isset($options['filter_key'], $options['filter_value'])) {
            // Filtra mantendo apenas registros onde a chave coincide com o valor esperado
            $key = $options['filter_key'];
            $val = $options['filter_value'];
            return array_values(array_filter($data, fn($item) => isset($item[$key]) && $item[$key] === $val));
        }

        // Sem filtro: retorna todos os registros
        return $data;
    }

    // -------------------------------------------------------
    // PASSOS PRIMITIVOS (abstract) — obrigatórios nas subclasses.
    // Cada formato de relatório implementa à sua forma.
    // -------------------------------------------------------

    /**
     * PASSO PRIMITIVO: Formata o cabeçalho do documento.
     * PDF: banner com título e data. Excel: linha de colunas. JSON: metadata.
     *
     * @param array<string, mixed> $options
     * @return mixed Cabeçalho formatado (string, array ou outro tipo conforme o formato)
     */
    abstract protected function formatHeaders(array $options): mixed;

    /**
     * PASSO PRIMITIVO: Formata o corpo do documento com os registros.
     * PDF: linhas de tabela paginada. Excel: linhas CSV. JSON: array de objetos.
     *
     * @param array<int, array<string, mixed>> $filteredData Registros já filtrados
     * @param array<string, mixed> $options
     * @return mixed Corpo formatado
     */
    abstract protected function formatBody(array $filteredData, array $options): mixed;

    /**
     * PASSO PRIMITIVO: Formata o rodapé com totais e observações.
     * PDF: totalizador e assinatura digital. Excel: linha de soma. JSON: analytics.
     *
     * @param array<string, mixed> $options
     * @return mixed Rodapé formatado
     */
    abstract protected function formatFooter(array $options): mixed;

    /**
     * PASSO PRIMITIVO: Monta e renderiza cabeçalho, corpo e rodapé no formato final.
     * PDF: string de texto formatada. Excel: CSV com \r\n. JSON: json_encode().
     *
     * @param mixed $headers Cabeçalho formatado
     * @param mixed $body Corpo formatado
     * @param mixed $footer Rodapé formatado
     * @param array<string, mixed> $options
     * @return string Relatório completo pronto para entrega
     */
    abstract protected function renderOutput(mixed $headers, mixed $body, mixed $footer, array $options): string;
}
