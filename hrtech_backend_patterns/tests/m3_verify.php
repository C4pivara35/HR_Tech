<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 3 Verification Test Suite
 *
 * Standalone verification harness designed for PHP 8.3.6 CLI.
 * Rigorously verifies all 8 Design Patterns (Option 01) across 20 concrete classes:
 *  - 2 Singletons: TenantContextManager, AuditLogger
 *  - 9 Template Method subclasses:
 *      * Payroll: CltPayroll, PjPayroll, InternPayroll
 *      * Importer: CsvImporter, JsonImporter, ApiImporter
 *      * Report: PdfReportGenerator, ExcelReportGenerator, JsonReportGenerator
 *  - 9 Strategy implementations:
 *      * Overtime: Standard50Strategy, Sunday100Strategy, BankHoursStrategy
 *      * BenefitDiscount: TransportationVoucherStrategy, HealthPlanStrategy, MealVoucherStrategy
 *      * Performance: OkrStrategy, Evaluation360Strategy, KpiStrategy
 *  - Cryptographic integrity (AuditLog SHA-256 block chaining, tamper detection, Portaria 671 hashes)
 *  - Integration between Patterns and the 12 Milestone 2 Domain Entities
 *
 * Usage: php tests/m3_verify.php
 */

require_once dirname(__DIR__) . '/src/Autoloader.php';
\HrTech\Autoloader::registerDefault();

use HrTech\Contracts\BenefitDiscountStrategyInterface;
use HrTech\Contracts\OvertimeStrategyInterface;
use HrTech\Contracts\PayrollCalculatorInterface;
use HrTech\Contracts\PerformanceStrategyInterface;
use HrTech\Contracts\ReportGeneratorInterface;
use HrTech\Contracts\SingletonInterface;
use HrTech\Contracts\TimeLogImporterInterface;
use HrTech\Domain\Entities\AuditLog;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Department;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Entities\Role;
use HrTech\Domain\Entities\Tenant;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Enums\BenefitType;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\Enums\UserRole;
use HrTech\Domain\ValueObjects\Cnpj;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\TenantContextException;
use HrTech\Exceptions\UnauthorizedException;
use HrTech\Exceptions\ValidationException;
use HrTech\Patterns\Singleton\AuditLogger;
use HrTech\Patterns\Singleton\TenantContextManager;
use HrTech\Patterns\Strategy\BenefitDiscount\HealthPlanStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\MealVoucherStrategy;
use HrTech\Patterns\Strategy\BenefitDiscount\TransportationVoucherStrategy;
use HrTech\Patterns\Strategy\Overtime\BankHoursStrategy;
use HrTech\Patterns\Strategy\Overtime\Standard50Strategy;
use HrTech\Patterns\Strategy\Overtime\Sunday100Strategy;
use HrTech\Patterns\Strategy\Performance\Evaluation360Strategy;
use HrTech\Patterns\Strategy\Performance\KpiStrategy;
use HrTech\Patterns\Strategy\Performance\OkrStrategy;
use HrTech\Patterns\TemplateMethod\Importer\ApiImporter;
use HrTech\Patterns\TemplateMethod\Importer\CsvImporter;
use HrTech\Patterns\TemplateMethod\Importer\JsonImporter;
use HrTech\Patterns\TemplateMethod\Importer\TimeLogImporterTemplate;
use HrTech\Patterns\TemplateMethod\Payroll\CltPayroll;
use HrTech\Patterns\TemplateMethod\Payroll\InternPayroll;
use HrTech\Patterns\TemplateMethod\Payroll\PayrollCalculatorTemplate;
use HrTech\Patterns\TemplateMethod\Payroll\PjPayroll;
use HrTech\Patterns\TemplateMethod\Report\ExcelReportGenerator;
use HrTech\Patterns\TemplateMethod\Report\JsonReportGenerator;
use HrTech\Patterns\TemplateMethod\Report\PdfReportGenerator;
use HrTech\Patterns\TemplateMethod\Report\ReportGeneratorTemplate;

// --------------------------------------------------------------------------
// 1. ANSI Test Runner Engine
// --------------------------------------------------------------------------

final class M3TestRunner
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;
    private int $failedAssertions = 0;
    /** @var string[] */
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
            echo "  \033[31m✘ FAIL: {$message}\033[0m\n";
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
            echo "  \033[31m✘ FAIL: {$message} (Expected: {$expStr}, got: {$actStr})\033[0m\n";
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
            echo "  \033[31m✘ FAIL: {$message} (Expected {$expected} ±{$delta}, got {$actual})\033[0m\n";
            $this->failures[] = "[{$this->currentSuite}] {$message}";
        }
    }

    public function assertThrows(callable $callback, string $expectedExceptionClass, string $message): void
    {
        $this->totalAssertions++;
        try {
            $callback();
            $this->failedAssertions++;
            echo "  \033[31m✘ FAIL: {$message} (Expected exception {$expectedExceptionClass}, none thrown)\033[0m\n";
            $this->failures[] = "[{$this->currentSuite}] {$message} (No exception thrown)";
        } catch (\Throwable $e) {
            if ($e instanceof $expectedExceptionClass) {
                $this->passedAssertions++;
                echo "  \033[32m✔\033[0m {$message} (Caught expected " . get_class($e) . ")\n";
            } else {
                $this->failedAssertions++;
                echo "  \033[31m✘ FAIL: {$message} (Expected {$expectedExceptionClass}, got " . get_class($e) . ": {$e->getMessage()})\033[0m\n";
                $this->failures[] = "[{$this->currentSuite}] {$message} (Wrong exception type)";
            }
        }
    }

    public function printSummary(): int
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "\033[1;37mMILESTONE 3 VERIFICATION SUMMARY\033[0m\n";
        echo str_repeat('=', 70) . "\n";
        echo "Total Assertions : {$this->totalAssertions}\n";
        echo "Passed           : \033[32m{$this->passedAssertions}\033[0m\n";
        echo "Failed           : " . ($this->failedAssertions > 0 ? "\033[31m{$this->failedAssertions}\033[0m" : "0") . "\n";

        if ($this->failedAssertions > 0) {
            echo "\n\033[1;31mFAILED TESTS:\033[0m\n";
            foreach ($this->failures as $failure) {
                echo "  • {$failure}\n";
            }
            echo "\n\033[1;31mRESULT: VERIFICATION FAILED\033[0m\n";
            return 1;
        }

        echo "\n\033[1;32mRESULT: ALL MILESTONE 3 VERIFICATIONS PASSED (100%)\033[0m\n\n";
        return 0;
    }
}

$runner = new M3TestRunner();

// ==========================================================================
// 2. SUITE: Autoloading & Interface Conformance for All 20 Pattern Classes
// ==========================================================================
$runner->suite('Pattern Classes Autoloading & Interface Conformance');

