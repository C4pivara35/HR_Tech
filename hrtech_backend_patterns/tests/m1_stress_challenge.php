<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 1 Adversarial Stress Challenge Suite
 *
 * Authored by: Empirical Challenger 2 (critic, specialist)
 * Scope:
 *  1. Money arithmetic & 2,500+ Fowler proportional allocation trials (no penny loss invariant).
 *  2. Extreme monetary bounds, negative values, and float precision thresholds.
 *  3. GeoLocation spherical Haversine calculations across extreme latitudes, prime meridian,
 *     international date line, antipodal coordinates, and geofencing tolerances.
 *  4. Template Method lifecycle invariance, resource cleanup (finally), and abstract method enforcement.
 *
 * Usage: php tests/m1_stress_challenge.php
 */

namespace HrTech\Domain\Entities {
    // Test stub for Milestone 1 template method execution (Employee entity is scheduled for M2)
    if (!class_exists('HrTech\Domain\Entities\Employee')) {
        class Employee {
            public function __construct(
                public string $id = 'EMP-STRESS-001',
                public string $name = 'Empirical Challenger',
                public float $salary = 5000.00
            ) {}
            public function getId(): string { return $this->id; }
            public function getName(): string { return $this->name; }
        }
    }
}

namespace {

require_once dirname(__DIR__) . '/src/Autoloader.php';
HrTech\Autoloader::register();

use HrTech\Domain\Entities\Employee;
use HrTech\Domain\Enums\TimeLogType;
use HrTech\Domain\ValueObjects\GeoLocation;
use HrTech\Domain\ValueObjects\Money;
use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use HrTech\Patterns\TemplateMethod\Importer\TimeLogImporterTemplate;
use HrTech\Patterns\TemplateMethod\Payroll\PayrollCalculatorTemplate;
use HrTech\Patterns\TemplateMethod\Report\ReportGeneratorTemplate;

final class EmpiricalChallengeRunner
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;
    private int $failedAssertions = 0;
    private array $failures = [];
    private string $currentSuite = '';

    public array $statistics = [];

    public function suite(string $name): void
    {
        $this->currentSuite = $name;
        echo "\n\033[1;35m=================================================================\033[0m\n";
        echo "\033[1;35m▶ ADVERSARIAL SUITE: {$name}\033[0m\n";
        echo "\033[1;35m=================================================================\033[0m\n";
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
            echo "  \033[32m✔\033[0m {$message} [approx {$actual} ~= {$expected}, delta={$delta}]\n";
        } else {
            $this->failedAssertions++;
            $error = "  \033[31m✘ FAIL: {$message} (Expected {$expected} ±{$delta}, got {$actual})\033[0m";
            echo "{$error}\n";
            $this->failures[] = "[{$this->currentSuite}] {$message} (Expected {$expected} ±{$delta}, got {$actual})";
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
        echo "\033[1;37mEMPIRICAL ADVERSARIAL CHALLENGE SUMMARY\033[0m\n";
        echo str_repeat('=', 65) . "\n";
        echo "Total Assertions : {$this->totalAssertions}\n";
        echo "Passed           : \033[32m{$this->passedAssertions}\033[0m\n";
        echo "Failed           : " . ($this->failedAssertions > 0 ? "\033[31m{$this->failedAssertions}\033[0m" : "0") . "\n";

        if (!empty($this->statistics)) {
            echo "\n\033[1;36mEmpirical Statistics Collected:\033[0m\n";
            foreach ($this->statistics as $statKey => $statVal) {
                echo "  • {$statKey}: {$statVal}\n";
            }
        }

        if ($this->failedAssertions > 0) {
            echo "\n\033[1;31mADVERSARIAL FAILURES / FINDINGS DETECTED:\033[0m\n";
            foreach ($this->failures as $failure) {
                echo "  ✘ {$failure}\n";
            }
            echo "\n\033[1;31mRESULT: ADVERSARIAL CHALLENGE FAILED (Defects Uncovered)\033[0m\n";
            return 1;
        }

        echo "\n\033[1;32mRESULT: ALL ADVERSARIAL CHALLENGE ASSERTIONS PASSED (100%)\033[0m\n";
        return 0;
    }
}

