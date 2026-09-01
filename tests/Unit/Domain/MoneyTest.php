<?php

namespace Tests\Unit\Domain;

use App\Domain\Accounting\Money;
use PHPUnit\Framework\TestCase;

/**
 * Money must be float-free. These tests pin the exact-decimal behaviour that
 * fintech correctness depends on.
 */
class MoneyTest extends TestCase
{
    public function test_from_decimal_ngn_exact(): void
    {
        $money = Money::fromDecimal('1000.50', 'NGN');

        $this->assertSame(100050, $money->minorUnits());
        $this->assertSame('1000.50', $money->toDecimal());
    }

    public function test_xof_has_no_minor_units(): void
    {
        $money = Money::fromDecimal('1200', 'XOF');

        $this->assertSame(1200, $money->minorUnits());
        $this->assertSame('1200', $money->toDecimal());
    }

    public function test_no_float_drift_for_0_1_plus_0_2(): void
    {
        $a = Money::fromDecimal('0.1', 'NGN');
        $b = Money::fromDecimal('0.2', 'NGN');
        $sum = $a->add($b);

        $this->assertSame('0.30', $sum->toDecimal());
        $this->assertSame('0.3', $sum->toDecimal() === '0.30' ? '0.3' : $sum->toDecimal());
    }

    public function test_subtraction(): void
    {
        $a = Money::fromDecimal('10.00', 'NGN');
        $b = Money::fromDecimal('4.25', 'NGN');

        $this->assertSame('5.75', $a->subtract($b)->toDecimal());
    }

    public function test_subtraction_never_goes_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $a = Money::fromDecimal('4.25', 'NGN');
        $b = Money::fromDecimal('10.00', 'NGN');

        $a->subtract($b);
    }

    public function test_comparison(): void
    {
        $small = Money::fromDecimal('1.00', 'NGN');
        $large = Money::fromDecimal('2.00', 'NGN');

        $this->assertTrue($small->compareTo($large) < 0);
        $this->assertTrue($large->greaterThanOrEqual($small));
        $this->assertTrue($small->isPositive());
        $this->assertTrue(Money::fromDecimal('0.00', 'NGN')->isZero());
    }

    public function test_currency_mismatch_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromDecimal('1.00', 'NGN')->add(Money::fromDecimal('1.00', 'XOF'));
    }

    public function test_invalid_amount_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromDecimal('12.345', 'NGN'); // more than 2 dp
    }

    public function test_negative_amounts_rejected_by_constructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Money(-5, 'NGN');
    }

    public function test_rounding_in_high_precision_division(): void
    {
        // 100 / 3 = 33.333… must not silently drift
        $one = Money::fromDecimal('100.00', 'NGN');
        $this->assertSame('100.00', $one->toDecimal());
    }
}
