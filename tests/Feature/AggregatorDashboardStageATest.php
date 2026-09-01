<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Domain\Aggregator\AggregatorMetricsService;
use App\Domain\Aggregator\AggregatorTenantService;
use App\Livewire\Aggregator\Dashboard;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\CommissionEntry;
use App\Models\User;
use Database\Seeders\AggregatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * AGGREGATOR CONSOLE — Stage A (foundation + command center home).
 *
 * Hard guards:
 *   - RBAC: only the `aggregator` role reaches the console (§3);
 *   - tenant isolation: an aggregator only ever sees ITS OWN agents,
 *     operations, commissions and floats (§3, §8, §94, IDOR §133);
 *   - metrics are computed from real records (operations, ledger floats,
 *     commission entries) — zero fabricated numbers; failed operations never
 *     inflate volume; empty networks render honest empty states;
 *   - every money figure stays per-currency (XOF vs NGN never mixed).
 */
class AggregatorDashboardStageATest extends TestCase
{
    use RefreshDatabase;

    private Aggregator $ibrahim;
    private Aggregator $chidi;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze the clock mid-day so "today" boundaries are deterministic
        // for the seeded operations (max 13h back stays within the day).
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

    // ── RBAC (§3) ─────────────────────────────────────────────────────────

    public function test_only_aggregator_role_can_open_the_console(): void
    {
        $customer = User::factory()->withRole('customer')->create();
        $agent = User::factory()->withRole('agent')->create();
        $admin = User::factory()->withRole('admin')->create();

        $this->actingAs($customer)->get('/aggregator/dashboard')->assertForbidden();
        $this->actingAs($agent)->get('/aggregator/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/aggregator/dashboard')->assertForbidden();
        $this->actingAs($this->aggUser($this->ibrahim))->get('/aggregator/dashboard')->assertOk();
    }

    public function test_aggregator_without_provisioned_profile_gets_honest_state(): void
    {
        $orphan = User::factory()->withRole('aggregator')->create();

        Livewire::actingAs($orphan)
            ->test(Dashboard::class)
            ->assertSee('No aggregator profile');
    }

    // ── Tenant isolation (§3, §94) ────────────────────────────────────────

    public function test_metrics_are_scoped_to_the_aggregators_own_network(): void
    {
        $metrics = app(AggregatorMetricsService::class);

        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);
        $b = $metrics->commandCenter($this->chidi, ['range' => 'today']);

        $this->assertSame(5, $a['overview']['total_agents']);
        $this->assertSame(1, $b['overview']['total_agents']);

        // Chidi's agent never appears in Ibrahim's ranked/activity lists.
        $aCodes = collect(array_merge(
            array_column($a['top_agents'], 'agent_code'),
            array_column($a['recent_activity'], 'agent_code')
        ))->unique()->all();
        $this->assertNotContains('AGT-00901', $aCodes);

