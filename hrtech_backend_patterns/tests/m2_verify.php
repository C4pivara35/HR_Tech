<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 2 Verification Test Suite
 *
 * Standalone verification harness designed for PHP 8.3.6 CLI.
 * Rigorously verifies:
 *  - 12 Domain Entities (Tenant, User, Role, Department, Employee, TimeLog,
 *    TimeAdjustmentRequest, VacationRequest, EquipmentASO, Benefit, AuditLog, InsurancePolicy)
 *  - The 3 Milestone 1 adversarial fixes (withContext, Autoloader static/closure classmap & registration, GeoLocation antipodal clamping)
 *  - Cryptographic integrity (TimeLog Portaria 671/2021 SHA-256 chaining, AuditLog SHA-256 chaining & LGPD redaction)
 *  - Complete business calculations (CLT divisors, hourly rates, time bank, vacations, benefits, ASO validity, insurance premiums)
 *  - Multi-tenant boundary isolation and polymorphic interface consistency
 *  - Validation failure invariants and security constraints
 *
 * Usage: php tests/m2_verify.php
 */

require_once dirname(__DIR__) . '/src/Autoloader.php';
\HrTech\Autoloader::registerDefault();

// --------------------------------------------------------------------------
// 1. ANSI Test Runner Engine
// --------------------------------------------------------------------------

final class M2TestRunner
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
        echo "\033[1;37mMILESTONE 2 VERIFICATION SUMMARY\033[0m\n";
        echo str_repeat('=', 70) . "\n";
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

        echo "\n\033[1;32mRESULT: ALL MILESTONE 2 VERIFICATIONS PASSED (100%)\033[0m\n";
        return 0;
    }
}

$runner = new M2TestRunner();

echo "\033[1;36m======================================================================\033[0m\n";
echo "\033[1;36m       HRTech Core Backend — Milestone 2 Verification Test Suite      \033[0m\n";
echo "\033[1;36m======================================================================\033[0m\n";

// Domain imports
use HrTech\Autoloader;
use HrTech\Domain\Entities\AuditLog;
use HrTech\Domain\Entities\Benefit;
use HrTech\Domain\Entities\Department;
use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Entities\EquipmentASO;
use HrTech\Domain\Entities\InsurancePolicy;
use HrTech\Domain\Entities\Role;
use HrTech\Domain\Entities\Tenant;
use HrTech\Domain\Entities\TimeAdjustmentRequest;
use HrTech\Domain\Entities\TimeLog;
use HrTech\Domain\Entities\User;
use HrTech\Domain\Entities\VacationRequest;
use HrTech\Domain\Enums\AdjustmentStatus;
use HrTech\Domain\Enums\BenefitType;
use HrTech\Domain\Enums\EmploymentType;
use HrTech\Domain\Enums\ExamType;
use HrTech\Domain\Enums\PolicyStatus;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\Enums\UserRole;
use HrTech\Domain\Enums\VacationStatus;
use HrTech\Domain\ValueObjects\Cnpj;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\HrTechException;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;

// --------------------------------------------------------------------------
// SUITE 1: Milestone 1 Quick Fixes Verification
// --------------------------------------------------------------------------
$runner->suite('M1 Quick Fixes Verification');

// Fix 1.1: HrTechException::withContext()
try {
    $baseEx = new HrTechException('Base domain error', 500, null, ['init' => 'val1']);
    $enrichedEx = $baseEx->withContext(['tenant_id' => 't-123', 'user_id' => 999]);
    $runner->assertTrue($enrichedEx instanceof HrTechException, 'HrTechException::withContext() returns instance');
    $runner->assertEquals('Base domain error', $enrichedEx->getMessage(), 'Preserved exception message');
    $runner->assertEquals(500, $enrichedEx->getCode(), 'Preserved exception code');
    $runner->assertEquals(['init' => 'val1', 'tenant_id' => 't-123', 'user_id' => 999], $enrichedEx->getContext(), 'Context merged cleanly');
} catch (\Throwable $e) {
    $runner->assertTrue(false, 'HrTechException::withContext() threw: ' . $e->getMessage());
}

// Fix 1.2: ValidationException::withContext()
try {
    $valEx = ValidationException::forField('email', 'Invalid format');
    $enrichedValEx = $valEx->withContext(['request_id' => 'req-456']);
    $runner->assertTrue($enrichedValEx instanceof ValidationException, 'ValidationException::withContext() returns instance');
    $runner->assertTrue($enrichedValEx->hasError('email'), 'ValidationException retained error array');
    $runner->assertEquals('req-456', $enrichedValEx->getContext()['request_id'], 'ValidationException retained custom context');
} catch (\Throwable $e) {
    $runner->assertTrue(false, 'ValidationException::withContext() failed: ' . $e->getMessage());
}

// Fix 2.1: Autoloader instance register() vs static register()
try {
    $customLoader = new Autoloader();
    $customLoader->register();
    $runner->assertTrue($customLoader->isRegistered(), 'Autoloader instance register() registers custom instance');
    $customLoader->unregister();
    $runner->assertFalse($customLoader->isRegistered(), 'Autoloader unregister() unregisters cleanly');

    $staticLoader = Autoloader::register();
    $runner->assertTrue($staticLoader->isRegistered(), 'Autoloader::register() static call succeeds');
} catch (\Throwable $e) {
    $runner->assertTrue(false, 'Autoloader registration failed: ' . $e->getMessage());
}

// Fix 2.2: Autoloader static and closure class maps
try {
    $testLoader = new Autoloader();
    $testLoader->addClass('HrTech\\Dynamic\\DummyTestClass', function (string $class) {
        return dirname(__DIR__) . '/src/Domain/Entities/Tenant.php';
    });
    $map = $testLoader->getClassMap();
    $runner->assertTrue(isset($map['HrTech\\Dynamic\\DummyTestClass']), 'Instance class map registered closure');

    Autoloader::addStaticClass('HrTech\\Static\\DummyStaticClass', function (string $class) {
        return dirname(__DIR__) . '/src/Domain/Entities/Tenant.php';
    });
    $staticMap = Autoloader::getStaticClassMap();
    $runner->assertTrue(isset($staticMap['HrTech\\Static\\DummyStaticClass']), 'Static class map registered closure');
} catch (\Throwable $e) {
    $runner->assertTrue(false, 'Autoloader class map threw: ' . $e->getMessage());
}

// Fix 3: GeoLocation antipodal distance clamping (no NAN)
$pointA = new GeoLocation(9.81824, -137.34216);
$pointB = new GeoLocation(-9.81824, 42.65784);
$antipodalDist = $pointA->distanceTo($pointB);
$runner->assertFalse(is_nan($antipodalDist), 'Antipodal distanceTo() does not return NAN');
$runner->assertCloseTo(20015086.8, $antipodalDist, 100.0, 'Antipodal distance corresponds to half Earth circumference (~20,015 km)');