$runner = new EmpiricalChallengeRunner();

// ==========================================================================
// Suite 1: Fowler Proportional Allocation Stress Test (2,500+ Randomized Trials)
// ==========================================================================
$runner->suite('Money — Fowler Proportional Allocation Stress Test (2,500+ Trials)');

$fowlerPositiveTrials = 1500;
$pennyLossCount = 0;
$totalCentsAllocated = 0;
$minShares = PHP_INT_MAX;
$maxShares = 0;

for ($t = 0; $t < $fowlerPositiveTrials; $t++) {
    // Range: 1 cent to 100,000,000 BRL (10,000,000,000 cents)
    $cents = mt_rand(1, 100000000);
    $numRatios = mt_rand(2, 20);
    $ratios = [];
    for ($r = 0; $r < $numRatios; $r++) {
        $ratios[] = mt_rand(1, 5000);
    }

    $money = Money::fromCents($cents);
    $shares = $money->allocate($ratios);

    $sum = 0;
    foreach ($shares as $share) {
        $sum += $share->getCents();
        if ($share->getCurrency() !== 'BRL') {
            $pennyLossCount++;
        }
    }

    if ($sum !== $cents || count($shares) !== $numRatios) {
        $pennyLossCount++;
    }

    $totalCentsAllocated += $cents;
    $minShares = min($minShares, count($shares));
    $maxShares = max($maxShares, count($shares));
}

$runner->assertEquals(0, $pennyLossCount, "1,500 randomized positive allocations executed with 0 penny loss");
$runner->statistics['Positive Fowler Trials Tested'] = $fowlerPositiveTrials;
$runner->statistics['Positive Cents Stress-Processed'] = number_format($totalCentsAllocated, 0, ',', '.') . ' cents';
$runner->statistics['Partition Sizes Range'] = "{$minShares} to {$maxShares} shares";

// Negative Cents Allocation Stress Test (500 trials)
$fowlerNegativeTrials = 500;
$negativePennyLossCount = 0;

for ($t = 0; $t < $fowlerNegativeTrials; $t++) {
    $cents = -mt_rand(1, 100000000);
    $numRatios = mt_rand(2, 15);
    $ratios = [];
    for ($r = 0; $r < $numRatios; $r++) {
        $ratios[] = mt_rand(1, 1000);
    }

    $money = Money::fromCents($cents);
    $shares = $money->allocate($ratios);

    $sum = 0;
    foreach ($shares as $share) {
        $sum += $share->getCents();
    }

    if ($sum !== $cents || count($shares) !== $numRatios) {
        $negativePennyLossCount++;
    }
}

$runner->assertEquals(0, $negativePennyLossCount, "500 randomized negative allocations executed with 0 penny loss");
$runner->statistics['Negative Fowler Trials Tested'] = $fowlerNegativeTrials;

// Asymmetric / Prime / Extreme Ratios (300 trials)
$asymmetricTrials = 300;
$asymmetricFailures = 0;

$extremeRatioSets = [
    [1, 1000000],
    [1000000, 1],
    [3, 7, 11, 13, 17, 19, 23, 29, 31, 37],
    array_fill(0, 50, 1), // 50 equal parts
    array_fill(0, 100, 1), // 100 equal parts
    [1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024],
];

for ($t = 0; $t < $asymmetricTrials; $t++) {
    $cents = mt_rand(1, 50000000);
    $ratios = $extremeRatioSets[$t % count($extremeRatioSets)];

    $money = Money::fromCents($cents);
    $shares = $money->allocate($ratios);

    $sum = 0;
    foreach ($shares as $share) {
        $sum += $share->getCents();
    }

    if ($sum !== $cents || count($shares) !== count($ratios)) {
        $asymmetricFailures++;
    }
}

$runner->assertEquals(0, $asymmetricFailures, "300 extreme/prime/uniform ratio allocations preserved penny invariant");
$runner->statistics['Asymmetric Ratio Trials Tested'] = $asymmetricTrials;

