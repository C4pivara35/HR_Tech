<?php

declare(strict_types=1);

namespace HrTech\Lps;

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Contracts\OvertimeStrategyInterface;
use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Entities\EquipmentASO;
use HrTech\Domain\Entities\Tenant;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\BenefitType;
use HrTech\Lps\Enums\TenantSegment;
use HrTech\Patterns\Strategy\BenefitDiscount\HealthPlanStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\MealVoucherStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\TransportationVoucherStrategy;
use HrTech\Patterns\Strategy\Overtime\BankHoursStrategy;
use HrTech\Patterns\Strategy\Overtime\Standard50Strategy;
use HrTech\Patterns\Strategy\Overtime\Sunday100Strategy;
use HrTech\Patterns\Strategy\Performance\Evaluation360Strategy;
use HrTech\Patterns\Strategy\Performance\KpiStrategy;
use HrTech\Patterns\Strategy\Performance\OkrStrategy;

/**
 * Class LpsVariabilityEngine
 *
 * Domain business rule resolver and strategy dispatcher for the Software Product Line.
 * Dynamically resolves operational eligibility, regulatory compliance (NR-6, NR-7, Portaria 671),
 * and polymorphic strategy pattern instantiation based on tenant profiles.
 *
 * @package HrTech\Lps
 * @author Fernando Lopes Duarte (LPS Architecture Lead)
 */
class LpsVariabilityEngine
{
    private FeatureToggleManager $toggleManager;

    public function __construct(?FeatureToggleManager $toggleManager = null)
    {
        $this->toggleManager = $toggleManager ?? FeatureToggleManager::getInstance();
    }

    /**
     * Resolves a Tenant, TenantSegment or string identifier into a TenantSegment enum case.
     */
    public function resolveSegment(Tenant|TenantSegment|string $tenantOrSegment): TenantSegment
    {
        if ($tenantOrSegment instanceof TenantSegment) {
            return $tenantOrSegment;
        }

        if ($tenantOrSegment instanceof Tenant) {
            $segVal = method_exists($tenantOrSegment, 'getSegment')
                ? $tenantOrSegment->getSegment()
                : 'tech';
            return TenantSegment::fromValue($segVal);
        }

        return TenantSegment::fromValue($tenantOrSegment);
    }

    /**
     * Checks if a feature flag is active for the given tenant or segment context.
     */
    public function isFeatureActive(string $feature, Tenant|TenantSegment|string $tenantOrSegment): bool
    {
        $segment = $this->resolveSegment($tenantOrSegment);
        $tenant = $tenantOrSegment instanceof Tenant ? $tenantOrSegment : null;
        $tenantId = $tenant?->getId() ?? (is_string($tenantOrSegment) && !in_array(strtolower(trim($tenantOrSegment)), ['tech', 'industria', 'financeiro'], true) ? $tenantOrSegment : null);

        return $this->toggleManager->isFeatureEnabled(
            feature: $feature,
            tenant: $tenant,
            segment: $segment,
            tenantId: $tenantId
        );
    }

