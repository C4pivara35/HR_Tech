<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface TenantScopedInterface
 *
 * Enforces tenant boundary isolation. Implementers are explicitly bound
 * a um tenant específico e não pode ser acessada fora dos limites da empresa.
 */
interface TenantScopedInterface
{
    /**
     * Retorna o identificador do tenant (empresa) ao qual esta entidade pertence.
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Verifica se esta entidade pertence à empresa especificada.
     *
     * @param string $tenantId
     * @return bool
     */
    public function belongsToTenant(string $tenantId): bool;
}
