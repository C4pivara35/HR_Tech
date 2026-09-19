<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 4 Adversarial Stress & Attack Challenge
 *
 * Designed to stress-test Milestone 4 deliverables:
 *  1. Cryptographic Chain Tamper Detection (Portaria 671)
 *  2. Cross-Tenant Data Isolation & BOLA/IDOR Security
 *  3. SQL Injection Resistance on Dynamic Query Filters
 *  4. LPS Variability Engine Edge Cases & Precedence Hierarchy
 *  5. Business Service Boundary Invariants & Exceptions
 *  6. Student Attribution & Integrity Audit
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

final class AdversarialChallengeRunner
{
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    /** @var string[] */
    private array $failures = [];
    private string $currentSection = '';

    public function section(string $name): void
    {
        $this->currentSection = $name;
        echo "\n\033[1;35m⚡ Challenge Section: {$name}\033[0m\n";
    }

    public function assert(bool $condition, string $description): void
    {
        $this->totalTests++;
        if ($condition) {
            $this->passedTests++;
            echo "  \033[32m✔ PASS:\033[0m {$description}\n";
        } else {
            $this->failedTests++;
            echo "  \033[31m✘ FAIL:\033[0m {$description}\n";
            $this->failures[] = "[{$this->currentSection}] {$description}";
        }
    }

    public function summary(): int
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "\033[1;37mADVERSARIAL CHALLENGE EXECUTION SUMMARY\033[0m\n";
        echo str_repeat('=', 70) . "\n";
        echo "Total Stress Tests : {$this->totalTests}\n";
        echo "Passed             : \033[32m{$this->passedTests}\033[0m\n";
        echo "Failed             : " . ($this->failedTests > 0 ? "\033[31m{$this->failedTests}\033[0m" : "0") . "\n";

        if ($this->failedTests > 0) {
            echo "\n\033[1;31mADVERSARIAL FAILURES SURFACED:\033[0m\n";
            foreach ($this->failures as $f) {
                echo "  • {$f}\n";
            }
            return 1;
        }

        echo "\n\033[1;32mALL ADVERSARIAL STRESS CHALLENGES PASSED (100%)\033[0m\n\n";
        return 0;
    }
}

$c = new AdversarialChallengeRunner();

// Setup fresh in-memory database
DatabaseManager::resetInstance();
$db = DatabaseManager::getInstance(':memory:');
$db->migrate();
$pdo = $db->getConnection();

// ==========================================================================
// Challenge 1: Cryptographic Chain Tampering Detection (Portaria 671)
// ==========================================================================
$c->section('Portaria 671 SHA-256 Tamper Resistance');

$tRepo = new TenantRepository($db);
$tServ = new TenantService($tRepo);
$tServ->createTenant('t-crypto', '11.222.333/0001-81', 'Crypto Test Corp', 'CryptoCorp', 'tech');

$eRepo = new EmployeeRepository($db);
$eServ = new EmployeeService($eRepo);
$eServ->hireEmployee(
    'emp-1', 't-crypto', '111.222.333-96', 'Crypto Worker', 'w@crypto.com',
    '11900000000', '1990-01-01', '2020-01-01', 'd-crypto', 'r-crypto', 5000, 'CLT'
);

$timeRepo = new TimeLogRepository($db);
$timeServ = new TimeLogService($timeRepo);

$now = new DateTimeImmutable('2026-04-01T08:00:00+00:00');
$loc = new GeoLocation(-23.55, -46.63);

// Record 4 chained punches
$p1 = $timeServ->recordPunch('p1', 't-crypto', 'emp-1', $now, TimeLogType::ENTRY, $loc);
$p2 = $timeServ->recordPunch('p2', 't-crypto', 'emp-1', $now->modify('+4 hours'), TimeLogType::INTERVAL_START, $loc);
$p3 = $timeServ->recordPunch('p3', 't-crypto', 'emp-1', $now->modify('+5 hours'), TimeLogType::INTERVAL_END, $loc);
$p4 = $timeServ->recordPunch('p4', 't-crypto', 'emp-1', $now->modify('+9 hours'), TimeLogType::EXIT, $loc);

$c->assert($timeServ->verifyTamperProofChain('t-crypto', 'emp-1'), 'Valid 4-punch chain initially verifies as true');