// --------------------------------------------------------------------------
// SUITE 2: Domain Entity 1 — Tenant
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 1: Tenant');

$cnpj = new Cnpj('11.222.333/0001-81');
$tenant = new Tenant(
    id: 'tenant-acme',
    cnpj: $cnpj,
    corporateName: 'Acme Corporation S.A.',
    tradingName: 'Acme Corp',
    isActive: true,
    moduleLicenses: ['payroll', 'timelog', 'benefits']
);

$runner->assertEquals('tenant-acme', $tenant->getId(), 'Tenant ID getter');
$runner->assertEquals('11.222.333/0001-81', $tenant->getCnpj()->getFormatted(), 'Tenant CNPJ formatted representation');
$runner->assertEquals('11222333000181', $tenant->getCnpj()->getValue(), 'Tenant CNPJ unformatted digits');
$runner->assertEquals('Acme Corporation S.A.', $tenant->getCorporateName(), 'Corporate name getter');
$runner->assertEquals('Acme Corp', $tenant->getTradingName(), 'Trading name getter');
$runner->assertTrue($tenant->isActive(), 'Tenant isActive initially true');
$runner->assertTrue($tenant->hasModule('payroll'), 'Tenant hasModule payroll');
$runner->assertTrue($tenant->hasModule('timelog'), 'Tenant hasModule timelog');
$runner->assertFalse($tenant->hasModule('fincorp_broker'), 'Tenant does not have unassigned module');

// Mutation & module lifecycle
$tenant->addModule('compliance');
$runner->assertTrue($tenant->hasModule('compliance'), 'Tenant addModule adds new module');
$tenant->removeModule('payroll');
$runner->assertFalse($tenant->hasModule('payroll'), 'Tenant removeModule removes module');
$tenant->deactivate();
$runner->assertFalse($tenant->isActive(), 'Tenant deactivate sets status to false');
$tenant->activate();
$runner->assertTrue($tenant->isActive(), 'Tenant activate restores status to true');
$tenant->updateNames('Acme Global S.A.', 'Acme Global');
$runner->assertEquals('Acme Global S.A.', $tenant->getCorporateName(), 'Updated corporate name');
$runner->assertEquals('Acme Global', $tenant->getTradingName(), 'Updated trading name');

// Serialization & Contracts
$runner->assertTrue($tenant->isValid(), 'Tenant isValid returns true');
$tenantArray = $tenant->toArray();
$runner->assertEquals('tenant-acme', $tenantArray['id'], 'Tenant toArray includes id');
$runner->assertEquals('Acme Global', $tenantArray['trading_name'], 'Tenant toArray includes trading_name');
$runner->assertEquals($tenantArray, $tenant->toAuditArray(), 'Tenant toAuditArray matches toArray');
$runner->assertTrue(str_contains((string)$tenant, 'Acme Global'), 'Tenant __toString contains trading name');

// Invariant Rejections
$runner->assertThrows(
    fn() => new Tenant('', $cnpj, 'Corp', 'Trade'),
    ValidationException::class,
    'Tenant rejects empty ID'
);
$runner->assertThrows(
    fn() => new Tenant('t-bad', $cnpj, '', 'Trade'),
    ValidationException::class,
    'Tenant rejects empty corporate name'
);
$runner->assertThrows(
    fn() => new Tenant('t-bad', $cnpj, 'Corp', ''),
    ValidationException::class,
    'Tenant rejects empty trading name'
);

// --------------------------------------------------------------------------
// SUITE 3: Domain Entity 2 — User
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 2: User');

$passwordHash = password_hash('SecretPassword123!', PASSWORD_BCRYPT);
$user = new User(
    id: 'usr-001',
    tenantId: 'tenant-acme',
    username: 'maria.silva',
    email: 'maria.silva@acme.com',
    passwordHash: $passwordHash,
    role: UserRole::HR_MANAGER,
    isActive: true,
    mfaEnabled: true,
    employeeId: 'emp-001'
);

$runner->assertEquals('usr-001', $user->getId(), 'User ID getter');
$runner->assertEquals('tenant-acme', $user->getTenantId(), 'User tenant ID getter');
$runner->assertTrue($user->belongsToTenant('tenant-acme'), 'User belongsToTenant true for matching tenant');
$runner->assertFalse($user->belongsToTenant('tenant-alien'), 'User belongsToTenant false for alien tenant');
$runner->assertEquals('maria.silva', $user->getUsername(), 'User username getter');
$runner->assertEquals('maria.silva@acme.com', $user->getEmail(), 'User email getter');
$runner->assertEquals(UserRole::HR_MANAGER, $user->getRole(), 'User role getter');
$runner->assertTrue($user->isHr(), 'User isHr() returns true for HR_MANAGER');
$runner->assertTrue($user->isMfaEnabled(), 'User MFA is enabled');
$runner->assertEquals('emp-001', $user->getEmployeeId(), 'User linked employee ID');

// Authentication & password verification
$runner->assertTrue($user->verifyPassword('SecretPassword123!'), 'User verifyPassword succeeds with correct secret');
$runner->assertFalse($user->verifyPassword('WrongPassword'), 'User verifyPassword fails with incorrect secret');
$runner->assertTrue($user->authenticate('SecretPassword123!'), 'User authenticate succeeds when active');

$user->deactivate();
$runner->assertFalse($user->authenticate('SecretPassword123!'), 'User authenticate fails when deactivated');
$user->activate();
$runner->assertTrue($user->authenticate('SecretPassword123!'), 'User authenticate succeeds after reactivation');

// Password change & updates
$user->updatePassword('NewSecretPassword999!');
$runner->assertTrue($user->verifyPassword('NewSecretPassword999!'), 'User verifyPassword works with updated password');
$runner->assertFalse($user->changePassword('WrongOldPassword', 'EvenNewerPassword123!'), 'changePassword fails on wrong current password');
$runner->assertTrue($user->changePassword('NewSecretPassword999!', 'FinalSecretPassword123!'), 'changePassword succeeds on correct current password');

// Login recording & MFA toggle
$user->recordLogin();
$runner->assertTrue($user->getLastLoginAt() instanceof \DateTimeImmutable, 'recordLogin sets lastLoginAt timestamp');
$user->disableMfa();
$runner->assertFalse($user->isMfaEnabled(), 'disableMfa sets mfaEnabled to false');
$user->enableMfa();
$runner->assertTrue($user->isMfaEnabled(), 'enableMfa restores mfaEnabled to true');