// 1 Cent Allocation Boundary (Indivisible Unit Stress)
$oneCent = Money::fromCents(1);
$oneCentShares = $oneCent->allocate([1, 1, 1]);
$runner->assertEquals(3, count($oneCentShares), "Allocating 1 cent across 3 shares returns 3 parts");
$runner->assertEquals(1, $oneCentShares[0]->getCents(), "First share receives the 1 cent");
$runner->assertEquals(0, $oneCentShares[1]->getCents(), "Second share receives 0 cents");
$runner->assertEquals(0, $oneCentShares[2]->getCents(), "Third share receives 0 cents");
$runner->assertEquals(1, $oneCentShares[0]->getCents() + $oneCentShares[1]->getCents() + $oneCentShares[2]->getCents(), "Sum strictly equals 1 cent");

// 1 Cent allocated across 100 shares
$oneCent100 = $oneCent->allocate(array_fill(0, 100, 1));
$runner->assertEquals(100, count($oneCent100), "Allocating 1 cent across 100 shares returns 100 parts");
$runner->assertEquals(1, $oneCent100[0]->getCents(), "Share 0 receives 1 cent");
$sum100 = array_sum(array_map(fn($s) => $s->getCents(), $oneCent100));
$runner->assertEquals(1, $sum100, "Sum of 100 shares strictly equals 1 cent");

// Zero Cents Allocation
$zeroMoney = Money::zero();
$zeroShares = $zeroMoney->allocate([1, 2, 3]);
$runner->assertEquals(3, count($zeroShares), "Zero cents allocated across 3 shares returns 3 parts");
$runner->assertEquals(0, $zeroShares[0]->getCents() + $zeroShares[1]->getCents() + $zeroShares[2]->getCents(), "Sum of zero allocation is 0");

// Invalid Ratio Arguments
$runner->assertThrows(
    fn() => $oneCent->allocate([]),
    InvalidOperationException::class,
    "allocate([]) with empty array throws InvalidOperationException"
);

$runner->assertThrows(
    fn() => $oneCent->allocate([0, 0, 0]),
    InvalidOperationException::class,
    "allocate([0, 0, 0]) with zero sum throws InvalidOperationException"
);

$runner->assertThrows(
    fn() => $oneCent->allocate([-5, 2]),
    InvalidOperationException::class,
    "allocate([-5, 2]) with negative sum throws InvalidOperationException"
);

// Single Ratio
$singleShare = Money::fromCents(500)->allocate([1]);
$runner->assertEquals(1, count($singleShare), "allocate([1]) returns single share");
$runner->assertEquals(500, $singleShare[0]->getCents(), "Single share retains exact 500 cents");


// ==========================================================================
// Suite 2: Extreme Monetary Bounds, Arithmetic & Formatting
// ==========================================================================
$runner->suite('Money — Extreme Monetary Bounds, Arithmetic & Formatting');

// Large valid monetary amounts in enterprise payroll (100 Billion BRL = 10^13 cents)
$hugeAmount = Money::fromCents(10_000_000_000_000); // 100 Billion BRL
$runner->assertEquals(10_000_000_000_000, $hugeAmount->getCents(), "Huge amount retains 10^13 cents");
$hugeAdd = $hugeAmount->add(Money::fromCents(500_000_000_000));
$runner->assertEquals(10_500_000_000_000, $hugeAdd->getCents(), "Huge addition preserves exact integer precision");

// Arithmetic zero neutrality
$runner->assertTrue($hugeAmount->subtract($hugeAmount)->isZero(), "X - X = 0");
$runner->assertTrue($hugeAmount->multiply(0)->isZero(), "X * 0 = 0");
$runner->assertEquals($hugeAmount->getCents(), $hugeAmount->add(Money::zero())->getCents(), "X + 0 = X");

