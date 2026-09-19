<?php

declare(strict_types=1);

namespace HrTech\Domain\ValueObjects;

use HrTech\Exceptions\ValidationException;
use JsonSerializable;
use Stringable;

/**
 * CPF (Cadastro de Pessoas Físicas) Value Object.
 * Enforces Brazilian Federal Revenue Modulo 11 check-digit validation.
 */
readonly class Cpf implements Stringable, JsonSerializable
{
    private string $digits;

    /**
     * @throws ValidationException if CPF format or check digits fail
     */
    public function __construct(string $cpf)
    {
        $cleaned = self::clean($cpf);
        self::assertValidStructureAndCheckDigits($cleaned, $cpf);
        $this->digits = $cleaned;
    }

    public static function from(string $cpf): self
    {
        return new self($cpf);
    }

    public static function clean(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf) ?? '';
    }

    public static function isValid(string $cpf): bool
    {
        $cleaned = self::clean($cpf);

        if (strlen($cleaned) !== 11) {
            return false;
        }

        if (str_repeat($cleaned[0], 11) === $cleaned) {
            return false;
        }

        return self::verifyCheckDigits($cleaned);
    }

    /**
     * @throws ValidationException
     */
    private static function assertValidStructureAndCheckDigits(string $cleaned, string $original): void
    {
        $length = strlen($cleaned);
        if ($length !== 11) {
            throw ValidationException::forField(
                'cpf',
                "CPF must contain exactly 11 digits, {$length} provided: '{$original}'."
            );
        }

        if (str_repeat($cleaned[0], 11) === $cleaned) {
            throw ValidationException::forField(
                'cpf',
                "CPF cannot consist of identical repeated digits: '{$original}'."
            );
        }

        if (!self::verifyCheckDigits($cleaned)) {
            throw ValidationException::forField(
                'cpf',
                "CPF verification digits (Modulo 11) failed for: '{$original}'."
            );
        }
    }

    private static function verifyCheckDigits(string $digits): bool
    {
        // First check digit
        $sum1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum1 += ((int)$digits[$i]) * (10 - $i);
        }
        $remainder1 = $sum1 % 11;
        $expectedDigit1 = ($remainder1 < 2) ? 0 : (11 - $remainder1);
        if ((int)$digits[9] !== $expectedDigit1) {
            return false;
        }

        // Second check digit
        $sum2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum2 += ((int)$digits[$i]) * (11 - $i);
        }
        $remainder2 = $sum2 % 11;
        $expectedDigit2 = ($remainder2 < 2) ? 0 : (11 - $remainder2);

        return (int)$digits[10] === $expectedDigit2;
    }

    public function getValue(): string
    {
        return $this->digits;
    }

    /**
     * Formatted string: XXX.XXX.XXX-XX.
     */
    public function getFormatted(): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($this->digits, 0, 3),
            substr($this->digits, 3, 3),
            substr($this->digits, 6, 3),
            substr($this->digits, 9, 2)
        );
    }

    /**
     * Anonymized format for LGPD compliance: ***.XXX.XXX-**.
     */
    public function getMasked(): string
    {
        return sprintf(
            '***.%s.%s-**',
            substr($this->digits, 3, 3),
            substr($this->digits, 6, 3)
        );
    }

    public function equals(Cpf $other): bool
    {
        return $this->digits === $other->digits;
    }

    public function __toString(): string
    {
        return $this->getFormatted();
    }

    public function jsonSerialize(): string
    {
        return $this->getFormatted();
    }
}
