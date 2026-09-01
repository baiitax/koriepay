<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Agency\AgencyService;
use App\Domain\Agency\Exceptions\AgencyNotActiveException;
use App\Domain\Agency\Exceptions\MissingCustomerWalletException;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * PHASE 6 — Agency Banking.
 *
 * Agents + aggregators are a real ledger-integrated distribution network:
 * floats are LIABILITY accounts, every cash-in/cash-out is a balanced,
 * idempotent ledger posting, and commissions accrue through the ledger via
 * configurable rules (never code).
 */
class AgencyBankingTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledger;
    private AgencyService $agency;
    private User $agentUser;
    private User $customer;
    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = app(LedgerService::class);
        $this->agency = app(AgencyService::class);

        $this->agentUser = User::factory()->create(['name' => 'Agent Ada']);
        $this->customer = User::factory()->create(['name' => 'Customer Chinedu']);

        $this->agent = $this->agency->registerAgent($this->agentUser, [
            'country_iso2' => 'NG',
            'region' => 'Kano',
            'city' => 'Kano City',
        ]);
        $this->agency->activateAgent($this->agent);

        $this->ensureCustomerWallet($this->customer, 'NGN', 5000);
        $this->fundAgentFloat($this->agent, 'NGN', 10000);
    }

    // ── Network lifecycle ─────────────────────────────────────────────────

    public function test_agent_registration_provisions_profile_and_ledger(): void
    {
        $newUser = User::factory()->create(['name' => 'Fresh Agent']);
        $fresh = $this->agency->registerAgent($newUser, [
            'country_iso2' => 'NG',
            'region' => 'Lagos',
            'city' => 'Ikeja',
        ]);

        $this->assertSame('pending', $fresh->status);
        $this->assertStringStartsWith('AGT-', $fresh->agent_code);
        $this->assertSame('NG', $fresh->country_iso2);
        $this->assertSame('agent', $newUser->fresh()->role, 'role assignment must be explicit');

        // Float + commission accrual ledger accounts (liability, owner agent).
        $this->assertNotNull($fresh->floatAccount('NGN'));
        $this->assertSame('liability', $fresh->floatAccount('NGN')->account_type);

        $accrual = LedgerAccount::query()
            ->where('owner_type', 'agent')->where('owner_id', $fresh->id)
            ->where('name', 'Agent Commission Accrual')->where('currency_code', 'NGN')
            ->first();
        $this->assertNotNull($accrual, 'commission accrual account must be provisioned at registration');
    }

    public function test_agent_status_transitions_are_audited(): void
    {
        $this->assertSame('active', $this->agent->fresh()->status);

        $this->agency->suspendAgent($this->agent, actorId: null, reason: 'Compliance review');
        $this->assertSame('suspended', $this->agent->fresh()->status);

        $this->agency->reactivateAgent($this->agent);
        $this->assertSame('active', $this->agent->fresh()->status);

        $actions = AuditLog::query()
            ->where('action', 'like', 'agent.%')
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertSame(
            ['agent.registered', 'agent.activated', 'agent.suspended', 'agent.reactivated'],
            $actions,
            'every lifecycle transition must be audited in order'
        );
    }

    public function test_illegal_agent_transition_is_rejected(): void
    {
        $this->expectException(\DomainException::class);
        $this->agency->activateAgent($this->agent); // already active
    }

    // ── Cash-in / cash-out (ledger-sourced) ───────────────────────────────

    public function test_cash_in_moves_float_to_customer_wallet(): void
    {
        $op = $this->agency->cashIn($this->agent, $this->customer, '2000.00', 'NGN', 'ag-cashin-1');

        $this->assertSame('posted', $op->status);
        $this->assertSame('cash_in', $op->operation_type);
        $this->assertStringStartsWith('AGY-', $op->reference);

        // Float 10,000 − 2,000; wallet 5,000 + 2,000.
        $this->assertSame('8000.00', bcadd((string) $this->agent->floatAccount('NGN')->balance, '0', 2));
        $this->assertSame('7000.00', bcadd((string) $this->customerWallet()->balance, '0', 2));
        $this->assertLedgerBalanced('NGN');
    }

    public function test_cash_out_reverses_and_fails_atomically(): void
    {
        $this->agency->cashOut($this->agent, $this->customer, '3000.00', 'NGN', 'ag-cashout-1');

        // Wallet 5,000 − 3,000; float 10,000 + 3,000.
        $this->assertSame('2000.00', bcadd((string) $this->customerWallet()->balance, '0', 2));
        $this->assertSame('13000.00', bcadd((string) $this->agent->floatAccount('NGN')->balance, '0', 2));

        // Insufficient customer funds → atomic failure, balances unchanged.
        try {
            $this->agency->cashOut($this->agent, $this->customer, '999999.00', 'NGN', 'ag-cashout-2');
            $this->fail('insufficient funds must throw');
        } catch (\App\Domain\Accounting\Exceptions\InsufficientFundsException $e) {
            // expected
        }

        $this->assertSame('2000.00', bcadd((string) $this->customerWallet()->balance, '0', 2));
        $this->assertSame('13000.00', bcadd((string) $this->agent->floatAccount('NGN')->balance, '0', 2));
        $this->assertLedgerBalanced('NGN');

        // The failed attempt is recorded for metrics (never double-posted).
        $failed = AgencyOperation::query()
            ->where('idempotency_key', 'ag-cashout-2')
            ->first();
        $this->assertNotNull($failed);
        $this->assertSame('failed', $failed->status);
    }

    public function test_inactive_agent_cannot_operate(): void
    {
        $this->agency->suspendAgent($this->agent);

        $this->expectException(AgencyNotActiveException::class);
        $this->agency->cashIn($this->agent, $this->customer, '100.00', 'NGN', 'ag-suspended-op');
    }

    public function test_missing_customer_wallet_fails_loudly(): void
    {
        $noWallet = User::factory()->create(['name' => 'No Wallet']);

        $this->expectException(MissingCustomerWalletException::class);
        $this->agency->cashIn($this->agent, $noWallet, '100.00', 'NGN', 'ag-no-wallet');
    }

    public function test_agency_operation_is_idempotent(): void
    {
        $first = $this->agency->cashIn($this->agent, $this->customer, '500.00', 'NGN', 'ag-idem');
        $replay = $this->agency->cashIn($this->agent, $this->customer, '500.00', 'NGN', 'ag-idem');

        $this->assertSame($first->id, $replay->id, 'replay must return the original operation');
        $this->assertSame(1, AgencyOperation::where('idempotency_key', 'ag-idem')->count());
        $this->assertSame('5500.00', bcadd((string) $this->customerWallet()->balance, '0', 2),
            'cash-in must not be applied twice');
    }

    // ── Commission engine ─────────────────────────────────────────────────

    public function test_commission_resolves_and_accrues_through_ledger(): void
    {
        CommissionRule::create([
            'name' => 'NG agent cash-in flat',
            'country_iso2' => 'NG',
            'transaction_type' => 'cash_in',
            'channel' => 'agent',
            'agent_tier' => 'bronze',
            'flat_amount' => '50.00',
            'priority' => 10,
        ]);

        $op = $this->agency->cashIn($this->agent, $this->customer, '1000.00', 'NGN', 'ag-comm-1');

        $this->assertSame('50.00', $op->commission_amount);

        // Commission entry accrued.
        $entry = CommissionEntry::query()
            ->where('beneficiary_type', 'agent')
            ->where('beneficiary_id', $this->agent->id)
            ->first();
        $this->assertNotNull($entry);
        $this->assertSame('accrued', $entry->status);
        $this->assertSame('50.00', (string) $entry->amount);

        // Ledger: expense debited, agent accrual credited.
        $expense = LedgerAccount::query()
            ->where('account_type', 'expense')->where('name', 'Commission Expense')
            ->where('currency_code', 'NGN')->first();
        $this->assertNotNull($expense);
        $this->assertSame('50.00', bcadd((string) $expense->balance, '0', 2));

        $accrual = LedgerAccount::query()
            ->where('owner_type', 'agent')->where('owner_id', $this->agent->id)
            ->where('name', 'Agent Commission Accrual')->where('currency_code', 'NGN')
            ->first();
        $this->assertSame('50.00', bcadd((string) $accrual->balance, '0', 2));
        $this->assertLedgerBalanced('NGN');
    }

    public function test_flat_commission_preferred_over_rate(): void
    {
        CommissionRule::create([
            'name' => 'Flat+rate rule',
            'country_iso2' => 'NG',
            'transaction_type' => 'cash_in',
            'channel' => 'agent',
            'rate' => '10.0000',      // would be 100 on 1,000
            'flat_amount' => '25.00', // must win
            'priority' => 5,
        ]);

        $this->agency->cashIn($this->agent, $this->customer, '1000.00', 'NGN', 'ag-comm-2');

        $entry = CommissionEntry::query()->first();
        $this->assertSame('25.00', (string) $entry->amount);
    }

    // ── Aggregators ───────────────────────────────────────────────────────

    public function test_aggregator_onboarding_and_agent_assignment(): void
    {
        $aggUser = User::factory()->create(['name' => 'Agg Aisha']);
        $aggregator = $this->agency->registerAggregator([
            'name' => 'Kano Network Ltd',
            'country_iso2' => 'NG',
            'region' => 'Kano',
        ], $aggUser);
        $this->agency->activateAggregator($aggregator);

        $this->assertStringStartsWith('AGG-', $aggregator->code);
        $this->assertSame('active', $aggregator->fresh()->status);
        $this->assertSame('aggregator', $aggUser->fresh()->role);
        $this->assertNotNull($aggregator->floatAccount('NGN'));

        $this->agency->assignAgentToAggregator($this->agent, $aggregator);
        $this->assertSame($aggregator->id, $this->agent->fresh()->aggregator_id);

        $this->assertDatabaseHas('audit_logs', ['action' => 'agent.assigned.aggregator']);
    }

    // ── RBAC ──────────────────────────────────────────────────────────────

    public function test_agency_management_permissions_are_enforced(): void
    {
        $admin = User::factory()->create(['name' => 'Admin']);
        $admin->forceFill(['role' => 'admin'])->save();

        $customerRole = User::factory()->create(['name' => 'Plain']);
        $customerRole->forceFill(['role' => 'customer'])->save();

        $this->assertTrue(Gate::forUser($admin)->allows('agency.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('agent.approve'));
        $this->assertFalse(Gate::forUser($customerRole)->allows('agency.manage'));
        $this->assertFalse(Gate::forUser($customerRole)->allows('agent.approve'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function ensureCustomerWallet(User $user, string $currency, string $amount): void
    {
        $wallet = LedgerAccount::create([
            'account_type' => 'liability', 'currency_code' => $currency,
            'name' => $user->name.' Wallet', 'owner_type' => 'user', 'owner_id' => $user->id,
        ]);

        $cash = LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => $currency,
            'name' => 'Platform Cash', 'is_system' => true,
        ]);

        $this->ledger->post(
            [
                ['account_id' => $cash->id, 'side' => 'debit', 'amount' => $amount],
                ['account_id' => $wallet->id, 'side' => 'credit', 'amount' => $amount],
            ],
            'opening_balance', 'OPEN-CUST-'.$user->id
        );
    }

    private function fundAgentFloat(Agent $agent, string $currency, string $amount): void
    {
        $float = $agent->floatAccount($currency);
        $cash = LedgerAccount::create([
            'account_type' => 'asset', 'currency_code' => $currency,
            'name' => 'Platform Cash', 'is_system' => true,
        ]);

        $this->ledger->post(
            [
                ['account_id' => $cash->id, 'side' => 'debit', 'amount' => $amount],
                ['account_id' => $float->id, 'side' => 'credit', 'amount' => $amount],
            ],
            'float_funding', 'OPEN-FLOAT-'.$agent->id
        );
    }

    private function customerWallet(): LedgerAccount
    {
        return LedgerAccount::query()
            ->where('owner_type', 'user')->where('owner_id', $this->customer->id)
            ->where('currency_code', 'NGN')->firstOrFail();
    }

    private function assertLedgerBalanced(string $currency): void
    {
        $rows = DB::table('ledger_entries')
            ->selectRaw('currency_code, side, SUM(amount) as total')
            ->groupBy('currency_code', 'side')
            ->get()
            ->groupBy('currency_code');

        foreach ($rows as $cur => $legs) {
            if ($cur !== $currency) {
                continue;
            }
            $debits = (string) ($legs->firstWhere('side', 'debit')->total ?? 0);
            $credits = (string) ($legs->firstWhere('side', 'credit')->total ?? 0);
            $this->assertSame($debits, $credits, "Σ debits must equal Σ credits for {$cur}");
        }
    }
}
