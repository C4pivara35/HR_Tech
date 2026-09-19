<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 4 Adversarial Stress & Attack Harness
 *
 * Empirical verification script rigorously attacking all 10 CRUD modules:
 *  - Suite 1: Cross-Tenant Data Isolation Attacks (Read & Mutation Shielding across all 10 CRUDs)
 *  - Suite 2: Portaria 671 Mutation Rejection & Tamper-Proof Cryptographic Verification
 *  - Suite 3: SQL Injection Payloads in Search, Filter, and ID Lookups
 *  - Suite 4: Foreign Key Cascading & Zero-Orphan Integrity (Tenant & Employee Cascades)
 *  - Suite 5: Atomic Transaction Rollback on Simulated Failures
 *  - Suite 6: Malformed Inputs, Extreme Boundary Conditions & Collision Resilience
 *
 * Conforms to Challenger M4-1 specifications.
 * Usage: php tests/m4_adversarial_cruds.php
 */

require_once dirname(__DIR__) . '/src/Autoloader.php';
\HrTech\Autoloader::registerDefault();

use HrTech\Database\DatabaseManager;
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
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Repositories\BenefitRepository;
use HrTech\Repositories\DepartmentRoleRepository;
use HrTech\Repositories\EmployeeRepository;
use HrTech\Repositories\EquipmentASORepository;
use HrTech\Repositories\InsurancePolicyRepository;
use HrTech\Repositories\TenantRepository;
use HrTech\Repositories\TimeAdjustmentRepository;
use HrTech\Repositories\TimeLogRepository;
use HrTech\Repositories\UserRepository;
use HrTech\Repositories\VacationRepository;
use HrTech\Services\BenefitService;
use HrTech\Services\DepartmentRoleService;
use HrTech\Services\EmployeeService;
use HrTech\Services\EquipmentASOService;
use HrTech\Services\InsurancePolicyService;
use HrTech\Services\TenantService;
use HrTech\Services\TimeAdjustmentService;
use HrTech\Services\TimeLogService;
use HrTech\Services\UserService;
use HrTech\Services\VacationService;

final class M4AdversarialHarness
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;
    private int $failedAssertions = 0;
    /** @var string[] */
    private array $failures = [];
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
        echo "\n\033[1;35m⚡ Suite: {$name}\033[0m\n";
    }

    public function assertTrue(bool $condition, string $message): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  \033[32m✔\033[0m {$message}\n";
        } else {
            $this->failedAssertions++;
            echo "  \033[31m✘ ATTACK DETECTED / FAIL: {$message}\033[0m\n";
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
                echo "  \033[32m✔\033[0m {$message} (Caught " . get_class($e) . ")\n";
            } else {
                $this->failedAssertions++;
                echo "  \033[31m✘ FAIL: {$message} (Expected {$expectedExceptionClass}, got " . get_class($e) . ": {$e->getMessage()})\033[0m\n";
                $this->failures[] = "[{$this->currentSuite}] {$message} (Wrong exception type: " . get_class($e) . ")";
            }
        }
    }

    public function printSummary(): int
    {
        $elapsed = round((microtime(true) - $this->startTime) * 1000, 2);
        $peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        echo "\n" . str_repeat('=', 75) . "\n";
        echo "\033[1;37mCHALLENGER M4-1: ADVERSARIAL CRUD STRESS TEST SUMMARY\033[0m\n";
        echo str_repeat('=', 75) . "\n";
        echo "Total Assertions : {$this->totalAssertions}\n";
        echo "Passed           : \033[32m{$this->passedAssertions}\033[0m\n";
        echo "Failed           : " . ($this->failedAssertions > 0 ? "\033[31m{$this->failedAssertions}\033[0m" : "0") . "\n";
        echo "Execution Time   : {$elapsed} ms\n";
        echo "Peak Memory      : {$peakMem} MB\n";

        if ($this->failedAssertions > 0) {
            echo "\n\033[1;31mADVERSARIAL FAILURES ENCOUNTERED:\033[0m\n";
            foreach ($this->failures as $failure) {
                echo "  • {$failure}\n";
            }
            echo "\n\033[1;31mVERDICT: FAIL — ADVERSARIAL CHALLENGES REVEALED DEFECTS\033[0m\n\n";
            return 1;
        }

        echo "\n\033[1;32mVERDICT: PASS — 100% ADVERSARIAL ATTACKS SUCCESSFULLY SHIELDED\033[0m\n\n";
        return 0;
    }
}

$harness = new M4AdversarialHarness();

// ==========================================================================
// Setup In-Memory Database & Repositories
// ==========================================================================
DatabaseManager::resetInstance();
$db = DatabaseManager::getInstance(':memory:');
$db->migrate();
$pdo = $db->getConnection();

// Instantiate Repositories
$tenantRepo = new TenantRepository($db);
$userRepo = new UserRepository($db);
$deptRoleRepo = new DepartmentRoleRepository($db);
$empRepo = new EmployeeRepository($db);
$timeLogRepo = new TimeLogRepository($db);
$adjRepo = new TimeAdjustmentRepository($db);
$vacRepo = new VacationRepository($db);
$benefitRepo = new BenefitRepository($db);
$eqRepo = new EquipmentASORepository($db);
$policyRepo = new InsurancePolicyRepository($db);

// Instantiate Services
$tenantService = new TenantService($tenantRepo);
$userService = new UserService($userRepo);
$deptRoleService = new DepartmentRoleService($deptRoleRepo);
$empService = new EmployeeService($empRepo);
$timeLogService = new TimeLogService($timeLogRepo);
$adjService = new TimeAdjustmentService($adjRepo);
$vacService = new VacationService($vacRepo, $empRepo);
$benefitService = new BenefitService($benefitRepo);
$eqService = new EquipmentASOService($eqRepo);
$policyService = new InsurancePolicyService($policyRepo);

// Seed Victim and Attacker Tenants
$victimTenant = $tenantService->createTenant(
    id: 'tenant-victim',
    cnpj: '11.222.333/0001-81',
    corporateName: 'Victim Technologies Corp',
    tradingName: 'VictimTech',
    segment: 'tech',
    modules: ['payroll', 'time_tracking', 'benefits', 'insurance']
);

$attackerTenant = $tenantService->createTenant(
    id: 'tenant-attacker',
    cnpj: '99.888.777/0001-00',
    corporateName: 'Hostile Attacker Inc',
    tradingName: 'HostileCorp',
    segment: 'tech',
    modules: ['payroll', 'time_tracking']
);

// Seed Full Entity Tree for Victim Tenant
$victimUser = $userService->createUser(
    id: 'user-victim-1',
    tenantId: 'tenant-victim',
    username: 'victim_admin',
    email: 'admin@victim.com',
    plainPassword: 'SecretVictimPassword123!',
    role: UserRole::TENANT_ADMIN
);