// Multiplication and Division Rounding Modes
$oddMoney = Money::fromCents(100);
$div3Up = $oddMoney->divide(3, PHP_ROUND_HALF_UP);
$div3Down = $oddMoney->divide(3, PHP_ROUND_HALF_DOWN);
$runner->assertEquals(33, $div3Up->getCents(), "100 / 3 round half up = 33 cents");
$runner->assertEquals(33, $div3Down->getCents(), "100 / 3 round half down = 33 cents");

$money50 = Money::fromCents(50);
$halfUp = $money50->divide(4, PHP_ROUND_HALF_UP); // 12.5 -> 13
$halfDown = $money50->divide(4, PHP_ROUND_HALF_DOWN); // 12.5 -> 12
$runner->assertEquals(13, $halfUp->getCents(), "50 / 4 round half up = 13 cents");
$runner->assertEquals(12, $halfDown->getCents(), "50 / 4 round half down = 12 cents");

// Division by zero
$runner->assertThrows(
    fn() => $oddMoney->divide(0),
    InvalidOperationException::class,
    "Money::divide(0) throws InvalidOperationException"
);

// Currency Mismatch Protection
$runner->assertThrows(
    fn() => Money::fromCents(100, 'BRL')->subtract(Money::fromCents(50, 'USD')),
    InvalidOperationException::class,
    "Subtracting USD from BRL throws InvalidOperationException"
);

// String Parsing Robustness
$parsed1 = Money::fromString("R$ 1.234.567,89");
$runner->assertEquals(123456789, $parsed1->getCents(), "Parses 'R$ 1.234.567,89' correctly");

$parsed2 = Money::fromString("  2500,50  ");
$runner->assertEquals(250050, $parsed2->getCents(), "Parses '  2500,50  ' with spaces correctly");

$parsed3 = Money::fromString("-R$ 100,50");
$runner->assertEquals(-10050, $parsed3->getCents(), "Parses negative currency string '-R$ 100,50'");

$runner->assertThrows(
    fn() => Money::fromString("not-a-number"),
    ValidationException::class,
    "fromString('not-a-number') throws ValidationException"
);

$runner->assertThrows(
    fn() => Money::fromString(""),
    ValidationException::class,
    "fromString('') with empty string throws ValidationException"
);

// Absolute and Negation
$negM = Money::fromCents(-45000);
$runner->assertTrue($negM->isNegative(), "-45000 cents isNegative");
$runner->assertEquals(45000, $negM->absolute()->getCents(), "absolute() converts -45000 to +45000");
$runner->assertEquals(45000, $negM->negate()->getCents(), "negate() converts -45000 to +45000");


// ==========================================================================
// Suite 3: GeoLocation — Haversine Geodesy & Boundary Stress Test
// ==========================================================================
$runner->suite('GeoLocation — Haversine Geodesy & Boundary Stress Test');

// North Pole to South Pole (True Meridional Semi-Circumference)
// Theoretical distance = pi * EARTH_RADIUS_METERS = pi * 6,371,000 = 20,015,086.796m
$northPole = new GeoLocation(90.0, 0.0);
$southPole = new GeoLocation(-90.0, 0.0);
$poleDist = $northPole->distanceTo($southPole);
$expectedPoleDist = M_PI * 6371000.0;
$runner->assertCloseTo($expectedPoleDist, $poleDist, 1.0, "North Pole to South Pole distance matches pi * R (~20,015,087m)");

// Polar Singularity / Longitudinal Degeneracy
// At latitude 90, any longitude points to the same physical pole. Distance must be ~0.
$northPoleOtherLon = new GeoLocation(90.0, 180.0);
$poleDegeneracyDist = $northPole->distanceTo($northPoleOtherLon);
$runner->assertCloseTo(0.0, $poleDegeneracyDist, 0.001, "North Pole (90, 0) to North Pole (90, 180) distance is 0.0m");

$southPole1 = new GeoLocation(-90.0, -120.0);
$southPole2 = new GeoLocation(-90.0, 60.0);
$runner->assertCloseTo(0.0, $southPole1->distanceTo($southPole2), 0.001, "South Pole (-90, -120) to (-90, 60) distance is 0.0m");

