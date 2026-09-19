<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 1 Verification Test Suite
 *
 * Standalone verification harness designed for PHP 8.3.6 CLI.
 * Verifies PSR-4 autoloader, contracts, enums, value objects (Modulo 11 & Haversine),
 * exception hierarchy, strategy pattern interfaces, and template method patterns.
 *
 * Usage: php tests/m1_verify.php
 */

// --------------------------------------------------------------------------
// 1. Lightweight ANSI Test Runner & Assertion Engine
// --------------------------------------------------------------------------

final class M1TestRunner
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;
    private int $failedAssertions = 0;
    private array $failures = [];
    private string $currentSuite = '';

    public function suite(string $name): void
    {
        $this->currentSuite = $name;
        echo "\n\033[1;34m▶ Suite: {$name}\033[0m\n";
    }

    public function assertTrue(bool $condition, string $message): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  \033[32m✔\033[0m {$message}\n";
        } else {
            $this->failedAssertions++;
            $error = "  \033[31m✘ FAIL: {$message}\033[0m";
            echo "{$error}\n";
            $this->failures[] = "[{$this->currentSuite}] {$message}";
        }
    }

    public function assertFalse(bool $condition, string $message): void
    {
        $this->assertTrue(!$condition, $message);
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
            $error = "  \033[31m✘ FAIL: {$message} (Expected: {$expStr}, got: {$actStr})\033[0m";
            echo "{$error}\n";
            $this->failures[] = "[{$this->currentSuite}] {$message} (Expected: {$expStr}, got: {$actStr})";
        }
    }

    public function assertCloseTo(float $expected, float $actual, float $delta, string $message): void
    {
        $this->totalAssertions++;
        if (abs($expected - $actual) <= $delta) {
            $this->passedAssertions++;
            echo "  \033[32m✔\033[0m {$message} [approx {$actual} ~= {$expected}]\n";
        } else {
            $this->failedAssertions++;
            $error = "  \033[31m✘ FAIL: {$message} (Expected {$expected} ±{$delta}, got {$actual})\033[0m";
            echo "{$error}\n";
            $this->failures[] = "[{$this->currentSuite}] {$message}";
        }
    }

    public function assertThrows(callable $callback, string $expectedExceptionClass, string $message): void
    {
        $this->totalAssertions++;
        try {
            $callback();
            $this->failedAssertions++;
            $error = "  \033[31m✘ FAIL: {$message} (Expected exception {$expectedExceptionClass}, but none thrown)\033[0m";
            echo "{$error}\n";
            $this->failures[] = "[{$this->currentSuite}] {$message} (No exception thrown)";
        } catch (\Throwable $e) {
            if ($e instanceof $expectedExceptionClass) {
                $this->passedAssertions++;
                echo "  \033[32m✔\033[0m {$message} (Caught expected " . get_class($e) . ")\n";
            } else {
                $this->failedAssertions++;
                $error = "  \033[31m✘ FAIL: {$message} (Expected {$expectedExceptionClass}, got " . get_class($e) . ": {$e->getMessage()})\033[0m";
                echo "{$error}\n";
                $this->failures[] = "[{$this->currentSuite}] {$message} (Wrong exception type)";
            }
        }
    }

    public function printSummary(): int
    {
        echo "\n" . str_repeat('=', 65) . "\n";
        echo "\033[1;37mMILESTONE 1 VERIFICATION SUMMARY\033[0m\n";
        echo str_repeat('=', 65) . "\n";
        echo "Total Assertions : {$this->totalAssertions}\n";
        echo "Passed           : \033[32m{$this->passedAssertions}\033[0m\n";
        echo "Failed           : " . ($this->failedAssertions > 0 ? "\033[31m{$this->failedAssertions}\033[0m" : "0") . "\n";

        if ($this->failedAssertions > 0) {
            echo "\n\033[1;31mFAILED TESTS:\033[0m\n";
            foreach ($this->failures as $failure) {
                echo "  - {$failure}\n";
            }
            echo "\n\033[1;31mRESULT: VERIFICATION FAILED\033[0m\n";
            return 1;
        }

        echo "\n\033[1;32mRESULT: ALL MILESTONE 1 VERIFICATIONS PASSED (100%)\033[0m\n";
        return 0;
    }
}

// --------------------------------------------------------------------------
// 2. Execution Setup & Autoloader Bootstrap
// --------------------------------------------------------------------------