$classesToVerify = [
    // Singletons
    [TenantContextManager::class, SingletonInterface::class, null],
    [AuditLogger::class, SingletonInterface::class, null],
    // Template Method: Payroll
    [CltPayroll::class, PayrollCalculatorInterface::class, PayrollCalculatorTemplate::class],
    [PjPayroll::class, PayrollCalculatorInterface::class, PayrollCalculatorTemplate::class],
    [InternPayroll::class, PayrollCalculatorInterface::class, PayrollCalculatorTemplate::class],
    // Template Method: Importer
    [CsvImporter::class, TimeLogImporterInterface::class, TimeLogImporterTemplate::class],
    [JsonImporter::class, TimeLogImporterInterface::class, TimeLogImporterTemplate::class],
    [ApiImporter::class, TimeLogImporterInterface::class, TimeLogImporterTemplate::class],
    // Template Method: Report
    [PdfReportGenerator::class, ReportGeneratorInterface::class, ReportGeneratorTemplate::class],
    [ExcelReportGenerator::class, ReportGeneratorInterface::class, ReportGeneratorTemplate::class],
    [JsonReportGenerator::class, ReportGeneratorInterface::class, ReportGeneratorTemplate::class],
    // Strategy: Overtime
    [Standard50Strategy::class, OvertimeStrategyInterface::class, null],
    [Sunday100Strategy::class, OvertimeStrategyInterface::class, null],
    [BankHoursStrategy::class, OvertimeStrategyInterface::class, null],
    // Strategy: BenefitDiscount
    [TransportationVoucherStrategy::class, BenefitDiscountStrategyInterface::class, null],
    [HealthPlanStrategy::class, BenefitDiscountStrategyInterface::class, null],
    [MealVoucherStrategy::class, BenefitDiscountStrategyInterface::class, null],
    // Strategy: Performance
    [OkrStrategy::class, PerformanceStrategyInterface::class, null],
    [Evaluation360Strategy::class, PerformanceStrategyInterface::class, null],
    [KpiStrategy::class, PerformanceStrategyInterface::class, null],
];

foreach ($classesToVerify as [$className, $expectedInterface, $expectedParent]) {
    $runner->assertTrue(class_exists($className), "Class {$className} exists and is autoloadable");
    $ref = new ReflectionClass($className);
    $runner->assertTrue($ref->implementsInterface($expectedInterface), "{$className} implements {$expectedInterface}");
    if ($expectedParent !== null) {
        $runner->assertTrue($ref->isSubclassOf($expectedParent), "{$className} extends {$expectedParent}");
    }
}

// ==========================================================================
// 3. SUITE: Singleton Pattern — TenantContextManager
// ==========================================================================
$runner->suite('Singleton: TenantContextManager');

TenantContextManager::resetInstance();

$mgr1 = TenantContextManager::getInstance();
$mgr2 = TenantContextManager::getInstance();
$runner->assertTrue($mgr1 === $mgr2, 'TenantContextManager::getInstance() returns exact same instance');

$runner->assertFalse($mgr1->hasActiveTenant(), 'Initial manager state has no active tenant');
$runner->assertThrows(
    fn() => $mgr1->getActiveTenantId(),
    TenantContextException::class,
    'getActiveTenantId() throws TenantContextException when context is uninitialized'
);
$runner->assertThrows(
    fn() => $mgr1->assertTenantActive(),
    TenantContextException::class,
    'assertTenantActive() throws TenantContextException when context is uninitialized'
);

// Set tenant via string ID
$mgr1->setActiveTenant('tenant-alpha');
$runner->assertTrue($mgr1->hasActiveTenant(), 'hasActiveTenant() returns true after setActiveTenant');
$runner->assertEquals('tenant-alpha', $mgr1->getActiveTenantId(), 'getActiveTenantId() matches string tenant ID');
$runner->assertEquals(null, $mgr1->getActiveTenant(), 'getActiveTenant() is null when set via string ID');

// Tenant assertion matches
$mgr1->assertTenantActive(); // should not throw
$mgr1->assertMatchesTenant('tenant-alpha'); // should not throw
$runner->assertTrue(true, 'assertMatchesTenant() succeeds for current tenant ID');

$runner->assertThrows(
    fn() => $mgr1->assertMatchesTenant('tenant-beta'),
    TenantContextException::class,
    'assertMatchesTenant() throws TenantContextException on tenant ID mismatch'
);

// Set tenant via Tenant domain entity
$tenantObj = new Tenant(
    id: 'tenant-omega',
    cnpj: '11.222.333/0001-81',
    corporateName: 'Omega Technologies SA',
    tradingName: 'Omega Tech'
);
$mgr1->setActiveTenant($tenantObj);
$runner->assertEquals('tenant-omega', $mgr1->getActiveTenantId(), 'getActiveTenantId() returns Tenant entity ID');
$runner->assertTrue($mgr1->getActiveTenant() === $tenantObj, 'getActiveTenant() returns Tenant entity instance');

// Clear context
$mgr1->clearContext();
$runner->assertFalse($mgr1->hasActiveTenant(), 'clearContext() removes active tenant');
$runner->assertEquals(null, $mgr1->getActiveTenant(), 'getActiveTenant() is null after clearContext');

// Scoped execution with runInContext
$mgr1->setActiveTenant('tenant-outer');
$runner->assertEquals('tenant-outer', $mgr1->getActiveTenantId(), 'Active tenant is outer before runInContext');

$result = $mgr1->runInContext('tenant-scoped', function () use ($mgr1, $runner) {
    $runner->assertEquals('tenant-scoped', $mgr1->getActiveTenantId(), 'Active tenant is scoped inside callback');
    return 42;
});
$runner->assertEquals(42, $result, 'runInContext returns callback return value');
$runner->assertEquals('tenant-outer', $mgr1->getActiveTenantId(), 'runInContext restores original tenant after completion');

// Scoped execution restores even on thrown exception
try {
    $mgr1->runInContext('tenant-broken', function () {
        throw new \RuntimeException('Scoped boom');
    });
} catch (\RuntimeException) {
    // Expected
}
$runner->assertEquals('tenant-outer', $mgr1->getActiveTenantId(), 'runInContext restores original tenant after exception');

// Empty tenant ID validation
$runner->assertThrows(
    fn() => $mgr1->setActiveTenant('   '),
    ValidationException::class,
    'setActiveTenant() throws ValidationException for blank string'
);

// Reset instance
TenantContextManager::resetInstance();
$mgr3 = TenantContextManager::getInstance();
$runner->assertFalse($mgr3 === $mgr1, 'resetInstance() recreates a fresh instance');
$runner->assertFalse($mgr3->hasActiveTenant(), 'Fresh instance has no active tenant');

// Unserialization prevention
$runner->assertThrows(
    fn() => $mgr3->__wakeup(),
    TenantContextException::class,
    'TenantContextManager::__wakeup throws TenantContextException preventing deserialization'
);

// ==========================================================================
// 4. SUITE: Singleton Pattern — AuditLogger with SHA-256 Chaining
// ==========================================================================
$runner->suite('Singleton: AuditLogger (SHA-256 Block Chaining & Integrity)');

AuditLogger::resetInstance();
$logger1 = AuditLogger::getInstance();
$logger2 = AuditLogger::getInstance();
$runner->assertTrue($logger1 === $logger2, 'AuditLogger::getInstance() returns singleton instance');
$runner->assertEquals(0, $logger1->count(), 'Initial AuditLogger has 0 logs');
$runner->assertTrue($logger1->verifyChainIntegrity(), 'verifyChainIntegrity() returns true on empty log store');