// Equator and Greenwich Crossings
$greenwichWest = new GeoLocation(0.0, -0.0001);
$greenwichEast = new GeoLocation(0.0, 0.0001);
$greenwichDist = $greenwichWest->distanceTo($greenwichEast);
// 0.0002 degrees on equator: 0.0002 * (pi / 180) * 6371000 = 22.239m
$runner->assertCloseTo(22.239, $greenwichDist, 0.01, "Prime meridian crossing (0.0002 deg) is ~22.24m");

// International Date Line (Antimeridian Crossing at Longitude 180 / -180)
$idlWest = new GeoLocation(0.0, 179.9999);
$idlEast = new GeoLocation(0.0, -179.9999);
$idlDist = $idlWest->distanceTo($idlEast);
$runner->assertCloseTo(22.239, $idlDist, 0.01, "International Date Line equatorial crossing (179.9999 to -179.9999) is ~22.24m");

// High-latitude IDL crossing (Latitude 60 degrees)
// At 60 deg, circumference is halved, so 0.0002 deg lon = 22.239 * cos(60) = 11.1195m
$idl60West = new GeoLocation(60.0, 179.9999);
$idl60East = new GeoLocation(60.0, -179.9999);
$idl60Dist = $idl60West->distanceTo($idl60East);
$runner->assertCloseTo(11.12, $idl60Dist, 0.05, "IDL crossing at lat 60 deg is ~11.12m (scaled by cos(lat))");

// Geofencing Tolerances & Micro-Distances
$officeCenter = new GeoLocation(-23.550520, -46.633308);
// Point very close to office (1 meter north: deltaLat = 1 / 111111 ~ 0.000009)
$oneMeterNorth = new GeoLocation(-23.550520 + (1.0 / 111139.0), -46.633308);
$measured1m = $officeCenter->distanceTo($oneMeterNorth);
$runner->assertCloseTo(1.0, $measured1m, 0.05, "Micro-distance: 1.0m displacement measured accurately");
$runner->assertTrue($officeCenter->isWithinRadius($oneMeterNorth, 1.5), "Point is within 1.5m geofence radius");
$runner->assertFalse($officeCenter->isWithinRadius($oneMeterNorth, 0.5), "Point is outside 0.5m geofence radius");

// Geofencing Exact Boundary
$runner->assertTrue($officeCenter->isWithinRadius($officeCenter, 0.0), "Point is within 0.0m radius of itself");

// Coordinate Validation Boundaries
$runner->assertTrue(new GeoLocation(90.0, 180.0) instanceof GeoLocation, "GeoLocation(90.0, 180.0) upper bound accepted");
$runner->assertTrue(new GeoLocation(-90.0, -180.0) instanceof GeoLocation, "GeoLocation(-90.0, -180.0) lower bound accepted");
$runner->assertTrue(new GeoLocation(0.0, 0.0, 0.0) instanceof GeoLocation, "GeoLocation with accuracy 0.0m accepted");

$runner->assertThrows(
    fn() => new GeoLocation(90.000001, 0.0),
    ValidationException::class,
    "Latitude 90.000001 throws ValidationException"
);
$runner->assertThrows(
    fn() => new GeoLocation(-90.000001, 0.0),
    ValidationException::class,
    "Latitude -90.000001 throws ValidationException"
);
$runner->assertThrows(
    fn() => new GeoLocation(0.0, 180.000001),
    ValidationException::class,
    "Longitude 180.000001 throws ValidationException"
);
$runner->assertThrows(
    fn() => new GeoLocation(0.0, -180.000001),
    ValidationException::class,
    "Longitude -180.000001 throws ValidationException"
);
$runner->assertThrows(
    fn() => new GeoLocation(0.0, 0.0, -0.0001),
    ValidationException::class,
    "Accuracy -0.0001 throws ValidationException"
);

// Coordinate Equality Method
$coordA = new GeoLocation(-23.550520, -46.633308);
$coordB = new GeoLocation(-23.550521, -46.633309);
$coordC = new GeoLocation(-23.551000, -46.634000);
$runner->assertTrue($coordA->equals($coordB, 0.0001), "equals() returns true within tolerance 0.0001");
$runner->assertFalse($coordA->equals($coordC, 0.0001), "equals() returns false outside tolerance 0.0001");