// Attack 1A: Alter timestamp in punch #2 directly in database
$pdo->exec("UPDATE time_logs SET timestamp = '2026-04-01T12:05:00+00:00' WHERE id = 'p2'");
$tamperDetected1A = false;
try {
    $tamperDetected1A = !$timeServ->verifyTamperProofChain('t-crypto', 'emp-1');
} catch (ValidationException) {
    $tamperDetected1A = true;
}
$c->assert($tamperDetected1A, 'Tampering with punch timestamp is detected (returns false or throws ValidationException)');

// Restore original timestamp
$pdo->exec("UPDATE time_logs SET timestamp = '2026-04-01T12:00:00+00:00' WHERE id = 'p2'");
$c->assert($timeServ->verifyTamperProofChain('t-crypto', 'emp-1'), 'Restoring original timestamp restores verification');

// Attack 1B: Alter previous_hash of punch #3
$pdo->exec("UPDATE time_logs SET previous_hash = 'badhashdeadbeef1234567890' WHERE id = 'p3'");
$tamperDetected1B = false;
try {
    $tamperDetected1B = !$timeServ->verifyTamperProofChain('t-crypto', 'emp-1');
} catch (ValidationException) {
    $tamperDetected1B = true;
}
$c->assert($tamperDetected1B, 'Tampering with previous_hash is detected (returns false or throws ValidationException)');

// Restore previous_hash
$pdo->exec("UPDATE time_logs SET previous_hash = '{$p2->signatureHash}' WHERE id = 'p3'");
$c->assert($timeServ->verifyTamperProofChain('t-crypto', 'emp-1'), 'Restoring valid previous_hash restores verification');

// Attack 1C: Delete punch #2 from the middle of the chain
$pdo->exec("DELETE FROM time_logs WHERE id = 'p2'");
$tamperDetected1C = false;
try {
    $tamperDetected1C = !$timeServ->verifyTamperProofChain('t-crypto', 'emp-1');
} catch (ValidationException) {
    $tamperDetected1C = true;
}
$c->assert($tamperDetected1C, 'Deleting intermediate punch #2 from chain breaks sequential integrity');

// Clean up
$pdo->exec("DELETE FROM time_logs WHERE tenant_id = 't-crypto'");

// ==========================================================================
// Challenge 2: Cross-Tenant Data Isolation & BOLA/IDOR Defense
// ==========================================================================
$c->section('Cross-Tenant Data Isolation & BOLA/IDOR Security');

$tServ->createTenant('t-alpha', '01.000.000/0001-54', 'Alpha Ltda', 'Alpha', 'tech');
$tServ->createTenant('t-beta', '01.100.000/0001-26', 'Beta Ltda', 'Beta', 'industria');

$uRepo = new UserRepository($db);
$uServ = new UserService($uRepo);
$eRepo = new EmployeeRepository($db);
$eServ = new EmployeeService($eRepo);
$vRepo = new VacationRepository($db);
$vServ = new VacationService($vRepo, $eRepo);
$adjRepo = new TimeAdjustmentRepository($db);
$adjServ = new TimeAdjustmentService($adjRepo);

// Seed Alpha Employee & User
$empAlpha = $eServ->hireEmployee(
    'emp-alpha-1', 't-alpha', '111.222.333-96', 'Alice Alpha', 'alice@alpha.com',
    '11911111111', '1990-01-01', '2022-01-01', 'dept-a', 'role-a', 8000, 'CLT'
);
$userAlpha = $uServ->createUser(
    'user-alpha-1', 't-alpha', 'alice_admin', 'alice@alpha.com', 'PassAlpha123!', UserRole::TENANT_ADMIN
);

// Attack 2A: Beta attempts to read Alpha Employee
$leakedEmp = $eServ->getEmployee('emp-alpha-1', 't-beta');
$c->assert($leakedEmp === null, 'Tenant Beta cannot read Tenant Alpha employee');

// Attack 2B: Beta attempts to delete Alpha Employee
$eServ->deleteEmployee('emp-alpha-1', 't-beta');
$empStillExists = $eServ->getEmployee('emp-alpha-1', 't-alpha');
$c->assert($empStillExists !== null, 'Alpha employee remains intact after unauthorized Beta delete attempt');