// Record first block (genesis)
$log1 = $logger1->log(
    tenantId: 'tenant-alpha',
    action: 'CREATE',
    entityType: 'Employee',
    entityId: 'EMP-001',
    payload: ['fullName' => 'Alice Silva', 'baseSalary' => 5000.00],
    userId: 'USER-ADMIN'
);
$runner->assertTrue($log1 instanceof AuditLog, 'log() returns valid AuditLog entity');
$runner->assertEquals('tenant-alpha', $log1->getTenantId(), 'log1 has correct tenantId');
$runner->assertEquals('CREATE', $log1->action, 'log1 has correct action');
$runner->assertEquals(AuditLog::GENESIS_HASH, $log1->previousHash, 'First block previousHash equals GENESIS_HASH');
$runner->assertEquals(64, strlen($log1->integrityHash), 'log1 integrityHash is 64 hex characters');
$runner->assertTrue($log1->verifyIntegrity(), 'log1 verifies its own internal integrity hash');

// Record second block (linked to log1)
$log2 = $logger1->log(
    tenantId: 'tenant-alpha',
    action: 'UPDATE',
    entityType: 'Employee',
    entityId: 'EMP-001',
    payload: ['previous_state' => ['salary' => 5000], 'new_state' => ['salary' => 5500]],
    userId: 'USER-ADMIN'
);
$runner->assertEquals($log1->integrityHash, $log2->previousHash, 'Second block previousHash links to first block integrityHash');
$runner->assertTrue($log2->verifyIntegrity($log1->integrityHash), 'Second block verifies cryptographic integrity with prevHash');

// Record third block
$log3 = $logger1->log(
    tenantId: 'tenant-beta',
    action: 'CREATE',
    entityType: 'Benefit',
    entityId: 'BEN-001',
    payload: ['name' => 'VR Premium', 'value' => 600.00]
);
$runner->assertEquals($log2->integrityHash, $log3->previousHash, 'Third block previousHash links to second block integrityHash');

$runner->assertEquals(3, $logger1->count(), 'AuditLogger count is 3');
$runner->assertEquals(2, $logger1->count('tenant-alpha'), 'Filtered count for tenant-alpha is 2');
$runner->assertEquals(1, $logger1->count('tenant-beta'), 'Filtered count for tenant-beta is 1');
$runner->assertTrue($logger1->getLastLog() === $log3, 'getLastLog() returns most recent entry');
$runner->assertTrue($logger1->verifyChainIntegrity(), 'verifyChainIntegrity() verifies 3-block chain successfully');

// Test tamper detection: mutate log in reflection to simulate tampering
$logsProp = (new ReflectionClass($logger1))->getProperty('logs');
$logsProp->setAccessible(true);
$currentLogs = $logsProp->getValue($logger1);

// Create a block with a broken chain pointer (non-matching previousHash)
$corruptedLog = AuditLog::record(
    id: 'corrupted-block',
    tenantId: 'tenant-alpha',
    actorUserId: 'tamperer',
    action: 'UPDATE',
    entityType: 'Employee',
    entityId: 'EMP-001',
    previousState: [],
    newState: ['salary' => 999999],
    previousHash: 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff'
);
$corruptedChain = [$currentLogs[0], $corruptedLog, $currentLogs[2]];
$logsProp->setValue($logger1, $corruptedChain);

$runner->assertFalse($logger1->verifyChainIntegrity(), 'verifyChainIntegrity() detects tampered chain pointer and returns false');

// Restore chain and clear
$logsProp->setValue($logger1, $currentLogs);
$runner->assertTrue($logger1->verifyChainIntegrity(), 'verifyChainIntegrity() returns true after restoring valid chain');

$logger1->clearLogs();
$runner->assertEquals(0, $logger1->count(), 'clearLogs() removes all records');
$runner->assertTrue($logger1->verifyChainIntegrity(), 'verifyChainIntegrity() is true after clearLogs');

// Validation exceptions on invalid log args
$runner->assertThrows(
    fn() => $logger1->log('', 'ACTION', 'Entity', '1', []),
    ValidationException::class,
    'log() throws ValidationException on blank tenantId'
);
$runner->assertThrows(
    fn() => $logger1->log('tenant-1', '', 'Entity', '1', []),
    ValidationException::class,
    'log() throws ValidationException on blank action'
);

AuditLogger::resetInstance();

// ==========================================================================
// 5. SUITE: Template Method — Payroll Calculators
// ==========================================================================
$runner->suite('Template Method: Payroll Calculators (CLT, PJ, Intern)');

// Employee helpers
$cltEmployee = new Employee(
    id: 'EMP-CLT-1',
    tenantId: 'tenant-corp',
    cpf: '123.456.789-09',
    fullName: 'Carlos Mendes',
    email: 'carlos@empresa.com',
    phone: '11988887777',
    birthDate: '1985-05-15',
    admissionDate: '2020-01-10',
    departmentId: 'DEP-ENG',
    roleId: 'ROLE-ENG',
    baseSalary: 4000.00,
    employmentType: EmploymentType::CLT
);

$pjEmployee = new Employee(
    id: 'EMP-PJ-1',
    tenantId: 'tenant-corp',
    cpf: '529.982.247-25',
    fullName: 'Roberto Consultoria ME',
    email: 'roberto@consultoria.com',
    phone: '11977776666',
    birthDate: '1980-02-20',
    admissionDate: '2021-03-01',
    departmentId: 'DEP-IT',
    roleId: 'ROLE-CONSULTANT',
    baseSalary: 12000.00,
    employmentType: EmploymentType::PJ
);

$internEmployee = new Employee(
    id: 'EMP-INT-1',
    tenantId: 'tenant-corp',
    cpf: '735.345.932-86',
    fullName: 'Beatriz Estagiária',
    email: 'beatriz@empresa.com',
    phone: '11966665555',
    birthDate: '2004-09-10',
    admissionDate: '2024-02-01',
    departmentId: 'DEP-HR',
    roleId: 'ROLE-INTERN',
    baseSalary: 1800.00,
    employmentType: EmploymentType::INTERN
);

// --- CLT Payroll ---
$cltCalculator = new CltPayroll();

// Test contract validation mismatch
$runner->assertThrows(
    fn() => $cltCalculator->calculatePayroll($pjEmployee, []),
    ValidationException::class,
    'CltPayroll throws ValidationException when evaluating a PJ contractor'
);

// Standard CLT execution with overtime & dependents
$cltData = [
    'overtime_amount' => 500.00,
    'bonus' => 300.00,
    'dependents' => 1,
    'benefits_discount' => 150.00,
];
$cltPayslip = $cltCalculator->calculatePayroll($cltEmployee, $cltData);

$runner->assertEquals('EMP-CLT-1', $cltPayslip['employee_id'], 'CLT payslip has correct employee_id');
$runner->assertEquals('CLT', $cltPayslip['contract_type'], 'CLT payslip contract_type is CLT');