$runner = new M1TestRunner();

echo "\033[1;36m=================================================================\033[0m\n";
echo "\033[1;36m       HRTech Core Backend — Milestone 1 Verification Suite      \033[0m\n";
echo "\033[1;36m=================================================================\033[0m\n";

$autoloaderFile = dirname(__DIR__) . '/src/Autoloader.php';

// Suite 1: Autoloader Registration
$runner->suite('Autoloader Registration');
$runner->assertTrue(file_exists($autoloaderFile), 'Autoloader.php exists at src/Autoloader.php');

require_once $autoloaderFile;
$runner->assertTrue(class_exists('HrTech\Autoloader'), 'Autoloader class exists');

if (method_exists('HrTech\Autoloader', 'register')) {
    $autoloader = HrTech\Autoloader::register();
    $runner->assertTrue(true, 'Autoloader::register() invoked successfully');
    $runner->assertTrue($autoloader->isRegistered(), 'Autoloader is marked as registered');
}

$prefixes = $autoloader->getPrefixes();
$runner->assertTrue(isset($prefixes['HrTech\\']), 'Prefix HrTech\\ registered');
$runner->assertTrue(isset($prefixes['HrTech\\Tests\\']), 'Prefix HrTech\\Tests\\ registered');

// Path traversal guard check
$traversalResult = $autoloader->loadClass('HrTech\\..\\..\\etc\\passwd');
$runner->assertEquals(false, $traversalResult, 'Security traversal check rejected directory traversal');

// --------------------------------------------------------------------------
// 3. Suite 2: Contract Interfaces Autoloading & Reflection
// --------------------------------------------------------------------------

$runner->suite('Contracts & Interfaces Autoloading');

$contracts = [
    'HrTech\Contracts\IdentifiableInterface',
    'HrTech\Contracts\ValidatableInterface',
    'HrTech\Contracts\ArrayableInterface',
    'HrTech\Contracts\JsonableInterface',
    'HrTech\Contracts\StringableInterface',
    'HrTech\Contracts\TenantScopedInterface',
    'HrTech\Contracts\AuditableInterface',
    'HrTech\Contracts\SingletonInterface',
    'HrTech\Contracts\OvertimeStrategyInterface',
    'HrTech\Contracts\BenefitDiscountStrategyInterface',
    'HrTech\Contracts\PerformanceStrategyInterface',
    'HrTech\Contracts\PayrollCalculatorInterface',
    'HrTech\Contracts\TimeLogImporterInterface',
    'HrTech\Contracts\ReportGeneratorInterface',
];

foreach ($contracts as $contract) {
    $runner->assertTrue(
        interface_exists($contract),
        "Interface {$contract} resolved by autoloader"
    );
}

// Check OvertimeStrategyInterface methods
$overtimeRef = new ReflectionClass('HrTech\Contracts\OvertimeStrategyInterface');
$runner->assertTrue($overtimeRef->hasMethod('calculateOvertime'), 'OvertimeStrategyInterface::calculateOvertime() exists');
$runner->assertTrue($overtimeRef->hasMethod('getDescription'), 'OvertimeStrategyInterface::getDescription() exists');

// Check BenefitDiscountStrategyInterface methods
$benefitRef = new ReflectionClass('HrTech\Contracts\BenefitDiscountStrategyInterface');
$runner->assertTrue($benefitRef->hasMethod('calculateDiscount'), 'BenefitDiscountStrategyInterface::calculateDiscount() exists');
$runner->assertTrue($benefitRef->hasMethod('getBenefitType'), 'BenefitDiscountStrategyInterface::getBenefitType() exists');

// Check PerformanceStrategyInterface methods
$perfRef = new ReflectionClass('HrTech\Contracts\PerformanceStrategyInterface');
$runner->assertTrue($perfRef->hasMethod('calculateScore'), 'PerformanceStrategyInterface::calculateScore() exists');
$runner->assertTrue($perfRef->hasMethod('getScoringModel'), 'PerformanceStrategyInterface::getScoringModel() exists');

// Check PayrollCalculatorInterface methods
$payrollRef = new ReflectionClass('HrTech\Contracts\PayrollCalculatorInterface');
$runner->assertTrue($payrollRef->hasMethod('calculatePayroll'), 'PayrollCalculatorInterface::calculatePayroll() exists');