// Attack 2C: Beta attempts to change Alpha User password
$pwdChanged = false;
try {
    $uServ->changePassword('user-alpha-1', 't-beta', 'PassAlpha123!', 'HackedPass123!');
    $pwdChanged = true;
} catch (\Throwable $e) {
    $pwdChanged = false;
}
$c->assert(!$pwdChanged, 'Tenant Beta cannot alter Tenant Alpha user password');

// Attack 2D: Alpha employee requests vacation; Beta attempts to approve it
$vacAlpha = $vServ->requestVacation(
    'vac-alpha-1', 't-alpha', 'emp-alpha-1',
    (new DateTimeImmutable())->modify('+30 days'),
    (new DateTimeImmutable())->modify('+44 days'),
    15
);
$betaApprovedVac = false;
try {
    $vServ->approveVacation('vac-alpha-1', 't-beta', 'user-beta-rogue');
    $betaApprovedVac = true;
} catch (\Throwable $e) {
    $betaApprovedVac = false;
}
$c->assert(!$betaApprovedVac, 'Tenant Beta cannot approve Tenant Alpha vacation request');

// Attack 2E: Alpha employee requests time adjustment; Beta attempts to approve it
$adjAlpha = $adjServ->requestAdjustment(
    'adj-alpha-1', 't-alpha', 'emp-alpha-1',
    new DateTimeImmutable('2026-03-01'), null, new DateTimeImmutable('2026-03-01T08:00:00'), 'Ajuste'
);
$betaApprovedAdj = false;
try {
    $adjServ->approveAdjustment('adj-alpha-1', 't-beta', 'user-beta-rogue');
    $betaApprovedAdj = true;
} catch (\Throwable $e) {
    $betaApprovedAdj = false;
}
$c->assert(!$betaApprovedAdj, 'Tenant Beta cannot approve Tenant Alpha time adjustment request');

// Attack 2F: Cross-Tenant Same CPF Re-use (CPF should be allowed in Beta if it exists in Alpha)
$empBetaSameCpf = null;
try {
    $empBetaSameCpf = $eServ->hireEmployee(
        'emp-beta-1', 't-beta', '111.222.333-96', 'Alice in Beta Corp', 'alice@beta.com',
        '11922222222', '1990-01-01', '2023-01-01', 'dept-b', 'role-b', 9000, 'CLT'
    );
} catch (\Throwable $e) {
    $empBetaSameCpf = null;
}
$c->assert($empBetaSameCpf !== null, 'Same CPF is legally allowed across different independent corporate tenants');

// ==========================================================================
// Challenge 3: SQL Injection Resistance on Dynamic Query Filters
// ==========================================================================
$c->section('SQL Injection Resistance on Dynamic Query Filters');

$sqliPayload = "' OR '1'='1' --";

// 3A: EmployeeRepository filter SQL injection
$sqliResults = $eRepo->findAllByTenant('t-alpha', ['department_id' => $sqliPayload]);
$c->assert(count($sqliResults) === 0, 'SQL injection payload in department_id filter safely yields 0 records');

// 3B: TimeAdjustmentRepository status filter SQL injection
$sqliAdj = $adjRepo->findAllByTenant('t-alpha', ['status' => $sqliPayload]);
$c->assert(count($sqliAdj) === 0, 'SQL injection payload in status filter safely yields 0 records');

// 3C: BenefitRepository type filter SQL injection
$bRepo = new BenefitRepository($db);
$sqliBen = $bRepo->findByType($sqliPayload, 't-alpha');
$c->assert(count($sqliBen) === 0, 'SQL injection payload in benefit type safely yields 0 records');

// 3D: DepartmentRoleRepository findRoleById SQL injection
$drRepo = new DepartmentRoleRepository($db);
$sqliRole = $drRepo->findRoleById($sqliPayload, 't-alpha');
$c->assert($sqliRole === null, 'SQL injection payload in findRoleById safely yields null');

// ==========================================================================
// Challenge 4: LPS Variability Engine Edge Cases & Precedence Hierarchy
// ==========================================================================
$c->section('LPS Variability Engine Precedence & Edge Cases');

$ftm = FeatureToggleManager::getInstance();
$lps = new LpsVariabilityEngine($ftm);