    public function isBankOfHoursActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('bank_of_hours', $tenant);
    }

    public function isOvertimePayoutActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('overtime_payout', $tenant);
    }

    public function isPpeMandatory(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('risk_ppe_required', $tenant);
    }

    public function isFlexibleBenefitsActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('flexible_benefits', $tenant);
    }

    public function isDAndOInsuranceActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('d_and_o_insurance', $tenant);
    }

    public function isCharteredTransportActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('chartered_transport', $tenant);
    }

    public function isBiometricPunchMandatory(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('biometric_punch_mandatory', $tenant);
    }

    public function isExecutiveHealthPlanActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('executive_health_plan', $tenant);
    }

    public function isAggressiveBonusActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('aggressive_bonus', $tenant);
    }

    public function isStrictLgpdAuditActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('strict_lgpd_audit', $tenant);
    }

    public function isFinCorpLifePolicyActive(Tenant|TenantSegment|string $tenant): bool
    {
        return $this->isFeatureActive('fincorp_life_policy', $tenant);
    }

    /**
     * Evaluates collaborator work eligibility based on segment occupational health & safety regulations.
     * In Indústria: strictly blocks work if ASO is missing, expired, CA is expired, or collaborator is unfit.
     * In Tech/Financeiro: occupational PPE is waived and collaborator is cleared.
     *
     * @return array{allowed: bool, reason: string, code: string}
     */
    public function validateWorkEligibility(
        Employee $employee,
        ?EquipmentASO $aso,
        Tenant|TenantSegment|string $tenant
    ): array {
        if (!$this->isPpeMandatory($tenant)) {
            return [
                'allowed' => true,
                'reason' => 'Work cleared: PPE and occupational ASO waived for this segment profile.',
                'code' => 'PPE_WAIVED',
            ];
        }

        // Under Indústria profile, strict compliance is enforced:
        if ($aso === null) {
            return [
                'allowed' => false,
                'reason' => 'Work blocked: No ASO occupational health record registered for industrial collaborator.',
                'code' => 'ASO_MISSING',
            ];
        }

        if (!$aso->isFit) {
            return [
                'allowed' => false,
                'reason' => 'Work blocked: Collaborator clinically deemed UNFIT (Inapto) in ASO examination.',
                'code' => 'ASO_UNFIT',
            ];
        }

        if ($aso->isExamExpired()) {
            return [
                'allowed' => false,
                'reason' => 'Work blocked: ASO medical examination certificate is expired (NR-7 compliance breach).',
                'code' => 'ASO_EXPIRED',
            ];
        }

        if ($aso->isCaExpired()) {
            return [
                'allowed' => false,
                'reason' => 'Work blocked: Equipment CA certification has expired (NR-6 safety violation).',
                'code' => 'PPE_CA_EXPIRED',
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Work cleared: Collaborator holds valid ASO and compliant PPE.',
            'code' => 'COMPLIANT',
        ];
    }

    /**
     * Validates electronic time punch eligibility.
     * In Financeiro: strictly requires biometric verification.
     *
     * @return array{allowed: bool, reason: string}
     */
    public function validateTimePunch(
        TimeLog $punch,
        Tenant|TenantSegment|string $tenant,
        bool $biometricVerified = false
    ): array {
        if ($this->isBiometricPunchMandatory($tenant) && !$biometricVerified) {
            return [
                'allowed' => false,
                'reason' => 'Punch rejected: Financial sector profile strictly requires biometric facial/fingerprint verification under Portaria 671.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Punch accepted.',
        ];
    }

    /**
     * Factory dispatcher for Overtime Compensation Strategy.
     */
    public function resolveOvertimeStrategy(
        Tenant|TenantSegment|string $tenant,
        bool $isSundayOrHoliday = false
    ): OvertimeStrategyInterface {
        if ($this->isBankOfHoursActive($tenant)) {
            return new BankHoursStrategy();
        }

        if ($isSundayOrHoliday) {
            return new Sunday100Strategy();
        }

        return new Standard50Strategy();
    }

    /**
     * Factory dispatcher for Performance Scoring Strategy.
     */
    public function resolvePerformanceStrategy(
        Tenant|TenantSegment|string $tenant
    ): PerformanceStrategyInterface {
        if ($this->isAggressiveBonusActive($tenant)) {
            return new KpiStrategy(allowOverachievement: true);
        }

        $segment = $this->resolveSegment($tenant);
        return match ($segment) {
            TenantSegment::TECH => new OkrStrategy(allowOverachievement: true),
            TenantSegment::INDUSTRIA => new Evaluation360Strategy(),
            TenantSegment::FINANCEIRO => new KpiStrategy(allowOverachievement: true),
        };
    }

    /**
     * Factory dispatcher for Benefit Discount Strategy.
     */
    public function resolveBenefitDiscountStrategy(
        Tenant|TenantSegment|string $tenant,
        BenefitType $type
    ): BenefitDiscountStrategyInterface {
        return match ($type) {
            BenefitType::TRANSPORTATION,
            BenefitType::TRANSPORTATION_VOUCHER => new TransportationVoucherStrategy(),
            BenefitType::HEALTH_PLAN => new HealthPlanStrategy(),
            BenefitType::MEAL_VOUCHER,
            BenefitType::FOOD_VOUCHER => new MealVoucherStrategy(),
            default => new MealVoucherStrategy(),
        };
    }
}
