<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface AuditableInterface
 *
 * Contrato para entidades sujeitas a registro imutável de auditoria e conformidade.
 */
interface AuditableInterface
{
    /**
     * Retorna o identificador úúnico do objeto para indexação de auditoria.
     *
     * @return string
     */
    public function getAuditIdentifier(): string;

    /**
     * Retorna a categoria ou classificação do objeto auditável (ex.: 'Employee', 'TimeLog').
     *
     * @return string
     */
    public function getAuditCategory(): string;

    /**
     * Retorna uma representação em array sanitizada e adequada para retenção em trilha de auditoria.
     * Sensitive attributes (passwords, tokens, salts) must be redacted.
     *
     * @return array<string, mixed>
     */
    public function toAuditArray(): array;
}
