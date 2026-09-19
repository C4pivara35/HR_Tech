<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 4 Adversarial LPS Variability Test Suite
 *
 * Tier 5 Empirical Stress & Adversarial Challenge Harness:
 *  - Suite 1: Segment Baseline Features & Alias Resolution (Tech, Indústria, Financeiro)
 *  - Suite 2: Multi-Tenant Override Precedence & Tenant Isolation Stress
 *  - Suite 3: Indústria Work Eligibility Adversarial Edge Cases (NR-6 / NR-7)
 *  - Suite 4: Tech and Financeiro Work Eligibility Waiver & Adversarial Inversion
 *  - Suite 5: Financeiro Biometric Punch Enforcement (Portaria 671) & Token Guarding
 *  - Suite 6: Polymorphic Strategy Dispatch (Overtime, Performance Bonus & Benefit Deductions)
 *  - Suite 7: High-Throughput Fuzzing, Invariant Verification & Memory Profiling (10,000+ evaluations)
 *
 * Usage: php tests/m4_adversarial_lps.php
 */

require_once dirname(__DIR__) . '/src/Autoloader.php';
\HrTech\Autoloader::registerDefault();

use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Department;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Entities\EquipmentASO;
use HrTech\Domain\Entities\Role;
use HrTech\Domain\Entities\Tenant;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\BenefitType;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Domain\Enums\ExamType;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\Cnpj;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\ValidationException;
use HrTech\Lps\Enums\TenantSegment;
use HrTech\Lps\FeatureToggleManager;
use HrTech\Lps\LpsVariabilityEngine;
use HrTech\Patterns\Strategy\BenefitDiscount\HealthPlanStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\MealVoucherStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\TransportationVoucherStrategy;
use HrTech\Patterns\Strategy\Overtime\BankHoursStrategy;
use HrTech\Patterns\Strategy\Overtime\Standard50Strategy;
use HrTech\Patterns\Strategy\Overtime\Sunday100Strategy;
use HrTech\Patterns\Strategy\Performance\Evaluation360Strategy;
use HrTech\Patterns\Strategy\Performance\KpiStrategy;
use HrTech\Patterns\Strategy\Performance\OkrStrategy;

