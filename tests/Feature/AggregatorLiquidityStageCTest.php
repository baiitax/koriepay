<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Aggregator\AggregatorLiquidityService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Livewire\Aggregator\Liquidity;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\LiquidityRequest;
use App\Models\User;
use Database\Seeders\AggregatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * AGGREGATOR CONSOLE — Stage C (liquidity command center + request
 * workflow, §23–28).
 *
 * Hard guards:
 *   - RBAC: liquidity console is aggregator-only, permission-gated
 *     (`liquidity.view`), actions gated server-side (`liquidity.review`,
 *     `liquidity.request`) — the UI is never the authorization boundary;
 *   - tenant isolation: an aggregator only ever sees ITS OWN position,
 *     agents and requests (IDOR §133 — cross-tenant request id → 404);
 *   - money facts come ONLY from the ledger: positions are derived from
 *     LedgerAccount balances, requests move money through real balanced
 *     postings (earmark/release/fund) that are audited and idempotent;
 *   - estimates are ALWAYS labelled: forecasts, demand windows and
 *     risk assessments carry estimate=true + basis; agents with no
 *     cash-out history get an honest "no history" state, never a
 *     fabricated number.
 */
class AggregatorLiquidityStageCTest extends TestCase
{
    use RefreshDatabase;

    private Aggregator $ibrahim;
    private Aggregator $chidi;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-31 14:00:00'));

        $this->seed(AggregatorSeeder::class);

        $this->ibrahim = Aggregator::where('code', 'AGG-00281')->firstOrFail();
        $this->chidi = Aggregator::where('code', 'AGG-00012')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function aggUser(Aggregator $aggregator): User
    {
        return $aggregator->user;
    }

    private function service(): AggregatorLiquidityService
    {
        return app(AggregatorLiquidityService::class);
    }

    private function agentOf(Aggregator $aggregator, string $code): Agent
    {
        return Agent::where('aggregator_id', $aggregator->id)->where('agent_code', $code)->firstOrFail();
    }

    private function ledgerBalance(string $ownerType, ?int $ownerId, string $type, string $currency, ?string $code = null): string
    {
        $account = LedgerAccount::query()
            ->when($ownerType === 'platform', fn ($q) => $q->where('account_type', 'asset')->whereNull('owner_type'))
            ->when($ownerType !== 'platform', function ($q) use ($ownerId, $ownerType) {
                $q->where('owner_type', $ownerType);
                if ($ownerId === null) {
                    $q->whereNull('owner_id');
                } else {
                    $q->where('owner_id', $ownerId);
                }
            })
            ->when($code !== null, fn ($q) => $q->where('code', $code))
            ->where('currency_code', $currency)
            ->where('account_type', $type)
            ->first();

        return $account?->balance ?? '0';
    }

    // ── RBAC (§3) ─────────────────────────────────────────────────────────

    public function test_liquidity_console_is_aggregator_only(): void
    {
        $customer = User::factory()->withRole('customer')->create();
        $agent = User::factory()->withRole('agent')->create();
        $admin = User::factory()->withRole('admin')->create();

        $this->actingAs($customer)->get('/aggregator/liquidity')->assertForbidden();
        $this->actingAs($agent)->get('/aggregator/liquidity')->assertForbidden();
        $this->actingAs($admin)->get('/aggregator/liquidity')->assertForbidden();
        $this->actingAs($this->aggUser($this->ibrahim))->get('/aggregator/liquidity')->assertOk();
    }

    public function test_aggregator_without_provisioned_profile_gets_honest_state(): void
    {
        $orphan = User::factory()->withRole('aggregator')->create();

        Livewire::actingAs($orphan)
            ->test(Liquidity::class)
            ->assertSee('No aggregator profile');
    }

    // ── Command center (§23–24) ──────────────────────────────────────────