$victimDept = $deptRoleService->createDepartment(
    id: 'dept-victim-1',
    tenantId: 'tenant-victim',
    code: 'VIC-ENG',
    name: 'Victim R&D',
    costCenter: 'CC-9000'
);

$victimRole = $deptRoleService->createRole(
    id: 'role-victim-1',
    tenantId: 'tenant-victim',
    name: 'Principal Engineer',
    description: 'R&D Leader',
    hierarchyLevel: 90,
    permissions: ['all_access'],
    departmentId: 'dept-victim-1'
);

$victimEmp = $empService->hireEmployee(
    id: 'emp-victim-1',
    tenantId: 'tenant-victim',
    cpf: '123.456.789-09',
    fullName: 'Alice Victim',
    email: 'alice@victim.com',
    phone: '11987654321',
    birthDate: '1990-01-01',
    admissionDate: '2021-01-01',
    departmentId: 'dept-victim-1',
    roleId: 'role-victim-1',
    baseSalary: 15000,
    employmentType: 'CLT'
);

$victimPunch = $timeLogService->recordPunch(
    id: 'punch-victim-1',
    tenantId: 'tenant-victim',
    employeeId: 'emp-victim-1',
    timestamp: new DateTimeImmutable('2026-03-01T08:00:00-03:00'),
    type: TimeLogType::ENTRY,
    location: new GeoLocation(-23.5505, -46.6333)
);

$victimAdj = $adjService->requestAdjustment(
    id: 'adj-victim-1',
    tenantId: 'tenant-victim',
    employeeId: 'emp-victim-1',
    requestedDate: new DateTimeImmutable('2026-03-02'),
    originalTime: null,
    requestedTime: new DateTimeImmutable('2026-03-02T08:05:00-03:00'),
    reason: 'Power outage punch adjustment'
);

$vacStart = (new DateTimeImmutable('now'))->modify('+35 days');
$vacEnd = $vacStart->modify('+9 days');
$victimVac = $vacService->requestVacation(
    id: 'vac-victim-1',
    tenantId: 'tenant-victim',
    employeeId: 'emp-victim-1',
    startDate: $vacStart,
    endDate: $vacEnd,
    durationDays: 10
);

$victimBen = $benefitService->createBenefit(
    id: 'ben-victim-1',
    tenantId: 'tenant-victim',
    type: BenefitType::HEALTH_PLAN,
    name: 'Executive Medical Plan',
    provider: 'Bradesco Saúde',
    value: Money::fromCents(120000),
    copayPercentage: 10.0
);

$victimPpe = $eqService->deliverEquipment(
    id: 'ppe-victim-1',
    tenantId: 'tenant-victim',
    employeeId: 'emp-victim-1',
    equipmentName: 'Victim Hardhat',
    caNumber: '54321'
);

$victimAso = $eqService->recordMedicalExam(
    id: 'aso-victim-1',
    tenantId: 'tenant-victim',
    employeeId: 'emp-victim-1',
    examType: ExamType::ADMISSION,
    examDate: new DateTimeImmutable('2021-01-01'),
    expirationDate: (new DateTimeImmutable('now'))->modify('+180 days'),
    physicianName: 'Dr. House',
    physicianCrm: '112233-SP',
    isFit: true
);

$victimPolicy = $policyService->issuePolicy(
    id: 'pol-victim-1',
    tenantId: 'tenant-victim',
    policyNumber: 'VIC-POL-999',
    brokerCode: 'BRK-VIC',
    insurerName: 'SulAmerica S.A.',
    employeeId: 'emp-victim-1',
    insuredCapital: Money::fromCents(100000000),
    monthlyPremium: Money::fromCents(25000),
    status: PolicyStatus::ACTIVE,
    startDate: new DateTimeImmutable('2026-01-01'),
    endDate: new DateTimeImmutable('2026-12-31')
);

// ==========================================================================
// Suite 1: Cross-Tenant Data Isolation Attacks (Read & Mutation Shielding)
// ==========================================================================
$harness->suite('Cross-Tenant Data Isolation & Mutation Shielding (All 10 CRUDs)');

// 1.1 CRUD 2 (Users) Cross-Tenant Read Attacks
$harness->assertTrue($userRepo->findById('user-victim-1', 'tenant-attacker') === null, 'Users: Attacker cannot read victim by ID');
$harness->assertTrue($userRepo->findByUsername('victim_admin', 'tenant-attacker') === null, 'Users: Attacker cannot read victim by username');
$harness->assertTrue($userRepo->findByEmail('admin@victim.com', 'tenant-attacker') === null, 'Users: Attacker cannot read victim by email');
$attackerUsers = $userRepo->findByTenant('tenant-attacker');
$harness->assertEquals(0, count($attackerUsers), 'Users: Attacker tenant user list does not include victim users');
$harness->assertTrue($userService->authenticate('victim_admin', 'SecretVictimPassword123!', 'tenant-attacker') === null, 'Users: Attacker cannot authenticate as victim under attacker tenant context');

// 1.2 CRUD 2 (Users) Cross-Tenant Mutation Attacks
$userRepo->delete('user-victim-1', 'tenant-attacker');
$harness->assertTrue($userRepo->findById('user-victim-1', 'tenant-victim') !== null, 'Users: Victim user remains intact after attacker repo delete attempt');
$harness->assertThrows(function () use ($userService) {
    $userService->changePassword('user-victim-1', 'tenant-attacker', 'SecretVictimPassword123!', 'NewHackedPass123!');
}, InvalidOperationException::class, 'Users: Cross-tenant changePassword rejected with InvalidOperationException');
$harness->assertThrows(function () use ($userService) {
    $userService->updateRole('user-victim-1', 'tenant-attacker', UserRole::TENANT_ADMIN);
}, InvalidOperationException::class, 'Users: Cross-tenant updateRole rejected with InvalidOperationException');
$harness->assertThrows(function () use ($userService) {
    $userService->toggleActive('user-victim-1', 'tenant-attacker', false);
}, InvalidOperationException::class, 'Users: Cross-tenant toggleActive rejected with InvalidOperationException');
$harness->assertThrows(function () use ($userService) {
    $userService->linkEmployee('user-victim-1', 'tenant-attacker', 'emp-attacker-1');
}, InvalidOperationException::class, 'Users: Cross-tenant linkEmployee rejected with InvalidOperationException');