final class M4AdversarialLpsHarness
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;
    private int $failedAssertions = 0;
    /** @var array<int, array{suite: string, assertion: string, details: string}> */
    private array $anomalies = [];
    private string $currentSuite = '';
    private float $startTime;
    private int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    public function suite(string $name): void
    {
        $this->currentSuite = $name;
        echo "\n\033[1;34m▶ Suite: {$name}\033[0m\n";
    }

    public function assertTrue(bool $condition, string $message, ?string $failDetails = null): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  \033[32m✔\033[0m {$message}\n";
        } else {
            $this->failedAssertions++;
            $details = $failDetails ?? 'Expected TRUE, got FALSE';
            echo "  \033[31m✘ FAIL: {$message} ({$details})\033[0m\n";
            $this->anomalies[] = [
                'suite' => $this->currentSuite,
                'assertion' => $message,
                'details' => $details,
            ];
        }
    }

    public function assertFalse(bool $condition, string $message, ?string $failDetails = null): void
    {
        $this->assertTrue(!$condition, $message, $failDetails ?? 'Expected FALSE, got TRUE');
    }

    public function assertEquals(mixed $expected, mixed $actual, string $message): void
    {
        $this->totalAssertions++;
        if ($expected === $actual) {
            $this->passedAssertions++;
            echo "  \033[32m✔\033[0m {$message}\n";
        } else {
            $this->failedAssertions++;
            $expStr = var_export($expected, true);
            $actStr = var_export($actual, true);
            $details = "Expected: {$expStr}, got: {$actStr}";
            echo "  \033[31m✘ FAIL: {$message} ({$details})\033[0m\n";
            $this->anomalies[] = [
                'suite' => $this->currentSuite,
                'assertion' => $message,
                'details' => $details,
            ];
        }
    }

    public function assertCloseTo(float $expected, float $actual, float $delta, string $message): void
    {
        $this->totalAssertions++;
        $diff = abs($expected - $actual);
        if ($diff <= $delta) {
            $this->passedAssertions++;
            echo "  \033[32m✔\033[0m {$message} [actual {$actual} ~= expected {$expected}]\n";
        } else {
            $this->failedAssertions++;
            $details = "Expected {$expected} ±{$delta}, got {$actual} (diff: {$diff})";
            echo "  \033[31m✘ FAIL: {$message} ({$details})\033[0m\n";
            $this->anomalies[] = [
                'suite' => $this->currentSuite,
                'assertion' => $message,
                'details' => $details,
            ];
        }
    }

    public function assertThrows(callable $callback, string $expectedExceptionClass, string $message): void
    {
        $this->totalAssertions++;
        try {
            $callback();
            $this->failedAssertions++;
            $details = "Expected exception {$expectedExceptionClass}, none thrown";
            echo "  \033[31m✘ FAIL: {$message} ({$details})\033[0m\n";
            $this->anomalies[] = [
                'suite' => $this->currentSuite,
                'assertion' => $message,
                'details' => $details,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof $expectedExceptionClass) {
                $this->passedAssertions++;
                echo "  \033[32m✔\033[0m {$message} (Caught expected " . get_class($e) . ")\n";
            } else {
                $this->failedAssertions++;
                $details = "Expected {$expectedExceptionClass}, caught " . get_class($e) . ": {$e->getMessage()}";
                echo "  \033[31m✘ FAIL: {$message} ({$details})\033[0m\n";
                $this->anomalies[] = [
                    'suite' => $this->currentSuite,
                    'assertion' => $message,
                    'details' => $details,
                ];
            }
        }
    }

    // --------------------------------------------------------------------------
    // Mathematical Oracles (Modulo 11) for Valid Test Artifacts
    // --------------------------------------------------------------------------

    public static function generateValidCpf(?string $customNine = null): string
    {
        if ($customNine !== null) {
            $nine = str_pad(substr($customNine, 0, 9), 9, '0', STR_PAD_LEFT);
        } else {
            do {
                $nine = '';
                for ($i = 0; $i < 9; $i++) {
                    $nine .= (string)random_int(0, 9);
                }
            } while (str_repeat($nine[0], 9) === $nine);
        }

        $sum1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum1 += ((int)$nine[$i]) * (10 - $i);
        }
        $r1 = $sum1 % 11;
        $d1 = ($r1 < 2) ? 0 : (11 - $r1);

        $ten = $nine . $d1;
        $sum2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum2 += ((int)$ten[$i]) * (11 - $i);
        }
        $r2 = $sum2 % 11;
        $d2 = ($r2 < 2) ? 0 : (11 - $r2);

        return $nine . $d1 . $d2;
    }

    public static function generateValidCnpj(?string $customRoot = null, string $branch = '0001'): string
    {
        if ($customRoot !== null) {
            $root = str_pad(substr($customRoot, 0, 8), 8, '0', STR_PAD_LEFT);
        } else {
            do {
                $root = '';
                for ($i = 0; $i < 8; $i++) {
                    $root .= (string)random_int(0, 9);
                }
            } while (str_repeat($root[0], 8) === $root);
        }

        $twelve = $root . $branch;

        $w1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum1 += ((int)$twelve[$i]) * $w1[$i];
        }
        $r1 = $sum1 % 11;
        $d1 = ($r1 < 2) ? 0 : (11 - $r1);

        $thirteen = $twelve . $d1;
        $w2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum2 += ((int)$thirteen[$i]) * $w2[$i];
        }
        $r2 = $sum2 % 11;
        $d2 = ($r2 < 2) ? 0 : (11 - $r2);

        return $twelve . $d1 . $d2;
    }

    public static function makeCnpj(?string $root = null, string $branch = '0001'): Cnpj
    {
        return new Cnpj(self::generateValidCnpj($root, $branch));
    }

    public static function makeCpf(?string $nine = null): Cpf
    {
        return new Cpf(self::generateValidCpf($nine));
    }

    // ==========================================================================
    // SUITE 1: SEGMENT BASELINE FEATURE CATALOG & RESOLUTION
    // ==========================================================================
    public function testSegmentBaselinesAndAliases(): void
    {
        $this->suite('1. Segment Baseline Feature Catalog & Strict Segregation');

        FeatureToggleManager::resetInstance();
        $toggleManager = FeatureToggleManager::getInstance();
        $engine = new LpsVariabilityEngine($toggleManager);

        // 1.1 Complete 11-Feature Matrix across all 3 Segments
        $expectedBaselines = [
            TenantSegment::TECH->value => [
                'bank_of_hours' => true,
                'overtime_payout' => false,
                'risk_ppe_required' => false,
                'flexible_benefits' => true,
                'd_and_o_insurance' => true,
                'biometric_punch_mandatory' => false,
                'chartered_transport' => false,
                'executive_health_plan' => false,
                'aggressive_bonus' => false,
                'strict_lgpd_audit' => false,
                'fincorp_life_policy' => false,
            ],
            TenantSegment::INDUSTRIA->value => [
                'bank_of_hours' => false,
                'overtime_payout' => true,
                'risk_ppe_required' => true,
                'flexible_benefits' => false,
                'd_and_o_insurance' => false,
                'biometric_punch_mandatory' => false,
                'chartered_transport' => true,
                'executive_health_plan' => false,
                'aggressive_bonus' => false,
                'strict_lgpd_audit' => false,
                'fincorp_life_policy' => false,
            ],
            TenantSegment::FINANCEIRO->value => [
                'bank_of_hours' => false,
                'overtime_payout' => true,
                'risk_ppe_required' => false,
                'flexible_benefits' => false,
                'd_and_o_insurance' => false,
                'biometric_punch_mandatory' => true,
                'chartered_transport' => false,
                'executive_health_plan' => true,
                'aggressive_bonus' => true,
                'strict_lgpd_audit' => true,
                'fincorp_life_policy' => true,
            ],
        ];

        foreach (TenantSegment::cases() as $segment) {
            $segmentFeatures = $segment->defaultFeatures();
            $this->assertEquals(11, count($segmentFeatures), "Segment {$segment->name} defines exactly 11 default features");

            foreach ($expectedBaselines[$segment->value] as $feature => $expectedBool) {
                $actualFromEnum = $segmentFeatures[$feature] ?? null;
                $this->assertEquals(
                    $expectedBool,
                    $actualFromEnum,
                    "Segment {$segment->name} enum default for '{$feature}' is " . ($expectedBool ? 'TRUE' : 'FALSE')
                );

                $actualFromManager = $toggleManager->isFeatureEnabled($feature, segment: $segment);
                $this->assertEquals(
                    $expectedBool,
                    $actualFromManager,
                    "FeatureToggleManager default for '{$feature}' in {$segment->name} matches enum default"
                );

                $actualFromEngine = $engine->isFeatureActive($feature, $segment);
                $this->assertEquals(
                    $expectedBool,
                    $actualFromEngine,
                    "LpsVariabilityEngine::isFeatureActive for '{$feature}' in {$segment->name} matches expected baseline"
                );
            }
        }

        // 1.2 Dedicated Engine Method Invariants
        $this->assertTrue($engine->isBankOfHoursActive(TenantSegment::TECH), 'Tech has Bank of Hours active');
        $this->assertFalse($engine->isBankOfHoursActive(TenantSegment::INDUSTRIA), 'Indústria has Bank of Hours inactive');
        $this->assertFalse($engine->isBankOfHoursActive(TenantSegment::FINANCEIRO), 'Financeiro has Bank of Hours inactive');

        $this->assertFalse($engine->isOvertimePayoutActive(TenantSegment::TECH), 'Tech has Overtime Payout inactive');
        $this->assertTrue($engine->isOvertimePayoutActive(TenantSegment::INDUSTRIA), 'Indústria has Overtime Payout active');
        $this->assertTrue($engine->isOvertimePayoutActive(TenantSegment::FINANCEIRO), 'Financeiro has Overtime Payout active');

        $this->assertFalse($engine->isPpeMandatory(TenantSegment::TECH), 'Tech has PPE waived (false)');
        $this->assertTrue($engine->isPpeMandatory(TenantSegment::INDUSTRIA), 'Indústria has PPE mandatory (true)');
        $this->assertFalse($engine->isPpeMandatory(TenantSegment::FINANCEIRO), 'Financeiro has PPE waived (false)');

        $this->assertTrue($engine->isFlexibleBenefitsActive(TenantSegment::TECH), 'Tech has Flexible Benefits active');
        $this->assertFalse($engine->isFlexibleBenefitsActive(TenantSegment::INDUSTRIA), 'Indústria has Flexible Benefits inactive');

        $this->assertTrue($engine->isDAndOInsuranceActive(TenantSegment::TECH), 'Tech has D&O Insurance active');
        $this->assertFalse($engine->isDAndOInsuranceActive(TenantSegment::FINANCEIRO), 'Financeiro has D&O Insurance inactive');

        $this->assertTrue($engine->isCharteredTransportActive(TenantSegment::INDUSTRIA), 'Indústria has Chartered Transport active');
        $this->assertFalse($engine->isCharteredTransportActive(TenantSegment::TECH), 'Tech has Chartered Transport inactive');

        $this->assertTrue($engine->isBiometricPunchMandatory(TenantSegment::FINANCEIRO), 'Financeiro has Biometric Punch mandatory');
        $this->assertFalse($engine->isBiometricPunchMandatory(TenantSegment::TECH), 'Tech has Biometric Punch optional');
        $this->assertFalse($engine->isBiometricPunchMandatory(TenantSegment::INDUSTRIA), 'Indústria has Biometric Punch optional');

        $this->assertTrue($engine->isExecutiveHealthPlanActive(TenantSegment::FINANCEIRO), 'Financeiro has Executive Health Plan active');
        $this->assertTrue($engine->isAggressiveBonusActive(TenantSegment::FINANCEIRO), 'Financeiro has Aggressive Bonus active');
        $this->assertTrue($engine->isStrictLgpdAuditActive(TenantSegment::FINANCEIRO), 'Financeiro has Strict LGPD Audit active');
        $this->assertTrue($engine->isFinCorpLifePolicyActive(TenantSegment::FINANCEIRO), 'Financeiro has FinCorp Life Policy active');

        // 1.3 Segment Aliases & Tolerant Parsing
        $aliasMatrix = [
            [TenantSegment::TECH, ['tech', 'TECH', '  tech  ', 'tecnologia', 'TECNOLOGIA', 'startup', 'STARTUP']],
            [TenantSegment::INDUSTRIA, ['industria', 'INDUSTRIA', 'industry', 'INDUSTRY', 'manufatura', 'fabril', '  manufatura  ']],
            [TenantSegment::FINANCEIRO, ['financeiro', 'FINANCEIRO', 'financial', 'financas', 'banco', 'BANCO', '  banco  ']],
        ];

        foreach ($aliasMatrix as [$expectedSeg, $aliases]) {
            foreach ($aliases as $alias) {
                $parsed = TenantSegment::fromValue($alias);
                $this->assertEquals($expectedSeg, $parsed, "TenantSegment::fromValue parses alias '{$alias}' correctly");
            }
        }

        // Invalid segment names throw ValueError
        $invalidSegments = ['unknown_segment', 'retail', 'agronegocio', 'saude', ''];
        foreach ($invalidSegments as $badSeg) {
            $this->assertThrows(function () use ($badSeg) {
                TenantSegment::fromValue($badSeg);
            }, \ValueError::class, "TenantSegment::fromValue strictly rejects invalid segment: '{$badSeg}'");
        }

        // 1.4 Resolution via Tenant Entity
        $tenantTech = new Tenant(
            id: 't-ent-tech',
            cnpj: new Cnpj('11.222.333/0001-81'),
            corporateName: 'Tech Entity Ltda',
            tradingName: 'Tech Entity',
            segment: 'tech'
        );
        $this->assertEquals(TenantSegment::TECH, $engine->resolveSegment($tenantTech), 'Engine resolves segment from Tenant entity');
        $this->assertTrue($engine->isBankOfHoursActive($tenantTech), 'Engine resolves BankOfHours=true from Tenant entity');
    }

    // ==========================================================================
    // SUITE 2: MULTI-TENANT OVERRIDE PRECEDENCE & ISOLATION
    // ==========================================================================
    public function testTenantOverridesAndIsolation(): void
    {
        $this->suite('2. Multi-Tenant Override Precedence & Isolation Stress');

        FeatureToggleManager::resetInstance();
        $mgr = FeatureToggleManager::getInstance();
        $engine = new LpsVariabilityEngine($mgr);

        $tenantA = new Tenant(id: 'tenant-alpha', cnpj: self::makeCnpj('22333444'), corporateName: 'Alpha Corp', tradingName: 'Alpha', segment: 'tech');
        $tenantB = new Tenant(id: 'tenant-beta', cnpj: self::makeCnpj('33444555'), corporateName: 'Beta Corp', tradingName: 'Beta', segment: 'tech');
        $tenantInd = new Tenant(id: 'tenant-ind', cnpj: self::makeCnpj('44555666'), corporateName: 'Ind Corp', tradingName: 'Ind', segment: 'industria');

        // 2.1 Precedence: Tenant Override > Segment Default
        // Baseline: Tech has bank_of_hours = true
        $this->assertTrue($mgr->isFeatureEnabled('bank_of_hours', tenant: $tenantA), 'Baseline: Tenant Alpha has bank_of_hours = TRUE');

        // Override Alpha to FALSE
        $mgr->setTenantFeature('tenant-alpha', 'bank_of_hours', false);
        $this->assertFalse(
            $mgr->isFeatureEnabled('bank_of_hours', tenant: $tenantA),
            'Tenant override strictly inverts bank_of_hours from TRUE to FALSE for Tenant Alpha'
        );
        $this->assertFalse(
            $engine->isBankOfHoursActive($tenantA),
            'LpsVariabilityEngine reflects Tenant Alpha override of bank_of_hours = FALSE'
        );

        // 2.2 Strict Tenant Isolation (Beta must NOT be affected)
        $this->assertTrue(
            $mgr->isFeatureEnabled('bank_of_hours', tenant: $tenantB),
            'Tenant Beta remains isolated and retains Tech segment default bank_of_hours = TRUE'
        );
        $this->assertTrue(
            $engine->isBankOfHoursActive($tenantB),
            'LpsVariabilityEngine retains bank_of_hours = TRUE for Tenant Beta'
        );

        // 2.3 Precedence: Turning ON a feature that is OFF by segment default
        // Baseline: Tech has overtime_payout = false
        $this->assertFalse($mgr->isFeatureEnabled('overtime_payout', tenant: $tenantA), 'Baseline: Tenant Alpha overtime_payout is FALSE');
        $mgr->setTenantFeature('tenant-alpha', 'overtime_payout', true);
        $this->assertTrue(
            $mgr->isFeatureEnabled('overtime_payout', tenant: $tenantA),
            'Tenant override turns ON overtime_payout for Tenant Alpha'
        );
        $this->assertTrue(
            $engine->isOvertimePayoutActive($tenantA),
            'LpsVariabilityEngine reflects overtime_payout = TRUE for Tenant Alpha'
        );
        $this->assertFalse(
            $engine->isOvertimePayoutActive($tenantB),
            'Tenant Beta remains unaffected: overtime_payout is still FALSE'
        );

        // 2.4 Removal of Override Restores Segment Default
        $mgr->removeTenantFeature('tenant-alpha', 'bank_of_hours');
        $this->assertTrue(
            $mgr->isFeatureEnabled('bank_of_hours', tenant: $tenantA),
            'Removing override restores Tech segment default of bank_of_hours = TRUE for Tenant Alpha'
        );

        // 2.5 Batch Overrides and Clear Operations
        $mgr->setTenantFeature('tenant-ind', 'bank_of_hours', true);
        $mgr->setTenantFeature('tenant-ind', 'chartered_transport', false);
        $this->assertTrue($mgr->isFeatureEnabled('bank_of_hours', tenant: $tenantInd), 'Tenant Indústria has bank_of_hours overridden to TRUE');
        $this->assertFalse($mgr->isFeatureEnabled('chartered_transport', tenant: $tenantInd), 'Tenant Indústria has chartered_transport overridden to FALSE');

        // Clear specific tenant
        $mgr->clearTenantOverrides('tenant-ind');
        $this->assertFalse($mgr->isFeatureEnabled('bank_of_hours', tenant: $tenantInd), 'After clearTenantOverrides(tenantInd), bank_of_hours reverted to default FALSE');
        $this->assertTrue($mgr->isFeatureEnabled('chartered_transport', tenant: $tenantInd), 'After clearTenantOverrides(tenantInd), chartered_transport reverted to default TRUE');

        // Overrides on tenant-alpha still exist
        $this->assertTrue($mgr->isFeatureEnabled('overtime_payout', tenant: $tenantA), 'Tenant Alpha overtime_payout override remains active');

        // Global clear of all tenant overrides
        $mgr->clearTenantOverrides();
        $this->assertFalse($mgr->isFeatureEnabled('overtime_payout', tenant: $tenantA), 'Global clearTenantOverrides() reset Tenant Alpha to default FALSE');

        // 2.6 Dynamic Custom Feature Registration
        $mgr->registerFeature(
            feature: 'generative_ai_assistant',
            description: 'AI-assisted HR automation',
            segmentDefaults: [
                TenantSegment::TECH->value => true,
                TenantSegment::INDUSTRIA->value => false,
                TenantSegment::FINANCEIRO->value => true,
            ],
            globalDefault: false
        );

        $this->assertTrue($mgr->isFeatureEnabled('generative_ai_assistant', segment: TenantSegment::TECH), 'Custom feature registered and active in Tech');
        $this->assertFalse($mgr->isFeatureEnabled('generative_ai_assistant', segment: TenantSegment::INDUSTRIA), 'Custom feature inactive in Indústria');
        $this->assertTrue($mgr->isFeatureEnabled('generative_ai_assistant', segment: TenantSegment::FINANCEIRO), 'Custom feature active in Financeiro');

        // Override custom feature for Indústria tenant
        $mgr->setTenantFeature('tenant-ind', 'generative_ai_assistant', true);
        $this->assertTrue(
            $mgr->isFeatureEnabled('generative_ai_assistant', tenant: $tenantInd),
            'Tenant Indústria successfully overrides custom feature to TRUE'
        );

        // 2.7 Case Insensitivity and Whitespace Normalization in Feature Keys
        $mgr->setTenantFeature('tenant-alpha', '  FLEXIBLE_BENEFITS  ', false);
        $this->assertFalse(
            $mgr->isFeatureEnabled('flexible_benefits', tenant: $tenantA),
            'Feature key normalizes whitespace and uppercase: flexible_benefits is FALSE'
        );
    }

    // ==========================================================================
    // SUITE 3: INDÚSTRIA WORK ELIGIBILITY ADVERSARIAL EDGE CASES (NR-6 / NR-7)
    // ==========================================================================
    public function testIndustriaWorkEligibilityAdversarial(): void
    {
        $this->suite('3. Indústria Work Eligibility Adversarial Edge Cases (NR-6 / NR-7)');

        FeatureToggleManager::resetInstance();
        $engine = new LpsVariabilityEngine();

        $indWorker = new Employee(
            id: 'emp-ind-adv',
            tenantId: 'tenant-ind',
            cpf: self::makeCpf('123456789'),
            fullName: 'Operador Torno CNC',
            email: 'operador@fabrica.com',
            phone: '11987654321',
            birthDate: '1985-06-15',
            admissionDate: '2020-01-10',
            departmentId: 'd-usinagem',
            roleId: 'r-operador',
            baseSalary: 4200,
            employmentType: 'CLT'
        );

        // 3.1 Edge Case 1: Missing ASO Record (null)
        $resMissing = $engine->validateWorkEligibility($indWorker, null, TenantSegment::INDUSTRIA);
        $this->assertFalse($resMissing['allowed'], 'Missing ASO strictly blocks industrial worker');
        $this->assertEquals('ASO_MISSING', $resMissing['code'], 'Missing ASO returns ASO_MISSING error code');
        $this->assertTrue(str_contains($resMissing['reason'], 'No ASO occupational health record'), 'Reason cites missing ASO');

        // 3.2 Edge Case 2: Unfit Status (isFit = false) with valid dates
        $unfitAso = new EquipmentASO(
            id: 'aso-unfit-adv',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Exame Periódico com Audiometria',
            caNumber: '112233',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('+180 days'),
            deliveryDate: (new DateTimeImmutable('today'))->modify('-30 days'),
            examType: ExamType::PERIODIC,
            examDate: (new DateTimeImmutable('today'))->modify('-10 days'),
            expirationDate: (new DateTimeImmutable('today'))->modify('+180 days'),
            physicianName: 'Dra. Helena Medicina Ocupacional',
            physicianCrm: '998877-SP',
            isFit: false
        );

        $resUnfit = $engine->validateWorkEligibility($indWorker, $unfitAso, TenantSegment::INDUSTRIA);
        $this->assertFalse($resUnfit['allowed'], 'Clinically UNFIT collaborator strictly blocked from industrial floor');
        $this->assertEquals('ASO_UNFIT', $resUnfit['code'], 'Unfit collaborator returns ASO_UNFIT code');
        $this->assertTrue(str_contains($resUnfit['reason'], 'UNFIT (Inapto)'), 'Reason explicitly mentions UNFIT (Inapto)');

        // 3.3 Edge Case 3: Expired ASO Medical Exam
        $expiredAso = new EquipmentASO(
            id: 'aso-exp-adv',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Exame Clínico NR-7',
            caNumber: '112233',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('+180 days'),
            deliveryDate: (new DateTimeImmutable('today'))->modify('-400 days'),
            examType: ExamType::PERIODIC,
            examDate: (new DateTimeImmutable('today'))->modify('-375 days'),
            expirationDate: (new DateTimeImmutable('today'))->modify('-10 days'), // Expired 10 days ago
            physicianName: 'Dr. Roberto',
            physicianCrm: '123456-SP',
            isFit: true
        );

        $resExpAso = $engine->validateWorkEligibility($indWorker, $expiredAso, TenantSegment::INDUSTRIA);
        $this->assertFalse($resExpAso['allowed'], 'Expired ASO strictly blocks industrial worker (NR-7 violation)');
        $this->assertEquals('ASO_EXPIRED', $resExpAso['code'], 'Expired ASO returns ASO_EXPIRED code');

        // Boundary: ASO expired yesterday (-1 day)
        $yesterdayAso = new EquipmentASO(
            id: 'aso-yest-adv',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Exame Clínico',
            caNumber: '112233',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('+180 days'),
            examDate: (new DateTimeImmutable('today'))->modify('-366 days'),
            expirationDate: (new DateTimeImmutable('today'))->modify('-1 day'),
            isFit: true
        );
        $resYest = $engine->validateWorkEligibility($indWorker, $yesterdayAso, TenantSegment::INDUSTRIA);
        $this->assertFalse($resYest['allowed'], 'ASO expired yesterday strictly blocks industrial worker');
        $this->assertEquals('ASO_EXPIRED', $resYest['code'], 'ASO expired yesterday returns ASO_EXPIRED');

        // Boundary: ASO expiring TODAY (set at midnight) -> should still be valid today!
        $todayAso = new EquipmentASO(
            id: 'aso-today-adv',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Exame Clínico',
            caNumber: '112233',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('+180 days'),
            examDate: (new DateTimeImmutable('today'))->modify('-365 days'),
            expirationDate: (new DateTimeImmutable('today')), // Expires today
            isFit: true
        );
        $resToday = $engine->validateWorkEligibility($indWorker, $todayAso, TenantSegment::INDUSTRIA);
        $this->assertTrue($resToday['allowed'], 'ASO expiring today remains valid until end of day');
        $this->assertEquals('COMPLIANT', $resToday['code'], 'ASO expiring today returns COMPLIANT code');

        // 3.4 Edge Case 4: Expired PPE Certificado de Aprovação (CA)
        $expiredCaAso = new EquipmentASO(
            id: 'aso-ca-exp-adv',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Protetor Auricular Plug 3M',
            caNumber: '55667',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('-1 day'), // CA expired yesterday
            deliveryDate: (new DateTimeImmutable('today'))->modify('-30 days'),
            examType: ExamType::PERIODIC,
            examDate: (new DateTimeImmutable('today'))->modify('-30 days'),
            expirationDate: (new DateTimeImmutable('today'))->modify('+180 days'), // ASO valid for 180 days
            physicianName: 'Dr. Roberto',
            physicianCrm: '123456-SP',
            isFit: true
        );

        $resExpCa = $engine->validateWorkEligibility($indWorker, $expiredCaAso, TenantSegment::INDUSTRIA);
        $this->assertFalse($resExpCa['allowed'], 'Expired PPE CA certification strictly blocks industrial worker (NR-6 violation)');
        $this->assertEquals('PPE_CA_EXPIRED', $resExpCa['code'], 'Expired CA returns PPE_CA_EXPIRED code');

        // 3.5 Deterministic Multi-Failure Precedence Order
        // Case A: Unfit + Expired ASO + Expired CA -> Unfit is evaluated first
        $tripleFailureAso = new EquipmentASO(
            id: 'aso-triple-fail',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Máscara Facial',
            caNumber: '99999',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('-10 days'),
            examDate: (new DateTimeImmutable('today'))->modify('-380 days'),
            expirationDate: (new DateTimeImmutable('today'))->modify('-20 days'),
            isFit: false
        );
        $resTriple = $engine->validateWorkEligibility($indWorker, $tripleFailureAso, TenantSegment::INDUSTRIA);
        $this->assertFalse($resTriple['allowed'], 'Multi-failure ASO is blocked');
        $this->assertEquals('ASO_UNFIT', $resTriple['code'], 'Precedence hierarchy: ASO_UNFIT evaluated before ASO_EXPIRED or CA_EXPIRED');

        // Case B: Fit + Expired ASO + Expired CA -> Expired ASO evaluated before Expired CA
        $doubleFailureAso = new EquipmentASO(
            id: 'aso-double-fail',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Óculos de Proteção',
            caNumber: '88888',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('-15 days'),
            examDate: (new DateTimeImmutable('today'))->modify('-370 days'),
            expirationDate: (new DateTimeImmutable('today'))->modify('-5 days'),
            isFit: true
        );
        $resDouble = $engine->validateWorkEligibility($indWorker, $doubleFailureAso, TenantSegment::INDUSTRIA);
        $this->assertFalse($resDouble['allowed'], 'Double-failure ASO is blocked');
        $this->assertEquals('ASO_EXPIRED', $resDouble['code'], 'Precedence hierarchy: ASO_EXPIRED evaluated before PPE_CA_EXPIRED');

        // 3.6 Edge Case 5: 100% Fully Compliant Worker
        $compliantAso = new EquipmentASO(
            id: 'aso-compliant-adv',
            tenantId: 'tenant-ind',
            employeeId: 'emp-ind-adv',
            equipmentName: 'Kit Completo EPI: Botina, Capacete, Protetor Auricular',
            caNumber: '77777',
            caExpirationDate: (new DateTimeImmutable('today'))->modify('+240 days'),
            deliveryDate: (new DateTimeImmutable('today'))->modify('-15 days'),
            examType: ExamType::PERIODIC,
            examDate: (new DateTimeImmutable('today'))->modify('-15 days'),
            expirationDate: (new DateTimeImmutable('today'))->modify('+350 days'),
            physicianName: 'Dr. Roberto',
            physicianCrm: '123456-SP',
            isFit: true
        );
        $resCompliant = $engine->validateWorkEligibility($indWorker, $compliantAso, TenantSegment::INDUSTRIA);
        $this->assertTrue($resCompliant['allowed'], 'Fully compliant collaborator cleared for industrial operations');
        $this->assertEquals('COMPLIANT', $resCompliant['code'], 'Compliant collaborator returns COMPLIANT code');

        // 3.7 Edge Case 6: Administrative Tenant Override (Waiving PPE in Indústria)
        $indTenant = new Tenant(id: 'tenant-ind-override', cnpj: self::makeCnpj('55666777'), corporateName: 'Indústria Leve', tradingName: 'IndLeve', segment: 'industria');
        FeatureToggleManager::getInstance()->setTenantFeature('tenant-ind-override', 'risk_ppe_required', false);

        $resWaivedByOverride = $engine->validateWorkEligibility($indWorker, null, $indTenant);
        $this->assertTrue($resWaivedByOverride['allowed'], 'Indústria tenant with risk_ppe_required=false waives ASO and clears worker');
        $this->assertEquals('PPE_WAIVED', $resWaivedByOverride['code'], 'Waived Indústria returns PPE_WAIVED code');

        // 3.8 Temporal Invariant Violations in EquipmentASO Constructor
        $this->assertThrows(function () {
            new EquipmentASO(
                id: 'aso-illegal-dates',
                tenantId: 't-1',
                employeeId: 'e-1',
                equipmentName: 'Capacete',
                caNumber: '123',
                examDate: new DateTimeImmutable('2026-06-01'),
                expirationDate: new DateTimeImmutable('2026-05-01') // Expiration precedes exam date!
            );
        }, ValidationException::class, 'EquipmentASO rejects expirationDate preceding examDate');

        $this->assertThrows(function () {
            $aso = new EquipmentASO(
                id: 'aso-return-test',
                tenantId: 't-1',
                employeeId: 'e-1',
                equipmentName: 'Capacete',
                caNumber: '123',
                deliveryDate: new DateTimeImmutable('2026-03-10')
            );
            $aso->recordReturn(new DateTimeImmutable('2026-03-01')); // Return precedes delivery!
        }, ValidationException::class, 'EquipmentASO::recordReturn rejects returnDate preceding deliveryDate');
    }

    // ==========================================================================
    // SUITE 4: TECH AND FINANCEIRO WORK ELIGIBILITY WAIVER & INVERSION
    // ==========================================================================
    public function testTechAndFinanceiroEligibilityWaiverAndInversion(): void
    {
        $this->suite('4. Tech & Financeiro Work Eligibility Waiver & Adversarial Inversion');

        FeatureToggleManager::resetInstance();
        $engine = new LpsVariabilityEngine();

        $officeWorker = new Employee(
            id: 'emp-office-adv',
            tenantId: 'tenant-tech',
            cpf: self::makeCpf('987654321'),
            fullName: 'Tech Lead Cloud',
            email: 'lead@cloud.io',
            phone: '11911112222',
            birthDate: '1992-08-25',
            admissionDate: '2021-05-01',
            departmentId: 'd-eng',
            roleId: 'r-tech-lead',
            baseSalary: 16000,
            employmentType: 'CLT'
        );

        // 4.1 Tech Segment Baseline: All PPE/ASO requirements waived
        $resTechNull = $engine->validateWorkEligibility($officeWorker, null, TenantSegment::TECH);
        $this->assertTrue($resTechNull['allowed'], 'Tech collaborator with null ASO is cleared for work');
        $this->assertEquals('PPE_WAIVED', $resTechNull['code'], 'Tech returns PPE_WAIVED code');

        // Unfit ASO does not block office tech employee under baseline
        $unfitAso = new EquipmentASO('aso-u', 't', 'e', 'EPI', '123', isFit: false);
        $resTechUnfit = $engine->validateWorkEligibility($officeWorker, $unfitAso, TenantSegment::TECH);
        $this->assertTrue($resTechUnfit['allowed'], 'Tech collaborator with unfit ASO is cleared under baseline (PPE waived)');
        $this->assertEquals('PPE_WAIVED', $resTechUnfit['code'], 'Tech returns PPE_WAIVED even when ASO is unfit');

        // 4.2 Financeiro Segment Baseline: PPE/ASO requirements waived
        $resFinNull = $engine->validateWorkEligibility($officeWorker, null, TenantSegment::FINANCEIRO);
        $this->assertTrue($resFinNull['allowed'], 'Financeiro collaborator with null ASO is cleared for work');
        $this->assertEquals('PPE_WAIVED', $resFinNull['code'], 'Financeiro returns PPE_WAIVED code');

        // 4.3 Adversarial Inversion: Tech Tenant operating Datacenter/Hardware Lab
        $datacenterTenant = new Tenant(id: 'tenant-datacenter', cnpj: self::makeCnpj('66777888'), corporateName: 'Datacenter Corp', tradingName: 'DataCorp', segment: 'tech');
        FeatureToggleManager::getInstance()->setTenantFeature('tenant-datacenter', 'risk_ppe_required', true);

        $resDcNull = $engine->validateWorkEligibility($officeWorker, null, $datacenterTenant);
        $this->assertFalse(
            $resDcNull['allowed'],
            'Adversarial Inversion: Tech tenant with risk_ppe_required=true strictly BLOCKS worker with missing ASO'
        );
        $this->assertEquals('ASO_MISSING', $resDcNull['code'], 'Inverted Tech returns ASO_MISSING');

        $resDcUnfit = $engine->validateWorkEligibility($officeWorker, $unfitAso, $datacenterTenant);
        $this->assertFalse(
            $resDcUnfit['allowed'],
            'Adversarial Inversion: Tech tenant with risk_ppe_required=true strictly BLOCKS unfit worker'
        );
        $this->assertEquals('ASO_UNFIT', $resDcUnfit['code'], 'Inverted Tech returns ASO_UNFIT');

        // 4.4 Adversarial Inversion: Financeiro Tenant with Physical Vault / Armored Logistics
        $vaultTenant = new Tenant(id: 'tenant-vault', cnpj: self::makeCnpj('77888999'), corporateName: 'Vault Logistics S.A.', tradingName: 'VaultCorp', segment: 'financeiro');
        FeatureToggleManager::getInstance()->setTenantFeature('tenant-vault', 'risk_ppe_required', true);

        $resVaultNull = $engine->validateWorkEligibility($officeWorker, null, $vaultTenant);
        $this->assertFalse(
            $resVaultNull['allowed'],
            'Adversarial Inversion: Financeiro tenant with risk_ppe_required=true strictly BLOCKS worker with missing ASO'
        );
        $this->assertEquals('ASO_MISSING', $resVaultNull['code'], 'Inverted Financeiro returns ASO_MISSING');
    }

    // ==========================================================================
    // SUITE 5: FINANCEIRO BIOMETRIC PUNCH ENFORCEMENT & PORTARIA 671
    // ==========================================================================
    public function testFinanceiroBiometricPunchEnforcement(): void
    {
        $this->suite('5. Financeiro Biometric Punch Enforcement (Portaria 671) & Token Guarding');

        FeatureToggleManager::resetInstance();
        $engine = new LpsVariabilityEngine();

        $bankEmp = new Employee(
            id: 'emp-bank-adv',
            tenantId: 'tenant-bank',
            cpf: self::makeCpf('333444555'),
            fullName: 'Gerente Financeiro Alpha',
            email: 'gerente@bancoalpha.com',
            phone: '11933334444',
            birthDate: '1982-11-10',
            admissionDate: '2018-03-01',
            departmentId: 'd-agencia',
            roleId: 'r-gerente',
            baseSalary: 18000,
            employmentType: 'CLT'
        );

        $punch = new TimeLog(
            id: 'punch-bio-test',
            tenantId: 'tenant-bank',
            employeeId: 'emp-bank-adv',
            timestamp: new DateTimeImmutable('2026-03-10T08:00:00-03:00'),
            type: TimeLogType::ENTRY,
            location: new GeoLocation(-23.55052, -46.633308),
            nsr: 100
        );

        // 5.1 Financeiro Baseline: Biometrics Strictly Mandatory
        $rejectedPunch = $engine->validateTimePunch($punch, TenantSegment::FINANCEIRO, biometricVerified: false);
        $this->assertFalse(
            $rejectedPunch['allowed'],
            'Financeiro punch without biometric verification is REJECTED'
        );
        $this->assertTrue(
            str_contains($rejectedPunch['reason'], 'Portaria 671'),
            'Rejection reason explicitly cites legal compliance with Portaria 671'
        );

        $acceptedPunch = $engine->validateTimePunch($punch, TenantSegment::FINANCEIRO, biometricVerified: true);
        $this->assertTrue(
            $acceptedPunch['allowed'],
            'Financeiro punch with biometric verification is ACCEPTED'
        );

        // 5.2 Tech and Indústria Baselines: Biometrics Optional (allowed by default)
        $techPunch = $engine->validateTimePunch($punch, TenantSegment::TECH, biometricVerified: false);
        $this->assertTrue($techPunch['allowed'], 'Tech segment accepts electronic punch without biometric token');

        $indPunch = $engine->validateTimePunch($punch, TenantSegment::INDUSTRIA, biometricVerified: false);
        $this->assertTrue($indPunch['allowed'], 'Indústria segment accepts electronic punch without biometric token');

        // 5.3 Adversarial Guarding Pattern: Simulating Middleware / Service Token Validation
        $punchGuard = function (
            LpsVariabilityEngine $eng,
            TimeLog $p,
            TenantSegment $seg,
            ?string $biometricToken
        ): void {
            // Token must be present, non-empty, and carry verified cryptographic signature
            $isBiometricValid = ($biometricToken !== null && strlen($biometricToken) >= 16 && str_starts_with($biometricToken, 'bio_token_'));
            $res = $eng->validateTimePunch($p, $seg, biometricVerified: $isBiometricValid);

            if (!$res['allowed']) {
                throw new ValidationException($res['reason']);
            }
        };

        // Biometric token null -> throws ValidationException
        $this->assertThrows(function () use ($punchGuard, $engine, $punch) {
            $punchGuard($engine, $punch, TenantSegment::FINANCEIRO, null);
        }, ValidationException::class, 'Guarded punch without token throws ValidationException in Financeiro');

        // Biometric token invalid / truncated -> throws ValidationException
        $this->assertThrows(function () use ($punchGuard, $engine, $punch) {
            $punchGuard($engine, $punch, TenantSegment::FINANCEIRO, 'short_tok');
        }, ValidationException::class, 'Guarded punch with short/invalid token throws ValidationException in Financeiro');

        // Valid biometric token -> succeeds cleanly
        $succeeded = false;
        try {
            $punchGuard($engine, $punch, TenantSegment::FINANCEIRO, 'bio_token_facial_sha256_9988aabb');
            $succeeded = true;
        } catch (\Throwable) {
            $succeeded = false;
        }
        $this->assertTrue($succeeded, 'Guarded punch with valid biometric token succeeds without exception');

        // 5.4 Adversarial Inversion: High-Security Tech Facility Mandating Biometrics
        $techSecurityTenant = new Tenant(id: 'tenant-tech-secure', cnpj: self::makeCnpj('88999000'), corporateName: 'Tech Sec S.A.', tradingName: 'TechSec', segment: 'tech');
        FeatureToggleManager::getInstance()->setTenantFeature('tenant-tech-secure', 'biometric_punch_mandatory', true);

        $resTechBioBlocked = $engine->validateTimePunch($punch, $techSecurityTenant, biometricVerified: false);
        $this->assertFalse(
            $resTechBioBlocked['allowed'],
            'Adversarial Inversion: High-security Tech tenant mandates biometrics and rejects punch without verification'
        );

        $resTechBioAllowed = $engine->validateTimePunch($punch, $techSecurityTenant, biometricVerified: true);
        $this->assertTrue(
            $resTechBioAllowed['allowed'],
            'Adversarial Inversion: High-security Tech tenant accepts biometric verified punch'
        );

        // 5.5 Adversarial Inversion: Financeiro Tenant Emergency Bypass (e.g. biometric kiosk hardware failure)
        $finEmergencyTenant = new Tenant(id: 'tenant-fin-emergency', cnpj: self::makeCnpj('99000111'), corporateName: 'Banco Regional', tradingName: 'BancoReg', segment: 'financeiro');
        FeatureToggleManager::getInstance()->setTenantFeature('tenant-fin-emergency', 'biometric_punch_mandatory', false);

        $resFinBypass = $engine->validateTimePunch($punch, $finEmergencyTenant, biometricVerified: false);
        $this->assertTrue(
            $resFinBypass['allowed'],
            'Adversarial Inversion: Financeiro tenant with hardware emergency bypass allows non-biometric punch'
        );
    }

    // ==========================================================================
    // SUITE 6: POLYMORPHIC STRATEGY DISPATCH & COMPUTATION
    // ==========================================================================
    public function testPolymorphicStrategyDispatch(): void
    {
        $this->suite('6. Polymorphic Strategy Dispatch (Overtime, Performance & Benefits)');

        FeatureToggleManager::resetInstance();
        $engine = new LpsVariabilityEngine();

        $mockEmp = new Employee(
            id: 'emp-strat-adv',
            tenantId: 't-strat',
            cpf: self::makeCpf('444555666'),
            fullName: 'Especialista em Estratégias',
            email: 'strat@empresa.com',
            phone: '11944445555',
            birthDate: '1990-01-01',
            admissionDate: '2020-01-01',
            departmentId: 'd-eng',
            roleId: 'r-spec',
            baseSalary: Money::fromCents(1000000), // R$ 10.000,00
            employmentType: 'CLT'
        );

        // 6.1 Overtime Strategy Dispatch
        // Tech: BankHoursStrategy (weekday and weekend)
        $techOtWeekday = $engine->resolveOvertimeStrategy(TenantSegment::TECH, isSundayOrHoliday: false);
        $this->assertTrue($techOtWeekday instanceof BankHoursStrategy, 'Tech weekday resolves to BankHoursStrategy');
        $this->assertEquals(0.0, $techOtWeekday->calculateOvertime(50.0, 3.0), 'BankHoursStrategy monetary cost is strictly R$ 0.00');
        $this->assertEquals(180, $techOtWeekday->calculateBankMinutes(3.0), 'BankHoursStrategy credits 180 compensatory minutes for 3 hours');

        $techOtSunday = $engine->resolveOvertimeStrategy(TenantSegment::TECH, isSundayOrHoliday: true);
        $this->assertTrue($techOtSunday instanceof BankHoursStrategy, 'Tech Sunday resolves to BankHoursStrategy when bank_of_hours is active');

        // Indústria: Standard50Strategy (weekday) vs Sunday100Strategy (Sunday/Holiday)
        $indOtWeekday = $engine->resolveOvertimeStrategy(TenantSegment::INDUSTRIA, isSundayOrHoliday: false);
        $this->assertTrue($indOtWeekday instanceof Standard50Strategy, 'Indústria weekday resolves to Standard50Strategy');
        $this->assertEquals(225.0, $indOtWeekday->calculateOvertime(50.0, 3.0), 'Standard50Strategy calculates 50.00 * 1.5 * 3h = R$ 225.00');

        $indOtSunday = $engine->resolveOvertimeStrategy(TenantSegment::INDUSTRIA, isSundayOrHoliday: true);
        $this->assertTrue($indOtSunday instanceof Sunday100Strategy, 'Indústria Sunday resolves to Sunday100Strategy');
        $this->assertEquals(300.0, $indOtSunday->calculateOvertime(50.0, 3.0), 'Sunday100Strategy calculates 50.00 * 2.0 * 3h = R$ 300.00');

        // Financeiro: Standard50Strategy vs Sunday100Strategy
        $finOtWeekday = $engine->resolveOvertimeStrategy(TenantSegment::FINANCEIRO, isSundayOrHoliday: false);
        $this->assertTrue($finOtWeekday instanceof Standard50Strategy, 'Financeiro weekday resolves to Standard50Strategy');

        // Overtime Tenant Overrides
        $techPayoutTenant = new Tenant(id: 't-tech-payout', cnpj: self::makeCnpj('10111222'), corporateName: 'Tech Payout Ltda', tradingName: 'TechPay', segment: 'tech');
        FeatureToggleManager::getInstance()->setTenantFeature('t-tech-payout', 'bank_of_hours', false);
        $switchedOt = $engine->resolveOvertimeStrategy($techPayoutTenant, isSundayOrHoliday: false);
        $this->assertTrue($switchedOt instanceof Standard50Strategy, 'Tech tenant disabling bank_of_hours switches to Standard50Strategy');

        $indBankTenant = new Tenant(id: 't-ind-bank', cnpj: self::makeCnpj('20222333'), corporateName: 'Indústria Bank Ltda', tradingName: 'IndBank', segment: 'industria');
        FeatureToggleManager::getInstance()->setTenantFeature('t-ind-bank', 'bank_of_hours', true);
        $switchedIndOt = $engine->resolveOvertimeStrategy($indBankTenant, isSundayOrHoliday: true);
        $this->assertTrue($switchedIndOt instanceof BankHoursStrategy, 'Indústria tenant enabling bank_of_hours switches to BankHoursStrategy');

        // Boundary conditions for overtime calculations
        $this->assertEquals(0.0, $indOtWeekday->calculateOvertime(0.0, 5.0), 'Standard50Strategy with 0.0 hourly rate yields 0.00');
        $this->assertEquals(0.0, $indOtWeekday->calculateOvertime(50.0, 0.0), 'Standard50Strategy with 0.0 hours yields 0.00');
        $this->assertEquals(0.0, $indOtWeekday->calculateOvertime(-10.0, 5.0), 'Standard50Strategy with negative rate yields 0.00');
        $this->assertEquals(0.0, $indOtWeekday->calculateOvertime(50.0, -2.0), 'Standard50Strategy with negative hours yields 0.00');
        $this->assertEquals(0, $techOtWeekday->calculateBankMinutes(0.0), 'BankHoursStrategy with 0.0 hours yields 0 minutes');
        $this->assertEquals(0, $techOtWeekday->calculateBankMinutes(-5.0), 'BankHoursStrategy with negative hours yields 0 minutes');

        // 6.2 Performance Strategy Dispatch
        // Tech: OkrStrategy
        $techPerf = $engine->resolvePerformanceStrategy(TenantSegment::TECH);
        $this->assertTrue($techPerf instanceof OkrStrategy, 'Tech resolves to OkrStrategy');
        $this->assertEquals('OKR', $techPerf->getScoringModel(), 'OkrStrategy model name is OKR');

        // Test OKR Calculation: Structured KRs [current/target] -> avg 1.0 -> 100.0 score
        $okrScore = $techPerf->calculateScore($mockEmp, [
            'key_results' => [
                ['name' => 'KR1', 'current' => 90.0, 'target' => 100.0],
                ['name' => 'KR2', 'current' => 100.0, 'target' => 100.0],
                ['name' => 'KR3', 'current' => 110.0, 'target' => 100.0],
            ],
        ]);
        $this->assertEquals(100.0, $okrScore, 'OKR average achievement calculates 100.0 score');

        // Test OKR Overachievement (capped at 120.0)
        $okrOverScore = $techPerf->calculateScore($mockEmp, [
            'key_results' => [
                ['name' => 'Over KR1', 'current' => 130.0, 'target' => 100.0],
                ['name' => 'Over KR2', 'current' => 140.0, 'target' => 100.0],
            ],
        ]);
        $this->assertEquals(120.0, $okrOverScore, 'OKR overachievement capped at 120.0');

        // Test OKR Bonus: Base salary R$ 10.000 * 120% * 1.5 maxBonusMonths = R$ 18.000,00
        $okrBonus = $techPerf->calculateBonus($mockEmp, 120.0, maxBonusMonths: 1.5);
        $this->assertEquals(18000.0, $okrBonus, 'OKR bonus calculation matches 10,000 * 1.2 * 1.5 = R$ 18,000.00');

        // Indústria: Evaluation360Strategy
        $indPerf = $engine->resolvePerformanceStrategy(TenantSegment::INDUSTRIA);
        $this->assertTrue($indPerf instanceof Evaluation360Strategy, 'Indústria resolves to Evaluation360Strategy');
        $this->assertEquals('EVALUATION_360', $indPerf->getScoringModel(), 'Evaluation360Strategy model name is EVALUATION_360');

        // Test 360 Calculation: Self (4.0/5.0 = 80), Peers ([4.5, 4.5] = 90), Manager (5.0/5.0 = 100)
        // Default weights: 15% self, 35% peers, 50% manager
        // Expected: (80 * 0.15) + (90 * 0.35) + (100 * 0.50) = 12 + 31.5 + 50 = 93.5
        $evalScore = $indPerf->calculateScore($mockEmp, [
            'self' => 4.0,
            'peers' => [4.5, 4.5],
            'manager' => 5.0,
        ]);
        $this->assertCloseTo(93.5, $evalScore, 0.01, '360 degree evaluation calculates 93.5 composite score');

        // Financeiro: KpiStrategy (allowOverachievement: true because aggressive_bonus is active)
        $finPerf = $engine->resolvePerformanceStrategy(TenantSegment::FINANCEIRO);
        $this->assertTrue($finPerf instanceof KpiStrategy, 'Financeiro resolves to KpiStrategy');
        $this->assertEquals('KPI', $finPerf->getScoringModel(), 'KpiStrategy model name is KPI');

        // Test KPI Calculation: Standard Higher-Is-Better KPI with Overachievement
        $kpiScore = $finPerf->calculateScore($mockEmp, [
            'kpis' => [
                ['name' => 'Portfolio ROI', 'target' => 100.0, 'actual' => 115.0, 'weight' => 2.0],
                ['name' => 'Client Retention', 'target' => 90.0, 'actual' => 99.0, 'weight' => 1.0],
            ],
        ]);
        // ROI: 115/100 = 1.15; Retention: 99/90 = 1.10
        // Weighted: ((1.15 * 2) + (1.10 * 1)) / 3 = (2.30 + 1.10) / 3 = 3.40 / 3 = 1.1333 -> 113.33
        $this->assertCloseTo(113.33, $kpiScore, 0.01, 'KPI evaluation calculates 113.33 with overachievement');

        // Test KPI Lower-Is-Better (e.g. Operational Errors)
        $kpiInverseScore = $finPerf->calculateScore($mockEmp, [
            'kpis' => [
                ['name' => 'Error Count', 'target' => 2.0, 'actual' => 1.0, 'lower_is_better' => true, 'weight' => 1.0],
            ],
        ]);
        // target 2 / actual 1 = 2.0, capped at 1.20 (120%)
        $this->assertEquals(120.0, $kpiInverseScore, 'Lower-is-better KPI capped at 120.0 overachievement');

        // Test KPI Threshold Gate Breach (actual < threshold yields 0.0)
        $kpiThresholdFail = $finPerf->calculateScore($mockEmp, [
            'kpis' => [
                ['name' => 'Strict Gate', 'target' => 100.0, 'actual' => 40.0, 'threshold' => 50.0, 'weight' => 1.0],
            ],
        ]);
        $this->assertEquals(0.0, $kpiThresholdFail, 'KPI failing minimum qualifying gate yields 0.0 score');

        // Aggressive Bonus Overrides
        $techAggressiveTenant = new Tenant(id: 't-tech-aggr', cnpj: self::makeCnpj('30333444'), corporateName: 'Tech Aggressive', tradingName: 'TechAggr', segment: 'tech');
        FeatureToggleManager::getInstance()->setTenantFeature('t-tech-aggr', 'aggressive_bonus', true);
        $switchedTechPerf = $engine->resolvePerformanceStrategy($techAggressiveTenant);
        $this->assertTrue($switchedTechPerf instanceof KpiStrategy, 'Tech tenant enabling aggressive_bonus switches to KpiStrategy');

        $indAggressiveTenant = new Tenant(id: 't-ind-aggr', cnpj: self::makeCnpj('40444555'), corporateName: 'Ind Aggressive', tradingName: 'IndAggr', segment: 'industria');
        FeatureToggleManager::getInstance()->setTenantFeature('t-ind-aggr', 'aggressive_bonus', true);
        $switchedIndPerf = $engine->resolvePerformanceStrategy($indAggressiveTenant);
        $this->assertTrue($switchedIndPerf instanceof KpiStrategy, 'Indústria tenant enabling aggressive_bonus switches to KpiStrategy');

        // 6.3 Benefit Discount Strategy Dispatch
        $transpStrat = $engine->resolveBenefitDiscountStrategy(TenantSegment::TECH, BenefitType::TRANSPORTATION);
        $this->assertTrue($transpStrat instanceof TransportationVoucherStrategy, 'Benefit Transportation resolves to TransportationVoucherStrategy');

        $healthStrat = $engine->resolveBenefitDiscountStrategy(TenantSegment::FINANCEIRO, BenefitType::HEALTH_PLAN);
        $this->assertTrue($healthStrat instanceof HealthPlanStrategy, 'Benefit Health Plan resolves to HealthPlanStrategy');

        $mealStrat = $engine->resolveBenefitDiscountStrategy(TenantSegment::INDUSTRIA, BenefitType::MEAL_VOUCHER);
        $this->assertTrue($mealStrat instanceof MealVoucherStrategy, 'Benefit Meal Voucher resolves to MealVoucherStrategy');

        // Mathematical verification of Benefit strategies:
        // Transportation Voucher: 6% salary cap vs actual benefit value
        // Salary R$ 10.000 -> 6% is R$ 600,00. Benefit value R$ 800,00 -> deduction is R$ 600,00
        $transpBenefitHigh = new Benefit(
            id: 'b-vt-high',
            tenantId: 't-strat',
            type: BenefitType::TRANSPORTATION,
            name: 'VT Metropolitano',
            provider: 'SPTrans',
            value: Money::fromCents(80000), // R$ 800,00
            isDeductible: true
        );
        $deductionHigh = $transpStrat->calculateDiscount($mockEmp, $transpBenefitHigh);
        $this->assertEquals(600.0, $deductionHigh, 'Transportation voucher deduction capped at 6% salary cap (R$ 600,00)');

        // Benefit value R$ 400,00 -> deduction is R$ 400,00 (lower than R$ 600,00 cap)
        $transpBenefitLow = new Benefit(
            id: 'b-vt-low',
            tenantId: 't-strat',
            type: BenefitType::TRANSPORTATION,
            name: 'VT Básico',
            provider: 'SPTrans',
            value: Money::fromCents(40000), // R$ 400,00
            isDeductible: true
        );
        $deductionLow = $transpStrat->calculateDiscount($mockEmp, $transpBenefitLow);
        $this->assertEquals(400.0, $deductionLow, 'Transportation voucher deduction uses actual value when below 6% cap (R$ 400,00)');
    }

    // ==========================================================================
    // SUITE 7: HIGH-THROUGHPUT FUZZING & INVARIANT STRESS (10,000+ OPS)
    // ==========================================================================
    public function testHighThroughputFuzzing(): void
    {
        $this->suite('7. High-Throughput Fuzzing, Invariant Verification & Memory Profiling');

        FeatureToggleManager::resetInstance();
        $mgr = FeatureToggleManager::getInstance();
        $engine = new LpsVariabilityEngine($mgr);

        // Pre-generate 30 distinct tenants across segments
        /** @var Tenant[] $syntheticTenants */
        $syntheticTenants = [];
        $segments = [TenantSegment::TECH, TenantSegment::INDUSTRIA, TenantSegment::FINANCEIRO];

        for ($t = 0; $t < 30; $t++) {
            $seg = $segments[$t % 3];
            $tId = "tenant-fuzz-{$t}";
            $syntheticTenants[$tId] = new Tenant(
                id: $tId,
                cnpj: self::makeCnpj(sprintf('%08d', 10000000 + $t)),
                corporateName: "Fuzz Corp {$t}",
                tradingName: "Fuzz {$t}",
                segment: $seg->value
            );

            // Apply random overrides to 50% of tenants
            if ($t % 2 === 0) {
                $mgr->setTenantFeature($tId, 'bank_of_hours', ($t % 4 === 0));
                $mgr->setTenantFeature($tId, 'risk_ppe_required', ($t % 6 === 0));
                $mgr->setTenantFeature($tId, 'biometric_punch_mandatory', ($t % 8 === 0));
            }
        }

        $dummyWorker = new Employee(
            id: 'emp-fuzz',
            tenantId: 'tenant-fuzz-0',
            cpf: self::makeCpf('555666777'),
            fullName: 'Fuzz Worker',
            email: 'fuzz@test.com',
            phone: '11999999999',
            birthDate: '1995-01-01',
            admissionDate: '2022-01-01',
            departmentId: 'd-1',
            roleId: 'r-1',
            baseSalary: 5000,
            employmentType: 'CLT'
        );

        $dummyPunch = new TimeLog(
            id: 'p-fuzz',
            tenantId: 'tenant-fuzz-0',
            employeeId: 'emp-fuzz',
            timestamp: new DateTimeImmutable(),
            type: TimeLogType::ENTRY,
            location: new GeoLocation(-23.55, -46.63),
            nsr: 1
        );

        $iterations = 10000;
        $invariantsPreserved = 0;

        $t0 = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $tIdx = $i % 30;
            $tId = "tenant-fuzz-{$tIdx}";
            $tenant = $syntheticTenants[$tId];
            $seg = TenantSegment::fromValue($tenant->getSegment());

            // 1. Feature flag evaluation
            $isBank = $engine->isBankOfHoursActive($tenant);
            $isPpe = $engine->isPpeMandatory($tenant);
            $isBio = $engine->isBiometricPunchMandatory($tenant);

            // 2. Overtime strategy resolution
            $otStrat = $engine->resolveOvertimeStrategy($tenant, isSundayOrHoliday: ($i % 7 === 0));
            if ($isBank) {
                if (!($otStrat instanceof BankHoursStrategy)) {
                    continue; // Invariant violation!
                }
            } else {
                if ($i % 7 === 0) {
                    if (!($otStrat instanceof Sunday100Strategy)) {
                        continue;
                    }
                } else {
                    if (!($otStrat instanceof Standard50Strategy)) {
                        continue;
                    }
                }
            }

            // 3. Biometric punch invariant
            $punchAllowedWithoutBio = $engine->validateTimePunch($dummyPunch, $tenant, biometricVerified: false)['allowed'];
            if ($isBio && $punchAllowedWithoutBio) {
                continue; // Invariant violation: biometric mandatory cannot allow unverified punch!
            }
            if (!$isBio && !$punchAllowedWithoutBio) {
                continue; // Invariant violation: biometric optional cannot block unverified punch!
            }

            // 4. Work eligibility invariant
            $eligibility = $engine->validateWorkEligibility($dummyWorker, null, $tenant);
            if ($isPpe && $eligibility['allowed']) {
                continue; // Invariant violation: PPE mandatory cannot allow null ASO!
            }
            if (!$isPpe && !$eligibility['allowed']) {
                continue; // Invariant violation: PPE waived cannot block null ASO!
            }

            $invariantsPreserved++;
        }

        $elapsed = microtime(true) - $t0;
        $evalsPerSec = $iterations / max($elapsed, 0.0001);
        $peakMemoryMb = memory_get_peak_usage(true) / 1024 / 1024;

        $this->assertEquals($iterations, $invariantsPreserved, "10,000 synthetic iterations maintained 100% invariant preservation");
        $this->assertTrue($evalsPerSec > 10000.0, sprintf('Throughput exceeds 10,000 evals/sec (Achieved: %.0f ops/sec in %.3fs)', $evalsPerSec, $elapsed));
        $this->assertTrue($peakMemoryMb < 64.0, sprintf('Peak memory consumption under 64 MB (Peak: %.2f MB)', $peakMemoryMb));
    }

    // ==========================================================================
    // EXECUTION RUNNER & ANSI SUMMARY
    // ==========================================================================
    public function run(): int
    {
        echo "\033[1;36m" . str_repeat('=', 70) . "\033[0m\n";
        echo "\033[1;36m HRTECH BACKEND — M4 ADVERSARIAL LPS VARIABILITY TEST HARNESS\033[0m\n";
        echo "\033[1;36m" . str_repeat('=', 70) . "\033[0m\n";

        $this->testSegmentBaselinesAndAliases();
        $this->testTenantOverridesAndIsolation();
        $this->testIndustriaWorkEligibilityAdversarial();
        $this->testTechAndFinanceiroEligibilityWaiverAndInversion();
        $this->testFinanceiroBiometricPunchEnforcement();
        $this->testPolymorphicStrategyDispatch();
        $this->testHighThroughputFuzzing();

        $elapsed = microtime(true) - $this->startTime;
        $peakMem = memory_get_peak_usage(true) / 1024 / 1024;

        echo "\n" . str_repeat('=', 70) . "\n";
        echo "\033[1;37mM4 ADVERSARIAL LPS VARIABILITY VERIFICATION SUMMARY\033[0m\n";
        echo str_repeat('=', 70) . "\n";
        echo "Total Assertions : \033[1;33m{$this->totalAssertions}\033[0m\n";
        echo "Passed           : \033[32m{$this->passedAssertions}\033[0m\n";
        echo "Failed           : " . ($this->failedAssertions > 0 ? "\033[31m{$this->failedAssertions}\033[0m" : "\033[32m0\033[0m") . "\n";
        echo sprintf("Elapsed Time     : %.3f seconds\n", $elapsed);
        echo sprintf("Peak Memory      : %.2f MB\n", $peakMem);

        if ($this->failedAssertions > 0) {
            echo "\n\033[1;31mDETECTED ANOMALIES:\033[0m\n";
            foreach (array_slice($this->anomalies, 0, 25) as $anomaly) {
                echo "  • [{$anomaly['suite']}] {$anomaly['assertion']}: {$anomaly['details']}\n";
            }
            echo "\n\033[1;31mRESULT: ADVERSARIAL VERIFICATION FAILED\033[0m\n";
            return 1;
        }

        echo "\n\033[1;32mRESULT: 100% ADVERSARIAL LPS CHALLENGES PASSED (ZERO ANOMALIES)\033[0m\n\n";
        return 0;
    }
}

$harness = new M4AdversarialLpsHarness();
$exitCode = $harness->run();
exit($exitCode);