$b = $cltPayslip['breakdown'];
$runner->assertEquals(4800.00, $b['gross_salary'], 'Gross salary 4000 base + 500 overtime + 300 bonus = 4800.00');

// Calculate progressive INSS manually for 4800.00:
// 1412.00 * 0.075 = 105.90
// (2666.68 - 1412.00) * 0.09 = 112.9212
// (4000.03 - 2666.68) * 0.12 = 160.002
// (4800.00 - 4000.03) * 0.14 = 111.9958
// Sum = 105.90 + 112.9212 + 160.002 + 111.9958 = 490.819 -> 490.82
$runner->assertCloseTo(490.82, (float)$b['inss_deduction'], 0.05, 'CLT progressive INSS calculation matches 2024-2026 standard tables (~490.82)');

// Base IRRF = Gross (4800) - INSS (490.82) - 1 Dependent (189.59) = 4119.59
// Bracket: 3751.06 to 4664.68 (22.5%, deduction 662.77)
// IRRF = 4119.59 * 0.225 - 662.77 = 926.90775 - 662.77 = 264.14
$runner->assertCloseTo(264.14, (float)$b['irrf_deduction'], 0.05, 'CLT progressive IRRF calculation with dependent allowance matches (~264.14)');
$runner->assertEquals(150.00, (float)$b['benefits_discount'], 'Benefits discount 150.00 applied');

// Net salary = 4800 - 490.82 - 264.14 - 150.00 = 3895.04
$expectedNet = round(4800.00 - $b['inss_deduction'] - $b['irrf_deduction'] - 150.00, 2);
$runner->assertEquals($expectedNet, (float)$b['net_salary'], "Net salary accurately matches gross minus all deductions ({$expectedNet})");

// High salary hitting INSS teto
$highClt = new Employee(
    id: 'EMP-CLT-HIGH',
    tenantId: 'tenant-corp',
    cpf: '722.239.069-31',
    fullName: 'Diretora Maria',
    email: 'maria@empresa.com',
    phone: '11955554444',
    birthDate: '1975-01-01',
    admissionDate: '2015-01-01',
    departmentId: 'DEP-EXEC',
    roleId: 'ROLE-DIR',
    baseSalary: 25000.00,
    employmentType: EmploymentType::CLT
);
$highPayslip = $cltCalculator->calculatePayroll($highClt, []);
$tetoInss = (float)$highPayslip['breakdown']['inss_deduction'];
$runner->assertCloseTo(908.86, $tetoInss, 0.5, "High salary INSS capped at statutory teto ceiling (~908.86, got {$tetoInss})");

// --- PJ Payroll ---
$pjCalculator = new PjPayroll();

// Mismatch check
$runner->assertThrows(
    fn() => $pjCalculator->calculatePayroll($cltEmployee, []),
    ValidationException::class,
    'PjPayroll throws ValidationException when evaluating a CLT employee'
);

$pjData = [
    'invoice_amount' => 15000.00,
    'withhold_irrf' => true,
    'withhold_csrf' => true,
];
$pjPayslip = $pjCalculator->calculatePayroll($pjEmployee, $pjData);
$pjB = $pjPayslip['breakdown'];

$runner->assertEquals('PJ', $pjPayslip['contract_type'], 'PJ payslip contract_type is PJ');
$runner->assertEquals(15000.00, (float)$pjB['gross_salary'], 'PJ gross salary matches invoice amount');
$runner->assertEquals(0.0, (float)$pjB['inss_deduction'], 'PJ INSS labor deduction is strictly 0.0');

// IRRF 1.5% of 15000 = 225.00
$runner->assertEquals(225.00, (float)$pjB['irrf_deduction'], 'PJ IRRF withholding is 1.5% (225.00)');
// CSRF 4.65% of 15000 = 697.50
$runner->assertEquals(697.50, (float)$pjB['csrf_deduction'], 'PJ CSRF withholding is 4.65% (697.50)');
// Total withholdings = 225.00 + 697.50 = 922.50
$runner->assertEquals(922.50, (float)$pjB['total_withholdings'], 'PJ total corporate withholdings match 922.50');
// Net = 15000 - 922.50 = 14077.50
$runner->assertEquals(14077.50, (float)$pjB['net_salary'], 'PJ net invoice payment matches 14077.50');

// --- Intern Payroll ---
$internCalculator = new InternPayroll();

// Mismatch check
$runner->assertThrows(
    fn() => $internCalculator->calculatePayroll($cltEmployee, []),
    ValidationException::class,
    'InternPayroll throws ValidationException when evaluating a CLT employee'
);

$internData = [
    'grant_stipend' => 1800.00,
    'transport_allowance' => 250.00,
];
$internPayslip = $internCalculator->calculatePayroll($internEmployee, $internData);
$intB = $internPayslip['breakdown'];

$runner->assertEquals('INTERN', $internPayslip['contract_type'], 'Intern payslip contract_type is INTERN');
$runner->assertEquals(2050.00, (float)$intB['gross_salary'], 'Intern gross is Bolsa (1800) + Transport (250) = 2050.00');
$runner->assertEquals(0.0, (float)$intB['inss_deduction'], 'Intern INSS is strictly 0.0 under Lei 11.788/2008');
$runner->assertEquals(0.0, (float)$intB['irrf_deduction'], 'Intern IRRF is strictly 0.0 under Lei 11.788/2008');
$runner->assertEquals(0.0, (float)$intB['benefits_discount'], 'Intern mandatory benefit discounts are 0.0');
$runner->assertEquals(2050.00, (float)$intB['net_salary'], 'Intern net salary strictly equals full gross grant stipend (2050.00)');
$runner->assertTrue(str_contains((string)$intB['legal_framework'], '11.788/2008'), 'Intern payslip cites Lei 11.788/2008 framework');

// ==========================================================================
// 6. SUITE: Template Method — Biometric Time Log Importers
// ==========================================================================
$runner->suite('Template Method: Importers (CSV, JSON, Authenticated API)');

// --- CSV Importer (Comma and Semicolon) ---
$csvImporter = new CsvImporter();

$commaCsv = <<<CSV
employee_id,punch_time,type,lat,lon
EMP-001,2026-09-15 08:00:00,entry,-23.5505,-46.6333
EMP-001,2026-09-15 12:00:00,interval_start,-23.5505,-46.6333
EMP-001,2026-09-15 13:00:00,interval_end,-23.5505,-46.6333
EMP-001,2026-09-15 17:00:00,exit,-23.5505,-46.6333
CSV;

$csvSummary = $csvImporter->import($commaCsv);
$runner->assertEquals(4, $csvSummary['total_raw'], 'CsvImporter processes 4 raw comma-separated records');
$runner->assertEquals(4, $csvSummary['valid_count'], 'CsvImporter validates 4 records');
$runner->assertEquals(4, $csvSummary['persisted_count'], 'CsvImporter persists 4 records');
$runner->assertEquals(64, strlen($csvSummary['records'][0]['hash']), 'CsvImporter generates 64-character SHA-256 hash');

