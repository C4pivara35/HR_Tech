<?php

declare(strict_types=1);

namespace HrTech\Patterns\TemplateMethod\Importer;

// ============================================================
// PADRÃO DE PROJETO: TEMPLATE METHOD (Exemplo 3 de 3)
// ============================================================
// Intenção: Definir o esqueleto do algoritmo de importação de
// registros de ponto biométrico na classe base, deixando os
// passos de leitura da fonte para as subclasses.
//
// Subclasses concretas:
//   - CsvImporter   → lê arquivo CSV de relógio de ponto
//   - JsonImporter  → lê arquivo JSON de ponto eletrônico
//   - ApiImporter   → recebe payload de API com autenticação
//
// Elementos do Template Method neste arquivo:
//   1. import() é FINAL — a sequência de passos é imutável
//   2. openSource(), parseRecords(), closeSource() são PRIMITIVOS (abstract)
//   3. validateSchema() e transformToDomain() são PADRÃO com impl. base
//   4. beforeImport() e afterImport() são GANCHOS (hooks) opcionais
// ============================================================

use HrTech\Contracts\TimeLogImporterInterface;
use HrTech\Exceptions\ValidationException;

/**
 * Classe abstrata TimeLogImporterTemplate — PADRÃO TEMPLATE METHOD
 *
 * Define o esqueleto imutável do ciclo de importação de registros de ponto.
 * As subclasses implementam apenas como abrir e ler a fonte de dados específica.
 */
abstract class TimeLogImporterTemplate implements TimeLogImporterInterface
{
    // -------------------------------------------------------
    // MÉTODO TEMPLATE — núcleo do padrão.
    // "final" garante que a sequência de importação nunca mude.
    // -------------------------------------------------------
    /**
     * MÉTODO TEMPLATE: Define a sequência imutável do ciclo de importação.
     *
     * Ordem dos passos (fixa para todos os tipos de fonte):
     *   1. Hook pré-importação (opcional)
     *   2. Abertura da fonte (arquivo, JSON ou API)
     *   3. Leitura e parsing dos registros brutos
     *   4. Validação do schema obrigatório
     *   5. Transformação para domínio com hash SHA-256
     *   6. Persistência dos registros validados
     *   7. Fechamento da fonte
     *   8. Hook pós-importação com sumário (opcional)
     *
     * @param string $source Caminho do arquivo, string de conteúdo ou payload JSON
     * @return array<string, mixed> Sumário da importação com totais e registros
     */
    final public function import(string $source): array
    {
        // PASSO 1: Hook opcional antes de iniciar (ex: logging de início)
        $this->beforeImport($source);

        // PASSO 2: Abre a fonte de dados (CSV, JSON ou API)
        $handle = $this->openSource($source);

        try {
            // PASSO 3: Faz o parsing dos registros brutos
            $rawRecords = $this->parseRecords($handle);

            // PASSO 4: Valida o schema (campos obrigatórios: employee_id, timestamp, type)
            $validRecords = $this->validateSchema($rawRecords);

            // PASSO 5: Transforma para o formato de domínio com hash SHA-256 (Portaria 671 MTE)
            $domainLogs = $this->transformToDomain($validRecords);

            // PASSO 6: Persiste os registros válidos
            $persistedCount = $this->persistLogs($domainLogs);
        } finally {
            // PASSO 7: Fecha a fonte (executado sempre, mesmo com exceção)
            $this->closeSource($handle);
        }

        // Monta o sumário da importação para auditoria e feedback
        $summary = [
            'source'          => $source,
            'total_raw'       => count($rawRecords),
            'valid_count'     => count($validRecords),
            'persisted_count' => $persistedCount,
            'records'         => $domainLogs,
            'timestamp'       => date('Y-m-d H:i:s'),
        ];

        // PASSO 8: Hook opcional após importação (ex: logging de término)
        $this->afterImport($summary);

        return $summary;
    }

    // -------------------------------------------------------
    // GANCHO PRÉ-IMPORTAÇÃO — implementação padrão vazia.
    // -------------------------------------------------------
    /**
     * Gancho opcional executado antes da importação.
     * Padrão: sem ação. Subclasses podem sobrescrever para logging.
     *
     * @param string $source Identificador da fonte de dados
     */
    protected function beforeImport(string $source): void
    {
        // Implementação padrão: sem ação (gancho vazio)
    }