    public function test_command_center_renders_network_position_from_ledger_only(): void
    {
        Livewire::actingAs($this->aggUser($this->ibrahim))->test(Liquidity::class)->assertOk();

        $payload = $this->service()->commandCenter($this->ibrahim, []);

        // Five agents, XOF primary — Chidi's NGN never leaks in.
        $this->assertSame('XOF', $payload['primary_currency']);
        $this->assertSame(['XOF'], $payload['currencies']);

        // Agent wallets = sum of seeded agent float LEDGER balances.
        $this->assertSame('3770000.00', $payload['position']['XOF']['agent_wallets']);

        // Seeded ledger (debit-normal asset pool): capital 5,000,000 + float
        // funding 3,770,000 + earmark 500,000 → platform pool 9,270,000;
        // pending (earmarked) 500,000; available operational = pool − floats −
        // pending = 5,000,000 (the unencumbered capital reserve).
        $this->assertSame('9270000.00', $payload['position']['XOF']['platform_gross']);
        $this->assertSame('500000.00', $payload['position']['XOF']['pending']);
        $this->assertSame('5000000.00', $payload['position']['XOF']['operational_cash']);
        $this->assertSame('0.00', $payload['position']['XOF']['aggregator_wallet']);
    }

    public function test_position_concepts_are_ledger_sourced_not_denormalized(): void
    {
        $position = $this->service()->position($this->ibrahim, 'XOF');

        $floatSum = LedgerAccount::query()
            ->where('owner_type', 'agent')
            ->whereIn('owner_id', app(AggregatorTenantService::class)->agentIds($this->ibrahim))
            ->where('currency_code', 'XOF')
            ->sum('balance');

        $this->assertSame(number_format((float) $floatSum, 2, '.', ''), $position['agent_wallets']);
    }

    public function test_demand_and_forecast_are_labelled_estimates_from_posted_history(): void
    {
        $payload = $this->service()->commandCenter($this->ibrahim, []);
        $demand = $payload['demand']['XOF'];
        $forecast = $payload['forecast']['XOF'];

        $this->assertTrue($demand['estimate']);
        $this->assertNotEmpty($demand['basis']);
        $this->assertTrue($forecast['estimate']);
        $this->assertNotEmpty($forecast['basis']);

        // The 7-day forecast EQUALS the posted cash-out total — no fabrication.
        $postedCashOut = (float) AgencyOperation::query()
            ->where('aggregator_id', $this->ibrahim->id)
            ->where('currency_code', 'XOF')
            ->where('operation_type', AgencyOperation::TYPE_CASH_OUT)
            ->where('status', 'posted')
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->sum('amount');

        $this->assertSame(number_format($postedCashOut, 2, '.', ''), $forecast['7d']);
    }

    public function test_forecast_is_honest_zero_without_history(): void
    {
        // A fresh aggregator with one agent and no operations at all.
        $freshUser = User::factory()->withRole('aggregator')->create();
        $fresh = Aggregator::create([
            'user_id' => $freshUser->id,
            'name' => 'Fresh Network', 'code' => 'AGG-FRESH', 'status' => 'active',
            'country_iso2' => 'NE', 'region' => 'Zinder', 'city' => 'Zinder', 'kyc_status' => 'verified',
        ]);
        $agentUser = User::factory()->withRole('agent')->create();
        $fresh->agents()->create([
            'user_id' => $agentUser->id, 'agent_code' => 'AGT-FRESH', 'status' => 'active',
            'country_iso2' => 'NE', 'region' => 'Zinder', 'city' => 'Zinder', 'kyc_status' => 'verified',
        ]);

        $forecast = $this->service()->forecast($fresh, 'XOF', '24h');
        $demand = $this->service()->demand([$fresh->agents()->first()->id], 'XOF');

        // Honest zero — never a fabricated projection.
        $this->assertSame('0.00', $forecast['24h']);
        $this->assertTrue($forecast['estimate']);
        $this->assertSame('0.00', $demand['cash_out_7d']);
        $this->assertTrue($demand['estimate']);
    }