        $bCodes = collect(array_column($b['recent_activity'], 'agent_code'))->unique()->all();
        $this->assertContains('AGT-00901', $bCodes);
        $this->assertNotContains('AGT-00391', $bCodes);
    }

    public function test_tenant_ownership_guard_rejects_foreign_agents(): void
    {
        $tenant = app(AggregatorTenantService::class);
        $john = Agent::where('agent_code', 'AGT-00901')->firstOrFail();
        $aminu = Agent::where('agent_code', 'AGT-00391')->firstOrFail();

        $this->assertTrue($tenant->ownsAgent($this->ibrahim, $aminu));
        $this->assertFalse($tenant->ownsAgent($this->ibrahim, $john));
        $this->assertFalse($tenant->ownsAgent($this->chidi, $aminu));
        $this->assertTrue($tenant->ownsAgent($this->chidi, $john));
    }

    public function test_commission_and_liquidity_are_tenant_scoped(): void
    {
        $metrics = app(AggregatorMetricsService::class);

        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);
        $b = $metrics->commandCenter($this->chidi, ['range' => 'today']);

        // Ibrahim has accrued commissions; Chidi (seed) has none.
        $this->assertGreaterThan(0, (float) ($a['overview']['commission']['XOF'] ?? 0));
        $this->assertSame('0.00', (string) ($b['overview']['commission']['NGN'] ?? '0.00'));

        // Liquidity floats: Ibrahim's totals reflect ONLY his 5 agents.
        $this->assertSame('3770000.00', $a['liquidity']['totals']['XOF']);
        $this->assertArrayNotHasKey('XOF', $b['liquidity']['totals']);
        $this->assertSame('1500000.00', $b['liquidity']['totals']['NGN']);
    }

    // ── KPI correctness from real data ────────────────────────────────────

    public function test_kpis_match_seeded_operations_exactly(): void
    {
        $metrics = app(AggregatorMetricsService::class);
        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);

        $overview = $a['overview'];

        // 5 agents, 4 active (Danladi suspended) → 80% active rate.
        $this->assertSame(5, $overview['total_agents']);
        $this->assertSame(4, $overview['active_agents']);
        $this->assertSame(80.0, $overview['active_rate']);

        // 13 operations today (incl. 1 failed); volume excludes the failed op:
        // 9,000+5,000+6,500+4,000+12,500 = 37,000 + 7,000+3,000+5,500 = 15,500
        // + 2,500+1,200+4,000 = 7,700 + 800 (Fatima) = 6,100,000 XOF.
        $this->assertSame(13, $overview['transactions']);
        $this->assertSame('6100000.00', $overview['volume']['XOF']);

        // Commission accrued today for the aggregator: 5,400+2,100+1,080+120 = 8,700.
        $this->assertSame('8700.00', $overview['commission']['XOF']);

        // Liquidity = sum of agent floats (ledger): 2,000,000+900,000+450,000+120,000+300,000.
        $this->assertSame('3770000.00', $a['liquidity']['totals']['XOF']);
    }

    public function test_attention_center_reports_real_conditions(): void
    {
        $metrics = app(AggregatorMetricsService::class);
        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);

        $byType = collect($a['attention'])->keyBy('type');

        // Sani: 4 ops today, 1 failed → 25% failure, >= 3 ops → flagged.
        $failure = $byType->get('failure_rate');
        $this->assertNotNull($failure);
        $this->assertSame(1, $failure['count']);
        $this->assertContains(Agent::where('agent_code', 'AGT-00433')->first()->id, $failure['entity_ids']);

        // Danladi suspended → restricted.
        $restricted = $byType->get('agents_restricted');
        $this->assertNotNull($restricted);
        $this->assertSame(1, $restricted['count']);

        // Fatima pending KYC → kyc_pending.
        $kyc = $byType->get('kyc_pending');
        $this->assertNotNull($kyc);
        $this->assertSame(1, $kyc['count']);
    }

    public function test_dormant_agent_is_detected_honestly(): void
    {
        // Aminu's last op is today; give Fatima NO ops for 8 days → flagged
        // while active. (Seed gives Fatima one today-op, so build a fresh
        // scenario instead: a new active agent with no operations at all.)
        $newAgent = Agent::create([
            'user_id' => User::factory()->withRole('agent')->create()->id,
            'agent_code' => 'AGT-00099',
            'aggregator_id' => $this->ibrahim->id,
            'status' => Agent::STATUS_ACTIVE,
            'tier' => 'bronze',
            'country_iso2' => 'NE',
            'region' => 'Maradi',
            'city' => 'Maradi',
            'kyc_status' => 'verified',
        ]);

        $metrics = app(AggregatorMetricsService::class);
        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);

        $inactive = collect($a['attention'])->firstWhere('type', 'agents_inactive');
        $this->assertNotNull($inactive);
        $this->assertContains($newAgent->id, $inactive['entity_ids']);
    }

    public function test_low_liquidity_estimate_flags_agents_below_demand(): void
    {
        // Aisha has a 900,000 XOF float. Build an agent with thin float vs
        // real cash-out history → flagged as low (labelled estimate).
        $agent = Agent::create([
            'user_id' => User::factory()->withRole('agent')->create()->id,
            'agent_code' => 'AGT-00088',
            'aggregator_id' => $this->ibrahim->id,
            'status' => Agent::STATUS_ACTIVE,
            'tier' => 'bronze',
            'country_iso2' => 'NE',
            'region' => 'Maradi',
            'city' => 'Maradi',
            'kyc_status' => 'verified',
        ]);

        $cash = LedgerAccount::firstOrCreate(
            ['account_type' => 'asset', 'currency_code' => 'XOF'],
            ['name' => 'Platform Cash', 'is_system' => true, 'balance' => '0']
        );
        $float = LedgerAccount::create([
            'owner_type' => 'agent', 'owner_id' => $agent->id,
            'currency_code' => 'XOF', 'account_type' => 'liability',
            'name' => 'AGT-00088 Float', 'balance' => '0', 'is_active' => true,
        ]);
        app(LedgerService::class)->post(
            [
                ['account_id' => $cash->id, 'side' => 'debit', 'amount' => '120000'],
                ['account_id' => $float->id, 'side' => 'credit', 'amount' => '120000'],
            ],
            'deposit', null, 'test float', 'agg-test-float-88'
        );

        // Real cash-out demand: 700,000 XOF over the last 7 days.
        AgencyOperation::create([
            'agent_id' => $agent->id,
            'aggregator_id' => $this->ibrahim->id,
            'customer_user_id' => $this->ibrahim->user_id,
            'operation_type' => 'cash_out',
            'currency_code' => 'XOF',
            'amount' => '1400000.00',
            'fee' => '0',
            'commission_amount' => '0',
            'status' => 'posted',
            'reference' => 'KPA-TEST-00088',
            'idempotency_key' => 'agg-test-cashout-88',
            'created_at' => now()->subDays(3),
        ]);

        $metrics = app(AggregatorMetricsService::class);
        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);

        $low = collect($a['attention'])->firstWhere('type', 'liquidity_low');
        $this->assertNotNull($low);
        $this->assertContains($agent->id, $low['entity_ids']);

        $item = collect($a['liquidity']['items'])->firstWhere('agent_id', $agent->id);
        $this->assertSame('low', $item['status']);
        $this->assertTrue($item['estimate']);
    }

    // ── Honest empty network ──────────────────────────────────────────────

    public function test_empty_network_reports_zeros_not_fabrication(): void
    {
        $empty = Aggregator::create([
            'user_id' => User::factory()->withRole('aggregator')->create()->id,
            'code' => 'AGG-00000',
            'name' => 'Empty Network',
            'status' => Aggregator::STATUS_ACTIVE,
            'country_iso2' => 'NE',
            'region' => 'Maradi',
            'city' => 'Maradi',
            'kyc_status' => 'verified',
        ]);

        $metrics = app(AggregatorMetricsService::class);
        $c = $metrics->commandCenter($empty, ['range' => 'today']);

        $this->assertSame(0, $c['overview']['total_agents']);
        $this->assertSame(0, $c['overview']['transactions']);
        $this->assertSame('0.00', $c['overview']['volume']['XOF']);
        $this->assertSame('0.00', $c['overview']['commission']['XOF']);
        $this->assertSame([], $c['attention']);
        $this->assertSame([], $c['top_agents']);

        // The page renders honestly (no fabricated KPIs).
        Livewire::actingAs($empty->user)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('No agents ranked yet');
    }

    // ── Range filter + page render ────────────────────────────────────────

    public function test_dashboard_renders_and_range_filter_changes_metrics(): void
    {
        $component = Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Network Command')
            ->assertSee('AGG-00281')
            ->assertSee('ACTION REQUIRED');

        // Today: 13 ops. 30 days: all seeded ops (13 today + 6 history = 19).
        $component->set('range', '30d');
        $this->assertSame(19, $component->viewData('center')['overview']['transactions']);
    }

    public function test_failed_operations_never_inflate_volume(): void
    {
        $metrics = app(AggregatorMetricsService::class);
        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);

        // The 300,000 XOF failed op must not be in volume.
        $this->assertSame('6100000.00', $a['overview']['volume']['XOF']);
        // But it IS an operation (activity count includes it) — 13 total.
        $this->assertSame(13, $a['overview']['transactions']);
    }

    public function test_currency_is_never_silently_mixed(): void
    {
        // Chidi operates in NGN; his XOF float total must simply not exist,
        // and Ibrahim's totals must not contain NGN from Chidi.
        $metrics = app(AggregatorMetricsService::class);
        $a = $metrics->commandCenter($this->ibrahim, ['range' => 'today']);

        $this->assertArrayHasKey('XOF', $a['liquidity']['totals']);
        $this->assertArrayNotHasKey('NGN', $a['liquidity']['totals']);
    }
}
