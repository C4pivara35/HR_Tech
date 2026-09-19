<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 4 Verification Test Suite
 *
 * Standalone verification harness designed for PHP 8.3.6 CLI.
 * Rigorously verifies the complete Milestone 4 implementation:
 *  - Suite 1: DatabaseManager, SQLite Schema (12 tables), 11 Multi-Tenant Indexes, Foreign Keys & Cascades
 *  - Suite 2: 10 CRUD Modules across 5 Student Engineers:
 *      * Fernando Lopes Duarte: CRUD 1 (Tenants) & CRUD 2 (Users)
 *      * Andryus: CRUD 3 (Employees) & CRUD 4 (Departments & Roles)
 *      * Felipe: CRUD 5 (TimeLogs) & CRUD 6 (TimeAdjustments)
 *      * Valentin: CRUD 7 (Vacations) & CRUD 8 (Benefits)
 *      * Nicholas: CRUD 9 (Equipment & ASO) & CRUD 10 (Insurance Policies)
 *  - Suite 3: LPS Variability Engine & FeatureToggleManager (Tech, Indústria, Financeiro, NR-6/NR-7 Blocking, Portaria 671 Biometrics)
 *  - Suite 4: End-to-End Multi-Tenant Pipeline (Cross-cutting transactions, tenant isolation)
 *  - Suite 5: Zero-Regression Verification (m1_verify.php, m2_verify.php, m3_verify.php via isolated sub-processes)
 *
 * Usage: php tests/m4_verify.php
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
use HrTech\Exceptions\TenantNotFoundException;
use HrTech\Exceptions\ValidationException;
use HrTech\Lps\Enums\TenantSegment;
use HrTech\Lps\FeatureToggleManager;
use HrTech\Lps\LpsVariabilityEngine;
use HrTech\Patterns\Strategy\Overtime\BankHoursStrategy;
use HrTech\Patterns\Strategy\Overtime\Standard50Strategy;
use HrTech\Patterns\Strategy\Overtime\Sunday100Strategy;
use HrTech\Patterns\Strategy\Performance\Evaluation360Strategy;
use HrTech\Patterns\Strategy\Performance\KpiStrategy;
use HrTech\Patterns\Strategy\Performance\OkrStrategy;
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

// --------------------------------------------------------------------------
// 1. ANSI Test Runner Engine
// --------------------------------------------------------------------------

final class M4TestRunner
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
        echo "\033[1;37mMILESTONE 4 VERIFICATION SUMMARY\033[0m\n";
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

        echo "\n\033[1;32mRESULT: ALL MILESTONE 4 VERIFICATIONS PASSED (100%)\033[0m\n\n";
        return 0;
    }
}

$runner = new M4TestRunner();

// ==========================================================================
// Suite 1: DatabaseManager, SQLite Schema, Indexes & Transactions
// ==========================================================================
$runner->suite('DatabaseManager, SQLite Schema, Indexes & Transactions');

DatabaseManager::resetInstance();
$db = DatabaseManager::getInstance(':memory:');
$runner->assertTrue($db instanceof DatabaseManager, 'DatabaseManager singleton instance obtained for :memory:');
$runner->assertTrue(DatabaseManager::getInstance() === $db, 'DatabaseManager returns identical singleton instance');

// Migration execution
$db->migrate();
$pdo = $db->getConnection();
$runner->assertTrue($pdo instanceof PDO, 'PDO connection active');

// Verify Foreign Keys PRAGMA
$fkPragma = (int)$pdo->query('PRAGMA foreign_keys;')->fetchColumn();
$runner->assertEquals(1, $fkPragma, 'PRAGMA foreign_keys is strictly ON (1)');

// Verify all 12 tables exist in sqlite_master
$tablesStmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

$expectedTables = [
    'tenants', 'departments', 'roles', 'employees', 'users', 'time_logs',
    'time_adjustment_requests', 'vacation_requests', 'benefits',
    'equipment_aso', 'insurance_policies', 'audit_logs'
];

foreach ($expectedTables as $t) {
    $runner->assertTrue(in_array($t, $tables, true), "SQLite schema includes table: {$t}");
}
$runner->assertEquals(12, count(array_intersect($expectedTables, $tables)), 'All 12 relational tables present');

// Verify all 11 multi-tenant indexes exist
$indexesStmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%';");
$indexes = $indexesStmt->fetchAll(PDO::FETCH_COLUMN);

$expectedIndexes = [
    'idx_users_tenant',
    'idx_employees_tenant_cpf',
    'idx_time_logs_tenant_emp',
    'idx_audit_logs_tenant',
    'idx_vacation_requests_emp',
    'idx_equipment_aso_emp',
    'idx_insurance_policies_emp',
    'idx_departments_tenant',
    'idx_roles_tenant',
    'idx_time_adjustment_requests_tenant_emp',
    'idx_benefits_tenant',
];

foreach ($expectedIndexes as $idx) {
    $runner->assertTrue(in_array($idx, $indexes, true), "Multi-tenant index present: {$idx}");
}