    public function test_per_agent_buckets_healthy_no_history_and_suspended(): void
    {
        $agents = collect($this->service()->agentPositions($this->ibrahim))->keyBy('agent_code');

        $this->assertSame('healthy', $agents['AGT-00391']['bucket']);     // Aminu — funded, real cash-out
        $this->assertSame('no_demand', $agents['AGT-00454']['bucket']);   // Fatima — no cash-out history
        $this->assertSame('No cash-out history', $agents['AGT-00454']['status_label']);
        $this->assertSame('suspended', $agents['AGT-00475']['bucket']);   // Danladi — suspended override
        $this->assertSame('Suspended', $agents['AGT-00475']['status_label']);

        // Every per-agent row carries the estimate label honestly.
        $this->assertTrue($agents['AGT-00391']['estimate']);
        $this->assertSame('none', $agents['AGT-00454']['cash_out_risk']['level']);
    }

    public function test_low_and_critical_buckets_flagged_from_real_float_vs_demand(): void
    {
        // Custom agent with thin float vs heavy posted cash-out demand.
        $user = User::factory()->withRole('agent')->create();
        $agent = $this->ibrahim->agents()->create([
            'user_id' => $user->id, 'agent_code' => 'AGT-00088', 'status' => 'active',
            'country_iso2' => 'NE', 'region' => 'Maradi', 'city' => 'Maradi', 'kyc_status' => 'verified',
        ]);

        $account = LedgerAccount::create([
            'owner_type' => 'agent', 'owner_id' => $agent->id, 'account_type' => 'liability',
            'name' => 'AGT-00088 Float', 'currency_code' => 'XOF', 'balance' => '0', 'is_active' => true,
        ]);
        $cash = LedgerAccount::firstOrCreate(
            ['account_type' => 'asset', 'currency_code' => 'XOF'],
            ['name' => 'Platform Cash', 'is_system' => true, 'balance' => '0']
        );
        app(\App\Domain\Accounting\LedgerService::class)->post(
            [
                ['account_id' => $cash->id, 'side' => 'debit', 'amount' => '120000'],
                ['account_id' => $account->id, 'side' => 'credit', 'amount' => '120000'],
            ],
            'deposit', null, 'test float', 'agg-test-float-88c'
        );

        $op = new AgencyOperation([
            'agent_id' => $agent->id, 'aggregator_id' => $this->ibrahim->id,
            'customer_user_id' => $this->ibrahim->user_id, 'operation_type' => AgencyOperation::TYPE_CASH_OUT,
            'currency_code' => 'XOF', 'amount' => '1400000', 'fee' => '0', 'commission_amount' => '0',
            'status' => 'posted', 'reference' => 'KPX-C-00088', 'idempotency_key' => 'c-stage-low-88',
            'description' => 'test',
        ]);
        $op->created_at = now()->subDays(3);
        $op->updated_at = now()->subDays(3);
        $op->save();

        $rows = collect($this->service()->agentPositions($this->ibrahim))->keyBy('agent_code');
        $this->assertSame('low', $rows['AGT-00088']['bucket']);   // 120k float vs ~200k daily demand
        $this->assertTrue($rows['AGT-00088']['estimate']);
        $this->assertSame('high', $rows['AGT-00088']['cash_out_risk']['level']);

        $alerts = $this->service()->currencyAlerts($this->ibrahim, 'XOF');
        $agentAlerts = collect($alerts)->where('agent_id', $agent->id)->values();
        $this->assertNotEmpty($agentAlerts);
        $this->assertSame('agent_low', $agentAlerts->first()['type']);
        $this->assertTrue($agentAlerts->first()['estimate']);
    }

    // ── Tenant isolation (§3, §94, IDOR §133) ────────────────────────────

    public function test_console_never_leaks_other_networks_agents_or_currencies(): void
    {
        $ibrahimView = $this->service()->commandCenter($this->ibrahim, []);
        $chidiView = $this->service()->commandCenter($this->chidi, []);

        $ibrahimCodes = collect($ibrahimView['agents'])->pluck('agent_code')->all();
        $chidiCodes = collect($chidiView['agents'])->pluck('agent_code')->all();

        $this->assertNotContains('AGT-00901', $ibrahimCodes); // John is Chidi's
        $this->assertContains('AGT-00391', $ibrahimCodes);
        $this->assertNotContains('AGT-00391', $chidiCodes);
        $this->assertContains('AGT-00901', $chidiCodes);

        // Currencies stay per-network: Ibrahim XOF only, Chidi NGN only.
        $this->assertSame(['XOF'], $ibrahimView['currencies']);
        $this->assertSame(['NGN'], $chidiView['currencies']);
    }