// Convert to TimeLog entities
$csvEntities = $csvImporter->convertToTimeLogs($csvSummary, 'tenant-test');
$runner->assertEquals(4, count($csvEntities), 'convertToTimeLogs creates 4 TimeLog entities');
$runner->assertTrue($csvEntities[0] instanceof TimeLog, 'Converted record is TimeLog instance');
$runner->assertEquals(TimeLogType::ENTRY, $csvEntities[0]->type, 'Converted record 0 has ENTRY type');
$runner->assertEquals(TimeLogType::INTERVAL_START, $csvEntities[1]->type, 'Converted record 1 has INTERVAL_START type');
$runner->assertEquals('EMP-001', $csvEntities[0]->employeeId, 'Converted record employeeId matches');

// Semicolon-delimited CSV test
$semiCsv = <<<CSV
emp_id;timestamp;punch_type;latitude;longitude
EMP-002;2026-09-15 09:00:00;ENTRY;-23.55;-46.63
EMP-002;2026-09-15 18:00:00;EXIT;-23.55;-46.63
CSV;

$semiSummary = $csvImporter->import($semiCsv);
$runner->assertEquals(2, $semiSummary['valid_count'], 'CsvImporter handles semicolon delimiter with alternate header aliases');

// Invalid CSV missing required schema field
$badCsv = <<<CSV
employee_id,type
EMP-003,entry
CSV;
$runner->assertThrows(
    fn() => $csvImporter->import($badCsv),
    ValidationException::class,
    'CsvImporter throws ValidationException when required timestamp field is missing'
);

// --- JSON Importer ---
$jsonImporter = new JsonImporter();

$jsonPayload = json_encode([
    'punches' => [
        ['employee_id' => 'EMP-100', 'timestamp' => '2026-09-16 08:30:00', 'type' => 'entry', 'lat' => -23.56, 'lon' => -46.65],
        ['employee_id' => 'EMP-100', 'timestamp' => '2026-09-16 17:30:00', 'type' => 'exit', 'lat' => -23.56, 'lon' => -46.65],
    ],
]);

$jsonSummary = $jsonImporter->import($jsonPayload);
$runner->assertEquals(2, $jsonSummary['total_raw'], 'JsonImporter processes 2 nested punches');
$runner->assertEquals(2, $jsonSummary['valid_count'], 'JsonImporter validates 2 records');
$jsonEntities = $jsonImporter->convertToTimeLogs($jsonSummary, 'tenant-json');
$runner->assertEquals(2, count($jsonEntities), 'JsonImporter converts to 2 TimeLog entity instances');

// Malformed JSON check
$runner->assertThrows(
    fn() => $jsonImporter->import('{invalid-json'),
    ValidationException::class,
    'JsonImporter throws ValidationException on malformed JSON syntax'
);

// --- Authenticated API Importer ---
$apiImporter = new ApiImporter(expectedBearerToken: 'secret-token-xyz', expectedApiKey: 'api-key-999');

$unauthenticatedPayload = json_encode([
    'headers' => ['Authorization' => 'Bearer wrong-token', 'X-API-Key' => 'api-key-999'],
    'records' => [['employee_id' => 'EMP-1', 'timestamp' => '2026-09-16 08:00:00', 'type' => 'entry']],
]);
$runner->assertThrows(
    fn() => $apiImporter->import($unauthenticatedPayload),
    UnauthorizedException::class,
    'ApiImporter rejects request with invalid Bearer token'
);

$validAuthPayload = json_encode([
    'headers' => ['Authorization' => 'Bearer secret-token-xyz', 'X-API-Key' => 'api-key-999'],
    'records' => [
        ['employee_id' => 'EMP-API-1', 'timestamp' => '2026-09-16 08:00:00', 'type' => 'entry', 'latitude' => -23.55, 'longitude' => -46.63],
        ['employee_id' => 'EMP-API-1', 'timestamp' => '2026-09-16 17:00:00', 'type' => 'exit', 'latitude' => -23.55, 'longitude' => -46.63],
    ],
]);
$apiSummary = $apiImporter->import($validAuthPayload);
$runner->assertEquals(2, $apiSummary['valid_count'], 'ApiImporter successfully ingests authenticated punches');
$apiEntities = $apiImporter->convertToTimeLogs($apiSummary, 'tenant-api');
$runner->assertEquals(2, count($apiEntities), 'ApiImporter converts authenticated records into TimeLog entities');

// ==========================================================================
// 7. SUITE: Template Method — Report Generators
// ==========================================================================
$runner->suite('Template Method: Report Generators (PDF, Excel, JSON)');

$reportData = [
    ['id' => 'EMP-01', 'name' => 'Alice Silva', 'department' => 'IT', 'salary' => 6000.00],
    ['id' => 'EMP-02', 'name' => 'Bob Santos', 'department' => 'Finance', 'salary' => 7500.00],
    ['id' => 'EMP-03', 'name' => 'Carla Lima', 'department' => 'IT', 'salary' => 8200.00],
    ['id' => 'EMP-04', 'name' => 'Diego Costa', 'department' => 'Operations', 'salary' => 4500.00],
];

// --- PDF Report Generator ---
$pdfGen = new PdfReportGenerator();
$pdfOutput = $pdfGen->generate($reportData, [
    'title' => 'Payroll Audit Report',
    'company' => 'HRTech Global',
    'columns' => ['id', 'name', 'department', 'salary'],
]);

$runner->assertTrue(str_contains($pdfOutput, 'PAYROLL AUDIT REPORT'), 'PdfReportGenerator includes uppercase title');
$runner->assertTrue(str_contains($pdfOutput, 'HRTech Global'), 'PdfReportGenerator includes company header');
$runner->assertTrue(str_contains($pdfOutput, 'Alice Silva'), 'PdfReportGenerator includes Alice Silva');
$runner->assertTrue(str_contains($pdfOutput, 'SUMMARY TOTALS: 4 Record(s) Processed'), 'PdfReportGenerator footer includes record count');
$runner->assertTrue(str_contains($pdfOutput, '--- Page 1 of 1 ---'), 'PdfReportGenerator includes pagination indicator');

// Filter check in PDF generator
$filteredPdf = $pdfGen->generate($reportData, [
    'title' => 'IT Staff Only',
    'filter_key' => 'department',
    'filter_value' => 'IT',
    'columns' => ['id', 'name', 'department'],
]);
$runner->assertTrue(str_contains($filteredPdf, 'Alice Silva'), 'Filtered PDF contains IT member Alice');
$runner->assertTrue(str_contains($filteredPdf, 'Carla Lima'), 'Filtered PDF contains IT member Carla');
$runner->assertFalse(str_contains($filteredPdf, 'Bob Santos'), 'Filtered PDF excludes Finance member Bob');
$runner->assertTrue(str_contains($filteredPdf, 'SUMMARY TOTALS: 2 Record(s) Processed'), 'Filtered PDF reflects 2 records');

// --- Excel Report Generator ---
$excelGen = new ExcelReportGenerator();
$excelCsv = $excelGen->generate($reportData, [
    'columns' => ['id', 'name', 'department', 'salary'],
]);

