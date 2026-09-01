<?php

namespace Tests\Unit\Domain;

use App\Domain\Accounting\CommissionEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Commission is data-driven (commission_rules), never hardcoded.
 * No 1.5% / 0.5% literals anywhere in the engine.
 */
class CommissionEngineTest extends TestCase
{
    use RefreshDatabase;

    private CommissionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(CommissionEngine::class);

        // NOTE: SQL multi-row insert requires identical column keys per row.
        $base = ['country_iso2' => null, 'transaction_type' => null, 'channel' => null,
            'agent_tier' => null, 'customer_segment' => null, 'min_amount' => null,
            'max_amount' => null, 'rate' => null, 'flat_amount' => null,
            'created_at' => now(), 'updated_at' => now()];

        $rows = [
            // Specific country+type rule wins over generic by priority
            ['name' => 'NE cash-out 1.0%', 'country_iso2' => 'NE', 'transaction_type' => 'cash_out', 'channel' => 'agent', 'rate' => 1.0000, 'priority' => 10, 'is_active' => 1],
            ['name' => 'NG cash-in 0.5% flat 50', 'country_iso2' => 'NG', 'transaction_type' => 'cash_in', 'channel' => 'agent', 'rate' => 0.5000, 'flat_amount' => 50.00, 'priority' => 10, 'is_active' => 1],
            ['name' => 'Global p2p 1.5%', 'transaction_type' => 'p2p', 'rate' => 1.5000, 'priority' => 100, 'is_active' => 1],
            ['name' => 'Inactive rule must not match', 'transaction_type' => 'p2p', 'rate' => 99.0, 'priority' => 1, 'is_active' => 0],
        ];

        DB::table('commission_rules')->insert(array_map(fn ($row) => array_merge($base, $row), $rows));
    }

    public function test_country_specific_rule_wins(): void
    {
        $rule = $this->engine->resolveRule([
            'country_iso2' => 'NE',
            'transaction_type' => 'cash_out',
            'channel' => 'agent',
            'amount' => '10000',
        ]);

        $this->assertNotNull($rule);
        $this->assertSame('NE cash-out 1.0%', $rule->name);
        $this->assertSame('100.00', $this->engine->compute($rule, '10000'));
    }

    public function test_generic_fallback_rule(): void
    {
        $rule = $this->engine->resolveRule([
            'country_iso2' => 'BJ', // Benin — not seeded, falls back to global
            'transaction_type' => 'p2p',
            'amount' => '20000',
        ]);

        $this->assertNotNull($rule);
        $this->assertSame('Global p2p 1.5%', $rule->name);
        $this->assertSame('300.00', $this->engine->compute($rule, '20000'));
    }

    public function test_inactive_rules_never_match(): void
    {
        $rule = $this->engine->resolveRule([
            'transaction_type' => 'p2p',
            'amount' => '500',
        ]);

        $this->assertNotNull($rule);
        $this->assertNotSame('Inactive rule must not match', $rule->name);
    }

    public function test_flat_amount_rule(): void
    {
        $rule = $this->engine->resolveRule([
            'country_iso2' => 'NG',
            'transaction_type' => 'cash_in',
            'channel' => 'agent',
            'amount' => '1000',
        ]);

        $this->assertNotNull($rule);
        $this->assertSame('50.00', $this->engine->compute($rule, '1000'));
    }

    public function test_accrue_creates_commission_entry(): void
    {
        $entry = $this->engine->accrue(
            profile: ['country_iso2' => 'NE', 'transaction_type' => 'cash_out', 'channel' => 'agent', 'amount' => '50000'],
            principal: '50000',
            currency: 'XOF',
            beneficiaryId: 7,
            beneficiaryType: 'agent',
            transactionId: 99,
        );

        $this->assertNotNull($entry);
        // SQLite returns DECIMAL as numeric ('500' vs '500.00'); normalize with bcmath.
        $this->assertSame('500.00', bcadd((string) $entry->amount, '0', 2));
        $this->assertSame('accrued', $entry->status);
        $this->assertSame(7, (int) $entry->beneficiary_id);
        $this->assertSame(99, (int) $entry->transaction_id);
    }

    public function test_no_rule_means_no_commission(): void
    {
        $result = $this->engine->accrue(
            profile: ['transaction_type' => 'unknown_op'],
            principal: '1000',
            currency: 'NGN',
            beneficiaryId: 1,
            beneficiaryType: 'agent',
        );

        $this->assertNull($result);
    }
}