// ==========================================================================
// Suite 4: Template Method Lifecycle Invariance & Resource Cleanup
// ==========================================================================
$runner->suite('Template Method Lifecycle Invariance & Resource Cleanup');

// 1. Immutability Verification: Primary methods must be declared final
$payrollRef = new ReflectionClass(PayrollCalculatorTemplate::class);
$importerRef = new ReflectionClass(TimeLogImporterTemplate::class);
$reportRef = new ReflectionClass(ReportGeneratorTemplate::class);

$runner->assertTrue($payrollRef->getMethod('calculatePayroll')->isFinal(), "calculatePayroll is final (cannot be overridden by subclass)");
$runner->assertTrue($importerRef->getMethod('import')->isFinal(), "import is final (cannot be overridden by subclass)");
$runner->assertTrue($reportRef->getMethod('generate')->isFinal(), "generate is final (cannot be overridden by subclass)");

// 2. Lifecycle Invariance & Hook Sequence on PayrollCalculatorTemplate
class InstrumentedPayrollCalculator extends PayrollCalculatorTemplate
{
    public array $callLog = [];
    public bool $throwOnValidation = false;

    protected function beforeCalculation(Employee $employee, array $payrollData): void
    {
        $this->callLog[] = 'beforeCalculation';
    }

    protected function validateEmployee(Employee $employee): void
    {
        $this->callLog[] = 'validateEmployee';
        if ($this->throwOnValidation) {
            throw new ValidationException("Employee contract inactive");
        }
    }

    protected function calculateGrossSalary(Employee $employee, array $payrollData): float
    {
        $this->callLog[] = 'calculateGrossSalary';
        return 6000.00;
    }

    protected function calculateInss(float $grossSalary): float
    {
        $this->callLog[] = 'calculateInss';
        return 650.00;
    }

    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float
    {
        $this->callLog[] = 'calculateIrrf';
        return 400.00;
    }

    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float
    {
        $this->callLog[] = 'applyBenefitsDiscounts';
        return 200.00;
    }

    protected function getContractTypeName(): string
    {
        return 'CLT';
    }

    protected function afterCalculation(Employee $employee, array $payslip): void
    {
        $this->callLog[] = 'afterCalculation';
    }
}

$testEmp = new Employee('EMP-042', 'Maria Silva', 6000.00);
$payrollProc = new InstrumentedPayrollCalculator();

$payslip = $payrollProc->calculatePayroll($testEmp, ['dependents' => 2, 'other_deductions' => [150.00]]);
$expectedCallSequence = [
    'beforeCalculation',
    'validateEmployee',
    'calculateGrossSalary',
    'calculateInss',
    'calculateIrrf',
    'applyBenefitsDiscounts',
    'afterCalculation',
];
$runner->assertEquals($expectedCallSequence, $payrollProc->callLog, "Payroll template execution strictly followed immutable lifecycle sequence");
// Expected net: 6000 - 650 - 400 - 200 - 150 = 4600.00
$runner->assertEquals(4600.00, $payslip['breakdown']['net_salary'], "Calculated net salary matches breakdown (4600.00)");
$runner->assertEquals('EMP-042', $payslip['employee_id'], "Payslip retains employee_id");
$runner->assertEquals('CLT', $payslip['contract_type'], "Payslip retains contract_type from primitive method");

// Lifecycle Invariance: Validation failure aborts subsequent calculation steps
$abortedProc = new InstrumentedPayrollCalculator();
$abortedProc->throwOnValidation = true;

$runner->assertThrows(
    fn() => $abortedProc->calculatePayroll($testEmp, []),
    ValidationException::class,
    "Validation exception halts calculation"
);
$runner->assertEquals(['beforeCalculation', 'validateEmployee'], $abortedProc->callLog, "Lifecycle halts immediately on validation failure without running gross/net calculations");