// Role management & authority
$employeeUser = new User('usr-002', 'tenant-acme', 'joao.souza', 'joao@acme.com', 'Pass12345!', UserRole::EMPLOYEE);
$runner->assertTrue($user->canManage($employeeUser), 'HR_MANAGER can manage EMPLOYEE');
$runner->assertFalse($employeeUser->canManage($user), 'EMPLOYEE cannot manage HR_MANAGER');

// Audit LGPD redaction
$userAudit = $user->toAuditArray();
$runner->assertEquals('[REDACTED]', $userAudit['password_hash'], 'User password_hash is redacted in toAuditArray');

// Invariant Rejections
$runner->assertThrows(
    fn() => new User('u', 'tenant-acme', 'us', 'valid@acme.com', 'pwd', UserRole::EMPLOYEE),
    ValidationException::class,
    'User rejects username shorter than 3 characters'
);
$runner->assertThrows(
    fn() => new User('u', 'tenant-acme', 'valid.user', 'not-an-email', 'pwd', UserRole::EMPLOYEE),
    ValidationException::class,
    'User rejects invalid email address'
);
$runner->assertThrows(
    fn() => $user->updatePassword('short'),
    ValidationException::class,
    'User updatePassword rejects password shorter than 8 characters'
);

// --------------------------------------------------------------------------
// SUITE 4: Domain Entity 3 — Role
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 3: Role (RBAC & Hierarchy)');

$roleAdmin = new Role(
    id: 'role-admin',
    tenantId: 'tenant-acme',
    name: 'Administrator',
    description: 'System Administrator',
    hierarchyLevel: 100,
    permissions: ['*']
);

$roleLead = new Role(
    id: 'role-lead',
    tenantId: 'tenant-acme',
    name: 'Tech Lead',
    description: 'Technical Leader',
    hierarchyLevel: 70,
    permissions: ['timelog.*', 'vacation.approve', 'department.view']
);

$roleDev = new Role(
    id: 'role-dev',
    tenantId: 'tenant-acme',
    name: 'Software Developer',
    description: 'Core Engineering',
    hierarchyLevel: 30,
    permissions: ['timelog.punch', 'vacation.request'],
    departmentId: 'dept-eng'
);

$runner->assertEquals('role-admin', $roleAdmin->getId(), 'Role ID');
$runner->assertEquals(100, $roleAdmin->getHierarchyLevel(), 'Role hierarchy level getter');
$runner->assertEquals(100, $roleAdmin->getLevel(), 'Role level alias getter');
$runner->assertTrue($roleDev->isDepartmentScoped(), 'Role with departmentId isDepartmentScoped');
$runner->assertEquals('dept-eng', $roleDev->getDepartmentId(), 'Role departmentId getter');
$runner->assertTrue($roleAdmin->isCompanyWide(), 'Role without departmentId isCompanyWide');

// Permission matching: wildcard, prefix, exact
$runner->assertTrue($roleAdmin->hasPermission('any.arbitrary.permission'), 'Superuser wildcard * grants any permission');
$runner->assertTrue($roleLead->hasPermission('timelog.view'), 'Prefix wildcard timelog.* grants timelog.view');
$runner->assertTrue($roleLead->hasPermission('timelog.edit'), 'Prefix wildcard timelog.* grants timelog.edit');
$runner->assertFalse($roleLead->hasPermission('payroll.calculate'), 'Prefix wildcard does not grant unassigned prefix');
$runner->assertTrue($roleDev->hasPermission('timelog.punch'), 'Exact permission match succeeds');
$runner->assertFalse($roleDev->hasPermission('timelog.approve'), 'Exact permission missing returns false');

// Authority comparison
$runner->assertTrue($roleAdmin->canManage($roleLead), 'Admin (100) can manage Lead (70)');
$runner->assertTrue($roleLead->canManage($roleDev), 'Lead (70) can manage Dev (30)');
$runner->assertFalse($roleDev->canManage($roleLead), 'Dev (30) cannot manage Lead (70)');

// Dynamic permission assignment
$roleDev->addPermission('equipment.request');
$runner->assertTrue($roleDev->hasPermission('equipment.request'), 'addPermission dynamically assigns permission');
$roleDev->removePermission('equipment.request');
$runner->assertFalse($roleDev->hasPermission('equipment.request'), 'removePermission removes permission');

// TenantScopedInterface conformance for Role
$runner->assertEquals('tenant-acme', $roleLead->getTenantId(), 'Role returns string tenantId');
$runner->assertTrue($roleLead->belongsToTenant('tenant-acme'), 'Role belongsToTenant true');
$runner->assertFalse($roleLead->belongsToTenant('alien-tenant'), 'Role belongsToTenant false for alien tenant');

$globalRole = new Role('role-global', null, 'Global Auditor', 'Global audit role', 50);
$runner->assertEquals('', $globalRole->getTenantId(), 'Global role getTenantId returns empty string');
$runner->assertTrue($globalRole->belongsToTenant('any-tenant'), 'Global role belongs to any tenant');

// Invariant Rejections
$runner->assertThrows(
    fn() => new Role('r', 'tenant-acme', 'R', 'Desc', 0),
    ValidationException::class,
    'Role rejects hierarchy level < 1'
);
$runner->assertThrows(
    fn() => new Role('r', 'tenant-acme', 'R', 'Desc', 101),
    ValidationException::class,
    'Role rejects hierarchy level > 100'
);

// --------------------------------------------------------------------------
// SUITE 5: Domain Entity 4 — Department
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 4: Department');

$parentDept = new Department(
    id: 'dept-tech',
    tenantId: 'tenant-acme',
    code: 'TECH-01',
    name: 'Technology Division',
    costCenter: 'CC-TECH'
);

$childDept = new Department(
    id: 'dept-eng',
    tenantId: 'tenant-acme',
    code: 'ENG-01',
    name: 'Software Engineering',
    costCenter: 'CC-ENG-101',
    parentDepartmentId: 'dept-tech'
);

$runner->assertEquals('dept-tech', $parentDept->getId(), 'Department ID');
$runner->assertEquals('TECH-01', $parentDept->getCode(), 'Department code uppercase');
$runner->assertEquals('CC-TECH', $parentDept->getCostCenter(), 'Cost center uppercase');
$runner->assertFalse($parentDept->isSubDepartment(), 'Parent department is not subdepartment');
$runner->assertTrue($childDept->isSubDepartment(), 'Child department isSubDepartment returns true');
$runner->assertEquals('dept-tech', $childDept->getParentDepartmentId(), 'Parent department ID');

