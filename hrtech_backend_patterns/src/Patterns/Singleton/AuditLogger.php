<?php

declare(strict_types=1);

namespace HrTech\Patterns\Singleton;

// ============================================================
// PADRÃO DE PROJETO: SINGLETON (Exemplo 1 de 2)
// ============================================================
// Intenção: Garantir que esta classe possua UMA ÚNICA instância
// em toda a aplicação, fornecendo um ponto de acesso global.
//
// Como identificar o Singleton neste arquivo:
//   1. Atributo estático privado ($instance) armazena a única instância
//   2. Construtor privado impede criação com "new AuditLogger()"
//   3. Método estático getInstance() é o único ponto de criação/acesso
//   4. __clone() e __wakeup() bloqueiam cópias indevidas
// ============================================================

use DateTimeImmutable;
use DateTimeZone;
use HrTech\Contracts\SingletonInterface;
use HrTech\Domain\Entities\AuditLog;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;

/**
 * Classe AuditLogger — PADRÃO SINGLETON
 *
 * Logger centralizado de auditoria e conformidade (LGPD).
 * Mantém uma cadeia de registros com hash criptográfico SHA-256
 * para garantir integridade e imutabilidade dos logs.
 *
 * Por ser Singleton, TODOS os módulos do sistema compartilham
 * a mesma instância, garantindo que nenhum log seja perdido.
 */
class AuditLogger implements SingletonInterface
{
    // -------------------------------------------------------
    // PASSO 1 DO SINGLETON: atributo estático que guarda
    // a única instância criada. Começa como null (vazio).
    // -------------------------------------------------------
    private static ?self $instance = null;

    /**
     * Cadeia de registros de auditoria em memória.
     *
     * @var array<int, AuditLog>
     */
    private array $logs = [];

    // -------------------------------------------------------
    // PASSO 2 DO SINGLETON: construtor PRIVADO.
    // Ninguém de fora pode chamar "new AuditLogger()".
    // Apenas o próprio getInstance() pode criar a instância.
    // -------------------------------------------------------
    private function __construct()
    {
    }

    // -------------------------------------------------------
    // PASSO 3 DO SINGLETON: bloqueia a clonagem.
    // Sem isso, "clone $logger" criaria uma segunda instância,
    // quebrando a garantia do padrão.
    // -------------------------------------------------------
    private function __clone()
    {
    }

    // -------------------------------------------------------
    // PASSO 4 DO SINGLETON: bloqueia a desserialização.
    // Sem isso, unserialize() poderia recriar o objeto a partir
    // de uma string, gerando uma segunda instância.
    // -------------------------------------------------------
    /**
     * Impede a recriação do Singleton via desserialização.
     *
     * @throws InvalidOperationException
     */
    public function __wakeup(): void
    {
        throw new InvalidOperationException('Não é possível desserializar o Singleton AuditLogger.');
    }

