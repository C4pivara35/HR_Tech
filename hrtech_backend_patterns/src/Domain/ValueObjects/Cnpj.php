<?php

declare(strict_types=1);

namespace HrTech\Domain\ValueObjects;

use HrTech\Exceptions\ValidationException;
use JsonSerializable;
use Stringable;

/**
 * Value Object de CNPJ (Cadastro Nacional da Pessoa Jurídica).
 * Executa a validação oficial do Módulo 11 da Receita Federal do Brasil.
 */
readonly class Cnpj implements Stringable, JsonSerializable
{
    private const WEIGHTS_FIRST_DIGIT = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    private const WEIGHTS_SECOND_DIGIT = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private string $digits;

    /**
     * @throws ValidationException se CNPJ format or mathematical check digits fail
     */
    public function __construct(string $cnpj)
    {
        $cleaned = self::clean($cnpj);
        self::assertValidStructureAndCheckDigits($cleaned, $cnpj);
        $this->digits = $cleaned;
    }

    public static function from(string $cnpj): self
    {
        return new self($cnpj);
    }

    /**
     * Remove todos os caracteres não numéricos da entrada.
     */
    public static function clean(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj) ?? '';
    }

    /**
     * Validates CNPJ without throwing exceptions.
     */
    public static function isValid(string $cnpj): bool
    {
        $cleaned = self::clean($cnpj);

        if (strlen($cleaned) !== 14) {
            return false;
        }

        // Rejection of known invalid repeated sequences
        if (str_repeat($cleaned[0], 14) === $cleaned) {
            return false;
        }

        return self::verifyCheckDigits($cleaned);
    }

    /**
     * Internal assertion throwing domain ValidationException on failure.
     *
     * @throws ValidationException
     */
    private static function assertValidStructureAndCheckDigits(string $cleaned, string $original): void
    {
        $length = strlen($cleaned);
        if ($length !== 14) {
            throw ValidationException::forField(
                'cnpj',
                "CNPJ must contain exactly 14 digits, {$length} provided: '{$original}'."
            );
        }

        if (str_repeat($cleaned[0], 14) === $cleaned) {
            throw ValidationException::forField(
                'cnpj',
                "CNPJ cannot consist of identical repeated digits: '{$original}'."
            );
        }

        if (!self::verifyCheckDigits($cleaned)) {
            throw ValidationException::forField(
                'cnpj',
                "CNPJ verification digits (Modulo 11) failed for: '{$original}'."
            );
        }
    }

    /**
     * Executes Modulo 11 check digits verification.
     */
    private static function verifyCheckDigits(string $digits): bool
    {
        // First check digit
        $sum1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum1 += ((int)$digits[$i]) * self::WEIGHTS_FIRST_DIGIT[$i];
        }
        $remainder1 = $sum1 % 11;
        $expectedDigit1 = ($remainder1 < 2) ? 0 : (11 - $remainder1);
        if ((int)$digits[12] !== $expectedDigit1) {
            return false;
        }

        // Second check digit
        $sum2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum2 += ((int)$digits[$i]) * self::WEIGHTS_SECOND_DIGIT[$i];
        }
        $remainder2 = $sum2 % 11;
        $expectedDigit2 = ($remainder2 < 2) ? 0 : (11 - $remainder2);

        return (int)$digits[13] === $expectedDigit2;
    }

    /**
     * Returns raw 14-digit unformatted string.
     */
    public function getValue(): string
    {
        return $this->digits;
    }

    /**
     * Returns standard formatted string: XX.XXX.XXX/XXXX-XX.
     */
    public function getFormatted(): string
    {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($this->digits, 0, 2),
            substr($this->digits, 2, 3),
            substr($this->digits, 5, 3),
            substr($this->digits, 8, 4),
            substr($this->digits, 12, 2)
        );
    }

    /**
     * Retorna a raiz de 8 dígitos do CNPJ (base da matriz).
     */
    public function getRoot(): string
    {
        return substr($this->digits, 0, 8);
    }

    /**
     * Retorna os 4 dígitos do número de filial do CNPJ.
     */
    public function getBranch(): string
    {
        return substr($this->digits, 8, 4);
    }

    /**
     * Retorna os 2 dígitos verificadores do CNPJ.
     */
    public function getCheckDigits(): string
    {
        return substr($this->digits, 12, 2);
    }

    /**
     * Verifica se este CNPJ representa uma matriz (filial '0001').
     */
    public function isHeadquarters(): bool
    {
        return $this->getBranch() === '0001';
    }

    public function equals(Cnpj $other): bool
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