// Manager management
$runner->assertFalse($childDept->hasManager(), 'Initially no manager assigned');
$childDept->assignManager('emp-001');
$runner->assertTrue($childDept->hasManager(), 'hasManager returns true after assignment');
$runner->assertEquals('emp-001', $childDept->getManagerId(), 'Manager ID recorded');
$childDept->removeManager();
$runner->assertFalse($childDept->hasManager(), 'hasManager returns false after removeManager');

// Invariant Rejections
$runner->assertThrows(
    fn() => new Department('d', 'tenant-acme', 'X', 'Valid Name', 'CC'),
    ValidationException::class,
    'Department rejects code shorter than 2 characters'
);
$runner->assertThrows(
    fn() => new Department('d', 'tenant-acme', 'VALID-01', '', 'CC'),
    ValidationException::class,
    'Department rejects empty name'
);
$runner->assertThrows(
    fn() => new Department('d', 'tenant-acme', 'VALID-01', 'Valid Name', ''),
    ValidationException::class,
    'Department rejects empty cost center'
);

// --------------------------------------------------------------------------
// SUITE 6: Domain Entity 5 — Employee
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 5: Employee (CLT Divisors & Time Bank)');

$cpf = new Cpf('123.456.789-09');
$salary = Money::brl(8800.00);
$birthDate = new DateTimeImmutable('1995-04-12');
$admissionDate = new DateTimeImmutable('2022-01-10');

$employee = new Employee(
    id: 'emp-001',
    tenantId: 'tenant-acme',
    cpf: $cpf,
    fullName: 'Ana Carolina Machado',
    email: 'ana.machado@acme.com',
    phone: '+55 11 99999-8888',
    birthDate: $birthDate,
    admissionDate: $admissionDate,
    departmentId: 'dept-eng',
    roleId: 'role-lead',
    baseSalary: $salary,
    employmentType: EmploymentType::CLT,
    isActive: true,
    vacationBalanceDays: 30,
    bankHoursMinutes: 0
);

$runner->assertEquals('emp-001', $employee->getId(), 'Employee ID');
$runner->assertEquals('Ana Carolina Machado', $employee->getFullName(), 'Employee full name');
$runner->assertEquals('Ana Carolina Machado', $employee->getName(), 'Employee getName() contract alias');
$runner->assertTrue($employee->isClt(), 'Employee isClt() returns true');
$runner->assertFalse($employee->isPj(), 'Employee isPj() returns false');
$runner->assertEquals(8800.00, $employee->getBaseSalary()->toFloat(), 'Base salary matches R$ 8.800,00');

// Labor calculations: CLT Art. 64 divisor (44 hours/week * 5 = 220 hours/month)
$runner->assertEquals(220, $employee->getMonthlyWorkingHours(), 'CLT monthly divisor is 220 hours');
// Hourly rate: 8800 / 220 = 40.00
$runner->assertEquals(40.0, $employee->getHourlyRate(), 'Hourly rate is R$ 40.00/hour');
$runner->assertEquals('R$ 40,00', $employee->getHourlyRateMoney()->getFormatted(), 'Hourly rate Money object');

// Salary adjustments
$employee->adjustSalary(Money::brl(9900.00));
$runner->assertEquals(9900.00, $employee->getBaseSalary()->toFloat(), 'adjustSalary updates salary');
$employee->raiseSalaryByPercentage(10.0); // 9900 + 10% = 10890.00
$runner->assertEquals(10890.00, $employee->getBaseSalary()->toFloat(), 'raiseSalaryByPercentage increases salary');

// Time bank accounting (minutes)
$employee->creditBankHours(120); // +2 hours
$runner->assertEquals(120, $employee->getBankHoursMinutes(), 'creditBankHours credits 120 minutes');
$runner->assertEquals('+02:00', $employee->getBankHoursBalanceFormatted(), 'Bank hours formatted +02:00');
$runner->assertEquals(2.0, $employee->getBankHoursInHours(), 'Bank hours in decimal hours is 2.0');
$employee->debitBankHours(180); // 120 - 180 = -60 minutes (-1 hour)
$runner->assertEquals(-60, $employee->getBankHoursMinutes(), 'debitBankHours debits minutes into negative');
$runner->assertEquals('-01:00', $employee->getBankHoursBalanceFormatted(), 'Bank hours formatted -01:00');

// Vacation balance management
$employee->deductVacationDays(15);
$runner->assertEquals(15, $employee->getVacationBalanceDays(), 'deductVacationDays updates balance to 15');
$employee->accrueVacationDays(30);
$runner->assertEquals(45, $employee->getVacationBalanceDays(), 'accrueVacationDays increments balance to 45');

// Demographic & Tenure
$runner->assertTrue($employee->getAge() >= 29, 'Employee age calculated accurately');
$runner->assertTrue($employee->getTenureInMonths() >= 24, 'Employee tenure in months calculated accurately');

// Promotion, transfer & termination
$employee->transferDepartment('dept-tech');
$runner->assertEquals('dept-tech', $employee->getDepartmentId(), 'transferDepartment updates department');
$employee->promote('role-admin', Money::brl(15000.00));
$runner->assertEquals('role-admin', $employee->getRoleId(), 'promote updates role');
$runner->assertEquals(15000.00, $employee->getBaseSalary()->toFloat(), 'promote updates salary');

$terminationDate = new DateTimeImmutable('2026-12-31');
$employee->terminate($terminationDate);
$runner->assertFalse($employee->isActive(), 'terminate sets isActive to false');
$runner->assertEquals($terminationDate, $employee->getTerminationDate(), 'terminate sets terminationDate');
$employee->reactivate();
$runner->assertTrue($employee->isActive(), 'reactivate restores isActive to true');
$runner->assertEquals(null, $employee->getTerminationDate(), 'reactivate clears terminationDate');

// LGPD masking of CPF in audit array
$auditEmp = $employee->toAuditArray();
$runner->assertEquals('***.456.789-**', $auditEmp['cpf'], 'Employee CPF is LGPD-masked in toAuditArray');

// Invariant Rejections
$runner->assertThrows(
    fn() => new Employee('e', 'tenant-acme', $cpf, '', 'email@a.com', '123', $birthDate, $admissionDate, 'd', 'r', $salary, EmploymentType::CLT),
    ValidationException::class,
    'Employee rejects empty full name'
);
$runner->assertThrows(
    fn() => new Employee('e', 'tenant-acme', $cpf, 'Ana', 'email@a.com', '123', new DateTimeImmutable('-10 years'), $admissionDate, 'd', 'r', $salary, EmploymentType::CLT),
    ValidationException::class,
    'Employee rejects age < 14 (CLT Young Apprentice minimum)'
);
$runner->assertThrows(
    fn() => $employee->deductVacationDays(100),
    InvalidOperationException::class,
    'Employee rejects deducting vacation days exceeding current balance'
);
$runner->assertThrows(
    fn() => $employee->terminate(new DateTimeImmutable('2020-01-01')),
    ValidationException::class,
    'Employee rejects termination date earlier than admission date'
);