    // -------------------------------------------------------
    // PASSOS PRIMITIVOS (abstract) — obrigatórios nas subclasses.
    // -------------------------------------------------------

    /**
     * PASSO PRIMITIVO: Abre a fonte de dados e retorna um handle.
     * CSV: abre stream de arquivo. JSON: decodifica string. API: valida autenticação.
     *
     * @param string $source Caminho, conteúdo ou payload da fonte
     * @return mixed Handle (resource, array etc.) para uso no parseRecords
     */
    abstract protected function openSource(string $source): mixed;

    /**
     * PASSO PRIMITIVO: Lê e converte os registros brutos da fonte.
     * CSV: fgetcsv(). JSON: json_decode(). API: extrai do payload.
     *
     * @param mixed $handle Handle retornado por openSource()
     * @return array<int, array<string, mixed>> Registros brutos normalizados
     */
    abstract protected function parseRecords(mixed $handle): array;

    // -------------------------------------------------------
    // PASSOS PADRÃO — implementados aqui, subclasses podem sobrescrever.
    // -------------------------------------------------------

    /**
     * PASSO PADRÃO: Valida os campos obrigatórios dos registros.
     * Campos exigidos: employee_id, timestamp, type.
     * Subclasses podem sobrescrever para validações extras.
     *
     * @param array<int, array<string, mixed>> $rawRecords
     * @return array<int, array<string, mixed>> Apenas registros válidos
     * @throws ValidationException Se algum registro estiver incompleto
     */
    protected function validateSchema(array $rawRecords): array
    {
        // Campos que todo registro de ponto deve ter
        $requiredKeys = ['employee_id', 'timestamp', 'type'];
        $valid        = [];

        foreach ($rawRecords as $index => $record) {
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $record) || $record[$key] === null || $record[$key] === '') {
                    throw new ValidationException("Registro no índice {$index} está faltando o campo obrigatório '{$key}'.");
                }
            }
            $valid[] = $record;
        }

        return $valid;
    }

    /**
     * PASSO PADRÃO: Transforma registros validados para o formato de domínio.
     * Adiciona hash SHA-256 para integridade (conformidade Portaria 671 MTE).
     *
     * @param array<int, array<string, mixed>> $validRecords
     * @return array<int, array<string, mixed>> Registros prontos para persistência
     */
    protected function transformToDomain(array $validRecords): array
    {
        $transformed = [];
        foreach ($validRecords as $record) {
            // Gera hash SHA-256 para garantir imutabilidade do registro (Portaria 671 MTE)
            // Composto por: ID do funcionário | timestamp | tipo | latitude | longitude
            $hashPayload = sprintf(
                '%s|%s|%s|%s|%s',
                (string)($record['employee_id'] ?? ''),
                (string)($record['timestamp']   ?? ''),
                (string)($record['type']        ?? ''),
                (string)($record['latitude']    ?? '0.0'),
                (string)($record['longitude']   ?? '0.0')
            );
            $record['hash'] = hash('sha256', $hashPayload); // Hash à prova de adulteração
            $transformed[]  = $record;
        }
        return $transformed;
    }

    /**
     * PASSO PADRÃO: Persiste os registros transformados no banco de dados.
     * Implementação base apenas conta os registros (subclasses implementam a persistência real).
     *
     * @param array<int, array<string, mixed>> $domainLogs Registros com hash gerado
     * @return int Quantidade de registros persistidos com sucesso
     */
    protected function persistLogs(array $domainLogs): int
    {
        // Implementação base: simula persistência retornando o total
        // Subclasses devem sobrescrever para integrar com o banco de dados
        return count($domainLogs);
    }

    /**
     * PASSO PRIMITIVO: Fecha e libera o recurso da fonte de dados.
     * CSV: fclose(). JSON: liberado pelo GC. API: fecha conexão HTTP.
     *
     * @param mixed $handle Handle retornado por openSource()
     */
    abstract protected function closeSource(mixed $handle): void;

    // -------------------------------------------------------
    // GANCHO PÓS-IMPORTAÇÃO — implementação padrão vazia.
    // -------------------------------------------------------
    /**
     * Gancho opcional executado após a conclusão da importação.
     * Padrão: sem ação. Subclasses podem sobrescrever para auditoria.
     *
     * @param array<string, mixed> $summary Sumário com totais e registros importados
     */
    protected function afterImport(array $summary): void
    {
        // Implementação padrão: sem ação (gancho vazio)
    }
}
