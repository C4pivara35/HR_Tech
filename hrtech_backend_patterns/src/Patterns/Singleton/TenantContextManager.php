<?php

declare(strict_types=1);

namespace HrTech\Patterns\Singleton;

// ============================================================
// PADRÃO DE PROJETO: SINGLETON (Exemplo 2 de 2)
// ============================================================
// Intenção: Garantir que esta classe possua UMA ÚNICA instância
// em toda a aplicação, fornecendo um ponto de acesso global.
//
// Aplicação prática aqui:
//   Controla qual empresa (tenant) está ativa no momento.
//   Se houvesse múltiplas instâncias, módulos diferentes
//   poderiam ter tenants conflitantes, causando vazamento de dados.
//
// Elementos do Singleton neste arquivo:
//   1. $instance (privado e estático) — armazena a instância única
//   2. __construct() privado — impede "new TenantContextManager()"
//   3. __clone() privado — impede cópia com clone
//   4. __wakeup() com exceção — impede recriação por unserialize
//   5. getInstance() estático — úúnico ponto de acesso global
// ============================================================

use HrTech\Contracts\SingletonInterface;
use HrTech\Domain\Entities\Tenant;
use HrTech\Exceptions\TenantContextException;
use HrTech\Exceptions\ValidationException;

/**
 * Classe TenantContextManager — PADRÃO SINGLETON
 *
 * Gerencia o contexto multi-tenant ativo em toda a aplicação.
 * Como Singleton, garante que APENAS UM OBJETO controle qual empresa
 * está ativa no momento, prevenindo vazamentos de dados entre clientes.
 */
class TenantContextManager implements SingletonInterface
{
    // -------------------------------------------------------
    // PASSO 1 DO SINGLETON: atributo estático que armazena
    // a única instância do gerenciador de contexto de tenant.
    // -------------------------------------------------------
    private static ?self $instance = null;

    /** @var Tenant|null Entidade Tenant ativa no momento */
    private ?Tenant $activeTenant = null;

    /** @var string|null ID do tenant ativo no momento */
    private ?string $activeTenantId = null;

    // -------------------------------------------------------
    // PASSO 2 DO SINGLETON: construtor PRIVADO.
    // Impede qualquer código externo de criar uma nova instância
    // com "new TenantContextManager()".
    // -------------------------------------------------------
    private function __construct()
    {
    }

    // -------------------------------------------------------
    // PASSO 3 DO SINGLETON: impede clonagem.
    // Sem isso, "clone $manager" criaria uma segunda instância.
    // -------------------------------------------------------
    private function __clone()
    {
    }

    // -------------------------------------------------------
    // PASSO 4 DO SINGLETON: impede desserialização.
    // Sem isso, unserialize() poderia recriar o objeto.
    // -------------------------------------------------------
    /**
     * Impede a recriação do Singleton via desserialização.
     *
     * @throws TenantContextException
     */
    public function __wakeup(): void
    {
        throw new TenantContextException('Não é possível desserializar o Singleton TenantContextManager.');
    }

    // -------------------------------------------------------
    // PASSO 5 DO SINGLETON: getInstance() — CORAÇÃO DO PADRÃO.
    // Garante que só exista uma instância. Usa "lazy initialization":
    // cria apenas na primeira chamada, reutiliza nas demais.
    // -------------------------------------------------------
    /**
     * Retorna a única instância global do TenantContextManager (Singleton).
     * Cria na primeira chamada; reutiliza nas seguintes.
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            // Primeira chamada: cria a instância única
            self::$instance = new self();
        }

        // Todas as demais chamadas retornam sempre a mesma instância
        return self::$instance;
    }

    /**
     * Destrói a instância Singleton e limpa o contexto (usado em testes de isolamento).
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->clearContext();
            self::$instance = null;
        }
    }

    /**
     * Define o tenant ativo. Aceita um objeto Tenant ou um ID (string).
     *
     * @param Tenant|string $tenant Entidade Tenant ou ID do tenant
     * @throws ValidationException Se o ID do tenant estiver vazio
     */
    public function setActiveTenant(Tenant|string $tenant): void
    {
        if ($tenant instanceof Tenant) {
            // Recebeu um objeto Tenant completo
            $id = trim($tenant->getId());
            if ($id === '') {
                throw ValidationException::forField('tenant', 'O ID da entidade Tenant não pode ser vazio.');
            }
            $this->activeTenant   = $tenant;
            $this->activeTenantId = $id;
        } else {
            // Recebeu apenas o ID como string
            $id = trim($tenant);
            if ($id === '') {
                throw ValidationException::forField('tenant_id', 'O ID do tenant não pode ser vazio.');
            }
            $this->activeTenant   = null;
            $this->activeTenantId = $id;
        }
    }

