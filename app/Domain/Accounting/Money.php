<?php

namespace App\Domain\Accounting;

use InvalidArgumentException;

/**
 * KoriePay Money value object.
 *
 * Money is ALWAYS an integer count of minor units for the given currency.
 * Arithmetic uses bcmath (string math) so no float drift can ever occur.
 *
 *   NGN (minor_units=2):  ₦1,000.00   → 100000 minor
 *   XOF (minor_units=0):  1,000 FCFA  → 1000 minor
 */
final class Money
{
    private const MINOR_UNITS = [
        'NGN' => 2,
        'XOF' => 0,
        'USD' => 2,
        'GHS' => 2,
        'CAD' => 2,
        'FCFA' => 0,
        'EUR' => 2,
    ];

    public function __construct(
        private readonly int $minorUnits,
        private readonly string $currency,
    ) {
        if ($minorUnits < 0) {
            throw new InvalidArgumentException("Money cannot be negative: {$minorUnits}");
        }
        if ($currency !== strtoupper($currency)) {
            throw new InvalidArgumentException('Currency must be uppercase.');
        }
    }

    public static function fromMinorUnits(int $minorUnits, string $currency): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    /**
     * Build from a decimal string like "1234.50". Parsing is string-based
     * (no floats), so "0.1" + "0.2" stays exact.
     */
    public static function fromDecimal(string $amount, string $currency): self
    {
        $currency = strtoupper($currency);
        $units = self::minorUnitsFor($currency);

        $pattern = $units === 0
            ? '/^-?(\d+)(\.0+)?$/'   // whole units, optional zero fraction
            : '/^-?\d+(\.\d{1,'.$units.'})?$/';

        if (! preg_match($pattern, $amount)) {
            throw new InvalidArgumentException("Invalid decimal amount for {$currency}: {$amount}");
        }

        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '-');

        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '');

        if ($units === 0) {
            // Zero-minor currencies (XOF/FCFA): a ".00"/"00" fraction is just
            // decimal-string formatting and is accepted; any real fraction
            // cannot be represented.
            $minor = ($fraction === '' || (int) $fraction === 0) ? (int) $whole : throw new InvalidArgumentException("{$currency} has no minor units; got {$amount}");
        } else {
            $fraction = str_pad($fraction, $units, '0');
            $minor = ((int) $whole) * 10 ** $units + (int) $fraction;
        }

        return new self($negative ? -$minor : $minor, $currency);
    }

    public function toDecimal(): string
    {
        $units = self::minorUnitsFor($this->currency);
        $abs = abs($this->minorUnits);

        if ($units === 0) {
            $decimal = (string) $abs;
        } else {
            $decimal = substr($abs, 0, -$units) ?: '0';
            $decimal .= '.'.str_pad(substr($abs, -$units), $units, '0');
        }

        return $this->minorUnits < 0 ? '-'.$decimal : $decimal;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->minorUnits > $this->minorUnits) {
            throw new InvalidArgumentException(
                'Subtraction would produce a negative amount (use ledger sides for direction).'
            );
        }

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->minorUnits <=> $other->minorUnits;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    public function minorUnits(): int
    {
        return $this->minorUnits;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public static function minorUnitsFor(string $currency): int
    {
        return self::MINOR_UNITS[strtoupper($currency)] ?? 2;
    }

    public static function supported(string $currency): bool
    {
        return array_key_exists(strtoupper($currency), self::MINOR_UNITS);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
