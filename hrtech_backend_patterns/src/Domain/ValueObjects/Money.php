<?php

declare(strict_types=1);

namespace HrTech\Domain\ValueObjects;

use HrTech\Exceptions\InvalidOperationException;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;
use Stringable;

/**
 * Money Value Object.
 * Immutable fixed-point integer-cents representation for monetary arithmetic.
 */
readonly class Money implements Stringable, JsonSerializable
{
    public int $cents;
    public string $currency;

    /**
     * @throws ValidationException if currency format is invalid
     */
    public function __construct(int $cents, string $currency = 'BRL')
    {
        $normalizedCurrency = strtoupper(trim($currency));
        if (strlen($normalizedCurrency) !== 3) {
            throw ValidationException::forField(
                'currency',
                "Currency code must be a 3-letter ISO 4217 code, '{$currency}' given."
            );
        }

        $this->cents = $cents;
        $this->currency = $normalizedCurrency;
    }

    public static function fromCents(int $cents, string $currency = 'BRL'): self
    {
        return new self($cents, $currency);
    }

    public static function fromFloat(float $amount, string $currency = 'BRL'): self
    {
        return new self((int)round($amount * 100), $currency);
    }

    /**
     * Parses numeric or currency strings (e.g. "1250.50", "R$ 1.250,50", "1.250,50").
     */
    public static function fromString(string $amount, string $currency = 'BRL'): self
    {
        $cleaned = trim($amount);
        $cleaned = str_replace(['R$', '$', '€', ' '], '', $cleaned);

        // Detect Brazilian format "1.250,50" vs standard "1250.50"
        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (str_contains($cleaned, ',')) {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        if (!is_numeric($cleaned)) {
            throw ValidationException::forField('amount', "Invalid monetary string: '{$amount}'.");
        }

        return self::fromFloat((float)$cleaned, $currency);
    }

    public static function zero(string $currency = 'BRL'): self
    {
        return new self(0, $currency);
    }

    public static function brl(float $amount): self
    {
        return self::fromFloat($amount, 'BRL');
    }

    /**
     * Asserts that both Money instances share the exact same currency code.
     *
     * @throws InvalidOperationException
     */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidOperationException(
                "Currency mismatch: cannot perform operation between {$this->currency} and {$other->currency}."
            );
        }
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->cents - $other->cents, $this->currency);
    }

    public function multiply(float|int $multiplier, int $roundingMode = PHP_ROUND_HALF_UP): self
    {
        $resultCents = (int)round($this->cents * $multiplier, 0, $roundingMode);
        return new self($resultCents, $this->currency);
    }

    /**
     * Divides money by a numeric factor.
     *
     * @throws InvalidOperationException on division by zero
     */
    public function divide(float|int $divisor, int $roundingMode = PHP_ROUND_HALF_UP): self
    {
        if ($divisor == 0) {
            throw new InvalidOperationException("Division by zero in Money calculation.");
        }

        $resultCents = (int)round($this->cents / $divisor, 0, $roundingMode);
        return new self($resultCents, $this->currency);
    }

    /**
     * Calculates percentage of this amount (e.g. 10.0 for 10%).
     */
    public function percentage(float $percentage, int $roundingMode = PHP_ROUND_HALF_UP): self
    {
        return $this->multiply($percentage / 100.0, $roundingMode);
    }

    /**
     * Proportional allocation without losing cents (Martin Fowler's Money Pattern).
     * Distributes remaining cents deterministically across the highest ratio components.
     *
     * @param int[] $ratios
     * @return self[]
     * @throws InvalidOperationException if ratio sum is <= 0
     */
    public function allocate(array $ratios): array
    {
        $totalRatio = array_sum($ratios);
        if ($totalRatio <= 0) {
            throw new InvalidOperationException("Allocation ratios sum must be strictly positive.");
        }

        $results = [];
        $remainder = $this->cents;

        // Base shares
        foreach ($ratios as $ratio) {
            $share = (int)floor($this->cents * $ratio / $totalRatio);
            $results[] = $share;
            $remainder -= $share;
        }

        // Distribute remainder cent by cent to first elements
        for ($i = 0; $i < $remainder; $i++) {
            $results[$i]++;
        }

        return array_map(fn(int $cents) => new self($cents, $this->currency), $results);
    }

    public function absolute(): self
    {
        return new self(abs($this->cents), $this->currency);
    }

    public function negate(): self
    {
        return new self(-$this->cents, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency && $this->cents === $other->cents;
    }

    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->cents > $other->cents;
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->cents >= $other->cents;
    }

    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->cents < $other->cents;
    }

    public function lessThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->cents <= $other->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function getCents(): int
    {
        return $this->cents;
    }

    public function toFloat(): float
    {
        return $this->cents / 100.0;
    }

    public function getAmount(): float
    {
        return $this->toFloat();
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Formats according to currency standards.
     */
    public function format(): string
    {
        $absoluteAmount = abs($this->toFloat());
        $formattedNumber = number_format($absoluteAmount, 2, ',', '.');
        $prefix = $this->cents < 0 ? '-' : '';

        if ($this->currency === 'BRL') {
            return $prefix . 'R$ ' . $formattedNumber;
        }

        return $prefix . $this->currency . ' ' . $formattedNumber;
    }

    public function getFormatted(): string
    {
        return $this->format();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'cents' => $this->cents,
            'amount' => $this->toFloat(),
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }
}