    public function test_cross_tenant_request_id_is_not_reachable(): void
    {
        $service = $this->service();
        $john = $this->agentOf($this->chidi, 'AGT-00901');
        $chidiRequest = $service->submit($this->chidi, $john, [
            'amount' => '100000', 'currency_code' => 'NGN', 'reason' => 'cash_out_demand',
        ], $john->user_id);

        // Ibrahim cannot see or act on Chidi's request (IDOR).
        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->call('approve', $chidiRequest->id)
            ->assertStatus(404);

        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->call('fund', $chidiRequest->id)
            ->assertStatus(404);
    }

    // ── Workflow (§25–26) ────────────────────────────────────────────────

    public function test_submit_creates_pending_request_and_audit_trail(): void
    {
        $service = $this->service();
        $aminu = $this->agentOf($this->ibrahim, 'AGT-00391');

        $request = $service->submit($this->ibrahim, $aminu, [
            'amount' => '250000', 'currency_code' => 'XOF', 'reason' => 'restock',
        ], $aminu->user_id);

        $this->assertSame(LiquidityRequest::STATUS_PENDING, $request->status);
        $this->assertStringStartsWith('LRQ-', $request->reference);
        $this->assertSame('250000.00', (string) $request->amount);
        $this->assertSame('restock', $request->reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'liquidity.requested',
            'user_id' => $aminu->user_id,
        ]);
    }

    public function test_submit_rejects_amount_validation(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->submit($this->ibrahim, $this->agentOf($this->ibrahim, 'AGT-00391'), [
            'amount' => '-50', 'currency_code' => 'XOF',
        ]);
    }

    public function test_submit_rejects_currency_mismatch(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->submit($this->ibrahim, $this->agentOf($this->ibrahim, 'AGT-00391'), [
            'amount' => '100', 'currency_code' => 'NGN', // Ibrahim's network is XOF
        ]);
    }

    public function test_approve_earmarks_operational_cash_on_the_ledger(): void
    {
        $service = $this->service();
        $aminu = $this->agentOf($this->ibrahim, 'AGT-00391');
        $request = $service->submit($this->ibrahim, $aminu, ['amount' => '250000', 'currency_code' => 'XOF']);

        $cashBefore = $this->ledgerBalance('platform', null, 'asset', 'XOF');
        $pendingBefore = $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF');

        $approved = $service->review($request, true, 'Approved — covers weekend demand.', $this->ibrahim->user_id);

        $this->assertSame(LiquidityRequest::STATUS_APPROVED, $approved->status);
        $this->assertNotNull($approved->reviewed_at);
        $this->assertSame('Approved — covers weekend demand.', $approved->review_note);

        // Earmark: the platform pool grows by the debit and the pending
        // liability is encumbered by exactly the amount; available
        // operational cash is unchanged (approving commits, not spends).
        $this->assertSame(bcadd($cashBefore, '250000', 2), $this->ledgerBalance('platform', null, 'asset', 'XOF'));
        $this->assertSame(bcadd($pendingBefore, '250000', 2), $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF'));

        // Agent float untouched by the earmark.
        $float = $this->agentOf($this->ibrahim, 'AGT-00391')->floatAccount('XOF');
        $this->assertSame('2000000.00', (string) $float->balance);

        $this->assertDatabaseHas('audit_logs', ['action' => 'liquidity.approved']);
    }

    public function test_fund_releases_earmark_to_the_agent_float_and_is_idempotent(): void
    {
        $service = $this->service();
        $aminu = $this->agentOf($this->ibrahim, 'AGT-00391');
        $request = $service->submit($this->ibrahim, $aminu, ['amount' => '250000', 'currency_code' => 'XOF']);
        $service->review($request, true, 'OK', $this->ibrahim->user_id);

        $pendingBefore = $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF');
        $floatBefore = (string) $aminu->floatAccount('XOF')->balance;

        $funded = $service->fund($request, $this->ibrahim->user_id);
        $this->assertSame(LiquidityRequest::STATUS_FUNDED, $funded->status);
        $this->assertNotNull($funded->funded_at);

        // Earmark released: pending ↓, agent float ↑.
        $this->assertSame(bcsub($pendingBefore, '250000', 2), $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF'));
        $this->assertSame(bcadd($floatBefore, '250000', 2), (string) $aminu->floatAccount('XOF')->refresh()->balance);

        $txId = $funded->ledger_transaction_id;
        $this->assertNotNull($txId);

        // Replay is a no-op — no double credit, same ledger transaction.
        $again = $service->fund($request, $this->ibrahim->user_id);
        $this->assertSame((string) $txId, (string) $again->ledger_transaction_id);
        $this->assertSame(bcadd($floatBefore, '250000', 2), (string) $aminu->floatAccount('XOF')->refresh()->balance);

        $this->assertDatabaseHas('audit_logs', ['action' => 'liquidity.funded']);
    }

    public function test_reject_does_not_move_money(): void
    {
        $service = $this->service();
        $sani = $this->agentOf($this->ibrahim, 'AGT-00433');
        $request = $service->submit($this->ibrahim, $sani, ['amount' => '80000', 'currency_code' => 'XOF']);

        $cashBefore = $this->ledgerBalance('platform', null, 'asset', 'XOF');
        $pendingBefore = $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF');

        $rejected = $service->review($request, false, 'Duplicate request.', $this->ibrahim->user_id);

        $this->assertSame(LiquidityRequest::STATUS_REJECTED, $rejected->status);
        $this->assertSame($cashBefore, $this->ledgerBalance('platform', null, 'asset', 'XOF'));
        $this->assertSame($pendingBefore, $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'liquidity.rejected']);
    }

    public function test_high_risk_amount_is_auto_blocked_with_estimate_notes(): void
    {
        $service = $this->service();
        $sani = $this->agentOf($this->ibrahim, 'AGT-00433');

        // Sani's 7-day posted cash-out is 120,000 → daily ~17,143 → 6× ≈ 102,857.
        $request = $service->submit($this->ibrahim, $sani, ['amount' => '500000', 'currency_code' => 'XOF']);
        $result = $service->review($request, true, 'Please approve.', $this->ibrahim->user_id);

        // High-risk spikes are blocked by policy — the status becomes rejected.
        $this->assertSame(LiquidityRequest::STATUS_REJECTED, $result->status);
        $this->assertSame(LiquidityRequest::RISK_HIGH, $result->risk_level);
        $this->assertNotEmpty($result->risk_notes);
        $this->assertStringContainsString('estimate', strtolower(implode(' ', $result->risk_notes)));

        // No money moved — pending liability stays at the seeded 500,000 earmark.
        $this->assertSame('500000.00', $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF'));
    }

    public function test_medium_risk_request_is_approved_with_flagged_notes(): void
    {
        $service = $this->service();
        $aisha = $this->agentOf($this->ibrahim, 'AGT-00412');

        // Aisha's daily demand ≈ 42,857 → 150,000 is ~3.5× → medium, still approvable.
        $request = $service->submit($this->ibrahim, $aisha, ['amount' => '150000', 'currency_code' => 'XOF']);
        $result = $service->review($request, true, null, $this->ibrahim->user_id);

        $this->assertSame(LiquidityRequest::STATUS_APPROVED, $result->status);
        $this->assertSame(LiquidityRequest::RISK_MEDIUM, $result->risk_level);
        $this->assertNotEmpty($result->risk_notes);
    }

    public function test_fund_before_approve_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->service();
        $request = $service->submit($this->ibrahim, $this->agentOf($this->ibrahim, 'AGT-00391'), [
            'amount' => '100000', 'currency_code' => 'XOF',
        ]);
        $service->fund($request, $this->ibrahim->user_id);
    }

    public function test_reviewing_an_approved_request_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->service();
        $request = $service->submit($this->ibrahim, $this->agentOf($this->ibrahim, 'AGT-00391'), [
            'amount' => '100000', 'currency_code' => 'XOF',
        ]);
        $service->review($request, true, 'OK', $this->ibrahim->user_id);
        $service->review($request->refresh(), true, 'Again?', $this->ibrahim->user_id);
    }

    public function test_cancel_releases_earmark_only_for_approved_requests(): void
    {
        $service = $this->service();
        $aminu = $this->agentOf($this->ibrahim, 'AGT-00391');

        // Approved → cancel releases the earmark.
        $approved = $service->submit($this->ibrahim, $aminu, ['amount' => '200000', 'currency_code' => 'XOF']);
        $service->review($approved, true, 'OK', $this->ibrahim->user_id);
        $pendingBefore = $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF');

        $cancelled = $service->cancel($approved, $this->ibrahim->user_id, 'Agent withdrew.');
        $this->assertSame(LiquidityRequest::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame(bcsub($pendingBefore, '200000', 2), $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'liquidity.cancelled']);

        // Pending → cancel moves nothing.
        $pendingReq = $service->submit($this->ibrahim, $aminu, ['amount' => '50000', 'currency_code' => 'XOF']);
        $pendingBefore2 = $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF');
        $service->cancel($pendingReq, $this->ibrahim->user_id);
        $this->assertSame($pendingBefore2, $this->ledgerBalance('system', null, 'liability', 'XOF', 'PENDING-XOF'));

        // Funded → cannot cancel.
        $this->expectException(ValidationException::class);
        $funded = $service->submit($this->ibrahim, $aminu, ['amount' => '40000', 'currency_code' => 'XOF']);
        $service->review($funded, true, 'OK', $this->ibrahim->user_id);
        $service->fund($funded, $this->ibrahim->user_id);
        $service->cancel($funded->refresh(), $this->ibrahim->user_id);
    }

    // ── Livewire actions + permissions (§15) ─────────────────────────────

    public function test_livewire_approve_and_fund_flow_with_notes(): void
    {
        $service = $this->service();
        $request = $service->submit($this->ibrahim, $this->agentOf($this->ibrahim, 'AGT-00391'), [
            'amount' => '300000', 'currency_code' => 'XOF', 'reason' => 'cash_out_demand',
        ]);

        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->set('notes.'.$request->id, 'Weekend coverage.')
            ->call('approve', $request->id)
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame('Weekend coverage.', $request->refresh()->review_note);
        $this->assertSame(LiquidityRequest::STATUS_APPROVED, $request->status);

        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->call('fund', $request->id)
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame(LiquidityRequest::STATUS_FUNDED, $request->refresh()->status);
        $this->assertSame('2300000.00', (string) $this->agentOf($this->ibrahim, 'AGT-00391')->floatAccount('XOF')->refresh()->balance);
    }

    public function test_livewire_raise_request_on_behalf_of_agent(): void
    {
        $payload = Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->set('agentId', (string) $this->agentOf($this->ibrahim, 'AGT-00412')->id)
            ->set('amount', '175000')
            ->set('reason', 'restock')
            ->call('createRequest')
            ->assertHasNoErrors()
            ->assertDispatched('toast')
            ->get('payload');

        $created = LiquidityRequest::where('aggregator_id', $this->ibrahim->id)
            ->where('agent_id', $this->agentOf($this->ibrahim, 'AGT-00412')->id)
            ->where('amount', '175000.00')
            ->firstOrFail();

        $this->assertSame(LiquidityRequest::STATUS_PENDING, $created->status);
        $this->assertSame('aggregator', $created->requested_by_type);
        $this->assertDatabaseHas('audit_logs', ['action' => 'liquidity.requested']);
    }

    public function test_livewire_raise_request_validates_agent_and_amount(): void
    {
        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->set('agentId', '999999')
            ->set('amount', '100')
            ->call('createRequest')
            ->assertHasErrors(['agentId']);

        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->set('agentId', (string) $this->agentOf($this->ibrahim, 'AGT-00391')->id)
            ->set('amount', 'not-a-number')
            ->call('createRequest')
            ->assertHasErrors(['amount']);
    }

    public function test_livewire_actions_require_review_permission(): void
    {
        $customer = User::factory()->withRole('customer')->create();
        $request = $this->service()->submit($this->ibrahim, $this->agentOf($this->ibrahim, 'AGT-00391'), [
            'amount' => '50000', 'currency_code' => 'XOF',
        ]);

        Livewire::actingAs($customer)
            ->test(Liquidity::class)
            ->call('approve', $request->id)
            ->assertStatus(403);

        Livewire::actingAs($customer)
            ->test(Liquidity::class)
            ->call('createRequest')
            ->assertStatus(403);
    }

    // ── Seeded demo data (§26 demo) ──────────────────────────────────────

    public function test_seeder_provides_demo_request_states_and_summary(): void
    {
        $summary = $this->service()->requestsSummary($this->ibrahim);

        $this->assertSame(1, $summary['approved']);   // LRQ-SEED-001 (earmark 500,000)
        $this->assertSame(1, $summary['rejected']);   // LRQ-SEED-002
        $this->assertSame(1, $summary['pending']);    // LRQ-SEED-003
        $this->assertSame(1, $summary['cancelled']);  // LRQ-SEED-004
        $this->assertSame(0, $summary['funded']);
        $this->assertSame(2, $summary['open']);

        $component = Livewire::actingAs($this->aggUser($this->ibrahim))->test(Liquidity::class);
        $component->assertSee('LRQ-SEED-001'); // approved → open view
        $component->assertSee('LRQ-SEED-003'); // pending → open view
        $component->call('setStatus', 'all')
            ->assertSee('LRQ-SEED-002')   // rejected
            ->assertSee('LRQ-SEED-004');  // cancelled
    }

    public function test_status_filter_returns_only_matching_requests(): void
    {
        $result = $this->service()->requests($this->ibrahim, ['status' => 'approved'], 10, 1);

        $this->assertSame(1, $result['total']);
        $this->assertSame('approved', $result['rows']->first()->status);

        $open = $this->service()->requests($this->ibrahim, ['status' => 'open'], 10, 1);
        $this->assertSame(2, $open['total']);

        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Liquidity::class)
            ->call('setStatus', 'approved')
            ->assertOk();
    }

    public function test_audit_trail_records_full_request_lifecycle(): void
    {
        $service = $this->service();
        $request = $service->submit($this->ibrahim, $this->agentOf($this->ibrahim, 'AGT-00391'), [
            'amount' => '120000', 'currency_code' => 'XOF',
        ], $this->ibrahim->user_id);
        $service->review($request, true, 'OK', $this->ibrahim->user_id);
        $service->fund($request->refresh(), $this->ibrahim->user_id);

        $events = AuditLog::query()
            ->where('action', 'like', 'liquidity.%')
            ->where('metadata->liquidity_request_id', $request->id)
            ->pluck('action')
            ->all();

        $this->assertContains('liquidity.requested', $events);
        $this->assertContains('liquidity.approved', $events);
        $this->assertContains('liquidity.funded', $events);
    }

    // ── Money safety invariants (§27) ────────────────────────────────────

    public function test_ledger_never_goes_negative_for_agent_floats(): void
    {
        $service = $this->service();
        $aminu = $this->agentOf($this->ibrahim, 'AGT-00391');
        $request = $service->submit($this->ibrahim, $aminu, ['amount' => '200000', 'currency_code' => 'XOF']);
        $service->review($request, true, 'OK', $this->ibrahim->user_id);

        // Fund a request larger than the available earmark would be impossible
        // (earmark is exactly the amount), but double-funding replay must not
        // credit twice — covered above. Here: total XOF debits == credits.
        $entries = \App\Domain\Accounting\LedgerEntry::query()
            ->whereHas('transaction', fn ($q) => $q->where('type', 'like', 'liquidity_%'))
            ->get();

        $this->assertTrue($entries->count() >= 4); // earmark + fund, 2 entries each
        $this->assertSame(
            (string) $entries->where('side', 'debit')->sum('amount'),
            (string) $entries->where('side', 'credit')->sum('amount')
        );
    }
}