// 1.3 CRUD 4 (Departments & Roles) Cross-Tenant Read Attacks
$harness->assertTrue($deptRoleRepo->findDepartmentById('dept-victim-1', 'tenant-attacker') === null, 'Departments: Attacker cannot read victim department by ID');
$harness->assertTrue($deptRoleRepo->findDepartmentByCode('VIC-ENG', 'tenant-attacker') === null, 'Departments: Attacker cannot read victim department by code');
$harness->assertEquals(0, count($deptRoleRepo->findDepartmentsByTenant('tenant-attacker')), 'Departments: Attacker department list excludes victim departments');
$harness->assertTrue($deptRoleRepo->findRoleById('role-victim-1', 'tenant-attacker') === null, 'Roles: Attacker cannot read victim tenant-scoped role by ID');
$harness->assertEquals(0, count($deptRoleRepo->findRolesByTenant('tenant-attacker')), 'Roles: Attacker role list excludes victim roles');

// 1.4 CRUD 4 (Departments & Roles) Cross-Tenant Mutation Attacks
$deptRoleRepo->deleteDepartment('dept-victim-1', 'tenant-attacker');
$harness->assertTrue($deptRoleRepo->findDepartmentById('dept-victim-1', 'tenant-victim') !== null, 'Departments: Victim department remains intact after attacker delete attempt');
$harness->assertThrows(function () use ($deptRoleService) {
    $deptRoleService->updateDepartment('dept-victim-1', 'tenant-attacker', 'HACK', 'Hacked Dept', 'CC-0');
}, InvalidOperationException::class, 'Departments: Cross-tenant updateDepartment rejected with InvalidOperationException');
$deptRoleRepo->deleteRole('role-victim-1', 'tenant-attacker');
$harness->assertTrue($deptRoleRepo->findRoleById('role-victim-1', 'tenant-victim') !== null, 'Roles: Victim role remains intact after attacker delete attempt');

// 1.5 CRUD 3 (Employees) Cross-Tenant Read Attacks
$harness->assertTrue($empRepo->findById('emp-victim-1', 'tenant-attacker') === null, 'Employees: Attacker cannot read victim employee by ID');
$harness->assertTrue($empRepo->findByCpf('123.456.789-09', 'tenant-attacker') === null, 'Employees: Attacker cannot read victim employee by CPF');
$harness->assertTrue($empRepo->findByEmail('alice@victim.com', 'tenant-attacker') === null, 'Employees: Attacker cannot read victim employee by email');
$harness->assertEquals(0, count($empRepo->findAllByTenant('tenant-attacker')), 'Employees: Attacker employee list excludes victim employees');
$harness->assertEquals(0, count($empRepo->findByDepartment('dept-victim-1', 'tenant-attacker')), 'Employees: Cross-tenant department employee lookup returns empty');

// 1.6 CRUD 3 (Employees) Cross-Tenant Mutation Attacks
$empRepo->delete('emp-victim-1', 'tenant-attacker');
$harness->assertTrue($empRepo->findById('emp-victim-1', 'tenant-victim') !== null, 'Employees: Victim employee remains intact after attacker delete attempt');
$harness->assertThrows(function () use ($empService) {
    $empService->adjustSalary('emp-victim-1', 'tenant-attacker', 999999);
}, InvalidOperationException::class, 'Employees: Cross-tenant adjustSalary rejected with InvalidOperationException');
$harness->assertThrows(function () use ($empService) {
    $empService->transferDepartment('emp-victim-1', 'tenant-attacker', 'dept-fake');
}, InvalidOperationException::class, 'Employees: Cross-tenant transferDepartment rejected with InvalidOperationException');
$harness->assertThrows(function () use ($empService) {
    $empService->recordBankHours('emp-victim-1', 'tenant-attacker', 60);
}, InvalidOperationException::class, 'Employees: Cross-tenant recordBankHours rejected with InvalidOperationException');

