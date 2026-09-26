<?php

declare(strict_types=1);

namespace HrTech\Domain\Traits;

use DateTimeImmutable;

/**
 * Trait EntityBaseTrait
 *
 * Implementa a técnica de compactação de código focada em reuso (DRY).
 * Fornece a base comum para as entidades de domínio da plataforma,
 * garantindo consistência e reduzindo código duplicado nas classes principais.
 *
 * Centraliza os atributos exigidos pelas interfaces:
 * - IdentifiableInterface ($id)
 * - TenantScopedInterface ($tenantId)
 * - AuditableInterface ($createdAt, $updatedAt)
 */
trait EntityBaseTrait
{
    /** @var string Identificador único da entidade */
    protected string $id;

    /** @var string Identificador do tenant ao qual a entidade pertence */
    protected string $tenantId;

    /** @var DateTimeImmutable Data e hora de criação do registro */
    protected DateTimeImmutable $createdAt;

    /** @var DateTimeImmutable|null Data e hora da última atualização do registro */
    protected ?DateTimeImmutable $updatedAt = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenantId === trim($tenantId);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
