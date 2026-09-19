<?php

declare(strict_types=1);

namespace HrTech\Lps;

use HrTech\Contracts\SingletonInterface;
use HrTech\Domain\Entities\Tenant;
use HrTech\Lps\Enums\TenantSegment;
use RuntimeException;

/**
 * Class FeatureToggleManager
 *
 * Centralized feature toggle orchestrator for the Software Product Line (LPS).
 * Implements Singleton pattern with hierarchical resolution:
 * Tenant Override > Segment Default > Global Default.
 *
 * @package HrTech\Lps
 * @author Fernando Lopes Duarte (LPS Architecture Lead)
 */
class FeatureToggleManager implements SingletonInterface
{
    private static ?self $instance = null;

    /** @var array<string, array{description: string, globalDefault: bool}> */
    private array $registeredFeatures = [];

    /** @var array<string, array<string, bool>> Segment value => [feature => bool] */
    private array $segmentDefaults = [];

    /** @var array<string, array<string, bool>> Tenant ID => [feature => bool] */
    private array $tenantOverrides = [];

    /**
     * Protected constructor initializing the standard LPS feature catalog.
     */
    protected function __construct()
    {
        $this->initializeDefaultCatalog();
    }

    protected function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize singleton FeatureToggleManager.');
    }

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Registers baseline features and loads segment defaults from TenantSegment enum.
     */
    private function initializeDefaultCatalog(): void
    {
        $features = [
            'bank_of_hours' => 'Banco de Horas compensatório (Art. 59 §2 CLT)',
            'overtime_payout' => 'Pagamento pecuniário de horas extras (50% e 100%)',
            'risk_ppe_required' => 'Obrigatoriedade estrita de EPI e ASO (NR-6 / NR-7)',
            'flexible_benefits' => 'Cartão de benefícios flexíveis multicarteira',
            'd_and_o_insurance' => 'Seguro de Responsabilidade Civil D&O FinCorp',
            'chartered_transport' => 'Transporte Fretado para colaboradores industriais',
            'biometric_punch_mandatory' => 'Marcação de ponto com biometria facial obrigatória',
            'executive_health_plan' => 'Plano de Saúde Executivo sem coparticipação',
            'aggressive_bonus' => 'Metas e Bônus Agressivos com alavancagem até 120%',
            'strict_lgpd_audit' => 'Auditoria estrita de acessos a dados sensíveis (LGPD)',
            'fincorp_life_policy' => 'Apólice de Seguro de Vida em Grupo FinCorp Capital Alto',
        ];

        foreach ($features as $feature => $desc) {
            $this->registeredFeatures[$feature] = [
                'description' => $desc,
                'globalDefault' => false,
            ];
        }

        foreach (TenantSegment::cases() as $segment) {
            $this->segmentDefaults[$segment->value] = $segment->defaultFeatures();
        }
    }

    /**
     * Registers a new feature flag with optional segment defaults and global default.
     *
     * @param string $feature Feature identifier key.
     * @param string $description Feature functional purpose.
     * @param array<string|TenantSegment, bool> $segmentDefaults
     * @param bool $globalDefault
     */
    public function registerFeature(
        string $feature,
        string $description = '',
        array $segmentDefaults = [],
        bool $globalDefault = false
    ): void {
        $cleanFeature = strtolower(trim($feature));
        $this->registeredFeatures[$cleanFeature] = [
            'description' => $description,
            'globalDefault' => $globalDefault,
        ];

        foreach ($segmentDefaults as $segment => $enabled) {
            $segKey = $segment instanceof TenantSegment ? $segment->value : strtolower(trim((string)$segment));
            $this->segmentDefaults[$segKey][$cleanFeature] = (bool)$enabled;
        }
    }

    /**
     * Sets a tenant-specific override for a feature flag.
     */
    public function setTenantFeature(string $tenantId, string $feature, bool $enabled): void
    {
        $cleanTenant = trim($tenantId);
        $cleanFeature = strtolower(trim($feature));
        $this->tenantOverrides[$cleanTenant][$cleanFeature] = $enabled;
    }

    /**
     * Removes a tenant override, restoring segment default behavior.
     */
    public function removeTenantFeature(string $tenantId, string $feature): void
    {
        $cleanTenant = trim($tenantId);
        $cleanFeature = strtolower(trim($feature));
        unset($this->tenantOverrides[$cleanTenant][$cleanFeature]);
    }

    /**
     * Returns all overrides defined for a given tenant.
     *
     * @return array<string, bool>
     */
    public function getTenantOverrides(string $tenantId): array
    {
        $cleanTenant = trim($tenantId);
        return $this->tenantOverrides[$cleanTenant] ?? [];
    }

    /**
     * Clears tenant overrides (for one tenant or all tenants).
     */
    public function clearTenantOverrides(?string $tenantId = null): void
    {
        if ($tenantId !== null) {
            unset($this->tenantOverrides[trim($tenantId)]);
        } else {
            $this->tenantOverrides = [];
        }
    }

    /**
     * Sets a segment-level default for a feature.
     */
    public function setSegmentDefault(TenantSegment|string $segment, string $feature, bool $enabled): void
    {
        $segKey = $segment instanceof TenantSegment ? $segment->value : strtolower(trim((string)$segment));
        $cleanFeature = strtolower(trim($feature));
        $this->segmentDefaults[$segKey][$cleanFeature] = $enabled;
    }

    /**
     * Resolves whether a feature is enabled, following precedence:
     * Tenant Override > Segment Default > Global Default.
     */
    public function isFeatureEnabled(
        string $feature,
        ?Tenant $tenant = null,
        ?TenantSegment $segment = null,
        ?string $tenantId = null
    ): bool {
        $cleanFeature = strtolower(trim($feature));

        // 1. Check Tenant Override
        $tId = $tenant?->getId() ?? $tenantId;
        if ($tId !== null && isset($this->tenantOverrides[$tId][$cleanFeature])) {
            return $this->tenantOverrides[$tId][$cleanFeature];
        }

        // 2. Determine Segment
        $targetSegment = $segment;
        if ($targetSegment === null && $tenant !== null) {
            $segVal = method_exists($tenant, 'getSegment') ? $tenant->getSegment() : 'tech';
            $targetSegment = TenantSegment::fromValue($segVal);
        }

        if ($targetSegment !== null && isset($this->segmentDefaults[$targetSegment->value][$cleanFeature])) {
            return $this->segmentDefaults[$targetSegment->value][$cleanFeature];
        }

        // 3. Global Default Fallback
        return $this->registeredFeatures[$cleanFeature]['globalDefault'] ?? false;
    }

    /**
     * @return array<string, bool>
     */
    public function getSegmentFeatures(TenantSegment $segment): array
    {
        return $this->segmentDefaults[$segment->value] ?? [];
    }

    /**
     * @return array<string, array{description: string, globalDefault: bool}>
     */
    public function getRegisteredFeatures(): array
    {
        return $this->registeredFeatures;
    }

    /**
     * Alias for getRegisteredFeatures.
     *
     * @return array<string, array{description: string, globalDefault: bool}>
     */
    public function getAllFeatures(): array
    {
        return $this->getRegisteredFeatures();
    }
}