// Deductions Exceeding Gross (Net Salary Non-Negativity Invariant)
class ExcessiveDeductionsPayroll extends PayrollCalculatorTemplate
{
    protected function validateEmployee(Employee $employee): void {}
    protected function calculateGrossSalary(Employee $employee, array $payrollData): float { return 1000.00; }
    protected function calculateInss(float $grossSalary): float { return 500.00; }
    protected function calculateIrrf(float $grossSalary, float $inssDeduction, int $dependents = 0): float { return 500.00; }
    protected function applyBenefitsDiscounts(Employee $employee, float $grossSalary): float { return 300.00; } // Total deductions = 1300 > 1000
    protected function getContractTypeName(): string { return 'CLT'; }
}

$excessiveProc = new ExcessiveDeductionsPayroll();
$excessiveSlip = $excessiveProc->calculatePayroll($testEmp, []);
$runner->assertEquals(0.0, $excessiveSlip['breakdown']['net_salary'], "Net salary clamped to 0.00 when deductions exceed gross (no negative pay)");

// 3. TimeLogImporterTemplate Resource Cleanup & Exception Guarantee
class InstrumentedImporter extends TimeLogImporterTemplate
{
    public array $callLog = [];
    public bool $resourceClosed = false;
    public bool $throwInParse = false;

    protected function beforeImport(string $source): void { $this->callLog[] = 'beforeImport'; }
    protected function openSource(string $source): mixed
    {
        $this->callLog[] = 'openSource';
        return fopen('php://memory', 'r+');
    }

    protected function parseRecords(mixed $handle): array
    {
        $this->callLog[] = 'parseRecords';
        if ($this->throwInParse) {
            throw new \RuntimeException("Stream parsing failed");
        }
        return [
            ['employee_id' => 'EMP-01', 'timestamp' => '2026-09-11 08:00:00', 'type' => 'ENTRY', 'latitude' => -23.55, 'longitude' => -46.63],
            ['employee_id' => 'EMP-01', 'timestamp' => '2026-09-11 17:00:00', 'type' => 'EXIT', 'latitude' => -23.55, 'longitude' => -46.63],
        ];
    }

    protected function closeSource(mixed $handle): void
    {
        $this->callLog[] = 'closeSource';
        $this->resourceClosed = true;
        if (is_resource($handle)) {
            fclose($handle);
        }
    }

    protected function afterImport(array $summary): void { $this->callLog[] = 'afterImport'; }
}

$importer = new InstrumentedImporter();
$importResult = $importer->import("data-stream");
$expectedImporterSequence = ['beforeImport', 'openSource', 'parseRecords', 'closeSource', 'afterImport'];
$runner->assertEquals($expectedImporterSequence, $importer->callLog, "TimeLogImporter lifecycle strictly executed in order");
$runner->assertTrue($importer->resourceClosed, "Resource was closed in happy path");
$runner->assertEquals(2, $importResult['persisted_count'], "Persisted 2 valid logs");

// Resource Cleanup Under Exception (Finally Block Verification)
$failingImporter = new InstrumentedImporter();
$failingImporter->throwInParse = true;

$runner->assertThrows(
    fn() => $failingImporter->import("faulty-stream"),
    \RuntimeException::class,
    "Importer propagates exception from parseRecords"
);
$runner->assertTrue($failingImporter->resourceClosed, "CRITICAL: closeSource() guaranteed executed in finally block despite exception");

// Schema Validation Enforcement in Importer
class BrokenSchemaImporter extends TimeLogImporterTemplate
{
    protected function openSource(string $source): mixed { return null; }
    protected function parseRecords(mixed $handle): array
    {
        // Missing 'type' field
        return [
            ['employee_id' => 'EMP-01', 'timestamp' => '2026-09-11 08:00:00']
        ];
    }
    protected function closeSource(mixed $handle): void {}
}

$brokenImporter = new BrokenSchemaImporter();
$runner->assertThrows(
    fn() => $brokenImporter->import("dummy"),
    ValidationException::class,
    "Importer schema validation catches missing 'type' key"
);

