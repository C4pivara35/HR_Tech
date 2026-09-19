<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Status of insurance policies in FinCorp Broker Portal.
 */
enum PolicyStatus: string
{
    case DRAFT = 'draft';
    case PROPOSAL_SUBMITTED = 'proposal_submitted';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case CLAIM_IN_PROGRESS = 'claim_in_progress';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Minuta / Em Cotação',
            self::PROPOSAL_SUBMITTED => 'Proposta Submetida à Seguradora',
            self::ACTIVE => 'Apólice Vigente / Ativa',
            self::SUSPENDED => 'Suspensa por Inadimplência ou Análise',
            self::EXPIRED => 'Apólice Expirada / Vencida',
            self::CANCELLED => 'Cancelada / Rescindida',
            self::CLAIM_IN_PROGRESS => 'Sinistro em Regulação',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Whether the policy provides active insurance coverage for claims.
     */
    public function canCoverClaim(): bool
    {
        return in_array($this, [self::ACTIVE, self::CLAIM_IN_PROGRESS], true);
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