$runner->assertTrue(str_contains($excelCsv, 'id,name,department,salary'), 'ExcelReportGenerator includes CSV header row');
$runner->assertTrue(str_contains($excelCsv, 'Alice Silva'), 'ExcelReportGenerator includes data rows');
$runner->assertTrue(str_contains($excelCsv, 'TOTAL (4 records)'), 'ExcelReportGenerator includes summary totals row');
// Total salary = 6000 + 7500 + 8200 + 4500 = 26200.00
$runner->assertTrue(str_contains($excelCsv, '26200.00'), 'ExcelReportGenerator calculates accurate numeric column sum (26200.00)');

// TSV format check
$excelTsv = $excelGen->generate($reportData, [
    'format' => 'tsv',
    'columns' => ['id', 'name', 'salary'],
]);
$runner->assertTrue(str_contains($excelTsv, "id\tname\tsalary"), 'ExcelReportGenerator supports TSV tab delimiter');

// --- JSON Report Generator ---
$jsonGen = new JsonReportGenerator();
$jsonReportOutput = $jsonGen->generate($reportData, [
    'title' => 'Analytics Summary',
    'tenant_id' => 'tenant-corp',
    'pretty_print' => true,
]);

$decodedReport = json_decode($jsonReportOutput, true);
$runner->assertTrue(is_array($decodedReport), 'JsonReportGenerator output is valid JSON');
$runner->assertEquals('Analytics Summary', $decodedReport['metadata']['report_name'], 'JsonReport metadata has report title');
$runner->assertEquals('tenant-corp', $decodedReport['metadata']['tenant_id'], 'JsonReport metadata has tenant_id');
$runner->assertEquals(4, count($decodedReport['records']), 'JsonReport records has 4 items');
$runner->assertEquals(4, $decodedReport['analytics']['total_records'], 'JsonReport analytics has total_records: 4');
$runner->assertEquals(26200.00, (float)$decodedReport['analytics']['aggregations']['salary'], 'JsonReport analytics has accurate salary sum (26200.00)');

// ==========================================================================
// 8. SUITE: Strategy Pattern — Overtime Calculations
// ==========================================================================
$runner->suite('Strategy: Overtime Compensation (50%, 100%, Bank Hours)');

$hourlyWage = 30.00; // R$ 30,00 / hour

// Standard 50%
$strat50 = new Standard50Strategy();
$pay50 = $strat50->calculateOvertime($hourlyWage, 10.0);
// 30 * 1.5 * 10 = 450.00
$runner->assertEquals(450.00, $pay50, 'Standard50Strategy: 30.00/h * 1.5 * 10h = 450.00');
$runner->assertEquals(0.0, $strat50->calculateOvertime($hourlyWage, 0.0), 'Standard50Strategy returns 0.0 for 0 hours');
$runner->assertEquals(0.0, $strat50->calculateOvertime($hourlyWage, -5.0), 'Standard50Strategy returns 0.0 for negative hours');
$runner->assertTrue(str_contains($strat50->getDescription(), 'Art. 59 §1 of CLT'), 'Standard50Strategy description cites Art. 59 §1 CLT');

// Sunday 100%
$strat100 = new Sunday100Strategy();
$pay100 = $strat100->calculateOvertime($hourlyWage, 8.0);
// 30 * 2.0 * 8 = 480.00
$runner->assertEquals(480.00, $pay100, 'Sunday100Strategy: 30.00/h * 2.0 * 8h = 480.00');
$runner->assertTrue(str_contains($strat100->getDescription(), 'Súmula 146 TST'), 'Sunday100Strategy description cites Súmula 146 TST');

// Bank Hours Strategy
$stratBank = new BankHoursStrategy(creditFactor: 1.5);
$payBank = $stratBank->calculateOvertime($hourlyWage, 10.0);
$runner->assertEquals(0.0, $payBank, 'BankHoursStrategy monetary overtime compensation is strictly 0.00');
// 10 hours * 60 minutes * 1.5 factor = 900 minutes
$minutesCredited = $stratBank->calculateBankMinutes(10.0);
$runner->assertEquals(900, $minutesCredited, 'BankHoursStrategy calculates 900 credited bank minutes (10h * 60 * 1.5)');
$runner->assertEquals(1.5, $stratBank->getCreditFactor(), 'BankHoursStrategy credit factor is 1.5');
$runner->assertTrue(str_contains($stratBank->getDescription(), 'Art. 59 §2 of CLT'), 'BankHoursStrategy description cites Art. 59 §2 CLT');

// ==========================================================================
// 9. SUITE: Strategy Pattern — Benefit Discount Calculations
// ==========================================================================
$runner->suite('Strategy: Benefit Discounts (Transportation, Health, Meal)');

// Collaboration collaborator: base salary R$ 3.000,00
$employeeBenefitTest = new Employee(
    id: 'EMP-BEN-1',
    tenantId: 'tenant-corp',
    cpf: '767.809.101-04',
    fullName: 'Juliana Ferreira',
    email: 'juliana@empresa.com',
    phone: '11944443333',
    birthDate: '1995-06-10', // ~31 years old
    admissionDate: '2022-04-01',
    departmentId: 'DEP-FIN',
    roleId: 'ROLE-ANALYST',
    baseSalary: 3000.00,
    employmentType: EmploymentType::CLT
);

// --- Transportation Voucher Strategy (Lei 7.418/1985) ---
// Statutory cap: 6% of 3000 = 180.00
$vtStrategy = new TransportationVoucherStrategy();
$runner->assertEquals(BenefitType::TRANSPORTATION->value, $vtStrategy->getBenefitType(), 'VT Strategy applies to TRANSPORTATION');

// Scenario A: Benefit value (350.00) > 6% salary cap (180.00) -> Employee pays cap (180.00)
$expensiveVt = new Benefit(
    id: 'BEN-VT-EXP',
    tenantId: 'tenant-corp',
    type: BenefitType::TRANSPORTATION,
    name: 'Vale Transporte SP',
    provider: 'SPTrans',
    value: Money::fromFloat(350.00),
    employeeCostSharePercentage: 6.0
);
$discountA = $vtStrategy->calculateDiscount($employeeBenefitTest, $expensiveVt);
$runner->assertEquals(180.00, $discountA, 'VT Strategy caps deduction at 6% of basic salary (180.00 vs 350.00 cost)');

// Scenario B: Benefit value (120.00) < 6% salary cap (180.00) -> Employee pays actual value (120.00)
$cheapVt = new Benefit(
    id: 'BEN-VT-CHEAP',
    tenantId: 'tenant-corp',
    type: BenefitType::TRANSPORTATION,
    name: 'Vale Transporte Parcial',
    provider: 'SPTrans',
    value: Money::fromFloat(120.00),
    employeeCostSharePercentage: 6.0
);
$discountB = $vtStrategy->calculateDiscount($employeeBenefitTest, $cheapVt);
$runner->assertEquals(120.00, $discountB, 'VT Strategy deducts actual voucher value when lower than statutory 6% cap (120.00)');

// --- Health Plan Strategy (Base copay + age bracket) ---
$healthStrategy = new HealthPlanStrategy(fixedBaseCopay: 50.00);
$runner->assertEquals(BenefitType::HEALTH_PLAN->value, $healthStrategy->getBenefitType(), 'Health Plan Strategy applies to HEALTH_PLAN');