// Check TimeLogImporterInterface methods
$importerRef = new ReflectionClass('HrTech\Contracts\TimeLogImporterInterface');
$runner->assertTrue($importerRef->hasMethod('import'), 'TimeLogImporterInterface::import() exists');

// Check ReportGeneratorInterface methods
$reportRef = new ReflectionClass('HrTech\Contracts\ReportGeneratorInterface');
$runner->assertTrue($reportRef->hasMethod('generate'), 'ReportGeneratorInterface::generate() exists');

// Check SingletonInterface methods
$singletonRef = new ReflectionClass('HrTech\Contracts\SingletonInterface');
$runner->assertTrue($singletonRef->hasMethod('getInstance'), 'SingletonInterface::getInstance() exists');
$runner->assertTrue($singletonRef->hasMethod('resetInstance'), 'SingletonInterface::resetInstance() exists');

// --------------------------------------------------------------------------
// 4. Suite 3: Domain Enums Autoloading & Methods
// --------------------------------------------------------------------------

$runner->suite('Domain Enums');

$enums = [
    'HrTech\Domain\Enums\UserRole',
    'HrTech\Domain\Enums\EmploymentType',
    'HrTech\Domain\Enums\TimeLogType',
    'HrTech\Domain\Enums\AdjustmentStatus',
    'HrTech\Domain\Enums\VacationStatus',
    'HrTech\Domain\Enums\BenefitType',
    'HrTech\Domain\Enums\ExamType',
    'HrTech\Domain\Enums\PolicyStatus',
];

foreach ($enums as $enumClass) {
    $runner->assertTrue(enum_exists($enumClass), "Enum {$enumClass} loaded successfully");
}

// Inspect EmploymentType
use HrTech\Domain\Enums\EmploymentType;
$runner->assertEquals('CLT', EmploymentType::CLT->value, 'EmploymentType::CLT backed value is CLT');
$runner->assertEquals('PJ', EmploymentType::PJ->value, 'EmploymentType::PJ backed value is PJ');
$runner->assertEquals('INTERN', EmploymentType::INTERN->value, 'EmploymentType::INTERN backed value is INTERN');
$runner->assertTrue(EmploymentType::CLT->hasLaborTaxes(), 'EmploymentType::CLT has labor taxes');
$runner->assertFalse(EmploymentType::PJ->hasLaborTaxes(), 'EmploymentType::PJ does not have CLT labor taxes');

// Inspect BenefitType
use HrTech\Domain\Enums\BenefitType;
$runner->assertTrue(in_array(BenefitType::TRANSPORTATION->value, ['TRANSPORTATION', 'VALE_TRANSPORTE']), 'BenefitType has TRANSPORTATION');
$runner->assertTrue(in_array(BenefitType::MEAL_VOUCHER->value, ['MEAL_VOUCHER', 'VALE_REFEICAO']), 'BenefitType has MEAL_VOUCHER');
$runner->assertTrue(in_array(BenefitType::HEALTH_PLAN->value, ['HEALTH_PLAN', 'PLANO_SAUDE']), 'BenefitType has HEALTH_PLAN');
$runner->assertEquals(6.0, BenefitType::TRANSPORTATION->maxLegalDeductionPercentage(), 'VT max legal deduction is 6%');

// Inspect UserRole
use HrTech\Domain\Enums\UserRole;
$runner->assertTrue(UserRole::SUPER_ADMIN->isAdministrative(), 'SUPER_ADMIN is administrative');
$runner->assertTrue(UserRole::SUPER_ADMIN->canManage(UserRole::EMPLOYEE), 'SUPER_ADMIN can manage EMPLOYEE');

// Inspect AdjustmentStatus
use HrTech\Domain\Enums\AdjustmentStatus;
$runner->assertTrue(AdjustmentStatus::PENDING->canTransitionTo(AdjustmentStatus::APPROVED), 'PENDING can transition to APPROVED');
$runner->assertFalse(AdjustmentStatus::APPROVED->canTransitionTo(AdjustmentStatus::PENDING), 'APPROVED cannot transition back to PENDING');

// Inspect VacationStatus
use HrTech\Domain\Enums\VacationStatus;
$runner->assertTrue(VacationStatus::REQUESTED->isPendingApproval(), 'REQUESTED is pending approval');