// 1.7 CRUD 5 (TimeLogs) Cross-Tenant Read Attacks
$harness->assertTrue($timeLogRepo->findById('punch-victim-1', 'tenant-attacker') === null, 'TimeLogs: Attacker cannot read victim punch by ID');
$harness->assertTrue($timeLogRepo->findLastByEmployee('emp-victim-1', 'tenant-attacker') === null, 'TimeLogs: Attacker cannot read victim last punch');
$harness->assertEquals(0, count($timeLogRepo->findByEmployeeAndPeriod('emp-victim-1', 'tenant-attacker', new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31'))), 'TimeLogs: Attacker cannot read victim punch period');
$harness->assertEquals(0, count($timeLogRepo->findAllByTenant('tenant-attacker')), 'TimeLogs: Attacker punch list excludes victim punches');
$harness->assertEquals(0, $timeLogRepo->getLatestNsr('tenant-attacker'), 'TimeLogs: Attacker latest NSR is 0, isolated from victim sequence');

// 1.8 CRUD 6 (TimeAdjustments) Cross-Tenant Read Attacks & Mutation Shielding
$harness->assertTrue($adjRepo->findById('adj-victim-1', 'tenant-attacker') === null, 'TimeAdjustments: Attacker cannot read victim adjustment by ID');
$harness->assertEquals(0, count($adjRepo->findByEmployee('emp-victim-1', 'tenant-attacker')), 'TimeAdjustments: Attacker cannot read adjustments by victim employee');
$harness->assertEquals(0, count($adjRepo->findAllByTenant('tenant-attacker')), 'TimeAdjustments: Attacker adjustment list excludes victim requests');
$adjRepo->delete('adj-victim-1', 'tenant-attacker');
$harness->assertTrue($adjRepo->findById('adj-victim-1', 'tenant-victim') !== null, 'TimeAdjustments: Victim adjustment remains intact after attacker delete attempt');
$harness->assertThrows(function () use ($adjService) {
    $adjService->approveAdjustment('adj-victim-1', 'tenant-attacker', 'attacker-user');
}, InvalidOperationException::class, 'TimeAdjustments: Cross-tenant approveAdjustment rejected with InvalidOperationException');
$harness->assertThrows(function () use ($adjService) {
    $adjService->rejectAdjustment('adj-victim-1', 'tenant-attacker', 'attacker-user', 'Denial');
}, InvalidOperationException::class, 'TimeAdjustments: Cross-tenant rejectAdjustment rejected with InvalidOperationException');

// 1.9 CRUD 7 (Vacations) Cross-Tenant Read Attacks & Mutation Shielding
$harness->assertTrue($vacRepo->findById('vac-victim-1', 'tenant-attacker') === null, 'Vacations: Attacker cannot read victim vacation by ID');
$harness->assertEquals(0, count($vacRepo->findByEmployee('emp-victim-1', 'tenant-attacker')), 'Vacations: Attacker cannot read vacations by victim employee');
$harness->assertEquals(0, count($vacRepo->findAllByTenant('tenant-attacker')), 'Vacations: Attacker vacation list excludes victim requests');
$vacRepo->delete('vac-victim-1', 'tenant-attacker');
$harness->assertTrue($vacRepo->findById('vac-victim-1', 'tenant-victim') !== null, 'Vacations: Victim vacation remains intact after attacker delete attempt');
$harness->assertThrows(function () use ($vacService) {
    $vacService->approveVacation('vac-victim-1', 'tenant-attacker', 'attacker-user');
}, InvalidOperationException::class, 'Vacations: Cross-tenant approveVacation rejected with InvalidOperationException');

// 1.10 CRUD 8 (Benefits) Cross-Tenant Read Attacks & Mutation Shielding
$harness->assertTrue($benefitRepo->findById('ben-victim-1', 'tenant-attacker') === null, 'Benefits: Attacker cannot read victim benefit by ID');
$harness->assertEquals(0, count($benefitRepo->findByType(BenefitType::HEALTH_PLAN, 'tenant-attacker')), 'Benefits: Attacker cannot read victim benefits by type');
$harness->assertEquals(0, count($benefitRepo->findAllByTenant('tenant-attacker')), 'Benefits: Attacker benefit catalog excludes victim benefits');
$benefitRepo->delete('ben-victim-1', 'tenant-attacker');
$harness->assertTrue($benefitRepo->findById('ben-victim-1', 'tenant-victim') !== null, 'Benefits: Victim benefit remains intact after attacker delete attempt');
$harness->assertThrows(function () use ($benefitService) {
    $benefitService->updatePricing('ben-victim-1', 'tenant-attacker', 500, 0.0);
}, InvalidOperationException::class, 'Benefits: Cross-tenant updatePricing rejected with InvalidOperationException');

// 1.11 CRUD 9 (Equipment & ASO) Cross-Tenant Read Attacks & Mutation Shielding
$harness->assertTrue($eqRepo->findById('ppe-victim-1', 'tenant-attacker') === null, 'EquipmentASO: Attacker cannot read victim PPE by ID');
$harness->assertTrue($eqRepo->findById('aso-victim-1', 'tenant-attacker') === null, 'EquipmentASO: Attacker cannot read victim ASO by ID');
$harness->assertEquals(0, count($eqRepo->findByEmployee('emp-victim-1', 'tenant-attacker')), 'EquipmentASO: Attacker cannot list victim equipment/ASO');
$harness->assertEquals(0, count($eqRepo->findAllByTenant('tenant-attacker')), 'EquipmentASO: Attacker list excludes victim records');
$eqRepo->delete('ppe-victim-1', 'tenant-attacker');
$harness->assertTrue($eqRepo->findById('ppe-victim-1', 'tenant-victim') !== null, 'EquipmentASO: Victim PPE remains intact after attacker delete attempt');
$harness->assertThrows(function () use ($eqService) {
    $eqService->recordEquipmentReturn('ppe-victim-1', 'tenant-attacker');
}, InvalidOperationException::class, 'EquipmentASO: Cross-tenant recordEquipmentReturn rejected with InvalidOperationException');

// 1.12 CRUD 10 (Insurance Policies) Cross-Tenant Read Attacks & Mutation Shielding
$harness->assertTrue($policyRepo->findById('pol-victim-1', 'tenant-attacker') === null, 'InsurancePolicies: Attacker cannot read victim policy by ID');
$harness->assertTrue($policyRepo->findByPolicyNumber('VIC-POL-999', 'tenant-attacker') === null, 'InsurancePolicies: Attacker cannot read victim policy by number');
$harness->assertEquals(0, count($policyRepo->findByEmployee('emp-victim-1', 'tenant-attacker')), 'InsurancePolicies: Attacker cannot read policies by victim employee');
$harness->assertEquals(0, count($policyRepo->findAllByTenant('tenant-attacker')), 'InsurancePolicies: Attacker policy list excludes victim policies');
$policyRepo->delete('pol-victim-1', 'tenant-attacker');
$harness->assertTrue($policyRepo->findById('pol-victim-1', 'tenant-victim') !== null, 'InsurancePolicies: Victim policy remains intact after attacker delete attempt');
$harness->assertThrows(function () use ($policyService) {
    $policyService->renewPolicy('pol-victim-1', 'tenant-attacker', new DateTimeImmutable('2030-01-01'));
}, InvalidOperationException::class, 'InsurancePolicies: Cross-tenant renewPolicy rejected with InvalidOperationException');
$harness->assertThrows(function () use ($policyService) {
    $policyService->cancelPolicy('pol-victim-1', 'tenant-attacker', 'Malicious cancellation');
}, InvalidOperationException::class, 'InsurancePolicies: Cross-tenant cancelPolicy rejected with InvalidOperationException');

// ==========================================================================
// Suite 2: Portaria 671 Mutation Rejection & Tamper-Proof Chain Integrity
// ==========================================================================
$harness->suite('Portaria 671 Immutability & Tamper-Proof Cryptographic Verification');

// 2.1 Service-level mutation rejection
$harness->assertThrows(function () use ($timeLogService) {
    $timeLogService->updatePunch();
}, InvalidOperationException::class, 'Portaria 671: Direct updatePunch() strictly prohibited with InvalidOperationException');

$harness->assertThrows(function () use ($timeLogService) {
    $timeLogService->deletePunch();
}, InvalidOperationException::class, 'Portaria 671: Direct deletePunch() strictly prohibited with InvalidOperationException');

// 2.2 Construct a valid multi-punch chain for employee
$punch2 = $timeLogService->recordPunch(
    id: 'punch-victim-2',
    tenantId: 'tenant-victim',
    employeeId: 'emp-victim-1',
    timestamp: new DateTimeImmutable('2026-03-01T12:00:00-03:00'),
    type: TimeLogType::INTERVAL_START,
    location: new GeoLocation(-23.5505, -46.6333)
);

$punch3 = $timeLogService->recordPunch(
    id: 'punch-victim-3',
    tenantId: 'tenant-victim',
    employeeId: 'emp-victim-1',
    timestamp: new DateTimeImmutable('2026-03-01T13:00:00-03:00'),
    type: TimeLogType::INTERVAL_END,
    location: new GeoLocation(-23.5505, -46.6333)
);

$harness->assertTrue($timeLogService->verifyTamperProofChain('tenant-victim'), 'Portaria 671: Valid untampered 3-punch chain verifies successfully');

// 2.3 Adversarial direct-database tampering simulation: Alter punch type
$originalType = $punch2->type->value;
$pdo->exec("UPDATE time_logs SET type = 'exit' WHERE id = 'punch-victim-2'");
$harness->assertThrows(function () use ($timeLogService) {
    $timeLogService->verifyTamperProofChain('tenant-victim');
}, ValidationException::class, 'Tamper Attack 1: Direct DB modification of punch type immediately caught by ValidationException upon hydration');
// Restore
$pdo->exec("UPDATE time_logs SET type = '{$originalType}' WHERE id = 'punch-victim-2'");
$harness->assertTrue($timeLogService->verifyTamperProofChain('tenant-victim'), 'Tamper Attack 1: Restoring valid punch type re-validates SHA-256 chain');

// 2.4 Adversarial direct-database tampering simulation: Alter timestamp
$pdo->exec("UPDATE time_logs SET timestamp = '2026-03-01T11:59:59+00:00' WHERE id = 'punch-victim-2'");
$harness->assertThrows(function () use ($timeLogService) {
    $timeLogService->verifyTamperProofChain('tenant-victim');
}, ValidationException::class, 'Tamper Attack 2: Direct DB modification of timestamp immediately caught by ValidationException');
$pdo->exec("UPDATE time_logs SET timestamp = '{$punch2->timestamp->format(DateTimeInterface::ATOM)}' WHERE id = 'punch-victim-2'");
$harness->assertTrue($timeLogService->verifyTamperProofChain('tenant-victim'), 'Tamper Attack 2: Restoring original timestamp re-validates SHA-256 chain');

// 2.5 Adversarial direct-database tampering simulation: Alter previous_hash
$pdo->exec("UPDATE time_logs SET previous_hash = '0000000000000000000000000000000000000000000000000000000000000000' WHERE id = 'punch-victim-3'");
$harness->assertThrows(function () use ($timeLogService) {
    $timeLogService->verifyTamperProofChain('tenant-victim');
}, ValidationException::class, 'Tamper Attack 3: Modifying previous_hash immediately caught by ValidationException');
$pdo->exec("UPDATE time_logs SET previous_hash = '{$punch2->signatureHash}' WHERE id = 'punch-victim-3'");
$harness->assertTrue($timeLogService->verifyTamperProofChain('tenant-victim'), 'Tamper Attack 3: Restoring authentic previous_hash re-validates chain');

// 2.6 Adversarial direct-database tampering simulation: Break NSR sequence
$pdo->exec("UPDATE time_logs SET nsr = 999 WHERE id = 'punch-victim-2'");
$harness->assertFalse($timeLogService->verifyTamperProofChain('tenant-victim'), 'Tamper Attack 4: Modifying NSR counter causes chain verification to return FALSE');
$pdo->exec("UPDATE time_logs SET nsr = {$punch2->nsr} WHERE id = 'punch-victim-2'");
$harness->assertTrue($timeLogService->verifyTamperProofChain('tenant-victim'), 'Tamper Attack 4: Restoring valid NSR re-validates chain');

// 2.7 Adversarial intermediate record deletion simulation: Break cryptographic link between punch 1 and punch 3
$pdo->exec("DELETE FROM time_logs WHERE id = 'punch-victim-2'");
$harness->assertFalse($timeLogService->verifyTamperProofChain('tenant-victim'), 'Tamper Attack 5: Deletion of intermediate punch cleanly causes chain verification to return FALSE');
$timeLogRepo->save($punch2);
$harness->assertTrue($timeLogService->verifyTamperProofChain('tenant-victim'), 'Tamper Attack 5: Re-inserting missing punch restores chain integrity to TRUE');

// ==========================================================================
// Suite 3: SQL Injection Attack Payloads in Search & Filter Queries
// ==========================================================================
$harness->suite('SQL Injection Payloads in Search, Filter, and ID Lookups');

$sqliPayloads = [
    "' OR '1'='1",
    "admin' --",
    "'; DROP TABLE tenants; --",
    "' UNION SELECT id, username, password_hash, role, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL FROM users --",
    "1' OR '1'='1' UNION ALL SELECT 'a','b','c','d','e','f','g','h','i','j','k','l' --",
    "0' OR 1=1 --",
    "') OR ('1'='1",
    "' OR ''='",
    "\\x00' OR 1=1 --",
    "1; SELECT 1;",
];

foreach ($sqliPayloads as $idx => $payload) {
    $tag = "Payload #{$idx} [{$payload}]";

    // 3.1 TenantRepository
    $harness->assertTrue($tenantRepo->findById($payload) === null, "SQLi TenantRepo findById shielded: {$tag}");
    $harness->assertTrue($tenantRepo->findByCnpj($payload) === null, "SQLi TenantRepo findByCnpj shielded: {$tag}");
    $harness->assertFalse($tenantRepo->existsCnpj($payload), "SQLi TenantRepo existsCnpj shielded: {$tag}");

    // 3.2 UserRepository
    $harness->assertTrue($userRepo->findById($payload, 'tenant-victim') === null, "SQLi UserRepo findById shielded: {$tag}");
    $harness->assertTrue($userRepo->findByUsername($payload, 'tenant-victim') === null, "SQLi UserRepo findByUsername shielded: {$tag}");
    $harness->assertTrue($userRepo->findByEmail($payload, 'tenant-victim') === null, "SQLi UserRepo findByEmail shielded: {$tag}");
    $harness->assertFalse($userRepo->existsUsername($payload, 'tenant-victim'), "SQLi UserRepo existsUsername shielded: {$tag}");
    $harness->assertFalse($userRepo->existsEmail($payload, 'tenant-victim'), "SQLi UserRepo existsEmail shielded: {$tag}");

    // 3.3 DepartmentRoleRepository
    $harness->assertTrue($deptRoleRepo->findDepartmentById($payload, 'tenant-victim') === null, "SQLi DeptRepo findDepartmentById shielded: {$tag}");
    $harness->assertTrue($deptRoleRepo->findDepartmentByCode($payload, 'tenant-victim') === null, "SQLi DeptRepo findDepartmentByCode shielded: {$tag}");
    $harness->assertTrue($deptRoleRepo->findRoleById($payload, 'tenant-victim') === null, "SQLi RoleRepo findRoleById shielded: {$tag}");

    // 3.4 EmployeeRepository
    $harness->assertTrue($empRepo->findById($payload, 'tenant-victim') === null, "SQLi EmpRepo findById shielded: {$tag}");
    $harness->assertTrue($empRepo->findByCpf($payload, 'tenant-victim') === null, "SQLi EmpRepo findByCpf shielded: {$tag}");
    $harness->assertTrue($empRepo->findByEmail($payload, 'tenant-victim') === null, "SQLi EmpRepo findByEmail shielded: {$tag}");
    $harness->assertFalse($empRepo->existsCpf($payload, 'tenant-victim'), "SQLi EmpRepo existsCpf shielded: {$tag}");
    // SQLi via filter array
    $filteredEmps = $empRepo->findAllByTenant('tenant-victim', [
        'department_id' => $payload,
        'role_id' => $payload,
        'employment_type' => $payload
    ]);
    $harness->assertEquals(0, count($filteredEmps), "SQLi EmpRepo findAllByTenant filter shielded: {$tag}");

    // 3.5 TimeLogRepository
    $harness->assertTrue($timeLogRepo->findById($payload, 'tenant-victim') === null, "SQLi TimeLogRepo findById shielded: {$tag}");
    $filteredPunches = $timeLogRepo->findAllByTenant('tenant-victim', ['employee_id' => $payload]);
    $harness->assertEquals(0, count($filteredPunches), "SQLi TimeLogRepo findAllByTenant filter shielded: {$tag}");

    // 3.6 TimeAdjustmentRepository
    $harness->assertTrue($adjRepo->findById($payload, 'tenant-victim') === null, "SQLi AdjRepo findById shielded: {$tag}");
    $harness->assertEquals(0, count($adjRepo->findByEmployee($payload, 'tenant-victim')), "SQLi AdjRepo findByEmployee shielded: {$tag}");

    // 3.7 VacationRepository
    $harness->assertTrue($vacRepo->findById($payload, 'tenant-victim') === null, "SQLi VacRepo findById shielded: {$tag}");
    $harness->assertEquals(0, count($vacRepo->findByEmployee($payload, 'tenant-victim')), "SQLi VacRepo findByEmployee shielded: {$tag}");

    // 3.8 BenefitRepository
    $harness->assertTrue($benefitRepo->findById($payload, 'tenant-victim') === null, "SQLi BenefitRepo findById shielded: {$tag}");

    // 3.9 EquipmentASORepository
    $harness->assertTrue($eqRepo->findById($payload, 'tenant-victim') === null, "SQLi EqRepo findById shielded: {$tag}");
    $harness->assertEquals(0, count($eqRepo->findByEmployee($payload, 'tenant-victim')), "SQLi EqRepo findByEmployee shielded: {$tag}");

    // 3.10 InsurancePolicyRepository
    $harness->assertTrue($policyRepo->findById($payload, 'tenant-victim') === null, "SQLi PolicyRepo findById shielded: {$tag}");
    $harness->assertTrue($policyRepo->findByPolicyNumber($payload, 'tenant-victim') === null, "SQLi PolicyRepo findByPolicyNumber shielded: {$tag}");
    $harness->assertEquals(0, count($policyRepo->findByEmployee($payload, 'tenant-victim')), "SQLi PolicyRepo findByEmployee shielded: {$tag}");
}

// Ensure schema and data integrity preserved after all SQL injection attacks
$tenantsCount = (int)$pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
$harness->assertTrue($tenantsCount >= 2, 'Schema Integrity: All tables still intact and tenants preserved after SQLi barrage');
$usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$harness->assertTrue($usersCount >= 1, 'Schema Integrity: Users table intact and uncorrupted');

// ==========================================================================
// Suite 4: Foreign Key Cascading & Zero-Orphan Integrity
// ==========================================================================
$harness->suite('Foreign Key Cascading & Zero-Orphan Integrity');

// 4.1 Tenant Cascade Test: Complete ecosystem populated across all 11 child tables
$tWipeId = 't-cascade-wipe';
$eWipeId = 'emp-cascade-wipe';

$db->transaction(function (PDO $conn) use ($tWipeId, $eWipeId) {
    // 1. Tenant
    $conn->exec("INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at)
                 VALUES ('{$tWipeId}', '33444555000199', 'Cascade Wipe Corp', 'WipeCorp', 'tech', 1, '[]', '2026-01-01T00:00:00+00:00')");
    // 2. Department
    $conn->exec("INSERT INTO departments (id, tenant_id, code, name, cost_center, is_active, created_at)
                 VALUES ('dept-wipe', '{$tWipeId}', 'WIPE-01', 'Wipe Dept', 'CC-W', 1, '2026-01-01T00:00:00+00:00')");
    // 3. Role
    $conn->exec("INSERT INTO roles (id, tenant_id, name, description, hierarchy_level, permissions, created_at)
                 VALUES ('role-wipe', '{$tWipeId}', 'Wipe Role', 'Wipe', 1, '[]', '2026-01-01T00:00:00+00:00')");
    // 4. Employee
    $conn->exec("INSERT INTO employees (id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, created_at)
                 VALUES ('{$eWipeId}', '{$tWipeId}', '98765432100', 'Wipe Emp', 'wipe@emp.com', '1199', '1990-01-01', '2020-01-01', 'dept-wipe', 'role-wipe', 500000, 'CLT', '2026-01-01T00:00:00+00:00')");
    // 5. User
    $conn->exec("INSERT INTO users (id, tenant_id, username, email, password_hash, role, employee_id, created_at)
                 VALUES ('user-wipe', '{$tWipeId}', 'wipeuser', 'wipe@emp.com', 'hash', 'EMPLOYEE', '{$eWipeId}', '2026-01-01T00:00:00+00:00')");
    // 6. TimeLog
    $conn->exec("INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, nsr, signature_hash)
                 VALUES ('punch-wipe', '{$tWipeId}', '{$eWipeId}', '2026-01-01T08:00:00+00:00', 'ENTRY', -23.5, -46.6, 1, 'sighash')");
    // 7. TimeAdjustmentRequest
    $conn->exec("INSERT INTO time_adjustment_requests (id, tenant_id, employee_id, requested_date, requested_time, reason, status, created_at)
                 VALUES ('adj-wipe', '{$tWipeId}', '{$eWipeId}', '2026-01-01', '2026-01-01T08:00:00+00:00', 'Wipe reason', 'PENDING', '2026-01-01T00:00:00+00:00')");
    // 8. VacationRequest
    $conn->exec("INSERT INTO vacation_requests (id, tenant_id, employee_id, start_date, end_date, duration_days, status, created_at)
                 VALUES ('vac-wipe', '{$tWipeId}', '{$eWipeId}', '2026-02-01', '2026-02-15', 15, 'REQUESTED', '2026-01-01T00:00:00+00:00')");
    // 9. Benefit
    $conn->exec("INSERT INTO benefits (id, tenant_id, type, name, provider, value_cents)
                 VALUES ('ben-wipe', '{$tWipeId}', 'MEAL_VOUCHER', 'Wipe VR', 'Pluxee', 60000)");
    // 10. EquipmentASO
    $conn->exec("INSERT INTO equipment_aso (id, tenant_id, employee_id, equipment_name, ca_number, exam_type, created_at)
                 VALUES ('eq-wipe', '{$tWipeId}', '{$eWipeId}', 'Wipe Boot', '999', 'PERIODIC', '2026-01-01T00:00:00+00:00')");
    // 11. InsurancePolicy
    $conn->exec("INSERT INTO insurance_policies (id, tenant_id, policy_number, broker_code, insurer_name, employee_id, insured_capital_cents, monthly_premium_cents, status, start_date, end_date)
                 VALUES ('pol-wipe', '{$tWipeId}', 'POL-WIPE-1', 'BRK-W', 'Porto', '{$eWipeId}', 1000000, 1000, 'ACTIVE', '2026-01-01', '2026-12-31')");
    // 12. AuditLog
    $conn->exec("INSERT INTO audit_logs (id, tenant_id, actor_user_id, action, entity_type, entity_id, ip_address, user_agent, timestamp, integrity_hash)
                 VALUES ('audit-wipe', '{$tWipeId}', 'user-wipe', 'CREATE', 'Tenant', '{$tWipeId}', '127.0.0.1', 'CLI', '2026-01-01T00:00:00+00:00', 'hash')");
});

$childTables = [
    'departments', 'roles', 'employees', 'users', 'time_logs',
    'time_adjustment_requests', 'vacation_requests', 'benefits',
    'equipment_aso', 'insurance_policies', 'audit_logs'
];

foreach ($childTables as $tbl) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$tbl} WHERE tenant_id = '{$tWipeId}'")->fetchColumn();
    $harness->assertTrue($count > 0, "Pre-Cascade: Child table '{$tbl}' has seeded records for tenant");
}

// Delete root tenant
$tenantRepo->delete($tWipeId);
$harness->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM tenants WHERE id = '{$tWipeId}'")->fetchColumn(), 'Root tenant successfully deleted');

// Verify 100% cascade wipe across all 11 child tables
foreach ($childTables as $tbl) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$tbl} WHERE tenant_id = '{$tWipeId}'")->fetchColumn();
    $harness->assertEquals(0, $count, "Post-Cascade: Child table '{$tbl}' wiped cleanly with zero orphaned rows");
}

// 4.2 Employee Cascade Test: Delete employee and verify employee child records wipe cleanly
$tEmpCascade = 't-emp-cascade';
$eCascadeTarget = 'emp-cascade-target';

$db->transaction(function (PDO $conn) use ($tEmpCascade, $eCascadeTarget) {
    $conn->exec("INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at)
                 VALUES ('{$tEmpCascade}', '44555666000100', 'Emp Cascade Corp', 'ECC', 'tech', 1, '[]', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO departments (id, tenant_id, code, name, cost_center, is_active, created_at)
                 VALUES ('d-ec', '{$tEmpCascade}', 'DEC', 'Dept EC', 'CC', 1, '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO roles (id, tenant_id, name, description, hierarchy_level, permissions, created_at)
                 VALUES ('r-ec', '{$tEmpCascade}', 'Role EC', 'Role', 1, '[]', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO employees (id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, created_at)
                 VALUES ('{$eCascadeTarget}', '{$tEmpCascade}', '11144477700', 'Emp Cascade', 'emp@ec.com', '11', '1990-01-01', '2020-01-01', 'd-ec', 'r-ec', 500000, 'CLT', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, nsr, signature_hash)
                 VALUES ('tl-ec', '{$tEmpCascade}', '{$eCascadeTarget}', '2026-01-01T08:00:00+00:00', 'ENTRY', 0, 0, 1, 'sig')");
    $conn->exec("INSERT INTO time_adjustment_requests (id, tenant_id, employee_id, requested_date, requested_time, reason, status, created_at)
                 VALUES ('tar-ec', '{$tEmpCascade}', '{$eCascadeTarget}', '2026-01-01', '2026-01-01T08:00:00+00:00', 'Reason', 'PENDING', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO vacation_requests (id, tenant_id, employee_id, start_date, end_date, duration_days, status, created_at)
                 VALUES ('vr-ec', '{$tEmpCascade}', '{$eCascadeTarget}', '2026-02-01', '2026-02-10', 10, 'REQUESTED', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO equipment_aso (id, tenant_id, employee_id, equipment_name, ca_number, exam_type, created_at)
                 VALUES ('eq-ec', '{$tEmpCascade}', '{$eCascadeTarget}', 'EPI', '123', 'PERIODIC', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO insurance_policies (id, tenant_id, policy_number, broker_code, insurer_name, employee_id, insured_capital_cents, monthly_premium_cents, status, start_date, end_date)
                 VALUES ('pol-ec', '{$tEmpCascade}', 'POL-EC', 'BRK', 'Ins', '{$eCascadeTarget}', 1000, 10, 'ACTIVE', '2026-01-01', '2026-12-31')");
});

$empChildTables = ['time_logs', 'time_adjustment_requests', 'vacation_requests', 'equipment_aso', 'insurance_policies'];
foreach ($empChildTables as $tbl) {
    $c = (int)$pdo->query("SELECT COUNT(*) FROM {$tbl} WHERE employee_id = '{$eCascadeTarget}'")->fetchColumn();
    $harness->assertTrue($c > 0, "Pre-Cascade: Employee child table '{$tbl}' has seeded records");
}

// Delete employee
$empRepo->delete($eCascadeTarget, $tEmpCascade);
$harness->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE id = '{$eCascadeTarget}'")->fetchColumn(), 'Employee deleted');

// Verify cascade on employee deletion
foreach ($empChildTables as $tbl) {
    $c = (int)$pdo->query("SELECT COUNT(*) FROM {$tbl} WHERE employee_id = '{$eCascadeTarget}'")->fetchColumn();
    $harness->assertEquals(0, $c, "Post-Cascade: Employee child table '{$tbl}' wiped cleanly");
}

// Clean up tenant
$tenantRepo->delete($tEmpCascade);

// ==========================================================================
// Suite 5: Atomic Transaction Rollback on Simulated Failures
// ==========================================================================
$harness->suite('Atomic Transaction Rollback on Simulated Failures');

// 5.1 Multi-table onboarding rollback simulation
$harness->assertThrows(function () use ($db, $pdo) {
    $db->transaction(function (PDO $conn) {
        // Step 1: Create tenant
        $conn->exec("INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at)
                     VALUES ('t-atomic-fail', '55666777000111', 'Atomic Corp', 'Atomic', 'tech', 1, '[]', '2026-01-01T00:00:00+00:00')");
        // Step 2: Create department
        $conn->exec("INSERT INTO departments (id, tenant_id, code, name, cost_center, is_active, created_at)
                     VALUES ('dept-atomic-fail', 't-atomic-fail', 'AT-01', 'Atomic Dept', 'CC-AT', 1, '2026-01-01T00:00:00+00:00')");
        // Step 3: Create employee
        $conn->exec("INSERT INTO employees (id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, created_at)
                     VALUES ('emp-atomic-fail', 't-atomic-fail', '55566677788', 'Atomic Emp', 'at@emp.com', '11', '1990-01-01', '2020-01-01', 'dept-atomic-fail', 'r1', 500000, 'CLT', '2026-01-01T00:00:00+00:00')");
        // Step 4: Simulate sudden fatal crash before user creation
        throw new \RuntimeException('Simulated catastrophic database failure during onboarding pipeline');
    });
}, \RuntimeException::class, 'Transaction: Simulated failure thrown and caught');

$harness->assertFalse($db->inTransaction(), 'Transaction: DatabaseManager is not in transaction after rollback');
$harness->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM tenants WHERE id = 't-atomic-fail'")->fetchColumn(), 'Transaction: Tenant record was rolled back');
$harness->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM departments WHERE id = 'dept-atomic-fail'")->fetchColumn(), 'Transaction: Department record was rolled back');
$harness->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE id = 'emp-atomic-fail'")->fetchColumn(), 'Transaction: Employee record was rolled back');

// 5.2 Managed rollback when business invariant fails
$harness->assertThrows(function () use ($db, $empRepo, $vacRepo) {
    $db->transaction(function () use ($empRepo, $vacRepo) {
        $emp = $empRepo->findById('emp-victim-1', 'tenant-victim');
        if ($emp !== null) {
            $emp->deductVacationDays(5);
            $empRepo->update($emp);
        }
        // Failure: attempt to update non-existent vacation triggers exception
        throw new InvalidOperationException('Rollback vacation business workflow');
    });
}, InvalidOperationException::class, 'Transaction: Business workflow exception triggers rollback');

$refreshedVictimEmp = $empRepo->findById('emp-victim-1', 'tenant-victim');
$harness->assertEquals(30, $refreshedVictimEmp->getVacationBalanceDays(), 'Transaction: Employee vacation balance reverted to 30 days after rollback');

// ==========================================================================
// Suite 6: Malformed Inputs, Extreme Boundary Conditions & Collision Resilience
// ==========================================================================
$harness->suite('Malformed Inputs, Extreme Boundary Conditions & Collision Resilience');

// 6.1 Empty and Whitespace Inputs
$harness->assertTrue($tenantRepo->findById('') === null, 'Boundary: Empty string tenant ID returns null');
$harness->assertTrue($userRepo->findById('   ', 'tenant-victim') === null, 'Boundary: Whitespace user ID returns null');
$harness->assertTrue($empRepo->findById('', 'tenant-victim') === null, 'Boundary: Empty employee ID returns null');
$harness->assertTrue($timeLogRepo->findById('', 'tenant-victim') === null, 'Boundary: Empty punch ID returns null');

// 6.2 Null byte string injection
$nullByteId = "emp\0victim";
$harness->assertTrue($empRepo->findById($nullByteId, 'tenant-victim') === null, 'Boundary: Null byte in ID handled safely without crash');

// 6.3 10,000-character oversized ID
$giantString = str_repeat('A', 10000);
$harness->assertTrue($tenantRepo->findById($giantString) === null, 'Boundary: 10,000-character ID handled safely');
$harness->assertTrue($userRepo->findById($giantString, 'tenant-victim') === null, 'Boundary: 10,000-character user ID handled safely');

// 6.4 Non-existent synthetic UUID lookups
$syntheticUuid = '00000000-0000-0000-0000-000000000000';
$harness->assertTrue($deptRoleRepo->findDepartmentById($syntheticUuid, 'tenant-victim') === null, 'Boundary: Synthetic non-existent UUID department returns null');
$harness->assertTrue($vacRepo->findById($syntheticUuid, 'tenant-victim') === null, 'Boundary: Synthetic non-existent UUID vacation returns null');
$harness->assertTrue($benefitRepo->findById($syntheticUuid, 'tenant-victim') === null, 'Boundary: Synthetic non-existent UUID benefit returns null');
$harness->assertTrue($policyRepo->findById($syntheticUuid, 'tenant-victim') === null, 'Boundary: Synthetic non-existent UUID policy returns null');

// 6.5 Duplicate Unique Constraints & Collision Invariants
// Duplicate CNPJ
$harness->assertThrows(function () use ($tenantService) {
    $tenantService->createTenant('t-dup-cnpj', '11.222.333/0001-81', 'Dup Corp', 'Dup Trade');
}, ValidationException::class, 'Collision: Duplicate CNPJ in tenant rejected');

// Duplicate CPF within same tenant
$harness->assertThrows(function () use ($empService) {
    $empService->hireEmployee(
        'emp-dup-cpf', 'tenant-victim', '123.456.789-09', 'Alice Duplicate',
        'alice2@victim.com', '1199999999', '1990-01-01', '2021-01-01',
        'dept-victim-1', 'role-victim-1', 15000, 'CLT'
    );
}, ValidationException::class, 'Collision: Duplicate CPF within tenant rejected');

// Duplicate Username within same tenant
$harness->assertThrows(function () use ($userService) {
    $userService->createUser('u-dup-user', 'tenant-victim', 'victim_admin', 'other@victim.com', 'Pass123!', UserRole::EMPLOYEE);
}, ValidationException::class, 'Collision: Duplicate username within tenant rejected');

// Duplicate Department Code within same tenant
$harness->assertThrows(function () use ($deptRoleService) {
    $deptRoleService->createDepartment('d-dup-code', 'tenant-victim', 'VIC-ENG', 'Another ENG', 'CC-9');
}, ValidationException::class, 'Collision: Duplicate department code within tenant rejected');

// 6.6 Cross-tenant identical code/username allowable isolation (Multi-tenant coexistence)
// Attacker tenant CAN have same department code 'VIC-ENG' because department codes are scoped per tenant
$attackerDept = $deptRoleService->createDepartment(
    'dept-attacker-1', 'tenant-attacker', 'VIC-ENG', 'Attacker Clone Dept', 'CC-ATT'
);
$harness->assertEquals('dept-attacker-1', $attackerDept->getId(), 'Multi-tenant Coexistence: Same department code in different tenants is permitted');

// Attacker tenant CAN have user with username 'victim_admin' because usernames are scoped per tenant
$attackerUserSameName = $userService->createUser(
    'user-attacker-1', 'tenant-attacker', 'victim_admin', 'admin@attacker.com', 'AttackerPass123!', UserRole::TENANT_ADMIN
);
$harness->assertEquals('user-attacker-1', $attackerUserSameName->getId(), 'Multi-tenant Coexistence: Same username in different tenants is permitted');

// Verify both users coexist and authenticate independently without collision
$authVictim = $userService->authenticate('victim_admin', 'SecretVictimPassword123!', 'tenant-victim');
$authAttacker = $userService->authenticate('victim_admin', 'AttackerPass123!', 'tenant-attacker');
$harness->assertEquals('user-victim-1', $authVictim?->getId(), 'Multi-tenant Auth: Authenticates correct victim user under victim tenant');
$harness->assertEquals('user-attacker-1', $authAttacker?->getId(), 'Multi-tenant Auth: Authenticates correct attacker user under attacker tenant');

// Final summary and exit code
$exitCode = $harness->printSummary();
exit($exitCode);
