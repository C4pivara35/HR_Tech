<?php

declare(strict_types=1);

/**
 * HRTech Core Backend — Milestone 1 Adversarial Challenge & Stress Test Harness
 *
 * Tier 5 Empirical Stress Harness:
 * - 10,000+ synthetic mathematically valid and invalid CPFs and CNPJs
 * - Edge cases (leading zeros, boundary conditions, formatting mutations)
 * - Autoloader traversal attack vectors, case-sensitivity, SPL lifecycle
 * - Exception hierarchy stress, LGPD masking, Fowler allocation stress
 * - Throughput, memory profiling, and failure rate accounting
 *
 * Usage: php tests/m1_adversarial_challenge.php
 */

require_once __DIR__ . '/../src/Autoloader.php';

\HrTech\Autoloader::registerDefault();

use HrTech\Autoloader;
use HrTech\Domain\ValueObjects\Cpf;
use HrTech\Domain\ValueObjects\Cnpj;
use HrTech\Exceptions\ValidationException;
use HrTech\Exceptions\HrTechException;

class EmpiricalAdversarialHarness
{
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private array $anomalies = [];
    private float $startTime;
    private int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    public function record(bool $condition, string $suite, string $description, ?string $anomalyDetails = null): void
    {
        $this->totalTests++;
        if ($condition) {
            $this->passedTests++;
        } else {
            $this->failedTests++;
            $this->anomalies[] = [
                'suite' => $suite,
                'description' => $description,
                'details' => $anomalyDetails ?? 'Assertion failed',
            ];
        }
    }

    // --------------------------------------------------------------------------
    // MATHEMATICAL ORACLES (Modulo 11)
    // --------------------------------------------------------------------------

    public static function generateValidCpf(int $prefixDigits = 9, ?string $customNine = null): string
    {
        if ($customNine !== null) {
            $nine = $customNine;
        } else {
            do {
                $nine = '';
                for ($i = 0; $i < 9; $i++) {
                    $nine .= (string)random_int(0, 9);
                }
            } while (str_repeat($nine[0], 9) === $nine); // avoid repeated sequences
        }

        // Check digit 1
        $sum1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum1 += ((int)$nine[$i]) * (10 - $i);
        }
        $r1 = $sum1 % 11;
        $d1 = ($r1 < 2) ? 0 : (11 - $r1);