// Inspect ExamType
use HrTech\Domain\Enums\ExamType;
$runner->assertTrue(ExamType::ADMISSION->isMandatoryOnHiring(), 'ADMISSION is mandatory on hiring');

// Inspect PolicyStatus
use HrTech\Domain\Enums\PolicyStatus;
$runner->assertTrue(PolicyStatus::ACTIVE->isActive(), 'ACTIVE policy status is active');

// --------------------------------------------------------------------------
// 5. Suite 4: Custom Exceptions Hierarchy
// --------------------------------------------------------------------------

$runner->suite('Custom Exceptions Hierarchy');

$exceptions = [
    'HrTech\Exceptions\HrTechException',
    'HrTech\Exceptions\ValidationException',
    'HrTech\Exceptions\TenantNotFoundException',
    'HrTech\Exceptions\TenantContextException',
    'HrTech\Exceptions\UnauthorizedException',
    'HrTech\Exceptions\InvalidOperationException',
];

foreach ($exceptions as $ex) {
    $runner->assertTrue(class_exists($ex), "Exception {$ex} resolved by autoloader");
}

use HrTech\Exceptions\HrTechException;
use HrTech\Exceptions\ValidationException;
use HrTech\Exceptions\TenantNotFoundException;
use HrTech\Exceptions\TenantContextException;
use HrTech\Exceptions\UnauthorizedException;
use HrTech\Exceptions\InvalidOperationException;

$valEx = new ValidationException('Validation error', ['field' => 'Value is required']);
$runner->assertTrue($valEx instanceof HrTechException, 'ValidationException extends HrTechException');
$runner->assertTrue($valEx instanceof \Throwable, 'ValidationException implements Throwable');
$runner->assertEquals(['field' => 'Value is required'], $valEx->getErrors(), 'ValidationException retains error payload');
$runner->assertEquals('Value is required', $valEx->getFirstError('field'), 'ValidationException::getFirstError() retrieves field error');

$tenantEx = new TenantNotFoundException('Tenant not found', 'tenant-123');
$runner->assertTrue($tenantEx instanceof HrTechException, 'TenantNotFoundException extends HrTechException');
$runner->assertEquals('tenant-123', $tenantEx->getTenantId(), 'TenantNotFoundException retains tenant ID');

$contextEx = TenantContextException::missingContext('audit_log');
$runner->assertTrue($contextEx instanceof HrTechException, 'TenantContextException extends HrTechException');

$unauthEx = UnauthorizedException::forRole('admin', 'delete_payroll');
$runner->assertEquals('admin', $unauthEx->getRequiredRole(), 'UnauthorizedException tracks required role');

$invOpEx = InvalidOperationException::businessRule('OvertimeLimit', 'Exceeded daily maximum of 2 hours');
$runner->assertTrue($invOpEx instanceof HrTechException, 'InvalidOperationException extends HrTechException');

// --------------------------------------------------------------------------
// 6. Suite 5: Value Object — CNPJ (Modulo 11 Validation)
// --------------------------------------------------------------------------

$runner->suite('Value Object: CNPJ');

use HrTech\Domain\ValueObjects\Cnpj;

// Valid CNPJs
$validCnpj1 = new Cnpj('00.000.000/0001-91'); // Banco do Brasil
$runner->assertEquals('00000000000191', $validCnpj1->getValue(), 'CNPJ Banco do Brasil stripped digits');
$runner->assertEquals('00.000.000/0001-91', $validCnpj1->getFormatted(), 'CNPJ Banco do Brasil formatted output');
$runner->assertEquals('00000000', $validCnpj1->getRoot(), 'CNPJ root extraction (matriz base)');
$runner->assertEquals('0001', $validCnpj1->getBranch(), 'CNPJ branch extraction (filial)');
$runner->assertTrue($validCnpj1->isHeadquarters(), 'CNPJ branch 0001 is headquarters');

$validCnpj2 = new Cnpj('33000167000101'); // Petrobras
$runner->assertEquals('33.000.167/0001-01', $validCnpj2->getFormatted(), 'CNPJ Petrobras formatting from unformatted string');

// Equality
$cnpjSame = new Cnpj('00000000000191');
$runner->assertTrue($validCnpj1->equals($cnpjSame), 'CNPJ equality check');