// --------------------------------------------------------------------------
// SUITE 7: Domain Entity 6 — TimeLog (Portaria 671/2021 MTE)
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 6: TimeLog (Portaria 671 MTE SHA-256 Chaining)');

$workplaceLoc = new GeoLocation(-23.55052, -46.633308, 5.0); // Praça da Sé, SP
$nearLoc = new GeoLocation(-23.55060, -46.633400, 3.0); // ~15m away
$farLoc = new GeoLocation(-23.561414, -46.655881, 10.0); // Paulista (~2.6km away)

// Genesis punch
$time1 = new DateTimeImmutable('2026-09-15 08:00:00');
$punch1 = TimeLog::record(
    id: 'punch-001',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    timestamp: $time1,
    type: TimeLogType::ENTRY,
    location: $nearLoc,
    nsr: 1,
    previousHash: null
);

$runner->assertEquals(1, $punch1->getNsr(), 'Genesis punch NSR is 1');
$runner->assertTrue($punch1->isEntry(), 'isEntry returns true for ENTRY');
$runner->assertFalse($punch1->isExit(), 'isExit returns false for ENTRY');
$runner->assertTrue($punch1->verifyIntegrity(), 'Genesis punch cryptographic integrity verified');
$runner->assertTrue($punch1->isWithinGeofence($workplaceLoc, 50.0), 'Punch is within 50m geofence radius');
$runner->assertFalse($punch1->isWithinGeofence($farLoc, 50.0), 'Punch is outside far location geofence');

// Chained Punch 2 via createNext()
$time2 = new DateTimeImmutable('2026-09-15 12:00:00');
$punch2 = $punch1->createNext(
    id: 'punch-002',
    timestamp: $time2,
    type: TimeLogType::INTERVAL_START,
    location: $nearLoc
);

$runner->assertEquals(2, $punch2->getNsr(), 'createNext increments NSR to 2');
$runner->assertEquals($punch1->getSignatureHash(), $punch2->getPreviousHash(), 'createNext links previousHash to punch 1 signature');
$runner->assertTrue($punch2->verifyIntegrity(), 'Chained punch 2 integrity verified');
$runner->assertTrue($punch2->verifyIntegrity($punch1->getSignatureHash()), 'Chained punch 2 verified against punch 1 hash');

// Chained Punch 3 via createNext()
$time3 = new DateTimeImmutable('2026-09-15 13:00:00');
$punch3 = $punch2->createNext(
    id: 'punch-003',
    timestamp: $time3,
    type: TimeLogType::INTERVAL_END,
    location: $nearLoc
);

$runner->assertEquals(3, $punch3->getNsr(), 'createNext increments NSR to 3');
$runner->assertEquals($punch2->getSignatureHash(), $punch3->getPreviousHash(), 'createNext links previousHash to punch 2 signature');
$runner->assertTrue($punch3->verifyIntegrity(), 'Chained punch 3 integrity verified');

// Tamper Detection
$tamperedPunch = new TimeLog(
    id: 'punch-tampered',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    timestamp: $time1,
    type: TimeLogType::ENTRY,
    location: $nearLoc,
    nsr: 1,
    previousHash: null
);
$runner->assertFalse($tamperedPunch->verifyIntegrity('bad-previous-hash'), 'verifyIntegrity fails on forged previousHash');

// Invariant Rejections
$runner->assertThrows(
    fn() => new TimeLog('p', 'tenant-acme', 'emp-001', $time1, TimeLogType::ENTRY, $nearLoc, 0),
    ValidationException::class,
    'TimeLog rejects non-positive NSR <= 0'
);

// --------------------------------------------------------------------------
// SUITE 8: Domain Entity 7 — TimeAdjustmentRequest
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 7: TimeAdjustmentRequest (Workflow & Separation of Duties)');

$adjReq = new TimeAdjustmentRequest(
    id: 'adj-101',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    requestedDate: new DateTimeImmutable('2026-09-14'),
    originalTime: new DateTimeImmutable('2026-09-14 08:30:00'),
    requestedTime: new DateTimeImmutable('2026-09-14 08:00:00'),
    reason: 'Turnstile reader biometric scanner timeout at main lobby',
    attachmentPath: '/storage/turnstile_ticket.pdf'
);

$runner->assertEquals(AdjustmentStatus::PENDING, $adjReq->getStatus(), 'Initial status is PENDING');
$runner->assertTrue($adjReq->isPending(), 'isPending() returns true');
$runner->assertTrue($adjReq->hasAttachment(), 'hasAttachment() returns true');
$runner->assertEquals(-30, $adjReq->getTimeDeltaMinutes(), 'Time delta is -30 minutes');

// Self-approval prohibition (Segregation of Duties)
$runner->assertThrows(
    fn() => $adjReq->approve('emp-001', 'Self approving my own punch'),
    InvalidOperationException::class,
    'Self-approval is strictly prohibited under segregation of duties'
);

// Legitimate approval by supervisor
$adjReq->approve('usr-lead', 'Approved after CCTV gate verification');
$runner->assertTrue($adjReq->isApproved(), 'Status is APPROVED');
$runner->assertEquals('usr-lead', $adjReq->getApproverId(), 'Approver ID recorded');
$runner->assertEquals('Approved after CCTV gate verification', $adjReq->getReviewComment(), 'Review comment recorded');

// Invalid state transition after approval
$runner->assertThrows(
    fn() => $adjReq->reject('usr-lead', 'Trying to reject approved'),
    InvalidOperationException::class,
    'Cannot reject already approved request'
);

// Cancellation by requester
$pendingReq = new TimeAdjustmentRequest(
    id: 'adj-102',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    requestedDate: new DateTimeImmutable('2026-09-14'),
    originalTime: null,
    requestedTime: new DateTimeImmutable('2026-09-14 18:00:00'),
    reason: 'Forgot to punch exit due to sudden power outage'
);

$runner->assertThrows(
    fn() => $pendingReq->cancel('other-user'),
    InvalidOperationException::class,
    'Alien user cannot cancel adjustment request'
);
$pendingReq->cancel('emp-001');
$runner->assertTrue($pendingReq->isCancelled(), 'Requester successfully cancels adjustment request');

// Invariant Rejection on rejection without explanation
$rejectReq = new TimeAdjustmentRequest(
    id: 'adj-103',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    requestedDate: new DateTimeImmutable('2026-09-14'),
    originalTime: null,
    requestedTime: new DateTimeImmutable('2026-09-14 18:00:00'),
    reason: 'Legitimate request justification here'
);
$runner->assertThrows(
    fn() => $rejectReq->reject('usr-lead', ''),
    ValidationException::class,
    'Rejection requires non-empty reason'
);