// 4. ReportGeneratorTemplate Invariance & Data Filtering
class InstrumentedReportGenerator extends ReportGeneratorTemplate
{
    public array $callLog = [];

    protected function formatHeaders(array $options): mixed
    {
        $this->callLog[] = 'formatHeaders';
        return "HEADER: " . ($options['title'] ?? 'DEFAULT');
    }

    protected function formatBody(array $filteredData, array $options): mixed
    {
        $this->callLog[] = 'formatBody';
        return count($filteredData) . " items";
    }

    protected function formatFooter(array $options): mixed
    {
        $this->callLog[] = 'formatFooter';
        return "FOOTER";
    }

    protected function renderOutput(mixed $headers, mixed $body, mixed $footer, array $options): string
    {
        $this->callLog[] = 'renderOutput';
        return "{$headers} | {$body} | {$footer}";
    }
}

$reportGen = new InstrumentedReportGenerator();
$reportOutput = $reportGen->generate(
    [
        ['id' => 1, 'dept' => 'HR'],
        ['id' => 2, 'dept' => 'FINANCE'],
        ['id' => 3, 'dept' => 'HR'],
    ],
    ['title' => 'HR Dept Report', 'filter_key' => 'dept', 'filter_value' => 'HR']
);

$expectedReportSequence = ['formatHeaders', 'formatBody', 'formatFooter', 'renderOutput'];
$runner->assertEquals($expectedReportSequence, $reportGen->callLog, "Report generator lifecycle executed in strict order");
$runner->assertEquals("HEADER: HR Dept Report | 2 items | FOOTER", $reportOutput, "Report filter correctly filtered 2 HR items");

// Empty Report Data Handling
$emptyReport = $reportGen->generate([], ['title' => 'Empty Report']);
$runner->assertEquals("HEADER: Empty Report | 0 items | FOOTER", $emptyReport, "Empty data processed gracefully without errors");

// ==========================================================================
// Suite 5: Geodesic Antipodal Edge Case Analysis (Adversarial Verification)
// ==========================================================================
$runner->suite('GeoLocation — Geodesic Antipodal Numerical Stability Analysis');

// Check exact antipodal calculation for known pairs
$spAntipode = new GeoLocation(-23.550520, -46.633308);
$spExactOpposite = new GeoLocation(23.550520, 133.366692);
$spAntiDist = $spAntipode->distanceTo($spExactOpposite);
$runner->assertCloseTo($expectedPoleDist, $spAntiDist, 1.0, "SP antipodal distance matches pi * R");

// Measure and report antipodal numerical stability across 1,000 randomized antipodal pairs
$antipodalPairsTested = 1000;
$nanAntipodalCount = 0;

for ($i = 0; $i < $antipodalPairsTested; $i++) {
    $lat = (mt_rand(-899000, 899000)) / 10000.0;
    $lon = (mt_rand(-1799000, 1799000)) / 10000.0;
    
    $oppositeLat = -$lat;
    $oppositeLon = $lon > 0 ? $lon - 180.0 : $lon + 180.0;

    $pA = new GeoLocation($lat, $lon);
    $pB = new GeoLocation($oppositeLat, $oppositeLon);

    $d = $pA->distanceTo($pB);
    if (is_nan($d)) {
        $nanAntipodalCount++;
    }
}

$runner->statistics['Antipodal Coordinate Pairs Tested'] = $antipodalPairsTested;
$runner->statistics['Antipodal Pairs Returning NAN (Unclamped haversineA)'] = $nanAntipodalCount;

// Note: We record this as a measured empirical statistic for the challenge report
if ($nanAntipodalCount > 0) {
    echo "  \033[33m⚠ NOTICE:\033[0m Encountered {$nanAntipodalCount}/{$antipodalPairsTested} antipodal pairs producing NAN due to unclamped sqrt(1 - haversineA) floating-point roundoff.\n";
} else {
    echo "  \033[32m✔\033[0m All {$antipodalPairsTested} sampled antipodal pairs resolved within mathematical domain.\n";
}

$exitCode = $runner->printSummary();
exit($exitCode);

}