// Rejection of invalid check digits
$runner->assertThrows(
    fn() => new Cnpj('00.000.000/0001-92'),
    ValidationException::class,
    'Rejects CNPJ with invalid first/second check digit'
);

// Rejection of repeated digits
$runner->assertThrows(
    fn() => new Cnpj('00000000000000'),
    ValidationException::class,
    'Rejects all-zeros repeated CNPJ sequence'
);
$runner->assertThrows(
    fn() => new Cnpj('11111111111111'),
    ValidationException::class,
    'Rejects all-ones repeated CNPJ sequence'
);

// Rejection of invalid length
$runner->assertThrows(
    fn() => new Cnpj('12345'),
    ValidationException::class,
    'Rejects too short CNPJ string'
);

// --------------------------------------------------------------------------
// 7. Suite 6: Value Object — CPF (Modulo 11 Validation)
// --------------------------------------------------------------------------

$runner->suite('Value Object: CPF');

use HrTech\Domain\ValueObjects\Cpf;

// Valid CPFs (Mathematically proven Modulo 11 check digits)
$validCpf1 = new Cpf('529.982.247-25');
$runner->assertEquals('52998224725', $validCpf1->getValue(), 'CPF stripped representation');
$runner->assertEquals('529.982.247-25', $validCpf1->getFormatted(), 'CPF canonical formatting');
$runner->assertEquals('***.982.247-**', $validCpf1->getMasked(), 'CPF LGPD masked output');

$validCpf2 = new Cpf('52998224725');
$runner->assertTrue($validCpf1->equals($validCpf2), 'CPF equality check');

// Rejection of invalid check digits
$runner->assertThrows(
    fn() => new Cpf('529.982.247-26'),
    ValidationException::class,
    'Rejects CPF with invalid check digits'
);

// Rejection of repeated sequences
$runner->assertThrows(
    fn() => new Cpf('111.111.111-11'),
    ValidationException::class,
    'Rejects all-ones repeated CPF sequence'
);
$runner->assertThrows(
    fn() => new Cpf('000.000.000-00'),
    ValidationException::class,
    'Rejects all-zeros repeated CPF sequence'
);

// Rejection of invalid length
$runner->assertThrows(
    fn() => new Cpf('12345678'),
    ValidationException::class,
    'Rejects short CPF string'
);

// --------------------------------------------------------------------------
// 8. Suite 7: Value Object — Money (Precision & Arithmetic)
// --------------------------------------------------------------------------

$runner->suite('Value Object: Money');

use HrTech\Domain\ValueObjects\Money;

$m1 = Money::fromFloat(1500.50);
$runner->assertEquals(150050, $m1->getCents(), 'Money::fromFloat(1500.50) sets 150050 cents');
$runner->assertEquals(1500.50, $m1->getAmount(), 'Money::getAmount() returns float 1500.50');
$runner->assertEquals('BRL', $m1->getCurrency(), 'Default currency is BRL');

$m2 = Money::fromCents(49950); // 499.50
$mSum = $m1->add($m2);
$runner->assertEquals(200000, $mSum->getCents(), 'Money addition: 1500.50 + 499.50 = 2000.00');

$mSub = $m1->subtract($m2);
$runner->assertEquals(100100, $mSub->getCents(), 'Money subtraction: 1500.50 - 499.50 = 1001.00');

// Percentage (e.g. 6% VT deduction from 2000.00 = 120.00)
$salary = Money::fromFloat(2000.00);
$vtDeduction = $salary->percentage(6.0);
$runner->assertEquals(12000, $vtDeduction->getCents(), 'Money 6% calculation: 2000.00 * 6% = 120.00');

// Immutability check
$runner->assertEquals(200000, $salary->getCents(), 'Money object is immutable; original retains 2000.00');

// Formatting
$formatted = $m1->getFormatted();
$runner->assertTrue(
    str_contains($formatted, '1.500,50') || str_contains($formatted, '1500,50'),
    "Money formatting contains Brazilian decimal format: '{$formatted}'"
);