$healthBenefit = new Benefit(
    id: 'BEN-HEALTH',
    tenantId: 'tenant-corp',
    type: BenefitType::HEALTH_PLAN,
    name: 'Plano Saúde Ouro',
    provider: 'Bradesco Saúde',
    value: Money::fromFloat(800.00),
    employeeCostSharePercentage: 0.0
);

// Employee is 31 years old -> Bracket 29-38 years has 10% rate (800 * 0.10 = 80.00)
// Total discount = fixedBaseCopay (50.00) + ageSurcharge (80.00) = 130.00
$healthDiscount = $healthStrategy->calculateDiscount($employeeBenefitTest, $healthBenefit);
$runner->assertEquals(130.00, $healthDiscount, 'HealthPlanStrategy calculates 50.00 base + 10% age tier of 800 (80.00) = 130.00');

// Verify age rate tier resolver across ages
$runner->assertEquals(0.00, $healthStrategy->resolveAgeRate(17), 'Age 17 tier is 0%');
$runner->assertEquals(0.05, $healthStrategy->resolveAgeRate(25), 'Age 25 tier is 5%');
$runner->assertEquals(0.10, $healthStrategy->resolveAgeRate(35), 'Age 35 tier is 10%');
$runner->assertEquals(0.15, $healthStrategy->resolveAgeRate(45), 'Age 45 tier is 15%');
$runner->assertEquals(0.20, $healthStrategy->resolveAgeRate(55), 'Age 55 tier is 20%');
$runner->assertEquals(0.30, $healthStrategy->resolveAgeRate(65), 'Age 65 tier is 30%');

// --- Meal Voucher Strategy (PAT Lei 6.321/1976 20% cap) ---
$mealStrategy = new MealVoucherStrategy(fixedNominalCopay: 80.00);
$runner->assertEquals(BenefitType::MEAL_VOUCHER->value, $mealStrategy->getBenefitType(), 'Meal Voucher Strategy applies to MEAL_VOUCHER');

// Scenario A: Benefit value 600.00, PAT 20% cap = 120.00. Nominal copay is 80.00 < 120.00 -> 80.00
$vrBenefit = new Benefit(
    id: 'BEN-VR-1',
    tenantId: 'tenant-corp',
    type: BenefitType::MEAL_VOUCHER,
    name: 'Vale Refeição Ticket',
    provider: 'Edenred',
    value: Money::fromFloat(600.00),
    employeeCostSharePercentage: 20.0
);
$vrDiscountA = $mealStrategy->calculateDiscount($employeeBenefitTest, $vrBenefit);
$runner->assertEquals(80.00, $vrDiscountA, 'MealVoucherStrategy deducts nominal copayment 80.00 when below PAT 20% cap');

// Scenario B: Nominal copay is 150.00 > PAT 20% cap (120.00) -> Capped at 120.00
$greedyMealStrategy = new MealVoucherStrategy(fixedNominalCopay: 150.00);
$vrDiscountB = $greedyMealStrategy->calculateDiscount($employeeBenefitTest, $vrBenefit);
$runner->assertEquals(120.00, $vrDiscountB, 'MealVoucherStrategy strictly caps deduction at 20% legal PAT limit (120.00)');

// ==========================================================================
// 10. SUITE: Strategy Pattern — Performance Appraisals
// ==========================================================================
$runner->suite('Strategy: Performance Scoring (OKR, 360 Evaluation, KPI)');

$perfEmployee = new Employee(
    id: 'EMP-PERF-1',
    tenantId: 'tenant-corp',
    cpf: '545.658.577-40',
    fullName: 'Lucas Oliveira',
    email: 'lucas@empresa.com',
    phone: '11933332222',
    birthDate: '1992-11-25',
    admissionDate: '2021-08-15',
    departmentId: 'DEP-PROD',
    roleId: 'ROLE-PM',
    baseSalary: 10000.00,
    employmentType: EmploymentType::CLT
);

// --- OKR Strategy ---
$okrStrat = new OkrStrategy();
$runner->assertEquals('OKR', $okrStrat->getScoringModel(), 'OKR Strategy scoring model identifier is OKR');

// 3 Key Results: 80%, 100%, 60% -> Average: (0.8 + 1.0 + 0.6) / 3 = 0.80 -> Score: 80.0
$okrMetrics = [
    'key_results' => [
        ['name' => 'KR1: Retention', 'current' => 80, 'target' => 100],
        ['name' => 'KR2: Features', 'current' => 10, 'target' => 10],
        ['name' => 'KR3: Latency', 'current' => 6, 'target' => 10],
    ],
];
$okrScore = $okrStrat->calculateScore($perfEmployee, $okrMetrics);
$runner->assertEquals(80.00, $okrScore, 'OkrStrategy calculates accurate score of 80.00 from 80% average achievement');

// Bonus calculation: 10000 base salary * 1.5 max bonus months * (80.0 / 100) = 12000.00
$bonus = $okrStrat->calculateBonus($perfEmployee, $okrScore, maxBonusMonths: 1.5);
$runner->assertEquals(12000.00, $bonus, 'OkrStrategy calculates accurate bonus R$ 12.000,00 from score');

// --- 360-Degree Evaluation Strategy ---
$eval360 = new Evaluation360Strategy();
$runner->assertEquals('EVALUATION_360', $eval360->getScoringModel(), '360 Evaluation model is EVALUATION_360');

// Self = 4.0/5 (80%), Peers = [4.5, 4.0] (85%), Manager = 4.5/5 (90%)
// Standard weights: self 15%, peers 35%, manager 50%
// Expected: (80 * 0.15) + (85 * 0.35) + (90 * 0.50) = 12.0 + 29.75 + 45.0 = 86.75
$evalMetrics = [
    'self' => 4.0,
    'peers' => [4.5, 4.0],
    'manager' => 4.5,
];
$evalScore = $eval360->calculateScore($perfEmployee, $evalMetrics);
$runner->assertEquals(86.75, $evalScore, 'Evaluation360Strategy computes weighted composite score (86.75) across raters');

// --- KPI Strategy ---
$kpiStrat = new KpiStrategy();
$runner->assertEquals('KPI', $kpiStrat->getScoringModel(), 'KPI Strategy scoring model is KPI');

// KPI 1: Target 100, Actual 90, Threshold 80 -> Achieved: 90 / 100 = 0.90
// KPI 2: Target 50, Actual 40, Threshold 45 -> FAILED threshold (40 < 45) -> Achieved: 0.0
// Both weight 1.0 -> Average: (0.90 + 0.0) / 2 = 0.45 -> Score: 45.0
$kpiMetrics = [
    'kpis' => [
        ['name' => 'Sales Quota', 'target' => 100, 'actual' => 90, 'threshold' => 80, 'weight' => 1.0],
        ['name' => 'CSAT Rating', 'target' => 50, 'actual' => 40, 'threshold' => 45, 'weight' => 1.0],
    ],
];
$kpiScore = $kpiStrat->calculateScore($perfEmployee, $kpiMetrics);
$runner->assertEquals(45.00, $kpiScore, 'KpiStrategy enforces minimum threshold gate (failing indicator yields 0.0, total 45.00)');