        // Check digit 2
        $ten = $nine . $d1;
        $sum2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum2 += ((int)$ten[$i]) * (11 - $i);
        }
        $r2 = $sum2 % 11;
        $d2 = ($r2 < 2) ? 0 : (11 - $r2);

        return $nine . $d1 . $d2;
    }

    public static function generateValidCnpj(?string $customBranch = null, ?string $customRoot = null): array
    {
        if ($customRoot !== null) {
            $root = str_pad($customRoot, 8, '0', STR_PAD_LEFT);
        } else {
            do {
                $root = '';
                for ($i = 0; $i < 8; $i++) {
                    $root .= (string)random_int(0, 9);
                }
            } while (str_repeat($root[0], 8) === $root);
        }

        $branch = $customBranch ?? '0001';
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

        $cnpj = $twelve . $d1 . $d2;
        return [
            'cnpj' => $cnpj,
            'root' => $root,
            'branch' => $branch,
            'checkDigits' => "{$d1}{$d2}",
            'isHeadquarters' => ($branch === '0001'),
        ];
    }

    // --------------------------------------------------------------------------
    // SUITE 1: 5,000 VALID & 5,000 INVALID CPFs
    // --------------------------------------------------------------------------
    public function stressCpfModulo11(): void
    {
        $suite = 'CPF Modulo 11 Stress';

        // 1. 5,000 Mathematically Valid CPFs
        $validTested = 0;
        for ($i = 0; $i < 5000; $i++) {
            $cpf = self::generateValidCpf();
            $isValid = Cpf::isValid($cpf);
            if (!$isValid) {
                $this->record(false, $suite, "Cpf::isValid failed on valid CPF {$cpf}");
                continue;
            }

            try {
                $vo = new Cpf($cpf);
                $cleanMatches = ($vo->getValue() === $cpf);
                $formattedMatches = (strlen($vo->getFormatted()) === 14);
                $maskedMatches = (str_starts_with($vo->getMasked(), '***.') && str_ends_with($vo->getMasked(), '-**'));
                $equalsSelf = $vo->equals(new Cpf($cpf));

                $this->record(
                    $cleanMatches && $formattedMatches && $maskedMatches && $equalsSelf,
                    $suite,
                    "Valid CPF instantiation and invariants: {$cpf}"
                );
                $validTested++;
            } catch (\Throwable $e) {
                $this->record(false, $suite, "Exception on valid CPF {$cpf}: " . $e->getMessage());
            }
        }

        // 2. CPFs with Leading Zeros (Regional variations, e.g. RS region 0)
        $leadingZeroRoots = ['000000001', '000123456', '012345678', '099887766'];
        foreach ($leadingZeroRoots as $lzRoot) {
            $lzCpf = self::generateValidCpf(9, $lzRoot);
            $vo = new Cpf($lzCpf);
            $this->record(
                $vo->getValue() === $lzCpf && strlen($vo->getValue()) === 11,
                $suite,
                "Leading-zero CPF preserved as 11-char string: {$lzCpf}"
            );
        }

        // 3. 5,000 Mathematically Invalid CPFs
        // Sub-categories:
        // - Corrupt 1st check digit
        // - Corrupt 2nd check digit
        // - Corrupt both check digits
        // - Single digit body corruption
        // - Transposition of adjacent digits
        for ($i = 0; $i < 5000; $i++) {
            $validCpf = self::generateValidCpf();
            $mutationType = $i % 5;
            $corrupted = $validCpf;

            switch ($mutationType) {
                case 0: // Corrupt 1st check digit
                    $badD1 = ((int)$validCpf[9] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCpf, 0, 9) . $badD1 . $validCpf[10];
                    break;
                case 1: // Corrupt 2nd check digit
                    $badD2 = ((int)$validCpf[10] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCpf, 0, 10) . $badD2;
                    break;
                case 2: // Corrupt both check digits
                    $badD1 = ((int)$validCpf[9] + random_int(1, 9)) % 10;
                    $badD2 = ((int)$validCpf[10] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCpf, 0, 9) . $badD1 . $badD2;
                    break;
                case 3: // Body mutation
                    $pos = random_int(0, 8);
                    $badDigit = ((int)$validCpf[$pos] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCpf, 0, $pos) . $badDigit . substr($validCpf, $pos + 1);
                    break;
                case 4: // Adjacent transposition
                    $pos = random_int(0, 7);
                    if ($validCpf[$pos] !== $validCpf[$pos + 1]) {
                        $chars = str_split($validCpf);
                        $temp = $chars[$pos];
                        $chars[$pos] = $chars[$pos + 1];
                        $chars[$pos + 1] = $temp;
                        $corrupted = implode('', $chars);
                    } else {
                        $corrupted = substr($validCpf, 0, 9) . (((int)$validCpf[9] + 1) % 10) . $validCpf[10];
                    }
                    break;
            }

            // If by rare accident mutation produced a valid CPF, skip
            if (Cpf::isValid($corrupted)) {
                continue;
            }

            $isValid = Cpf::isValid($corrupted);
            $threw = false;
            try {
                new Cpf($corrupted);
            } catch (ValidationException) {
                $threw = true;
            } catch (\Throwable) {
                $threw = false;
            }

            $this->record(
                !$isValid && $threw,
                $suite,
                "Invalid CPF rejected by both isValid() and constructor: {$corrupted}"
            );
        }

        // 4. Repeated Sequence Invariants (000.000.000-00 through 999.999.999-99)
        for ($d = 0; $d <= 9; $d++) {
            $repeated = str_repeat((string)$d, 11);
            $isValid = Cpf::isValid($repeated);
            $threw = false;
            try {
                new Cpf($repeated);
            } catch (ValidationException) {
                $threw = true;
            }
            $this->record(
                !$isValid && $threw,
                $suite,
                "Repeated sequence {$repeated} rejected by isValid() and constructor"
            );
        }

        // 5. Length Boundaries
        $boundaryLengths = ['', '1', '12', '123', '1234567890', '123456789012', str_repeat('9', 100)];
        foreach ($boundaryLengths as $badLength) {
            $this->record(!Cpf::isValid($badLength), $suite, "Invalid length rejected by isValid(): len=" . strlen($badLength));
            $threw = false;
            try {
                new Cpf($badLength);
            } catch (ValidationException) {
                $threw = true;
            }
            $this->record($threw, $suite, "Invalid length rejected by constructor: len=" . strlen($badLength));
        }
    }

    // --------------------------------------------------------------------------
    // SUITE 2: 5,000 VALID & 5,000 INVALID CNPJs
    // --------------------------------------------------------------------------
    public function stressCnpjModulo11(): void
    {
        $suite = 'CNPJ Modulo 11 Stress';

        // 1. 2,500 Headquarters (0001) & 2,500 Branches (0002..9999)
        for ($i = 0; $i < 5000; $i++) {
            $isHqTest = ($i < 2500);
            $branch = $isHqTest ? '0001' : str_pad((string)random_int(2, 9999), 4, '0', STR_PAD_LEFT);
            $data = self::generateValidCnpj($branch);
            $cnpj = $data['cnpj'];

            $isValid = Cnpj::isValid($cnpj);
            if (!$isValid) {
                $this->record(false, $suite, "Cnpj::isValid failed on valid CNPJ {$cnpj}");
                continue;
            }

            try {
                $vo = new Cnpj($cnpj);
                $valueOk = ($vo->getValue() === $cnpj);
                $rootOk = ($vo->getRoot() === $data['root']);
                $branchOk = ($vo->getBranch() === $data['branch']);
                $checkOk = ($vo->getCheckDigits() === $data['checkDigits']);
                $hqOk = ($vo->isHeadquarters() === $data['isHeadquarters']);
                $formattedOk = (strlen($vo->getFormatted()) === 18);
                $roundTrip = (new Cnpj($vo->getFormatted()))->getValue() === $cnpj;

                $allOk = $valueOk && $rootOk && $branchOk && $checkOk && $hqOk && $formattedOk && $roundTrip;
                $this->record($allOk, $suite, "CNPJ invariants verified for {$cnpj} (Branch: {$branch})");
            } catch (\Throwable $e) {
                $this->record(false, $suite, "Exception on valid CNPJ {$cnpj}: " . $e->getMessage());
            }
        }

        // 2. Leading Zero Roots (e.g. Banco do Brasil 00.000.000/0001-91)
        $bbData = self::generateValidCnpj('0001', '00000000');
        $bbCnpj = new Cnpj($bbData['cnpj']);
        $this->record($bbCnpj->getValue() === '00000000000191', $suite, 'Banco do Brasil CNPJ strictly matches 00000000000191');
        $this->record($bbCnpj->getRoot() === '00000000', $suite, 'Banco do Brasil root is 00000000');
        $this->record($bbCnpj->isHeadquarters(), $suite, 'Banco do Brasil branch 0001 is headquarters');

        // 3. 5,000 Invalid CNPJs
        for ($i = 0; $i < 5000; $i++) {
            $data = self::generateValidCnpj();
            $validCnpj = $data['cnpj'];
            $mutationType = $i % 5;
            $corrupted = $validCnpj;

            switch ($mutationType) {
                case 0: // Corrupt check digit 1
                    $badD1 = ((int)$validCnpj[12] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCnpj, 0, 12) . $badD1 . $validCnpj[13];
                    break;
                case 1: // Corrupt check digit 2
                    $badD2 = ((int)$validCnpj[13] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCnpj, 0, 13) . $badD2;
                    break;
                case 2: // Corrupt both
                    $badD1 = ((int)$validCnpj[12] + random_int(1, 9)) % 10;
                    $badD2 = ((int)$validCnpj[13] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCnpj, 0, 12) . $badD1 . $badD2;
                    break;
                case 3: // Corrupt root/branch body
                    $pos = random_int(0, 11);
                    $badDigit = ((int)$validCnpj[$pos] + random_int(1, 9)) % 10;
                    $corrupted = substr($validCnpj, 0, $pos) . $badDigit . substr($validCnpj, $pos + 1);
                    break;
                case 4: // Transposition
                    $pos = random_int(0, 10);
                    if ($validCnpj[$pos] !== $validCnpj[$pos + 1]) {
                        $chars = str_split($validCnpj);
                        $temp = $chars[$pos];
                        $chars[$pos] = $chars[$pos + 1];
                        $chars[$pos + 1] = $temp;
                        $corrupted = implode('', $chars);
                    } else {
                        $corrupted = substr($validCnpj, 0, 12) . (((int)$validCnpj[12] + 1) % 10) . $validCnpj[13];
                    }
                    break;
            }

            if (Cnpj::isValid($corrupted)) {
                continue;
            }

            $isValid = Cnpj::isValid($corrupted);
            $threw = false;
            try {
                new Cnpj($corrupted);
            } catch (ValidationException) {
                $threw = true;
            }

            $this->record(!$isValid && $threw, $suite, "Invalid CNPJ rejected by isValid() and constructor: {$corrupted}");
        }

        // 4. Repeated CNPJ sequences (00..00 to 99..99)
        for ($d = 0; $d <= 9; $d++) {
            $repeated = str_repeat((string)$d, 14);
            $isValid = Cnpj::isValid($repeated);
            $threw = false;
            try {
                new Cnpj($repeated);
            } catch (ValidationException) {
                $threw = true;
            }
            $this->record(!$isValid && $threw, $suite, "Repeated CNPJ {$repeated} rejected");
        }

        // 5. Boundary Lengths
        $badCnpjLengths = ['', '1', '12345678', '1234567890123', '123456789012345', str_repeat('8', 120)];
        foreach ($badCnpjLengths as $badLength) {
            $this->record(!Cnpj::isValid($badLength), $suite, "Invalid CNPJ length rejected: len=" . strlen($badLength));
            $threw = false;
            try {
                new Cnpj($badLength);
            } catch (ValidationException) {
                $threw = true;
            }
            $this->record($threw, $suite, "Invalid CNPJ length throws ValidationException: len=" . strlen($badLength));
        }
    }

    // --------------------------------------------------------------------------
    // SUITE 3: FORMATTING MUTATIONS & SANITIZATION STRESS
    // --------------------------------------------------------------------------
    public function stressFormattingMutations(): void
    {
        $suite = 'Formatting & Sanitization Stress';

        $validCpf = self::generateValidCpf();
        $cpfVo = new Cpf($validCpf);

        // Permutations of CPF
        $cpfMutations = [
            "  {$validCpf}  ",
            "\t{$validCpf}\n",
            $cpfVo->getFormatted(),
            str_replace('.', '-', $cpfVo->getFormatted()),
            str_replace(['.', '-'], ' ', $cpfVo->getFormatted()),
            str_replace(['.', '-'], '/', $cpfVo->getFormatted()),
            "..." . $validCpf . "---",
            "CPF: {$validCpf}",
            "Nº {$cpfVo->getFormatted()} (titular)",
        ];

        foreach ($cpfMutations as $mut) {
            $cleaned = Cpf::clean($mut);
            $this->record(
                $cleaned === $validCpf,
                $suite,
                "Cpf::clean correctly extracts digits from mutated input: '{$mut}'"
            );
            $obj = new Cpf($mut);
            $this->record(
                $obj->getValue() === $validCpf,
                $suite,
                "new Cpf() accepts sanitized input: '{$mut}'"
            );
        }

        $cnpjData = self::generateValidCnpj('0002');
        $validCnpj = $cnpjData['cnpj'];
        $cnpjVo = new Cnpj($validCnpj);

        // Permutations of CNPJ
        $cnpjMutations = [
            "  {$validCnpj}  ",
            "\r\n\t{$validCnpj}\t",
            $cnpjVo->getFormatted(),
            str_replace(['.', '/', '-'], '', $cnpjVo->getFormatted()),
            str_replace(['.', '/', '-'], ' ', $cnpjVo->getFormatted()),
            str_replace(['.', '/', '-'], '_', $cnpjVo->getFormatted()),
            "CNPJ: {$validCnpj} Matriz",
            "Filial nº {$cnpjVo->getFormatted()}",
        ];

        foreach ($cnpjMutations as $mut) {
            $cleaned = Cnpj::clean($mut);
            $this->record(
                $cleaned === $validCnpj,
                $suite,
                "Cnpj::clean correctly extracts digits from mutated input: '{$mut}'"
            );
            $obj = new Cnpj($mut);
            $this->record(
                $obj->getValue() === $validCnpj && $obj->getBranch() === '0002' && !$obj->isHeadquarters(),
                $suite,
                "new Cnpj() extracts filial branch 0002 from mutated input: '{$mut}'"
            );
        }

        // Stress with non-ASCII and Unicode characters
        $nonAsciiInput = "529.982.247-25\u{00A0}\u{200B}"; // non-breaking space and zero-width space
        $cleanNonAscii = Cpf::clean($nonAsciiInput);
        $this->record(
            $cleanNonAscii === '52998224725',
            $suite,
            'Cpf::clean strips unicode whitespace characters'
        );
    }

    // --------------------------------------------------------------------------
    // SUITE 4: AUTOLOADER ADVERSARIAL CHALLENGE
    // --------------------------------------------------------------------------
    public function stressAutoloaderAdversarial(): void
    {
        $suite = 'Autoloader Adversarial Stress';
        $autoloader = Autoloader::registerDefault();

        // 1. Path Traversal Attacks
        $traversalAttacks = [
            'HrTech\..\..\etc\passwd',
            'HrTech\..\..\..\..\etc\shadow',
            'HrTech\..\..\..\..\..\..\Windows\win.ini',
            'HrTech\..\..\..\..\..\..\Windows\System32\cmd.exe',
            'HrTech\..\src\Autoloader',
            'HrTech\Domain\ValueObjects\..\..\..\..\..\..\..\..\tmp\pwn',
            'HrTech\foo\..\bar',
            'HrTech\.\Domain\ValueObjects\Cpf',
            "HrTech\x00Domain\ValueObjects\Cpf",
            "HrTech\\Domain\\ValueObjects\\Cpf\0evil",
            'HrTech\.../something',
            '..\..\etc\passwd',
        ];

        foreach ($traversalAttacks as $attack) {
            $result = $autoloader->loadClass($attack);
            $this->record(
                $result === false,
                $suite,
                "Autoloader rejected traversal attack: " . addcslashes($attack, "\0\r\n\t")
            );
        }

        // 2. Case Sensitivity & Preservation
        // In Linux ext4, case sensitivity is strictly enforced.
        $lowercaseClass = 'hrtech\domain\valueobjects\cpf';
        $uppercaseClass = 'HRTECH\DOMAIN\VALUEOBJECTS\CPF';
        $mixedCaseClass = 'HrTech\domain\valueobjects\cpf';

        // None of these should resolve on Linux unless a case-matching file exists
        $this->record(
            $autoloader->loadClass($lowercaseClass) === false,
            $suite,
            'Autoloader preserves case and does not resolve incorrect lowercase namespace'
        );
        $this->record(
            $autoloader->loadClass($uppercaseClass) === false,
            $suite,
            'Autoloader preserves case and does not resolve all-caps namespace'
        );
        $this->record(
            $autoloader->loadClass($mixedCaseClass) === false,
            $suite,
            'Autoloader preserves case and does not resolve mixed-case subnamespace'
        );

        // 3. Deep Namespace Nesting (100 levels)
        $deepNamespace = 'HrTech\\' . implode('\\', array_map(fn($n) => "Level{$n}", range(1, 50))) . '\\TestClass';
        $deepResult = $autoloader->loadClass($deepNamespace);
        $this->record(
            $deepResult === false,
            $suite,
            'Autoloader gracefully handles 50-level deep namespace without stack overflow'
        );

        // 4. Fallback Directories and Non-Existent Classes
        $nonExistent = [
            'HrTech\NonExistentEntity',
            'HrTech\Domain\Entities\NonExistentDomainObject',
            'OtherNamespace\SomeClass',
            'GlobalNonExistentClass',
            '',
            '\\',
            '\\\\',
        ];
        foreach ($nonExistent as $class) {
            $this->record(
                $autoloader->loadClass($class) === false,
                $suite,
                "Autoloader returns false for nonexistent class: '{$class}'"
            );
        }

        // 5. Autoloader Lifecycle: Unregister and Re-register
        $testLoader = new Autoloader();
        $testLoader->addNamespace('HrTech\\Dummy\\', sys_get_temp_dir());
        $this->record(!$testLoader->isRegistered(), $suite, 'New autoloader instance is initially unregistered');
        $testLoader->registerLoader();
        $this->record($testLoader->isRegistered(), $suite, 'Autoloader isRegistered() returns true after registerLoader()');
        $testLoader->unregister();
        $this->record(!$testLoader->isRegistered(), $suite, 'Autoloader isRegistered() returns false after unregister()');

        // 6. Magic __call fallback
        $testLoader->register(); // Calls __call('register', []) -> registerLoader()
        $this->record($testLoader->isRegistered(), $suite, 'Magic __call("register") successfully delegates to registerLoader()');
        $testLoader->unregister();

        // Magic __call non-existent method throws BadMethodCallException
        $threwBadMethod = false;
        try {
            $testLoader->nonExistentMethod();
        } catch (\BadMethodCallException) {
            $threwBadMethod = true;
        }
        $this->record($threwBadMethod, $suite, 'Calling unknown method on Autoloader throws BadMethodCallException');
    }

    // --------------------------------------------------------------------------
    // SUITE 5: EXCEPTION HANDLING HIERARCHY & DATA INTEGRITY
    // --------------------------------------------------------------------------
    public function stressExceptionHandling(): void
    {
        $suite = 'Exception Hierarchy Stress';

        // 1. ValidationException with various shapes of $errors
        $v1 = ValidationException::forField('email', 'Email format is invalid');
        $this->record($v1->getFirstError('email') === 'Email format is invalid', $suite, 'getFirstError with field match');
        $this->record($v1->getFirstError('password') === null, $suite, 'getFirstError with non-existent field returns null');
        $this->record($v1->getFirstError() === 'Email format is invalid', $suite, 'getFirstError without args returns first error');
        $this->record($v1->getCode() === 422, $suite, 'ValidationException default code is 422 HTTP Unprocessable Entity');

        // 2. Empty error array
        $vEmpty = new ValidationException('Custom failure', []);
        $this->record($vEmpty->getFirstError() === 'Custom failure', $suite, 'Empty errors returns message as fallback');

        // 3. Multi-field and nested errors
        $vMulti = ValidationException::withErrors([
            'cpf' => ['CPF check digit mismatch', 'CPF length must be 11'],
            'cnpj' => ['CNPJ root cannot be empty'],
            'metadata' => ['nested' => 'Nested validation error'],
        ]);
        $this->record(count($vMulti->getErrors()) === 3, $suite, 'withErrors preserves all 3 top-level error keys');
        $this->record($vMulti->hasError('cpf'), $suite, 'hasError returns true for existing error key');
        $this->record(!$vMulti->hasError('unknown'), $suite, 'hasError returns false for non-existing error key');
        $this->record($vMulti->getFirstError('cpf') === 'CPF check digit mismatch', $suite, 'getFirstError retrieves first item in array');

        // 4. HrTechException Context Immutability (withContext)
        $baseEx = new HrTechException('Base error', 500, null, ['tenant' => 'acme_corp']);
        try {
            $clonedEx = $baseEx->withContext(['user_id' => 42, 'role' => 'admin']);
            $this->record($baseEx->getContext() === ['tenant' => 'acme_corp'], $suite, 'Original exception context remains immutable');
            $this->record(count($clonedEx->getContext()) === 3, $suite, 'Cloned exception context merged both payloads');
        } catch (\Throwable $e) {
            $this->record(
                false,
                $suite,
                'HrTechException::withContext() allows context enrichment via clone',
                'Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' (PHP Exception is uncloneable)'
            );
        }

        // 5. JSON Serialization
        $json = json_encode($vMulti);
        $decoded = json_decode($json, true);
        $this->record(is_array($decoded), $suite, 'ValidationException successfully serializes to JSON');
        $this->record(isset($decoded['exception']) && $decoded['exception'] === ValidationException::class, $suite, 'JSON contains class name');
        $this->record(isset($decoded['context']['errors']['cpf']), $suite, 'JSON preserves structured error context');
    }

    // --------------------------------------------------------------------------
    // SUITE 6: HIGH-THROUGHPUT & MEMORY BENCHMARK
    // --------------------------------------------------------------------------
    public function benchmarkThroughput(): void
    {
        $suite = 'Performance & Memory Benchmark';
        $iterations = 20000;

        // 1. CPF Validation Throughput
        $t0 = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cpf::isValid('529.982.247-25');
        }
        $tCpf = microtime(true) - $t0;
        $cpfOpsPerSec = $iterations / max($tCpf, 0.00001);

        // 2. CNPJ Validation Throughput
        $t1 = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cnpj::isValid('00.000.000/0001-91');
        }
        $tCnpj = microtime(true) - $t1;
        $cnpjOpsPerSec = $iterations / max($tCnpj, 0.00001);

        // 3. Autoloader Hit Throughput (Repeated calls)
        $autoloader = Autoloader::registerDefault();
        $t2 = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $autoloader->loadClass('HrTech\Domain\ValueObjects\Cpf');
        }
        $tAuto = microtime(true) - $t2;
        $autoOpsPerSec = $iterations / max($tAuto, 0.00001);

        $this->record($cpfOpsPerSec > 10000, $suite, sprintf('CPF throughput: %.0f ops/sec (Threshold: 10,000 ops/sec)', $cpfOpsPerSec));
        $this->record($cnpjOpsPerSec > 10000, $suite, sprintf('CNPJ throughput: %.0f ops/sec (Threshold: 10,000 ops/sec)', $cnpjOpsPerSec));
        $this->record($autoOpsPerSec > 5000, $suite, sprintf('Autoloader resolution: %.0f ops/sec (Threshold: 5,000 ops/sec)', $autoOpsPerSec));

        $peakMemoryMb = memory_get_peak_usage(true) / 1024 / 1024;
        $this->record($peakMemoryMb < 64.0, $suite, sprintf('Peak memory usage: %.2f MB (Threshold: < 64.0 MB)', $peakMemoryMb));
    }

    // --------------------------------------------------------------------------
    // EXECUTION RUNNER & REPORT GENERATOR
    // --------------------------------------------------------------------------
    public function runAll(): array
    {
        echo "\033[1;36m=================================================================\033[0m\n";
        echo "\033[1;36m HRTech Core M1 — Empirical Adversarial Challenge Suite (Tier 5)\033[0m\n";
        echo "\033[1;36m=================================================================\033[0m\n";

        echo "▶ Executing Suite 1: CPF Modulo 11 Stress (5,000 Valid / 5,000 Invalid / Boundaries)...\n";
        $this->stressCpfModulo11();

        echo "▶ Executing Suite 2: CNPJ Modulo 11 Stress (5,000 Valid / 5,000 Invalid / Branches)...\n";
        $this->stressCnpjModulo11();

        echo "▶ Executing Suite 3: Formatting & Mutation Sanitization Stress...\n";
        $this->stressFormattingMutations();

        echo "▶ Executing Suite 4: Autoloader Adversarial Traversal & Resolution...\n";
        $this->stressAutoloaderAdversarial();

        echo "▶ Executing Suite 5: Exception Hierarchy & Data Integrity Stress...\n";
        $this->stressExceptionHandling();

        echo "▶ Executing Suite 6: Performance & Memory Benchmark (20,000 ops/dimension)...\n";
        $this->benchmarkThroughput();

        $elapsed = microtime(true) - $this->startTime;
        $peakMem = memory_get_peak_usage(true) / 1024 / 1024;

        echo "\n\033[1;37m=================================================================\033[0m\n";
        echo "\033[1;37m ADVERSARIAL CHALLENGE EXECUTION SUMMARY\033[0m\n";
        echo "\033[1;37m=================================================================\033[0m\n";
        echo "Total Empirical Tests Run : \033[1;33m{$this->totalTests}\033[0m\n";
        echo "Passed Assertions         : \033[1;32m{$this->passedTests}\033[0m\n";
        echo "Failed Assertions         : " . ($this->failedTests > 0 ? "\033[1;31m{$this->failedTests}\033[0m" : "\033[1;32m0\033[0m") . "\n";
        echo sprintf("Elapsed Wall Time         : %.3f seconds\n", $elapsed);
        echo sprintf("Peak Memory Consumed      : %.2f MB\n", $peakMem);

        if (!empty($this->anomalies)) {
            echo "\n\033[1;31mANOMALIES & FAILURES DETECTED:\033[0m\n";
            foreach (array_slice($this->anomalies, 0, 20) as $anomaly) {
                echo "  - [{$anomaly['suite']}] {$anomaly['description']}: {$anomaly['details']}\n";
            }
            if (count($this->anomalies) > 20) {
                echo "  ... and " . (count($this->anomalies) - 20) . " more anomalies.\n";
            }
        } else {
            echo "\n\033[1;32mRESULT: 100% ADVERSARIAL CHALLENGES PASSED WITH ZERO ANOMALIES!\033[0m\n";
        }

        return [
            'total' => $this->totalTests,
            'passed' => $this->passedTests,
            'failed' => $this->failedTests,
            'elapsed' => $elapsed,
            'peakMemoryMb' => $peakMem,
            'anomalies' => $this->anomalies,
        ];
    }
}

$harness = new EmpiricalAdversarialHarness();
$results = $harness->runAll();
exit($results['failed'] === 0 ? 0 : 1);