// --------------------------------------------------------------------------
// SUITE 9: Domain Entity 8 — VacationRequest
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 8: VacationRequest (CLT Articles 129-145)');

// 30 days advance notice calculation
$vacStart = (new DateTimeImmutable('now'))->modify('+35 days');
$vacEnd = $vacStart->modify('+19 days'); // 20 days inclusive

$vacReq = new VacationRequest(
    id: 'vac-201',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    startDate: $vacStart,
    endDate: $vacEnd,
    durationDays: 20,
    abonoPecuniario: true, // Sells 1/3 (10 days)
    advanceThirteenthSalary: true
);

$runner->assertEquals(20, $vacReq->getDurationDays(), 'Vacation duration is 20 days');
$runner->assertEquals(7, $vacReq->calculateAbonoDays(), 'Abono days is 1/3 of 20 = 7 days');
$runner->assertTrue($vacReq->isAbonoPecuniario(), 'isAbonoPecuniario true');
$runner->assertTrue($vacReq->isAdvanceThirteenthSalary(), 'isAdvanceThirteenthSalary true');
$runner->assertEquals(VacationStatus::REQUESTED, $vacReq->getStatus(), 'Initial status is REQUESTED');

// Multi-tier Approval workflow
$vacReq->approve('usr-manager');
$runner->assertEquals(VacationStatus::APPROVED_BY_MANAGER, $vacReq->getStatus(), 'First approval transitions to APPROVED_BY_MANAGER');
$vacReq->approve('usr-hr');
$runner->assertEquals(VacationStatus::APPROVED_BY_HR, $vacReq->getStatus(), 'Second approval transitions to APPROVED_BY_HR');
$vacReq->startVacation();
$runner->assertEquals(VacationStatus::IN_PROGRESS, $vacReq->getStatus(), 'startVacation transitions to IN_PROGRESS');
$vacReq->completeVacation();
$runner->assertEquals(VacationStatus::COMPLETED, $vacReq->getStatus(), 'completeVacation transitions to COMPLETED');

// CLT Art. 134 §1 Period Splitting validation (static rule engine)
// Rule 1: Max 3 periods
$runner->assertThrows(
    fn() => VacationRequest::validatePeriodSplit([14, 6, 5, 5]),
    ValidationException::class,
    'validatePeriodSplit rejects > 3 periods'
);
// Rule 2: Must total 30 days (or 20 + 10 abono)
$runner->assertThrows(
    fn() => VacationRequest::validatePeriodSplit([14, 10]),
    ValidationException::class,
    'validatePeriodSplit rejects periods sum != 30 days'
);
// Rule 3: At least one period >= 14 days
$runner->assertThrows(
    fn() => VacationRequest::validatePeriodSplit([10, 10, 10]),
    ValidationException::class,
    'validatePeriodSplit rejects schedule where no period is >= 14 days'
);
// Rule 4: No period < 5 days
$runner->assertThrows(
    fn() => VacationRequest::validatePeriodSplit([20, 6, 4]),
    ValidationException::class,
    'validatePeriodSplit rejects any period < 5 days'
);
// Valid split schedules
try {
    VacationRequest::validatePeriodSplit([14, 8, 8]); // 30 days
    $runner->assertTrue(true, 'validatePeriodSplit accepts valid 14+8+8 split');
    VacationRequest::validatePeriodSplit([15, 15]); // 30 days
    $runner->assertTrue(true, 'validatePeriodSplit accepts valid 15+15 split');
    VacationRequest::validatePeriodSplit([20], true); // 20 days + 10 abono = 30 days
    $runner->assertTrue(true, 'validatePeriodSplit accepts valid 20 days + abono split');
} catch (\Throwable $e) {
    $runner->assertTrue(false, 'validatePeriodSplit rejected valid schedule: ' . $e->getMessage());
}

// Statutory Notice violation (< 30 days notice)
$runner->assertThrows(
    fn() => new VacationRequest(
        id: 'vac-invalid-notice',
        tenantId: 'tenant-acme',
        employeeId: 'emp-001',
        startDate: (new DateTimeImmutable('now'))->modify('+10 days'),
        endDate: (new DateTimeImmutable('now'))->modify('+24 days'),
        durationDays: 15
    ),
    ValidationException::class,
    'VacationRequest rejects start date with less than 30 days advance notice'
);

// --------------------------------------------------------------------------
// SUITE 10: Domain Entity 9 — EquipmentASO
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 9: EquipmentASO (NR-6 EPI & NR-7 ASO Lifecycle)');

$caExp = (new DateTimeImmutable('today'))->modify('+180 days');
$asoExp = (new DateTimeImmutable('today'))->modify('+90 days');

$equipmentAso = new EquipmentASO(
    id: 'aso-301',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    equipmentName: 'Óculos de Proteção Antirrisco 3M',
    caNumber: 'CA-44556',
    caExpirationDate: $caExp,
    deliveryDate: new DateTimeImmutable('2026-01-10'),
    examType: ExamType::PERIODIC,
    examDate: new DateTimeImmutable('2026-01-10'),
    expirationDate: $asoExp,
    physicianName: 'Dra. Beatriz Helena',
    physicianCrm: 'CRM/SP 98765',
    isFit: true
);

$runner->assertEquals('aso-301', $equipmentAso->getId(), 'EquipmentASO ID');
$runner->assertEquals('CA-44556', $equipmentAso->caNumber, 'CA number');
$runner->assertFalse($equipmentAso->isCaExpired(), 'isCaExpired returns false');
$runner->assertFalse($equipmentAso->isExamExpired(), 'isExamExpired returns false');
$runner->assertTrue($equipmentAso->daysUntilCaExpiration() > 170, 'daysUntilCaExpiration calculates positive countdown');
$runner->assertTrue($equipmentAso->daysUntilExamExpiration() > 80, 'daysUntilExamExpiration calculates positive countdown');
$runner->assertTrue($equipmentAso->isEquipmentActive(), 'isEquipmentActive true when delivered and not returned');
$runner->assertTrue($equipmentAso->isFitForWork(), 'isFitForWork true when fit and not expired');

// Equipment return recording
$returnDate = new DateTimeImmutable('2026-06-30');
$equipmentAso->recordReturn($returnDate);
$runner->assertEquals($returnDate, $equipmentAso->getReturnDate(), 'Return date recorded');
$runner->assertFalse($equipmentAso->isEquipmentActive(), 'isEquipmentActive false after equipment returned');