    /**
     * Retorna o ID do tenant atualmente ativo.
     *
     * @return string ID do tenant ativo
     * @throws TenantContextException Se nenhum tenant estiver ativo
     */
    public function getActiveTenantId(): string
    {
        $this->assertTenantActive();

        return (string)$this->activeTenantId;
    }

    /**
     * Retorna a entidade Tenant ativa (se foi definida como objeto).
     *
     * @return Tenant|null Entidade Tenant ou null se apenas o ID foi definido
     */
    public function getActiveTenant(): ?Tenant
    {
        return $this->activeTenant;
    }

    /**
     * Verifica se existe um tenant ativo no contexto atual.
     *
     * @return bool true se houver tenant ativo, false caso contrário
     */
    public function hasActiveTenant(): bool
    {
        return $this->activeTenantId !== null && $this->activeTenantId !== '';
    }

    /**
     * Remove o tenant ativo, limpando o contexto atual.
     */
    public function clearContext(): void
    {
        $this->activeTenant   = null;
        $this->activeTenantId = null;
    }

    /**
     * Garante que haja um tenant ativo; lança exceção se não houver.
     *
     * @throws TenantContextException Se nenhum tenant estiver ativo
     */
    public function assertTenantActive(): void
    {
        if (!$this->hasActiveTenant()) {
            throw TenantContextException::missingContext('assertTenantActive');
        }
    }

    /**
     * Verifica se o tenant ativo corresponde ao ID informado.
     *
     * @param string $tenantId ID do tenant a comparar
     * @throws TenantContextException Se o tenant ativo for diferente ou não existir
     */
    public function assertMatchesTenant(string $tenantId): void
    {
        $this->assertTenantActive();

        $cleanTenantId = trim($tenantId);
        if ($this->activeTenantId !== $cleanTenantId) {
            // Tenant ativo não corresponde ao esperado — possível violação de isolamento
            throw TenantContextException::mismatch((string)$this->activeTenantId, $cleanTenantId);
        }
    }

    /**
     * Executa um callback dentro de um contexto de tenant temporário.
     * Restaura o contexto anterior ao término (mesmo em caso de exceção).
     *
     * Uso típico: trocar de tenant durante uma operação específica sem
     * alterar permanentemente o contexto global da aplicação.
     *
     * @template T
     * @param Tenant|string $tenant Tenant temporário para o escopo da execução
     * @param callable(): T $callback Código a ser executado no contexto do tenant
     * @return T Resultado do callback
     */
    public function runInContext(Tenant|string $tenant, callable $callback): mixed
    {
        // Salva o contexto anterior para restauração posterior
        $previousTenant   = $this->activeTenant;
        $previousTenantId = $this->activeTenantId;

        // Ativa o novo tenant temporário
        $this->setActiveTenant($tenant);

        try {
            // Executa a operação no contexto do tenant temporário
            return $callback();
        } finally {
            // Restaura o contexto original (executado sempre, mesmo com exceção)
            $this->activeTenant   = $previousTenant;
            $this->activeTenantId = $previousTenantId;
        }
    }
}