// Martin Fowler Proportional Allocation (100 cents split 1:1:1 without penny loss)
$pennyTest = Money::fromCents(100);
$allocations = $pennyTest->allocate([1, 1, 1]);
$runner->assertEquals(3, count($allocations), 'Fowler allocation returns 3 shares');
$runner->assertEquals(34, $allocations[0]->getCents(), 'Share 1 receives 34 cents');
$runner->assertEquals(33, $allocations[1]->getCents(), 'Share 2 receives 33 cents');
$runner->assertEquals(33, $allocations[2]->getCents(), 'Share 3 receives 33 cents');
$totalAllocated = $allocations[0]->getCents() + $allocations[1]->getCents() + $allocations[2]->getCents();
$runner->assertEquals(100, $totalAllocated, 'Sum of allocated shares strictly equals 100 cents');

// Currency mismatch protection
$runner->assertThrows(
    fn() => $m1->add(Money::fromCents(100, 'USD')),
    InvalidOperationException::class,
    'Adding different currencies throws InvalidOperationException'
);

// --------------------------------------------------------------------------
// 9. Suite 8: Value Object — GeoLocation & Haversine Distance
// --------------------------------------------------------------------------

$runner->suite('Value Object: GeoLocation');

use HrTech\Domain\ValueObjects\GeoLocation;

// Valid Coordinates: São Paulo Centro (-23.550520, -46.633308)
$spCenter = new GeoLocation(-23.550520, -46.633308, 5.0);
$runner->assertEquals(-23.550520, $spCenter->getLatitude(), 'GeoLocation retains latitude');
$runner->assertEquals(-46.633308, $spCenter->getLongitude(), 'GeoLocation retains longitude');
$runner->assertEquals(5.0, $spCenter->getAccuracy(), 'GeoLocation retains accuracy');

// Avenida Paulista (-23.561414, -46.655881)
$spPaulista = new GeoLocation(-23.561414, -46.655881);

// Haversine Distance Calculation (known distance ~2600.25 meters)
$distance = $spCenter->distanceTo($spPaulista);
$runner->assertCloseTo(2600.0, $distance, 15.0, 'Haversine distance between SP Center and Paulista (~2600m)');

// Distance to self must be 0
$distSelf = $spCenter->distanceTo($spCenter);
$runner->assertCloseTo(0.0, $distSelf, 0.001, 'Distance to same coordinate is 0.0 meters');

// Geofence check
$runner->assertTrue($spCenter->isWithinRadius($spCenter, 50.0), 'Center is within 50m of itself');
$runner->assertFalse($spCenter->isWithinRadius($spPaulista, 500.0), 'Paulista is outside 500m radius of Center');

// Out of bounds rejection
$runner->assertThrows(
    fn() => new GeoLocation(-91.0, 0.0),
    ValidationException::class,
    'Rejects latitude < -90'
);
$runner->assertThrows(
    fn() => new GeoLocation(0.0, 181.0),
    ValidationException::class,
    'Rejects longitude > +180'
);
$runner->assertThrows(
    fn() => new GeoLocation(0.0, 0.0, -1.0),
    ValidationException::class,
    'Rejects negative accuracy'
);

// --------------------------------------------------------------------------
// 10. Suite 9: Strategy Pattern Interfaces Verification
// --------------------------------------------------------------------------

$runner->suite('Strategy Pattern Interfaces Verification');

use HrTech\Contracts\OvertimeStrategyInterface;
use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Contracts\PerformanceStrategyInterface;

// Test OvertimeStrategyInterface implementation
$mockOvertime50 = new class implements OvertimeStrategyInterface {
    public function calculateOvertime(float $hourlyRate, float $overtimeHours): float {
        return round($hourlyRate * 1.50 * $overtimeHours, 2);
    }
    public function getDescription(): string {
        return 'Mock Overtime 50%';
    }
};

$overtimeResult = $mockOvertime50->calculateOvertime(50.0, 10.0); // 50 * 1.5 * 10 = 750.00
$runner->assertEquals(750.00, $overtimeResult, 'OvertimeStrategyInterface implementation executes correctly');
$runner->assertEquals('Mock Overtime 50%', $mockOvertime50->getDescription(), 'OvertimeStrategyInterface getDescription()');

// --------------------------------------------------------------------------
// 11. Suite 10: Template Method Base Classes Verification & Execution
// --------------------------------------------------------------------------

$runner->suite('Template Method Base Classes Verification & Execution');

use HrTech\Patterns\TemplateMethod\Payroll\PayrollCalculatorTemplate;
use HrTech\Patterns\TemplateMethod\Importer\TimeLogImporterTemplate;
use HrTech\Patterns\TemplateMethod\Report\ReportGeneratorTemplate;