// Return date cannot precede delivery date
$runner->assertThrows(
    fn() => $equipmentAso->recordReturn(new DateTimeImmutable('2025-12-01')),
    ValidationException::class,
    'recordReturn rejects date earlier than deliveryDate'
);

// Expired exam detection
$expiredAso = new EquipmentASO(
    id: 'aso-expired',
    tenantId: 'tenant-acme',
    employeeId: 'emp-001',
    equipmentName: 'Capacete de Segurança',
    caNumber: 'CA-99999',
    deliveryDate: new DateTimeImmutable('2024-01-10'),
    examType: ExamType::ADMISSION,
    examDate: new DateTimeImmutable('2024-01-10'),
    expirationDate: new DateTimeImmutable('2025-01-10'),
    isFit: true
);
$runner->assertTrue($expiredAso->isExamExpired(), 'isExamExpired returns true for past date');
$runner->assertFalse($expiredAso->isFitForWork(), 'isFitForWork returns false when exam is expired even if deemed fit');

// --------------------------------------------------------------------------
// SUITE 11: Domain Entity 10 — Benefit
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 10: Benefit (PAT Copay & Statutory 6% VT Cap)');

// Benefit 1: Meal Voucher with 20% copay (PAT)
$vrTotal = Money::brl(800.00); // R$ 800,00
$benefitVR = new Benefit(
    id: 'ben-vr-01',
    tenantId: 'tenant-acme',
    type: BenefitType::MEAL_VOUCHER,
    name: 'Sodexo Pass Refeição',
    provider: 'Sodexo Pass do Brasil',
    value: $vrTotal,
    employeeCostSharePercentage: 20.0,
    isDeductible: true
);

$vrEmployeeContribution = $benefitVR->calculateEmployeeContribution();
$vrEmployerContribution = $benefitVR->calculateEmployerContribution();

$runner->assertEquals(160.00, $vrEmployeeContribution->toFloat(), 'Employee copay is 20% of 800.00 = R$ 160,00');
$runner->assertEquals(640.00, $vrEmployerContribution->toFloat(), 'Employer subsidy is 80% of 800.00 = R$ 640,00');
$runner->assertEquals(800.00, $vrEmployeeContribution->add($vrEmployerContribution)->toFloat(), 'Zero-penny-loss: Employee + Employer == Total Benefit Value');

// Benefit 2: Transportation Voucher with 6% statutory salary cap under Lei 7.418/1985
$vtTotal = Money::brl(350.00);
$benefitVT = new Benefit(
    id: 'ben-vt-01',
    tenantId: 'tenant-acme',
    type: BenefitType::TRANSPORTATION_VOUCHER,
    name: 'Vale Transporte Municipal',
    provider: 'SPTrans',
    value: $vtTotal,
    employeeCostSharePercentage: 100.0,
    isDeductible: true
);

// Scenario A: Salary = R$ 2.000,00. 6% of 2000 = R$ 120,00. Since 120 < 350, deduction capped at R$ 120,00
$salaryA = Money::brl(2000.00);
$deductionA = $benefitVT->calculateDeductionForSalary($salaryA);
$runner->assertEquals(120.00, $deductionA->toFloat(), 'Low salary capped at 6% of salary (R$ 120,00 < R$ 350,00 cost)');

// Scenario B: Salary = R$ 8.000,00. 6% of 8000 = R$ 480,00. Since 480 > 350, employee only pays actual voucher cost R$ 350,00
$salaryB = Money::brl(8000.00);
$deductionB = $benefitVT->calculateDeductionForSalary($salaryB);
$runner->assertEquals(350.00, $deductionB->toFloat(), 'High salary pays actual voucher cost when 6% exceeds cost');

// Invariant Rejections
$runner->assertThrows(
    fn() => new Benefit('b', 'tenant-acme', BenefitType::GYMPASS, 'Gym', 'Gympass', Money::brl(100), 10.0, false),
    ValidationException::class,
    'Non-deductible benefit cannot have employee cost share > 0%'
);
$runner->assertThrows(
    fn() => new Benefit('b', 'tenant-acme', BenefitType::HEALTH_PLAN, 'Health', 'Bradesco', Money::brl(0), 0.0, true),
    ValidationException::class,
    'Benefit monetary value must be strictly positive'
);

// --------------------------------------------------------------------------
// SUITE 12: Domain Entity 11 — AuditLog
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 11: AuditLog (LGPD Redaction & SHA-256 Chaining)');

// Block 1: Salary adjustment event
$log1 = AuditLog::record(
    id: 'audit-001',
    tenantId: 'tenant-acme',
    actorUserId: 'usr-admin',
    action: 'SALARY_ADJUSTMENT',
    entityType: 'Employee',
    entityId: 'emp-001',
    previousState: ['salary' => 8800.00, 'password_hash' => '$2y$10$abcdefghijklmnopqrstuvwxyz'],
    newState: ['salary' => 9900.00, 'password_hash' => '$2y$10$abcdefghijklmnopqrstuvwxyz'],
    ipAddress: '177.12.34.56',
    userAgent: 'HRTech-Portal/2.0',
    previousHash: null
);

$runner->assertEquals('audit-001', $log1->getId(), 'AuditLog ID');
$runner->assertTrue($log1->verifyIntegrity(), 'Block 1 SHA-256 seal integrity verified');
$runner->assertEquals('***REDACTED***', $log1->previousState['password_hash'], 'LGPD: password_hash redacted from previousState');
$runner->assertEquals('***REDACTED***', $log1->newState['password_hash'], 'LGPD: password_hash redacted from newState');

// Block 2: Chained audit entry linked to Block 1 integrityHash
$log2 = AuditLog::record(
    id: 'audit-002',
    tenantId: 'tenant-acme',
    actorUserId: 'usr-admin',
    action: 'ROLE_PROMOTION',
    entityType: 'Employee',
    entityId: 'emp-001',
    previousState: ['role_id' => 'role-lead'],
    newState: ['role_id' => 'role-admin'],
    ipAddress: '177.12.34.56',
    userAgent: 'HRTech-Portal/2.0',
    previousHash: $log1->integrityHash
);

$runner->assertEquals($log1->integrityHash, $log2->previousHash, 'Block 2 links previousHash to Block 1 integrityHash');
$runner->assertTrue($log2->verifyIntegrity(), 'Block 2 SHA-256 seal integrity verified');
$runner->assertTrue($log2->verifyIntegrity($log1->integrityHash), 'Block 2 verifies against Block 1 hash');

// Tamper Detection
$runner->assertFalse($log2->verifyIntegrity('forged-tampered-hash'), 'Block 2 detects forged previousHash');

// --------------------------------------------------------------------------
// SUITE 13: Domain Entity 12 — InsurancePolicy
// --------------------------------------------------------------------------
$runner->suite('Domain Entity 12: InsurancePolicy (FinCorp Portal Integration)');