    // -------------------------------------------------------
    // PASSO 5 DO SINGLETON: método getInstance() — NÚCLEO DO PADRÃO.
    // Na primeira chamada, cria a instância. Nas demais, retorna
    // sempre a mesma que já foi criada (lazy initialization).
    // -------------------------------------------------------
    /**
     * Retorna a única instância global do AuditLogger (Singleton).
     * Cria a instância na primeira chamada; reutiliza nas seguintes.
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            // Primeira chamada: cria a instância única
            self::$instance = new self();
        }

        // Todas as demais chamadas retornam a mesma instância
        return self::$instance;
    }

    /**
     * Destrói a instância Singleton e limpa os logs (usado em testes de isolamento).
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->clearLogs();
            self::$instance = null;
        }
    }

    /**
     * Registra um evento de auditoria, adicionando-o à cadeia SHA-256.
     *
     * @param string $tenantId Identificador da empresa (tenant)
     * @param string $action Ação realizada (ex: "EMPLOYEE_UPDATED")
     * @param string $entityType Tipo da entidade afetada (ex: "Employee")
     * @param string $entityId Identificador único da entidade
     * @param array<string, mixed> $payload Dados do estado anterior/novo
     * @param string|null $userId Usuário ou sistema que executou a ação
     * @return AuditLog
     * @throws ValidationException
     */
    public function log(
        string $tenantId,
        string $action,
        string $entityType,
        string $entityId,
        array $payload,
        ?string $userId = null
    ): AuditLog {
        // Sanitização dos campos obrigatórios
        $cleanTenantId   = trim($tenantId);
        $cleanAction     = trim($action);
        $cleanEntityType = trim($entityType);
        $cleanEntityId   = trim($entityId);

        // Validação: nenhum campo essencial pode estar vazio
        if ($cleanTenantId === '') {
            throw ValidationException::forField('tenant_id', 'O ID do tenant de auditoria não pode ser vazio.');
        }
        if ($cleanAction === '') {
            throw ValidationException::forField('action', 'A ação de auditoria não pode ser vazia.');
        }
        if ($cleanEntityType === '') {
            throw ValidationException::forField('entity_type', 'O tipo de entidade de auditoria não pode ser vazio.');
        }
        if ($cleanEntityId === '') {
            throw ValidationException::forField('entity_id', 'O ID da entidade de auditoria não pode ser vazio.');
        }

        // Determina o ator (usuário ou sistema) responsável pela ação
        $actorUserId = $userId !== null && trim($userId) !== ''
            ? trim($userId)
            : (string)($payload['actor_user_id'] ?? $payload['user_id'] ?? 'SISTEMA');

        // Separa estado anterior do estado novo, se disponíveis no payload
        if (array_key_exists('previous_state', $payload) || array_key_exists('new_state', $payload)) {
            $previousState = (array)($payload['previous_state'] ?? []);
            $newState      = (array)($payload['new_state'] ?? []);
        } else {
            $previousState = [];
            $newState      = $payload;
        }

        $ipAddress = (string)($payload['ip_address'] ?? '127.0.0.1');
        $userAgent = (string)($payload['user_agent'] ?? 'HRTech-Core/1.0');

        $timestamp = isset($payload['timestamp']) && $payload['timestamp'] instanceof DateTimeImmutable
            ? $payload['timestamp']
            : new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $logId = (string)($payload['id'] ?? $this->generateUuid());

        // Hash da entrada anterior para encadeamento criptográfico (blockchain de logs)
        $lastLog      = end($this->logs);
        $previousHash = $lastLog !== false ? $lastLog->integrityHash : AuditLog::GENESIS_HASH;

        // Cria o registro de auditoria com hash encadeado
        $auditLog = AuditLog::record(
            id: $logId,
            tenantId: $cleanTenantId,
            actorUserId: $actorUserId,
            action: $cleanAction,
            entityType: $cleanEntityType,
            entityId: $cleanEntityId,
            previousState: $previousState,
            newState: $newState,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            previousHash: $previousHash,
            timestamp: $timestamp
        );

        // Adiciona o registro à cadeia em memória
        $this->logs[] = $auditLog;

        return $auditLog;
    }

    /**
     * Retorna todos os logs registrados, opcionalmente filtrados por tenant.
     *
     * @param string|null $tenantId Se informado, filtra apenas os logs deste tenant
     * @return array<int, AuditLog>
     */
    public function getLogs(?string $tenantId = null): array
    {
        if ($tenantId === null) {
            // Sem filtro: retorna todos os logs da cadeia
            return $this->logs;
        }

        // Com filtro: retorna apenas os logs do tenant informado
        $targetTenant = trim($tenantId);
        return array_values(array_filter(
            $this->logs,
            fn(AuditLog $l) => $l->belongsToTenant($targetTenant)
        ));
    }

    /**
     * Retorna o registro de auditoria mais recente, ou null se a cadeia estiver vazia.
     *
     * @param string|null $tenantId Filtro opcional por tenant
     * @return AuditLog|null
     */
    public function getLastLog(?string $tenantId = null): ?AuditLog
    {
        $filtered = $this->getLogs($tenantId);
        $last     = end($filtered);

        return $last !== false ? $last : null;
    }

    /**
     * Retorna o total de logs armazenados na cadeia.
     *
     * @param string|null $tenantId Filtro opcional por tenant
     * @return int
     */
    public function count(?string $tenantId = null): int
    {
        return count($this->getLogs($tenantId));
    }

    /**
     * Verifica a integridade criptográfica da cadeia SHA-256 de logs.
     * Confirma que cada bloco referencia corretamente o hash do bloco anterior
     * e que nenhum registro foi adulterado.
     *
     * @return bool true se a cadeia estiver íntegra, false se adulterada
     */
    public function verifyChainIntegrity(): bool
    {
        if (empty($this->logs)) {
            return true; // Cadeia vazia é sempre válida
        }

        // Começa pelo hash gênese (bloco inicial da cadeia)
        $expectedPrevHash = AuditLog::GENESIS_HASH;

        foreach ($this->logs as $log) {
            // Verifica se o ponteiro de hash anterior está correto
            $actualPrev = $log->previousHash ?? AuditLog::GENESIS_HASH;
            if ($actualPrev !== $expectedPrevHash) {
                return false; // Cadeia quebrada: hash anterior não confere
            }

            // Verifica se o conteúdo do registro não foi alterado
            if (!$log->verifyIntegrity($expectedPrevHash)) {
                return false; // Registro adulterado detectado
            }

            // Avança para o próximo elo da cadeia
            $expectedPrevHash = $log->integrityHash;
        }

        return true;
    }

    /**
     * Remove todos os registros de auditoria da cadeia em memória.
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Gera um UUID v4 pseudoaleatório para identificação única dos logs.
     */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // versão 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variante RFC 4122

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