// 4A: Precedence Level 1 (Tenant Override) trumps Level 2 (Segment Default)
// Tech default for overtime_payout is FALSE.
$c->assert(!$lps->isOvertimePayoutActive(TenantSegment::TECH), 'Tech default for overtime_payout is false');
// Set tenant override to TRUE
$ftm->setTenantFeature('t-tech-override', 'overtime_payout', true);
$c->assert(
    $ftm->isFeatureEnabled('overtime_payout', segment: TenantSegment::TECH, tenantId: 't-tech-override'),
    'Tenant override to true overrides Tech segment default of false'
);
// Reset override
$ftm->removeTenantFeature('t-tech-override', 'overtime_payout');
$c->assert(
    !$ftm->isFeatureEnabled('overtime_payout', segment: TenantSegment::TECH, tenantId: 't-tech-override'),
    'Removing override restores default false'
);

// 4B: Precedence Level 1 (Tenant Override to FALSE) overrides Segment Default TRUE
// Tech default for bank_of_hours is TRUE.
$ftm->setTenantFeature('t-tech-override-2', 'bank_of_hours', false);
$c->assert(
    !$ftm->isFeatureEnabled('bank_of_hours', segment: TenantSegment::TECH, tenantId: 't-tech-override-2'),
    'Tenant override to false overrides Tech segment default of true'
);
$ftm->removeTenantFeature('t-tech-override-2', 'bank_of_hours');

// 4C: Overtime Strategy Resolution Under Override
// If Tech tenant overrides bank_of_hours to false, resolveOvertimeStrategy should return Standard50Strategy
$tTechEntity = new Tenant(
    id: 't-tech-override-3',
    cnpj: '01.200.000/0001-06',
    corporateName: 'Tech Override',
    tradingName: 'TechOver',
    segment: 'tech'
);
$ftm->setTenantFeature('t-tech-override-3', 'bank_of_hours', false);
$resolvedStrat = $lps->resolveOvertimeStrategy($tTechEntity);
$c->assert($resolvedStrat instanceof Standard50Strategy, 'Tech tenant with bank_of_hours=false resolves to Standard50Strategy');
$ftm->removeTenantFeature('t-tech-override-3', 'bank_of_hours');

// 4D: Case insensitivity & alias mapping in TenantSegment
$c->assert(TenantSegment::fromValue('STARTUP') === TenantSegment::TECH, 'TenantSegment resolves uppercase alias STARTUP to TECH');
$c->assert(TenantSegment::fromValue('FABRIL') === TenantSegment::INDUSTRIA, 'TenantSegment resolves uppercase alias FABRIL to INDUSTRIA');
$c->assert(TenantSegment::fromValue('FINANCAS') === TenantSegment::FINANCEIRO, 'TenantSegment resolves uppercase alias FINANCAS to FINANCEIRO');

// 4E: Work Eligibility exact boundary conditions
$dummyEmp = new Employee(
    'e-boundary', 't-ind', '100.000.000-19', 'Worker', 'w@ind.com',
    '11900000000', '1990-01-01', '2020-01-01', 'd', 'r', 3000, 'CLT'
);

// ASO expired yesterday
$asoExpiredYesterday = new EquipmentASO(
    'aso-yesterday', 't-ind', 'e-boundary', 'ASO', '123',
    expirationDate: (new DateTimeImmutable('now'))->modify('-1 day'),
    isFit: true
);
$checkYesterday = $lps->validateWorkEligibility($dummyEmp, $asoExpiredYesterday, TenantSegment::INDUSTRIA);
$c->assert(!$checkYesterday['allowed'] && $checkYesterday['code'] === 'ASO_EXPIRED', 'ASO expired yesterday blocks work eligibility with ASO_EXPIRED');

// CA expired yesterday
$caExpiredYesterday = new EquipmentASO(
    'aso-ca-yesterday', 't-ind', 'e-boundary', 'Luva de Raspa', '123',
    caExpirationDate: (new DateTimeImmutable('now'))->modify('-1 day'),
    expirationDate: (new DateTimeImmutable('now'))->modify('+100 days'),
    isFit: true
);
$checkCaYesterday = $lps->validateWorkEligibility($dummyEmp, $caExpiredYesterday, TenantSegment::INDUSTRIA);
$c->assert(!$checkCaYesterday['allowed'] && $checkCaYesterday['code'] === 'PPE_CA_EXPIRED', 'CA expired yesterday blocks work eligibility with PPE_CA_EXPIRED');

// ==========================================================================
// Challenge 5: Business Service Invariants & Failure Modes
// ==========================================================================
$c->section('Business Service Invariants & Failure Modes');