$policyStart = new DateTimeImmutable('2026-01-01');
$policyEnd = new DateTimeImmutable('2026-12-31');
$capital = Money::brl(500000.00); // R$ 500.000,00
$monthlyCost = Money::brl(150.00); // R$ 150,00/month

$policy = new InsurancePolicy(
    id: 'pol-501',
    tenantId: 'tenant-acme',
    policyNumber: 'FC-SULAMERICA-2026-001',
    brokerCode: 'SUSEP-BRK-9876',
    insurerName: 'SulAmérica Seguros S.A.',
    employeeId: 'emp-001',
    insuredCapital: $capital,
    monthlyPremium: $monthlyCost,
    status: PolicyStatus::ACTIVE,
    startDate: $policyStart,
    endDate: $policyEnd,
    coverageDetails: [
        'death' => true,
        'accidental_disability' => true,
        'funeral_assistance' => true,
    ]
);

$runner->assertEquals('pol-501', $policy->getId(), 'Policy ID');
$runner->assertEquals(PolicyStatus::ACTIVE, $policy->getStatus(), 'Policy status ACTIVE');
$runner->assertEquals('FC-SULAMERICA-2026-001', $policy->getPolicyNumber(), 'Policy number');
$runner->assertEquals(500000.00, $policy->getInsuredCapital()->toFloat(), 'Insured capital R$ 500.000,00');

// Annual premium projection: 12 * 150.00 = 1800.00
$runner->assertEquals(1800.00, $policy->calculateAnnualPremium()->toFloat(), 'Annual premium is 12 x 150.00 = R$ 1.800,00');

// Active date window checks
$runner->assertTrue($policy->isActive(new DateTimeImmutable('2026-06-15')), 'Policy isActive within validity window');
$runner->assertFalse($policy->isActive(new DateTimeImmutable('2025-12-31')), 'Policy isActive false before start date');
$runner->assertFalse($policy->isActive(new DateTimeImmutable('2027-01-01')), 'Policy isActive false after end date');

// Renewal workflow
$newEndDate = new DateTimeImmutable('2027-12-31');
$newCost = Money::brl(170.00);
$policy->renew($newEndDate, $newCost);
$runner->assertEquals($newEndDate, $policy->getEndDate(), 'renew updates policy end date');
$runner->assertEquals(170.00, $policy->getMonthlyPremium()->toFloat(), 'renew updates monthly premium');
$runner->assertEquals(2040.00, $policy->calculateAnnualPremium()->toFloat(), 'Updated annual premium 12 x 170.00 = R$ 2.040,00');

// Cancellation workflow
$policy->cancel('Employee transitioned to voluntary private plan');
$runner->assertEquals(PolicyStatus::CANCELLED, $policy->getStatus(), 'Status transitions to CANCELLED');
$runner->assertEquals('Employee transitioned to voluntary private plan', $policy->getCancellationReason(), 'Cancellation reason recorded');
$runner->assertFalse($policy->isActive(new DateTimeImmutable('2026-06-15')), 'Cancelled policy isActive returns false');

// Invariant Rejections
$runner->assertThrows(
    fn() => $policy->cancel('Cancel again'),
    InvalidOperationException::class,
    'Cannot cancel already cancelled insurance policy'
);
$runner->assertThrows(
    fn() => new InsurancePolicy(
        'p',
        'tenant-acme',
        'NUM',
        'BRK',
        'Insurer',
        'emp-001',
        $capital,
        $monthlyCost,
        PolicyStatus::ACTIVE,
        new DateTimeImmutable('2026-12-31'),
        new DateTimeImmutable('2026-01-01') // End before start
    ),
    ValidationException::class,
    'InsurancePolicy rejects endDate <= startDate'
);

// --------------------------------------------------------------------------
// SUITE 14: Cross-Entity Multi-Tenant Isolation & Contract Conformance
// --------------------------------------------------------------------------
$runner->suite('Cross-Entity Multi-Tenant Isolation & Contract Conformance');

$all12Entities = [
    $tenant,
    $user,
    $roleAdmin,
    $parentDept,
    $employee,
    $punch1,
    $adjReq,
    $vacReq,
    $equipmentAso,
    $benefitVR,
    $log1,
    $policy,
];

$runner->assertEquals(12, count($all12Entities), 'Exactly 12 domain entity instances present');

foreach ($all12Entities as $entity) {
    $className = (new \ReflectionClass($entity))->getShortName();

    // 1. IdentifiableInterface
    $runner->assertTrue($entity instanceof \HrTech\Contracts\IdentifiableInterface, "{$className} implements IdentifiableInterface");
    $runner->assertTrue(trim($entity->getId()) !== '', "{$className} returns non-empty ID");

    // 2. TenantScopedInterface (All except root Tenant implement TenantScopedInterface)
    if ($entity instanceof \HrTech\Contracts\TenantScopedInterface) {
        $runner->assertTrue($entity->belongsToTenant('tenant-acme'), "{$className} belongsToTenant('tenant-acme')");
        $runner->assertFalse($entity->belongsToTenant('alien-tenant'), "{$className} rejects alien-tenant");
    }

    // 3. ValidatableInterface
    $runner->assertTrue($entity instanceof \HrTech\Contracts\ValidatableInterface, "{$className} implements ValidatableInterface");
    $runner->assertTrue($entity->isValid(), "{$className} isValid returns true");

    // 4. ArrayableInterface & JsonableInterface
    $runner->assertTrue($entity instanceof \HrTech\Contracts\ArrayableInterface, "{$className} implements ArrayableInterface");
    $runner->assertTrue($entity instanceof \HrTech\Contracts\JsonableInterface, "{$className} implements JsonableInterface");

    $array = $entity->toArray();
    $runner->assertTrue(is_array($array) && !empty($array), "{$className} toArray returns non-empty array");

    $json = $entity->toJson();
    $decoded = json_decode($json, true);
    $runner->assertTrue(is_array($decoded) && !empty($decoded), "{$className} toJson encodes and decodes cleanly");

    // 5. AuditableInterface check
    if ($entity instanceof \HrTech\Contracts\AuditableInterface) {
        $runner->assertTrue(trim($entity->getAuditIdentifier()) !== '', "{$className} returns non-empty audit identifier");
        $runner->assertTrue(trim($entity->getAuditCategory()) !== '', "{$className} returns non-empty audit category");
        $runner->assertTrue(is_array($entity->toAuditArray()), "{$className} toAuditArray returns array");
    }
}

// --------------------------------------------------------------------------
// Print Summary & Exit Code
// --------------------------------------------------------------------------
exit($runner->printSummary());