// Transaction rollback test
$runner->assertThrows(function () use ($db, $pdo) {
    $db->transaction(function (PDO $conn) {
        $conn->exec("INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at)
                     VALUES ('tenant-fail', '11222333000181', 'Corp Fail', 'Trade Fail', 'tech', 1, '[]', '2026-01-01T00:00:00+00:00')");
        throw new RuntimeException('Intentional rollback test');
    });
}, RuntimeException::class, 'DatabaseManager transaction rolls back automatically on exception');

$countFail = (int)$pdo->query("SELECT COUNT(*) FROM tenants WHERE id = 'tenant-fail'")->fetchColumn();
$runner->assertEquals(0, $countFail, 'Tenant record was safely rolled back and does not exist');

// Foreign Key Cascade Deletion Test
$db->transaction(function (PDO $conn) {
    $conn->exec("INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at)
                 VALUES ('t-cascade', '22333444000192', 'Corp Cascade', 'Trade Cascade', 'tech', 1, '[]', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO employees (id, tenant_id, cpf, full_name, email, phone, birth_date, admission_date, department_id, role_id, base_salary_cents, employment_type, created_at)
                 VALUES ('emp-cascade', 't-cascade', '12345678909', 'Cascade Employee', 'casc@test.com', '1199999999', '1990-01-01', '2020-01-01', 'd1', 'r1', 500000, 'CLT', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO users (id, tenant_id, username, email, password_hash, role, employee_id, created_at)
                 VALUES ('user-cascade', 't-cascade', 'usercasc', 'casc@test.com', 'hash', 'EMPLOYEE', 'emp-cascade', '2026-01-01T00:00:00+00:00')");
    $conn->exec("INSERT INTO time_logs (id, tenant_id, employee_id, timestamp, type, latitude, longitude, nsr, signature_hash)
                 VALUES ('punch-casc', 't-cascade', 'emp-cascade', '2026-01-01T08:00:00+00:00', 'ENTRY', -23.55, -46.63, 1, 'hash-sig')");
});

$runner->assertEquals(1, (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE tenant_id = 't-cascade'")->fetchColumn(), 'Child employee inserted');
$runner->assertEquals(1, (int)$pdo->query("SELECT COUNT(*) FROM users WHERE tenant_id = 't-cascade'")->fetchColumn(), 'Child user inserted');
$runner->assertEquals(1, (int)$pdo->query("SELECT COUNT(*) FROM time_logs WHERE tenant_id = 't-cascade'")->fetchColumn(), 'Child punch inserted');

// Now delete root tenant and verify cascade deletes all children
$pdo->exec("DELETE FROM tenants WHERE id = 't-cascade'");
$runner->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM tenants WHERE id = 't-cascade'")->fetchColumn(), 'Tenant deleted');
$runner->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE tenant_id = 't-cascade'")->fetchColumn(), 'Cascade deleted employee');
$runner->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM users WHERE tenant_id = 't-cascade'")->fetchColumn(), 'Cascade deleted user');
$runner->assertEquals(0, (int)$pdo->query("SELECT COUNT(*) FROM time_logs WHERE tenant_id = 't-cascade'")->fetchColumn(), 'Cascade deleted time log');

// ==========================================================================
// Suite 2: 10 CRUD Operations (5 Student Engineers)
// ==========================================================================
$runner->suite('10 CRUD Operations across 5 Student Engineers');

// Initial clean test tenant for CRUD operations
$tenantRepo = new TenantRepository($db);
$tenantService = new TenantService($tenantRepo);

// --- Member 1: Fernando Lopes Duarte (CRUD 1: Tenants, CRUD 2: Users) ---
$tCnpj = new Cnpj('12.345.678/0001-95');
$tenant = $tenantService->createTenant(
    id: 'tenant-fernando',
    cnpj: $tCnpj,
    corporateName: 'Fernando Tech Solutions Ltda',
    tradingName: 'Fernando Tech',
    segment: 'tech',
    modules: ['payroll', 'time_tracking', 'benefits']
);

$runner->assertEquals('tenant-fernando', $tenant->getId(), 'CRUD 1 (Fernando): Tenant created successfully');
$runner->assertEquals('Fernando Tech Solutions Ltda', $tenant->getCorporateName(), 'CRUD 1 (Fernando): Corporate name set');
$runner->assertEquals('tech', $tenant->getSegment(), 'CRUD 1 (Fernando): Segment is tech');
$runner->assertTrue($tenant->hasModule('payroll'), 'CRUD 1 (Fernando): Module payroll licensed');

// Duplicate CNPJ check
$runner->assertThrows(function () use ($tenantService, $tCnpj) {
    $tenantService->createTenant('t-dup', $tCnpj, 'Dup Corp', 'Dup Trade');
}, ValidationException::class, 'CRUD 1 (Fernando): Duplicate CNPJ is strictly rejected');

// Update and retrieve tenant
$updatedTenant = $tenantService->updateTenantNames('tenant-fernando', 'Fernando Tech Advanced S.A.', 'Fernando Advanced');
$runner->assertEquals('Fernando Tech Advanced S.A.', $updatedTenant->getCorporateName(), 'CRUD 1 (Fernando): Tenant name updated');
$fetchedTenant = $tenantService->getTenantById('tenant-fernando');
$runner->assertEquals('Fernando Advanced', $fetchedTenant->getTradingName(), 'CRUD 1 (Fernando): Hydrated tenant matches update');

// CRUD 2: Users (Fernando)
$userRepo = new UserRepository($db);
$userService = new UserService($userRepo);

$user = $userService->createUser(
    id: 'user-fernando-admin',
    tenantId: 'tenant-fernando',
    username: 'flduarte',
    email: 'fernando@tech.com',
    plainPassword: 'SuperSecretPassword123!',
    role: UserRole::TENANT_ADMIN,
    mfaEnabled: true
);

$runner->assertEquals('user-fernando-admin', $user->getId(), 'CRUD 2 (Fernando): User created');
$runner->assertTrue($user->verifyPassword('SuperSecretPassword123!'), 'CRUD 2 (Fernando): Bcrypt password verified');
$runner->assertFalse($user->verifyPassword('WrongPassword'), 'CRUD 2 (Fernando): Wrong password rejected');

// Authenticate user
$authUser = $userService->authenticate('flduarte', 'SuperSecretPassword123!', 'tenant-fernando');
$runner->assertTrue($authUser !== null, 'CRUD 2 (Fernando): Authentication successful');
$runner->assertTrue($authUser->getLastLoginAt() !== null, 'CRUD 2 (Fernando): Last login timestamp recorded');

// Duplicate username check
$runner->assertThrows(function () use ($userService) {
    $userService->createUser('u2', 'tenant-fernando', 'flduarte', 'other@tech.com', 'Pass123!', UserRole::EMPLOYEE);
}, ValidationException::class, 'CRUD 2 (Fernando): Duplicate username in tenant rejected');

// --- Member 2: Andryus (CRUD 3: Employees, CRUD 4: Departments & Roles) ---
$deptRoleRepo = new DepartmentRoleRepository($db);
$deptRoleService = new DepartmentRoleService($deptRoleRepo);

// CRUD 4: Departments & Roles (Andryus)
$dept = $deptRoleService->createDepartment(
    id: 'dept-andryus-eng',
    tenantId: 'tenant-fernando',
    code: 'ENG-01',
    name: 'Software Engineering',
    costCenter: 'CC-2020'
);
$runner->assertEquals('dept-andryus-eng', $dept->getId(), 'CRUD 4 (Andryus): Department created');
$runner->assertEquals('ENG-01', $dept->getCode(), 'CRUD 4 (Andryus): Department code set');

$role = $deptRoleService->createRole(
    id: 'role-andryus-lead',
    tenantId: 'tenant-fernando',
    name: 'Tech Lead',
    description: 'Technical squad leadership',
    hierarchyLevel: 80,
    permissions: ['code_review', 'deploy_prod', 'approve_hours'],
    departmentId: 'dept-andryus-eng'
);
$runner->assertEquals('role-andryus-lead', $role->getId(), 'CRUD 4 (Andryus): Role created');
$runner->assertEquals(80, $role->getHierarchyLevel(), 'CRUD 4 (Andryus): Hierarchy level set');

// CRUD 3: Employees (Andryus)
$empRepo = new EmployeeRepository($db);
$empService = new EmployeeService($empRepo);

$empCpf = new Cpf('012.345.678-90');
$employee = $empService->hireEmployee(
    id: 'emp-andryus-1',
    tenantId: 'tenant-fernando',
    cpf: $empCpf,
    fullName: 'Andryus Dev',
    email: 'andryus@tech.com',
    phone: '11988887777',
    birthDate: '1995-05-15',
    admissionDate: '2023-01-10',
    departmentId: 'dept-andryus-eng',
    roleId: 'role-andryus-lead',
    baseSalary: Money::fromCents(1200000), // R$ 12.000,00
    employmentType: EmploymentType::CLT
);

$runner->assertEquals('emp-andryus-1', $employee->getId(), 'CRUD 3 (Andryus): Employee hired');
$runner->assertEquals(1200000, $employee->getBaseSalary()->getCents(), 'CRUD 3 (Andryus): Base salary stored in cents');
$runner->assertEquals(30, $employee->getVacationBalanceDays(), 'CRUD 3 (Andryus): Initial vacation balance is 30 days');
$runner->assertEquals(0, $employee->getBankHoursMinutes(), 'CRUD 3 (Andryus): Initial bank hours balance is 0 minutes');

// Employee bank hours update
$empService->recordBankHours('emp-andryus-1', 'tenant-fernando', 120); // +2 hours
$refreshedEmp = $empService->getEmployee('emp-andryus-1', 'tenant-fernando');
$runner->assertEquals(120, $refreshedEmp->getBankHoursMinutes(), 'CRUD 3 (Andryus): Bank hours credited to 120 minutes');

// Duplicate CPF check
$runner->assertThrows(function () use ($empService, $empCpf) {
    $empService->hireEmployee(
        'emp-dup', 'tenant-fernando', $empCpf, 'Dup Person', 'dup@tech.com',
        '1199999999', '1990-01-01', '2022-01-01', 'dept-andryus-eng', 'role-andryus-lead', 5000, 'CLT'
    );
}, ValidationException::class, 'CRUD 3 (Andryus): Duplicate CPF in tenant rejected');

// --- Member 3: Felipe (CRUD 5: Time Logs, CRUD 6: Time Adjustments) ---
$timeLogRepo = new TimeLogRepository($db);
$timeLogService = new TimeLogService($timeLogRepo);

// CRUD 5: TimeLog (Felipe)
$now = new DateTimeImmutable('2026-03-10T08:00:00-03:00');
$geo = new GeoLocation(-23.550520, -46.633308, 12.5);

$punch1 = $timeLogService->recordPunch(
    id: 'punch-felipe-1',
    tenantId: 'tenant-fernando',
    employeeId: 'emp-andryus-1',
    timestamp: $now,
    type: TimeLogType::ENTRY,
    location: $geo
);

$runner->assertEquals('punch-felipe-1', $punch1->id, 'CRUD 5 (Felipe): Entry punch recorded');
$runner->assertEquals(1, $punch1->nsr, 'CRUD 5 (Felipe): NSR sequenced to 1');
$runner->assertEquals(TimeLog::GENESIS_PREVIOUS_HASH, $punch1->previousHash, 'CRUD 5 (Felipe): First punch links to genesis');
$runner->assertEquals(64, strlen($punch1->signatureHash), 'CRUD 5 (Felipe): SHA-256 signature hash is 64 hex characters');

// Second chained punch
$punch2 = $timeLogService->recordPunch(
    id: 'punch-felipe-2',
    tenantId: 'tenant-fernando',
    employeeId: 'emp-andryus-1',
    timestamp: $now->modify('+4 hours'),
    type: TimeLogType::INTERVAL_START,
    location: $geo
);

$runner->assertEquals(2, $punch2->nsr, 'CRUD 5 (Felipe): Second punch NSR sequenced to 2');
$runner->assertEquals($punch1->signatureHash, $punch2->previousHash, 'CRUD 5 (Felipe): Punch 2 links to Punch 1 signature hash');

// Verify cryptographic chain
$runner->assertTrue($timeLogService->verifyTamperProofChain('tenant-fernando'), 'CRUD 5 (Felipe): Cryptographic tamper-proof chain verified');

// Verify immutability of time punch
$runner->assertThrows(function () use ($timeLogService) {
    $timeLogService->updatePunch();
}, InvalidOperationException::class, 'CRUD 5 (Felipe): Modification of punches strictly prohibited by Portaria 671');

$runner->assertThrows(function () use ($timeLogService) {
    $timeLogService->deletePunch();
}, InvalidOperationException::class, 'CRUD 5 (Felipe): Deletion of punches strictly prohibited by Portaria 671');

// CRUD 6: Time Adjustment Request (Felipe)
$adjRepo = new TimeAdjustmentRepository($db);
$adjService = new TimeAdjustmentService($adjRepo);

$adjReq = $adjService->requestAdjustment(
    id: 'adj-felipe-1',
    tenantId: 'tenant-fernando',
    employeeId: 'emp-andryus-1',
    requestedDate: new DateTimeImmutable('2026-03-09'),
    originalTime: null,
    requestedTime: new DateTimeImmutable('2026-03-09T08:05:00-03:00'),
    reason: 'Esquecimento de registro no retorno do almoço',
    attachmentPath: '/uploads/atestado_20260309.pdf'
);

$runner->assertEquals('adj-felipe-1', $adjReq->id, 'CRUD 6 (Felipe): Time adjustment requested');
$runner->assertEquals(AdjustmentStatus::PENDING, $adjReq->getStatus(), 'CRUD 6 (Felipe): Request is PENDING');

// Manager approval
$approvedAdj = $adjService->approveAdjustment(
    id: 'adj-felipe-1',
    tenantId: 'tenant-fernando',
    approverId: 'user-fernando-admin',
    comment: 'Ajuste deferido mediante comprovante'
);

$runner->assertEquals(AdjustmentStatus::APPROVED, $approvedAdj->getStatus(), 'CRUD 6 (Felipe): Request approved by supervisor');
$runner->assertEquals('user-fernando-admin', $approvedAdj->getApproverId(), 'CRUD 6 (Felipe): Approver ID recorded');

// --- Member 4: Valentin (CRUD 7: Vacations, CRUD 8: Benefits) ---
$vacRepo = new VacationRepository($db);
$vacService = new VacationService($vacRepo, $empRepo);

// CRUD 7: Vacations (Valentin)
$vacStart = (new DateTimeImmutable('now'))->modify('+35 days');
$vacEnd = $vacStart->modify('+14 days');

$vacReq = $vacService->requestVacation(
    id: 'vac-valentin-1',
    tenantId: 'tenant-fernando',
    employeeId: 'emp-andryus-1',
    startDate: $vacStart,
    endDate: $vacEnd,
    durationDays: 15,
    abonoPecuniario: false,
    advanceThirteenthSalary: true
);

$runner->assertEquals('vac-valentin-1', $vacReq->id, 'CRUD 7 (Valentin): Vacation request submitted');
$runner->assertEquals(15, $vacReq->durationDays, 'CRUD 7 (Valentin): Duration is 15 days');
$runner->assertEquals(VacationStatus::REQUESTED, $vacReq->getStatus(), 'CRUD 7 (Valentin): Status is REQUESTED');

// Approve vacation and check employee balance debit
$approvedVac = $vacService->approveVacation('vac-valentin-1', 'tenant-fernando', 'user-fernando-admin');
$runner->assertEquals(VacationStatus::APPROVED_BY_MANAGER, $approvedVac->getStatus(), 'CRUD 7 (Valentin): Vacation approved by manager');
$empAfterVac = $empService->getEmployee('emp-andryus-1', 'tenant-fernando');
$runner->assertEquals(15, $empAfterVac->getVacationBalanceDays(), 'CRUD 7 (Valentin): Employee vacation balance debited from 30 to 15 days');

// CRUD 8: Benefits (Valentin)
$benefitRepo = new BenefitRepository($db);
$benefitService = new BenefitService($benefitRepo);

$mealBenefit = $benefitService->createBenefit(
    id: 'ben-valentin-vr',
    tenantId: 'tenant-fernando',
    type: BenefitType::MEAL_VOUCHER,
    name: 'Vale Refeição Sodexo Premium',
    provider: 'Pluxee / Sodexo',
    value: Money::fromCents(80000), // R$ 800,00
    copayPercentage: 15.0,
    isDeductible: true
);

$runner->assertEquals('ben-valentin-vr', $mealBenefit->getId(), 'CRUD 8 (Valentin): Benefit package created');
$runner->assertEquals(80000, $mealBenefit->getValue()->getCents(), 'CRUD 8 (Valentin): Benefit value is R$ 800,00');
$runner->assertEquals(15.0, $mealBenefit->getEmployeeCostSharePercentage(), 'CRUD 8 (Valentin): Copay percentage set to 15%');

// Retrieve benefits catalog
$tenantBenefits = $benefitService->listBenefits('tenant-fernando');
$runner->assertEquals(1, count($tenantBenefits), 'CRUD 8 (Valentin): Benefit listed in tenant catalog');

// --- Member 5: Nicholas (CRUD 9: Equipment & ASO, CRUD 10: Insurance Policies) ---
$eqRepo = new EquipmentASORepository($db);
$eqService = new EquipmentASOService($eqRepo);

// CRUD 9: Equipment & ASO (Nicholas)
$deliveredPpe = $eqService->deliverEquipment(
    id: 'ppe-nicholas-1',
    tenantId: 'tenant-fernando',
    employeeId: 'emp-andryus-1',
    equipmentName: 'Capacete de Segurança MSA com jugular',
    caNumber: '12345',
    caExpirationDate: (new DateTimeImmutable('now'))->modify('+365 days'),
    deliveryDate: new DateTimeImmutable('now')
);

$runner->assertEquals('ppe-nicholas-1', $deliveredPpe->id, 'CRUD 9 (Nicholas): PPE delivered to employee');
$runner->assertEquals('12345', $deliveredPpe->caNumber, 'CRUD 9 (Nicholas): CA number recorded');
$runner->assertFalse($deliveredPpe->isCaExpired(), 'CRUD 9 (Nicholas): CA is valid (not expired)');

// Medical ASO exam
$asoExam = $eqService->recordMedicalExam(
    id: 'aso-nicholas-1',
    tenantId: 'tenant-fernando',
    employeeId: 'emp-andryus-1',
    examType: ExamType::PERIODIC,
    examDate: new DateTimeImmutable('now'),
    expirationDate: (new DateTimeImmutable('now'))->modify('+180 days'),
    physicianName: 'Dr. Roberto Ocupacional',
    physicianCrm: '123456-SP',
    isFit: true
);

$runner->assertEquals('aso-nicholas-1', $asoExam->id, 'CRUD 9 (Nicholas): ASO medical exam recorded');
$runner->assertTrue($asoExam->isFit, 'CRUD 9 (Nicholas): Collaborator is clinically FIT');
$runner->assertFalse($asoExam->isExamExpired(), 'CRUD 9 (Nicholas): ASO is within valid period');

// CRUD 10: Insurance Policy FinCorp (Nicholas)
$policyRepo = new InsurancePolicyRepository($db);
$policyService = new InsurancePolicyService($policyRepo);

$policy = $policyService->issuePolicy(
    id: 'pol-nicholas-1',
    tenantId: 'tenant-fernando',
    policyNumber: 'FC-2026-998877',
    brokerCode: 'FINCORP-BRK-01',
    insurerName: 'Porto Seguro S.A.',
    employeeId: 'emp-andryus-1',
    insuredCapital: Money::fromCents(50000000), // R$ 500.000,00
    monthlyPremium: Money::fromCents(12500),   // R$ 125,00
    status: PolicyStatus::ACTIVE,
    startDate: new DateTimeImmutable('2026-01-01'),
    endDate: new DateTimeImmutable('2026-12-31'),
    coverageDetails: ['morte_acidental' => true, 'invalidez_permanente' => true]
);

$runner->assertEquals('pol-nicholas-1', $policy->getId(), 'CRUD 10 (Nicholas): FinCorp insurance policy issued');
$runner->assertEquals(50000000, $policy->getInsuredCapital()->getCents(), 'CRUD 10 (Nicholas): Insured capital R$ 500.000,00');
$runner->assertEquals(PolicyStatus::ACTIVE, $policy->getStatus(), 'CRUD 10 (Nicholas): Policy is ACTIVE');

// Policy renewal
$renewedPolicy = $policyService->renewPolicy(
    id: 'pol-nicholas-1',
    tenantId: 'tenant-fernando',
    newEndDate: new DateTimeImmutable('2027-12-31'),
    newMonthlyPremium: Money::fromCents(14000)
);

$runner->assertEquals('2027-12-31', $renewedPolicy->getEndDate()->format('Y-m-d'), 'CRUD 10 (Nicholas): Policy renewed until 2027');
$runner->assertEquals(14000, $renewedPolicy->getMonthlyPremium()->getCents(), 'CRUD 10 (Nicholas): Premium updated to R$ 140,00');

// ==========================================================================
// Suite 3: LPS Variability Engine & FeatureToggleManager
// ==========================================================================
$runner->suite('LPS Variability Engine & FeatureToggleManager');

// TenantSegment Enum
$runner->assertEquals('tech', TenantSegment::TECH->value, 'TenantSegment TECH value is tech');
$runner->assertEquals('industria', TenantSegment::INDUSTRIA->value, 'TenantSegment INDUSTRIA value is industria');
$runner->assertEquals('financeiro', TenantSegment::FINANCEIRO->value, 'TenantSegment FINANCEIRO value is financeiro');

$runner->assertEquals(TenantSegment::TECH, TenantSegment::fromValue('tecnologia'), 'TenantSegment parses alias tecnologia');
$runner->assertEquals(TenantSegment::INDUSTRIA, TenantSegment::fromValue('manufatura'), 'TenantSegment parses alias manufatura');
$runner->assertEquals(TenantSegment::FINANCEIRO, TenantSegment::fromValue('banco'), 'TenantSegment parses alias banco');

// FeatureToggleManager Singleton & Priority Hierarchy
FeatureToggleManager::resetInstance();
$toggleManager = FeatureToggleManager::getInstance();
$runner->assertTrue($toggleManager instanceof FeatureToggleManager, 'FeatureToggleManager singleton instance obtained');

// Segment defaults
$runner->assertTrue($toggleManager->isFeatureEnabled('bank_of_hours', segment: TenantSegment::TECH), 'Tech default: bank_of_hours is ON');
$runner->assertFalse($toggleManager->isFeatureEnabled('overtime_payout', segment: TenantSegment::TECH), 'Tech default: overtime_payout is OFF');
$runner->assertFalse($toggleManager->isFeatureEnabled('bank_of_hours', segment: TenantSegment::INDUSTRIA), 'Indústria default: bank_of_hours is OFF');
$runner->assertTrue($toggleManager->isFeatureEnabled('overtime_payout', segment: TenantSegment::INDUSTRIA), 'Indústria default: overtime_payout is ON');
$runner->assertTrue($toggleManager->isFeatureEnabled('biometric_punch_mandatory', segment: TenantSegment::FINANCEIRO), 'Financeiro default: biometric_punch_mandatory is ON');

// Tenant Override precedence: Tenant Override > Segment Default
$toggleManager->setTenantFeature('tenant-override-test', 'bank_of_hours', false);
$runner->assertFalse(
    $toggleManager->isFeatureEnabled('bank_of_hours', segment: TenantSegment::TECH, tenantId: 'tenant-override-test'),
    'Tenant override turns OFF bank_of_hours despite Tech segment default being ON'
);

// Clear override restores segment default
$toggleManager->removeTenantFeature('tenant-override-test', 'bank_of_hours');
$runner->assertTrue(
    $toggleManager->isFeatureEnabled('bank_of_hours', segment: TenantSegment::TECH, tenantId: 'tenant-override-test'),
    'Removing override restores Tech segment default of bank_of_hours = ON'
);

// LPS Rule Engine Behavior
$lpsEngine = new LpsVariabilityEngine($toggleManager);

// Tech Profile Rules & Strategies
$runner->assertTrue($lpsEngine->isBankOfHoursActive(TenantSegment::TECH), 'LPS: Tech has Bank of Hours active');
$runner->assertFalse($lpsEngine->isOvertimePayoutActive(TenantSegment::TECH), 'LPS: Tech has Overtime Payout inactive');
$runner->assertFalse($lpsEngine->isPpeMandatory(TenantSegment::TECH), 'LPS: Tech has Risk PPE waived');
$runner->assertTrue($lpsEngine->isFlexibleBenefitsActive(TenantSegment::TECH), 'LPS: Tech has Flexible Benefits active');
$runner->assertTrue($lpsEngine->isDAndOInsuranceActive(TenantSegment::TECH), 'LPS: Tech has D&O Insurance active');

$techOtStrat = $lpsEngine->resolveOvertimeStrategy(TenantSegment::TECH);
$runner->assertTrue($techOtStrat instanceof BankHoursStrategy, 'LPS: Tech overtime resolves to BankHoursStrategy');

$techPerfStrat = $lpsEngine->resolvePerformanceStrategy(TenantSegment::TECH);
$runner->assertTrue($techPerfStrat instanceof OkrStrategy, 'LPS: Tech performance resolves to OkrStrategy');

// Indústria Profile Rules & Strategies
$runner->assertTrue($lpsEngine->isOvertimePayoutActive(TenantSegment::INDUSTRIA), 'LPS: Indústria has Overtime Payout active');
$runner->assertFalse($lpsEngine->isBankOfHoursActive(TenantSegment::INDUSTRIA), 'LPS: Indústria has Bank of Hours inactive');
$runner->assertTrue($lpsEngine->isPpeMandatory(TenantSegment::INDUSTRIA), 'LPS: Indústria strictly mandates PPE & ASO');
$runner->assertTrue($lpsEngine->isCharteredTransportActive(TenantSegment::INDUSTRIA), 'LPS: Indústria has Chartered Transport active');

$indOtStratNormal = $lpsEngine->resolveOvertimeStrategy(TenantSegment::INDUSTRIA, isSundayOrHoliday: false);
$runner->assertTrue($indOtStratNormal instanceof Standard50Strategy, 'LPS: Indústria standard day overtime resolves to Standard50Strategy');

$indOtStratSunday = $lpsEngine->resolveOvertimeStrategy(TenantSegment::INDUSTRIA, isSundayOrHoliday: true);
$runner->assertTrue($indOtStratSunday instanceof Sunday100Strategy, 'LPS: Indústria Sunday overtime resolves to Sunday100Strategy');

$indPerfStrat = $lpsEngine->resolvePerformanceStrategy(TenantSegment::INDUSTRIA);
$runner->assertTrue($indPerfStrat instanceof Evaluation360Strategy, 'LPS: Indústria performance resolves to Evaluation360Strategy');

// Indústria Strict Occupational Health & Safety (NR-6 / NR-7) Blocking Invariants
$industrialEmp = new Employee(
    id: 'emp-ind-1',
    tenantId: 'tenant-ind',
    cpf: new Cpf('111.222.333-96'),
    fullName: 'Operador Fabril',
    email: 'operador@fabril.com',
    phone: '11900000000',
    birthDate: '1988-03-20',
    admissionDate: '2021-01-01',
    departmentId: 'd-fabril',
    roleId: 'r-operador',
    baseSalary: 3500,
    employmentType: 'CLT'
);

// 1. Missing ASO blocks work
$missingCheck = $lpsEngine->validateWorkEligibility($industrialEmp, null, TenantSegment::INDUSTRIA);
$runner->assertFalse($missingCheck['allowed'], 'LPS Indústria: Missing ASO strictly BLOCKS work eligibility');
$runner->assertEquals('ASO_MISSING', $missingCheck['code'], 'LPS Indústria: Missing ASO returns ASO_MISSING code');

// 2. Unfit ASO blocks work
$unfitAso = new EquipmentASO(
    id: 'aso-unfit',
    tenantId: 'tenant-ind',
    employeeId: 'emp-ind-1',
    equipmentName: 'ASO Clínico',
    caNumber: '111',
    isFit: false
);
$unfitCheck = $lpsEngine->validateWorkEligibility($industrialEmp, $unfitAso, TenantSegment::INDUSTRIA);
$runner->assertFalse($unfitCheck['allowed'], 'LPS Indústria: Unfit (Inapto) collaborator strictly BLOCKED from work');
$runner->assertEquals('ASO_UNFIT', $unfitCheck['code'], 'LPS Indústria: Unfit ASO returns ASO_UNFIT code');

// 3. Expired ASO blocks work
$expiredAso = new EquipmentASO(
    id: 'aso-exp',
    tenantId: 'tenant-ind',
    employeeId: 'emp-ind-1',
    equipmentName: 'ASO Clínico',
    caNumber: '111',
    expirationDate: (new DateTimeImmutable('now'))->modify('-10 days'),
    isFit: true
);
$expiredCheck = $lpsEngine->validateWorkEligibility($industrialEmp, $expiredAso, TenantSegment::INDUSTRIA);
$runner->assertFalse($expiredCheck['allowed'], 'LPS Indústria: Expired ASO certificate strictly BLOCKS work');
$runner->assertEquals('ASO_EXPIRED', $expiredCheck['code'], 'LPS Indústria: Expired ASO returns ASO_EXPIRED code');

// 4. Expired CA blocks work
$expiredCaAso = new EquipmentASO(
    id: 'aso-ca-exp',
    tenantId: 'tenant-ind',
    employeeId: 'emp-ind-1',
    equipmentName: 'Máscara de Solda',
    caNumber: '999',
    caExpirationDate: (new DateTimeImmutable('now'))->modify('-5 days'),
    expirationDate: (new DateTimeImmutable('now'))->modify('+100 days'),
    isFit: true
);
$caCheck = $lpsEngine->validateWorkEligibility($industrialEmp, $expiredCaAso, TenantSegment::INDUSTRIA);
$runner->assertFalse($caCheck['allowed'], 'LPS Indústria: Expired PPE CA certification strictly BLOCKS work');
$runner->assertEquals('PPE_CA_EXPIRED', $caCheck['code'], 'LPS Indústria: Expired CA returns PPE_CA_EXPIRED code');

// 5. Compliant ASO clears worker
$compliantAso = new EquipmentASO(
    id: 'aso-valid',
    tenantId: 'tenant-ind',
    employeeId: 'emp-ind-1',
    equipmentName: 'Capacete e ASO Válidos',
    caNumber: '88888',
    caExpirationDate: (new DateTimeImmutable('now'))->modify('+200 days'),
    expirationDate: (new DateTimeImmutable('now'))->modify('+200 days'),
    isFit: true
);
$validCheck = $lpsEngine->validateWorkEligibility($industrialEmp, $compliantAso, TenantSegment::INDUSTRIA);
$runner->assertTrue($validCheck['allowed'], 'LPS Indústria: Compliant ASO & valid CA CLEARS collaborator for work');
$runner->assertEquals('COMPLIANT', $validCheck['code'], 'LPS Indústria: Returns COMPLIANT code');

// In Tech, ASO is waived
$techCheck = $lpsEngine->validateWorkEligibility($industrialEmp, null, TenantSegment::TECH);
$runner->assertTrue($techCheck['allowed'], 'LPS Tech: ASO is waived for office tech collaborators');
$runner->assertEquals('PPE_WAIVED', $techCheck['code'], 'LPS Tech: Returns PPE_WAIVED code');

// Financeiro Profile Rules & Biometric Punch Verification
$runner->assertTrue($lpsEngine->isBiometricPunchMandatory(TenantSegment::FINANCEIRO), 'LPS: Financeiro mandates biometric punches');
$runner->assertTrue($lpsEngine->isExecutiveHealthPlanActive(TenantSegment::FINANCEIRO), 'LPS: Financeiro has Executive Health Plan active');
$runner->assertTrue($lpsEngine->isAggressiveBonusActive(TenantSegment::FINANCEIRO), 'LPS: Financeiro has Aggressive Bonus active');
$runner->assertTrue($lpsEngine->isStrictLgpdAuditActive(TenantSegment::FINANCEIRO), 'LPS: Financeiro has Strict LGPD Audit active');
$runner->assertTrue($lpsEngine->isFinCorpLifePolicyActive(TenantSegment::FINANCEIRO), 'LPS: Financeiro has FinCorp Life Policy active');

$finPerfStrat = $lpsEngine->resolvePerformanceStrategy(TenantSegment::FINANCEIRO);
$runner->assertTrue($finPerfStrat instanceof KpiStrategy, 'LPS: Financeiro performance resolves to KpiStrategy');

// Biometric Punch Evaluation
$dummyPunch = new TimeLog(
    id: 'p-bio-test',
    tenantId: 'tenant-fin',
    employeeId: 'emp-fin-1',
    timestamp: new DateTimeImmutable(),
    type: TimeLogType::ENTRY,
    location: new GeoLocation(-23.55, -46.63),
    nsr: 10
);

$rejectedPunch = $lpsEngine->validateTimePunch($dummyPunch, TenantSegment::FINANCEIRO, biometricVerified: false);
$runner->assertFalse($rejectedPunch['allowed'], 'LPS Financeiro: Punch without biometric verification is REJECTED');

$acceptedPunch = $lpsEngine->validateTimePunch($dummyPunch, TenantSegment::FINANCEIRO, biometricVerified: true);
$runner->assertTrue($acceptedPunch['allowed'], 'LPS Financeiro: Punch with biometric verification is ACCEPTED');

// ==========================================================================
// Suite 4: End-to-End Multi-Tenant Pipeline
// ==========================================================================
$runner->suite('End-to-End Multi-Tenant Pipeline');

// Create 3 distinct organizations with different segments
$tTech = $tenantService->createTenant(
    't-e2e-tech', '33.444.555/0001-81', 'TechCorp Global Ltda', 'TechCorp', 'tech'
);
$tInd = $tenantService->createTenant(
    't-e2e-ind', '44.555.666/0001-81', 'Metalúrgica Fabril S.A.', 'Fabril S.A.', 'industria'
);
$tFin = $tenantService->createTenant(
    't-e2e-fin', '55.666.777/0001-81', 'Banco Alpha Investimentos S.A.', 'Banco Alpha', 'financeiro'
);

$runner->assertEquals('tech', $tTech->getSegment(), 'Pipeline: TechCorp created with tech segment');
$runner->assertEquals('industria', $tInd->getSegment(), 'Pipeline: Fabril S.A. created with industria segment');
$runner->assertEquals('financeiro', $tFin->getSegment(), 'Pipeline: Banco Alpha created with financeiro segment');

// Seed employees for each tenant
$empTech = $empService->hireEmployee(
    'emp-e2e-tech', 't-e2e-tech', '222.333.444-05', 'Dev Tech', 'dev@techcorp.com',
    '11911111111', '1992-04-10', '2022-01-01', 'd-tech', 'r-tech', 10000, 'CLT'
);

$empInd = $empService->hireEmployee(
    'emp-e2e-ind', 't-e2e-ind', '333.444.555-08', 'Operador Ind', 'op@fabril.com',
    '11922222222', '1985-08-22', '2019-06-01', 'd-ind', 'r-ind', 4000, 'CLT'
);

$empFin = $empService->hireEmployee(
    'emp-e2e-fin', 't-e2e-fin', '444.555.666-19', 'Analista Fin', 'ana@bancoalpha.com',
    '11933333333', '1990-11-30', '2020-03-15', 'd-fin', 'r-fin', 15000, 'CLT'
);

// Verify Strict Multi-Tenant Query Scoping
$techEmployees = $empService->listEmployees('t-e2e-tech');
$runner->assertEquals(1, count($techEmployees), 'Pipeline: TechCorp has exactly 1 employee');
$runner->assertEquals('emp-e2e-tech', $techEmployees[0]->getId(), 'Pipeline: TechCorp employee retrieved');

$indEmployees = $empService->listEmployees('t-e2e-ind');
$runner->assertEquals(1, count($indEmployees), 'Pipeline: Fabril S.A. has exactly 1 employee');
$runner->assertEquals('emp-e2e-ind', $indEmployees[0]->getId(), 'Pipeline: Fabril S.A. employee retrieved');

// Cross-tenant data isolation test: ensure t-e2e-tech cannot query t-e2e-ind employee
$leakTest = $empService->getEmployee('emp-e2e-ind', 't-e2e-tech');
$runner->assertTrue($leakTest === null, 'Pipeline: Cross-tenant data leakage blocked (getEmployee returns null across boundary)');

// 1. Tech Workflow: Overtime is credited to Bank of Hours
$strategyTech = $lpsEngine->resolveOvertimeStrategy($tTech);
$runner->assertTrue($strategyTech instanceof BankHoursStrategy, 'Pipeline Tech: Strategy is BankHoursStrategy');
$bankMinutes = $strategyTech->calculateBankMinutes(2.0); // 2 hours overtime
$empService->recordBankHours('emp-e2e-tech', 't-e2e-tech', $bankMinutes);
$updatedEmpTech = $empService->getEmployee('emp-e2e-tech', 't-e2e-tech');
$runner->assertEquals(120, $updatedEmpTech->getBankHoursMinutes(), 'Pipeline Tech: 2h * 1.0 factor = 120 minutes banked');

// 2. Indústria Workflow: Work blocked if ASO missing; unblocked when recorded; overtime paid in cash
$indEligibility1 = $lpsEngine->validateWorkEligibility($empInd, null, $tInd);
$runner->assertFalse($indEligibility1['allowed'], 'Pipeline Indústria: Collaborator blocked from shift due to missing ASO');

// HR delivers compliant PPE & records ASO
$indAso = $eqService->recordMedicalExam(
    'aso-e2e-ind', 't-e2e-ind', 'emp-e2e-ind', ExamType::PERIODIC,
    new DateTimeImmutable(), (new DateTimeImmutable())->modify('+365 days'),
    'Dr. Silva', '445566-SP', true, 'EPI Completo', '12345'
);
$indEligibility2 = $lpsEngine->validateWorkEligibility($empInd, $indAso, $tInd);
$runner->assertTrue($indEligibility2['allowed'], 'Pipeline Indústria: Collaborator cleared for work after compliant ASO recorded');

$strategyInd = $lpsEngine->resolveOvertimeStrategy($tInd, isSundayOrHoliday: false);
$runner->assertTrue($strategyInd instanceof Standard50Strategy, 'Pipeline Indústria: Overtime paid out in cash (Standard50Strategy)');
$otPayout = $strategyInd->calculateOvertime(hourlyRate: 20.0, overtimeHours: 3.0);
$runner->assertCloseTo(90.0, $otPayout, 0.01, 'Pipeline Indústria: 20.00/h * 1.5 * 3h = R$ 90.00 cash payout');

// 3. Financeiro Workflow: Strict Biometric punch requirement
$punchFin = new TimeLog(
    'p-e2e-fin', 't-e2e-fin', 'emp-e2e-fin', new DateTimeImmutable(),
    TimeLogType::ENTRY, new GeoLocation(-23.55, -46.63), 1
);
$bioDenied = $lpsEngine->validateTimePunch($punchFin, $tFin, biometricVerified: false);
$runner->assertFalse($bioDenied['allowed'], 'Pipeline Financeiro: Punch without biometric verification is rejected');

$bioAllowed = $lpsEngine->validateTimePunch($punchFin, $tFin, biometricVerified: true);
$runner->assertTrue($bioAllowed['allowed'], 'Pipeline Financeiro: Punch with biometric verification is accepted');

// ==========================================================================
// Suite 5: Zero-Regression Verification
// ==========================================================================
$runner->suite('Zero-Regression Verification (Milestones 1, 2, 3)');

$testsDir = __DIR__;
$phpBinary = PHP_BINARY;

// Sub-process verification: Milestone 1
$m1Cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($testsDir . '/m1_verify.php');
$outputM1 = [];
$codeM1 = 0;
exec($m1Cmd, $outputM1, $codeM1);
$runner->assertEquals(0, $codeM1, 'Milestone 1 test suite (m1_verify.php) executed with 0 failures');

// Sub-process verification: Milestone 2
$m2Cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($testsDir . '/m2_verify.php');
$outputM2 = [];
$codeM2 = 0;
exec($m2Cmd, $outputM2, $codeM2);
$runner->assertEquals(0, $codeM2, 'Milestone 2 test suite (m2_verify.php) executed with 0 failures');

// Sub-process verification: Milestone 3
$m3Cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($testsDir . '/m3_verify.php');
$outputM3 = [];
$codeM3 = 0;
exec($m3Cmd, $outputM3, $codeM3);
$runner->assertEquals(0, $codeM3, 'Milestone 3 test suite (m3_verify.php) executed with 0 failures');

// Final summary and exit code
$exitCode = $runner->printSummary();
exit($exitCode);