// ==========================================================================
// 11. SUITE: Cross-Pattern & Domain Entity Multi-Tenant Integration
// ==========================================================================
$runner->suite('Cross-Pattern & Domain Entity Integration Pipeline');

TenantContextManager::resetInstance();
AuditLogger::resetInstance();

$contextMgr = TenantContextManager::getInstance();
$auditLogger = AuditLogger::getInstance();

// 1. Configure active multi-tenant enterprise boundary
$acmeTenant = new Tenant(
    id: 'tenant-acme-corp',
    cnpj: '00.360.305/0001-04',
    corporateName: 'Acme International Corporation Ltda',
    tradingName: 'Acme HR Solutions'
);
$contextMgr->setActiveTenant($acmeTenant);
$runner->assertEquals('tenant-acme-corp', $contextMgr->getActiveTenantId(), 'Pipeline: Active tenant established');

// 2. Audit tenant activation
$auditLogger->log(
    tenantId: $contextMgr->getActiveTenantId(),
    action: 'TENANT_ACTIVATE',
    entityType: 'Tenant',
    entityId: $acmeTenant->getId(),
    payload: ['status' => 'ACTIVE', 'cnpj' => $acmeTenant->getCnpj()->getFormatted()]
);

// 3. Create Department, Role, and Employee within active tenant context
$dept = new Department(
    id: 'DEP-ENG-01',
    tenantId: $contextMgr->getActiveTenantId(),
    code: 'ENG',
    name: 'Software Engineering',
    costCenter: 'CC-ENG-100'
);

$role = new Role(
    id: 'ROLE-DEV-SR',
    tenantId: $contextMgr->getActiveTenantId(),
    name: 'Senior Systems Architect',
    description: 'Core backend design and implementation',
    hierarchyLevel: 50
);

$employee = new Employee(
    id: 'EMP-LEAD-01',
    tenantId: $contextMgr->getActiveTenantId(),
    cpf: '919.831.033-06',
    fullName: 'Fernando Albuquerque',
    email: 'fernando@acme.com',
    phone: '11922221111',
    birthDate: '1988-03-20',
    admissionDate: '2019-06-01',
    departmentId: $dept->getId(),
    roleId: $role->getId(),
    baseSalary: 12000.00,
    employmentType: EmploymentType::CLT
);
$runner->assertTrue($employee->belongsToTenant($contextMgr->getActiveTenantId()), 'Pipeline: Employee bound to active tenant');

// 4. Ingest TimeLog punches using CsvImporter
$punchCsv = <<<CSV
employee_id,punch_time,type,lat,lon
EMP-LEAD-01,2026-09-17 08:00:00,entry,-23.5505,-46.6333
EMP-LEAD-01,2026-09-17 19:00:00,exit,-23.5505,-46.6333
CSV;

$importer = new CsvImporter();
$importSummary = $importer->import($punchCsv);
$timeLogs = $importer->convertToTimeLogs($importSummary, $contextMgr->getActiveTenantId());
$runner->assertEquals(2, count($timeLogs), 'Pipeline: Ingested 2 daily punches via CsvImporter');
$runner->assertEquals('EMP-LEAD-01', $timeLogs[0]->employeeId, 'Pipeline: TimeLog linked to employee');

// 5. Calculate Overtime using Strategy
$otStrategy = new Standard50Strategy();
$hourlyRate = $employee->getBaseSalary()->getAmount() / 220.0; // CLT standard 220h divisor (~54.545)
$dailyOvertime = 3.0; // 11h worked - 8h regular = 3h overtime
$overtimeComp = $otStrategy->calculateOvertime($hourlyRate, $dailyOvertime);
$runner->assertCloseTo(245.45, $overtimeComp, 0.1, 'Pipeline: Standard50Strategy calculated ~245.45 for 3h overtime');

// 6. Calculate Benefit Discount using Strategy
$vtBenefit = new Benefit(
    id: 'BEN-VT-CORP',
    tenantId: $contextMgr->getActiveTenantId(),
    type: BenefitType::TRANSPORTATION,
    name: 'Vale Transporte Corporativo',
    provider: 'SPTrans',
    value: Money::fromFloat(400.00),
    employeeCostSharePercentage: 6.0
);
$discountStrat = new TransportationVoucherStrategy();
$vtDeduction = $discountStrat->calculateDiscount($employee, $vtBenefit);
// 6% of 12000 = 720.00 > actual value 400.00 -> capped at 400.00
$runner->assertEquals(400.00, $vtDeduction, 'Pipeline: TransportationVoucherStrategy capped at actual cost (400.00)');

// 7. Calculate Payroll using Template Method
$payrollCalc = new CltPayroll();
$payslip = $payrollCalc->calculatePayroll($employee, [
    'overtime_amount' => $overtimeComp,
    'benefits_discount' => $vtDeduction,
    'dependents' => 2,
]);
$runner->assertEquals('CLT', $payslip['contract_type'], 'Pipeline: Payroll processed as CLT');
$runner->assertCloseTo(12245.45, (float)$payslip['breakdown']['gross_salary'], 0.1, 'Pipeline: Gross salary matches base + overtime');

// 8. Audit Payroll Run into SHA-256 Chained Log
$auditLog = $auditLogger->log(
    tenantId: $contextMgr->getActiveTenantId(),
    action: 'PAYROLL_EXECUTE',
    entityType: 'Employee',
    entityId: $employee->getId(),
    payload: [
        'payslip_id' => 'PAY-' . date('Ym'),
        'gross' => $payslip['breakdown']['gross_salary'],
        'net' => $payslip['breakdown']['net_salary'],
    ],
    userId: 'SYSTEM-PAYROLL'
);
$runner->assertTrue($auditLog->verifyIntegrity(), 'Pipeline: AuditLog of payroll run is cryptographically verified');

// 9. Generate Final Compliance Report
$reportGen = new JsonReportGenerator();
$reportJson = $reportGen->generate([
    [
        'employee' => $employee->getFullName(),
        'gross_salary' => $payslip['breakdown']['gross_salary'],
        'net_salary' => $payslip['breakdown']['net_salary'],
        'inss' => $payslip['breakdown']['inss_deduction'],
        'irrf' => $payslip['breakdown']['irrf_deduction'],
    ],
], [
    'title' => 'Monthly Payroll Compliance Audit',
    'tenant_id' => $contextMgr->getActiveTenantId(),
]);

$decoded = json_decode($reportJson, true);
$runner->assertEquals('Monthly Payroll Compliance Audit', $decoded['metadata']['report_name'], 'Pipeline: Report generated with metadata');
$runner->assertEquals(1, $decoded['analytics']['total_records'], 'Pipeline: Report analytics totals 1 record');

// Final check: Complete Audit Chain Integrity
$runner->assertTrue($auditLogger->verifyChainIntegrity(), 'Pipeline: Complete audit chain integrity successfully verified');

// Teardown
TenantContextManager::resetInstance();
AuditLogger::resetInstance();

// ==========================================================================
// Summary
// ==========================================================================
$exitCode = $runner->printSummary();
exit($exitCode);