// 5A: Insufficient vacation days throws ValidationException
$vacFailed = false;
try {
    // empAlpha has 30 days. Request 35 days.
    $vServ->requestVacation(
        'vac-too-long', 't-alpha', 'emp-alpha-1',
        (new DateTimeImmutable())->modify('+40 days'),
        (new DateTimeImmutable())->modify('+75 days'),
        35
    );
} catch (ValidationException $e) {
    $vacFailed = true;
}
$c->assert($vacFailed, 'Requesting vacation exceeding employee balance strictly throws ValidationException');

// 5B: Duplicate CNPJ rejection
$cnpjFailed = false;
try {
    $tServ->createTenant('t-dup-cnpj', '01.000.000/0001-54', 'Dup Corp', 'Dup Trade');
} catch (ValidationException $e) {
    $cnpjFailed = true;
}
$c->assert($cnpjFailed, 'Registering tenant with already registered CNPJ strictly throws ValidationException');

// 5C: Non-existent Tenant in UserService throws TenantNotFoundException or returns null
$c->assert($tServ->getTenantByCnpj('01.300.000/0001-70') === null, 'Querying non-existent valid CNPJ returns null safely');
$notFoundThrew = false;
try {
    $tServ->getTenantById('t-ghost-non-existent');
} catch (TenantNotFoundException $e) {
    $notFoundThrew = true;
}
$c->assert($notFoundThrew, 'getTenantById for non-existent ID throws TenantNotFoundException');

// ==========================================================================
// Challenge 6: Code Integrity & Student Attribution Completeness
// ==========================================================================
$c->section('Code Integrity & Student Attribution Completeness');

$contractsDir = dirname(__DIR__) . '/src/Repositories/Contracts';
$reposDir = dirname(__DIR__) . '/src/Repositories';
$servicesDir = dirname(__DIR__) . '/src/Services';

$requiredAttributions = [
    'TenantRepositoryInterface.php' => 'Fernando Lopes Duarte',
    'TenantRepository.php' => 'Fernando Lopes Duarte',
    'TenantService.php' => 'Fernando Lopes Duarte',
    'UserRepositoryInterface.php' => 'Fernando Lopes Duarte',
    'UserRepository.php' => 'Fernando Lopes Duarte',
    'UserService.php' => 'Fernando Lopes Duarte',
    'EmployeeRepositoryInterface.php' => 'Andryus',
    'EmployeeRepository.php' => 'Andryus',
    'EmployeeService.php' => 'Andryus',
    'DepartmentRoleRepositoryInterface.php' => 'Andryus',
    'DepartmentRoleRepository.php' => 'Andryus',
    'DepartmentRoleService.php' => 'Andryus',
    'TimeLogRepositoryInterface.php' => 'Felipe',
    'TimeLogRepository.php' => 'Felipe',
    'TimeLogService.php' => 'Felipe',
    'TimeAdjustmentRepositoryInterface.php' => 'Felipe',
    'TimeAdjustmentRepository.php' => 'Felipe',
    'TimeAdjustmentService.php' => 'Felipe',
    'VacationRepositoryInterface.php' => 'Valentin',
    'VacationRepository.php' => 'Valentin',
    'VacationService.php' => 'Valentin',
    'BenefitRepositoryInterface.php' => 'Valentin',
    'BenefitRepository.php' => 'Valentin',
    'BenefitService.php' => 'Valentin',
    'EquipmentASORepositoryInterface.php' => 'Nicholas',
    'EquipmentASORepository.php' => 'Nicholas',
    'EquipmentASOService.php' => 'Nicholas',
    'InsurancePolicyRepositoryInterface.php' => 'Nicholas',
    'InsurancePolicyRepository.php' => 'Nicholas',
    'InsurancePolicyService.php' => 'Nicholas',
];

foreach ($requiredAttributions as $file => $student) {
    $found = false;
    foreach ([$contractsDir, $reposDir, $servicesDir] as $dir) {
        $path = $dir . '/' . $file;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if (str_contains($content, "@author {$student}")) {
                $found = true;
            }
            break;
        }
    }
    $c->assert($found, "File {$file} is explicitly attributed to student '{$student}' via @author");
}

// Exit with summary
$exitCode = $c->summary();
exit($exitCode);