$runner->assertTrue(class_exists(PayrollCalculatorTemplate::class), 'PayrollCalculatorTemplate class exists');
$runner->assertTrue(class_exists(TimeLogImporterTemplate::class), 'TimeLogImporterTemplate class exists');
$runner->assertTrue(class_exists(ReportGeneratorTemplate::class), 'ReportGeneratorTemplate class exists');

// Verify PayrollCalculatorTemplate reflection
$payrollTplRef = new ReflectionClass(PayrollCalculatorTemplate::class);
$runner->assertTrue($payrollTplRef->isAbstract(), 'PayrollCalculatorTemplate is an abstract class');
$runner->assertTrue($payrollTplRef->hasMethod('calculatePayroll'), 'PayrollCalculatorTemplate has calculatePayroll method');
$runner->assertTrue($payrollTplRef->getMethod('calculatePayroll')->isFinal(), 'PayrollCalculatorTemplate::calculatePayroll is final');

// Verify TimeLogImporterTemplate execution via concrete subclass
$mockImporter = new class extends TimeLogImporterTemplate {
    protected function openSource(string $source): mixed {
        return json_decode($source, true);
    }
    protected function parseRecords(mixed $handle): array {
        return (array)$handle;
    }
    protected function closeSource(mixed $handle): void {
        // no-op
    }
};

$sampleJson = json_encode([
    ['employee_id' => 'EMP-001', 'timestamp' => '2026-09-11 08:00:00', 'type' => 'entry', 'latitude' => -23.55, 'longitude' => -46.63],
    ['employee_id' => 'EMP-001', 'timestamp' => '2026-09-11 12:00:00', 'type' => 'interval_start', 'latitude' => -23.55, 'longitude' => -46.63],
]);

$importSummary = $mockImporter->import($sampleJson);
$runner->assertEquals(2, $importSummary['total_raw'], 'TimeLogImporter processed 2 raw records');
$runner->assertEquals(2, $importSummary['valid_count'], 'TimeLogImporter validated 2 records');
$runner->assertEquals(2, $importSummary['persisted_count'], 'TimeLogImporter persisted 2 records');
$runner->assertTrue(isset($importSummary['records'][0]['hash']), 'TimeLogImporter generated Portaria 671 SHA-256 hash');
$runner->assertEquals(64, strlen($importSummary['records'][0]['hash']), 'Hash is 64 hex characters');

// Verify ReportGeneratorTemplate execution via concrete subclass
$mockReport = new class extends ReportGeneratorTemplate {
    protected function formatHeaders(array $options): mixed {
        return '=== ' . ($options['title'] ?? 'REPORT') . ' ===';
    }
    protected function formatBody(array $filteredData, array $options): mixed {
        $lines = [];
        foreach ($filteredData as $row) {
            $lines[] = "- {$row['name']}: {$row['dept']}";
        }
        return implode("\n", $lines);
    }
    protected function formatFooter(array $options): mixed {
        return '=== END OF REPORT ===';
    }
    protected function renderOutput(mixed $headers, mixed $body, mixed $footer, array $options): string {
        return "{$headers}\n{$body}\n{$footer}";
    }
};

$reportData = [
    'items' => [
        ['name' => 'Alice', 'dept' => 'HR'],
        ['name' => 'Bob', 'dept' => 'IT'],
        ['name' => 'Charlie', 'dept' => 'HR'],
    ]
];

$renderedReport = $mockReport->generate($reportData, ['title' => 'HR Staff', 'filter_key' => 'dept', 'filter_value' => 'HR']);
$runner->assertTrue(str_contains($renderedReport, '=== HR Staff ==='), 'Report header rendered with options');
$runner->assertTrue(str_contains($renderedReport, 'Alice: HR'), 'Report body contains Alice');
$runner->assertTrue(str_contains($renderedReport, 'Charlie: HR'), 'Report body contains Charlie');
$runner->assertFalse(str_contains($renderedReport, 'Bob: IT'), 'Report filter correctly excluded Bob (IT)');
$runner->assertTrue(str_contains($renderedReport, '=== END OF REPORT ==='), 'Report footer rendered');

// --------------------------------------------------------------------------
// 12. Final Summary & Exit Code
// --------------------------------------------------------------------------

$exitCode = $runner->printSummary();
exit($exitCode);
