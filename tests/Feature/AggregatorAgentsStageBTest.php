<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Aggregator\AggregatorAgentsService;
use App\Domain\Agency\AgencyService;
use App\Livewire\Aggregator\AgentProfile;
use App\Livewire\Aggregator\Agents;
use App\Livewire\Aggregator\RecruitAgent;
use App\Models\AgencyOperation;
use App\Models\Agent;
use App\Models\Aggregator;
use App\Models\AuditLog;
use App\Models\CommissionEntry;
use App\Models\KycSubmission;
use App\Models\RiskAlert;
use App\Models\User;
use Database\Seeders\AggregatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * AGGREGATOR CONSOLE — Stage B (agents directory + profile, §14–22).
 *
 * Hard guards:
 *   - RBAC: directory/profile/recruit are permission-gated server-side;
 *   - tenant isolation: an aggregator only ever sees ITS OWN agents and
 *     their data (IDOR §133 — foreign agents 404);
 *   - money stays per-currency and ledger-sourced;
 *   - performance score is explainable and honestly null on no signal;
 *   - dormancy is measured from posted operations and its projection is a
 *     labelled estimate;
 *   - recruitment captures ONLY — pending until KYC, no activation;
 *   - status changes go through AgencyService (backend), not frontend writes.
 */
class AggregatorAgentsStageBTest extends TestCase
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

    private function agent(Aggregator $aggregator, string $code): Agent
    {
        return Agent::where('aggregator_id', $aggregator->id)->where('agent_code', $code)->firstOrFail();
    }

    // ── RBAC (§3, §83) ──────────────────────────────────────────────────

    public function test_only_aggregator_role_reaches_the_agents_directory(): void
    {
        $customer = User::factory()->withRole('customer')->create();
        $agent = User::factory()->withRole('agent')->create();
        $admin = User::factory()->withRole('admin')->create();

        $this->actingAs($customer)->get('/aggregator/agents')->assertForbidden();
        $this->actingAs($agent)->get('/aggregator/agents')->assertForbidden();
        $this->actingAs($admin)->get('/aggregator/agents')->assertForbidden();
        $this->actingAs($this->aggUser($this->ibrahim))->get('/aggregator/agents')->assertOk();
    }

    public function test_recruit_route_is_permission_gated(): void
    {
        $customer = User::factory()->withRole('customer')->create();
        $this->actingAs($customer)->get('/aggregator/agents/recruit')->assertForbidden();
        $this->actingAs($this->aggUser($this->ibrahim))->get('/aggregator/agents/recruit')->assertOk();
    }

    public function test_aggregator_without_profile_gets_honest_empty_state(): void
    {
        $orphan = User::factory()->withRole('aggregator')->create();

        $this->actingAs($orphan)->get('/aggregator/agents')
            ->assertOk()->assertSee('No aggregator profile');
    }

    // ── Directory (§14) ─────────────────────────────────────────────────

    public function test_directory_lists_only_own_agents(): void
    {
        $payload = app(AggregatorAgentsService::class)->directory($this->ibrahim, []);
        $codes = collect($payload['paginator']->items())->map(fn ($r) => $r['agent']->agent_code)->all();

        $this->assertSame(5, $payload['total']);
        $this->assertEqualsCanonicalizing(
            ['AGT-00391', 'AGT-00412', 'AGT-00433', 'AGT-00454', 'AGT-00475'],
            $codes
        );

        // Chidi's network is isolated — his one agent is never visible to Ibrahim.
        $chidiPayload = app(AggregatorAgentsService::class)->directory($this->chidi, []);
        $this->assertSame(1, $chidiPayload['total']);
        $this->assertSame('AGT-00901', $chidiPayload['paginator']->items()[0]['agent']->agent_code);
    }

    public function test_directory_status_filter(): void
    {
        $payload = app(AggregatorAgentsService::class)->directory($this->ibrahim, ['status' => 'suspended']);
        $this->assertSame(1, $payload['total']);
        $this->assertSame('AGT-00475', $payload['paginator']->items()[0]['agent']->agent_code);
    }

    public function test_directory_search_by_code_name_and_region(): void
    {
        $byCode = app(AggregatorAgentsService::class)->directory($this->ibrahim, ['search' => 'AGT-00412']);
        $this->assertSame(1, $byCode['total']);
        $this->assertSame('Aisha Bello', $byCode['paginator']->items()[0]['name']);

        $byName = app(AggregatorAgentsService::class)->directory($this->ibrahim, ['search' => 'Fatima']);
        $this->assertSame(1, $byName['total']);
        $this->assertSame('AGT-00454', $byName['paginator']->items()[0]['agent']->agent_code);

        $byRegion = app(AggregatorAgentsService::class)->directory($this->ibrahim, ['region' => 'Maradi']);
        $this->assertSame(3, $byRegion['total']);
    }

    public function test_directory_kyc_filter(): void
    {
        $payload = app(AggregatorAgentsService::class)->directory($this->ibrahim, ['kyc_status' => 'pending']);
        $this->assertSame(1, $payload['total']);
        $this->assertSame('AGT-00454', $payload['paginator']->items()[0]['agent']->agent_code);
    }

    public function test_directory_paginates_server_side(): void
    {
        $page1 = app(AggregatorAgentsService::class)->directory($this->ibrahim, [], 2, 1);
        $page3 = app(AggregatorAgentsService::class)->directory($this->ibrahim, [], 2, 3);

        $this->assertSame(2, count($page1['paginator']->items()));
        $this->assertSame(1, count($page3['paginator']->items()));
        $this->assertSame(3, $page1['paginator']->lastPage());
    }

    public function test_directory_rows_carry_live_ledger_floats_and_30d_stats(): void
    {
        $payload = app(AggregatorAgentsService::class)->directory($this->ibrahim, []);
        $aminu = collect($payload['paginator']->items())->first(fn ($r) => $r['agent']->agent_code === 'AGT-00391');

        // Float is ledger-sourced: seeded 2,000,000 XOF for Aminu.
        $this->assertSame('2000000.00', (string) $aminu['float']);
        $this->assertSame('XOF', $aminu['float_currency']);

        // Aminu has 8 posted operations across the seeded history, all within 30 days.
        $this->assertSame(8, $aminu['posted_30d']);
        $this->assertSame(0, $aminu['failed_30d']);
        $this->assertSame('6650000.00', $aminu['volume_30d']);
    }

    public function test_onboarding_pipeline_is_real_counts(): void
    {
        $pipeline = app(AggregatorAgentsService::class)->pipeline($this->ibrahim);
        $stages = collect($pipeline['stages'])->keyBy('key');

        $this->assertSame(5, $pipeline['total']);
        $this->assertSame(4, $pipeline['active']);
        $this->assertSame(80.0, $pipeline['conversion_rate']);
        $this->assertSame(0, $stages['recruited']['count']);
        $this->assertSame(1, $stages['kyc_pending']['count']);   // Fatima
        $this->assertSame(1, $stages['suspended']['count']);     // Danladi
        $this->assertSame(0, $stages['terminated']['count']);

        // Empty network → conversion is honestly null, not 0%.
        $empty = Aggregator::create([
            'user_id' => User::factory()->withRole('aggregator')->create()->id,
            'code' => 'AGG-EMPTY',
            'name' => 'Empty Network',
            'status' => Aggregator::STATUS_ACTIVE,
            'country_iso2' => 'NG',
        ]);
        $this->assertNull(app(AggregatorAgentsService::class)->pipeline($empty)['conversion_rate']);
    }

    // ── Profile (§15) ───────────────────────────────────────────────────

    public function test_profile_is_tenant_scoped_and_rejects_foreign_agents(): void
    {
        $this->actingAs($this->aggUser($this->ibrahim))
            ->get('/aggregator/agents/AGT-00391')->assertOk();

        // Chidi must NOT see Ibrahim's agent — 404, no existence leak.
        $this->actingAs($this->aggUser($this->chidi))
            ->get('/aggregator/agents/AGT-00391')->assertNotFound();
    }

    public function test_profile_overview_shows_ledger_sourced_floats(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');

        $profile = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $aminu, 'overview');

        $this->assertSame(1, count($profile['floats']));
        $this->assertSame('2000000.00', (string) $profile['floats'][0]['balance']);
        $this->assertSame(8, $profile['counts']['ops_total']);
        $this->assertSame('6650000.00', $profile['counts']['volume_30d']);
    }

    public function test_profile_kyc_autopasses_on_approved_submission(): void
    {
        // Fatima is seeded kyc_status=pending with NO submission → pending.
        $fatima = $this->agent($this->ibrahim, 'AGT-00454');
        $kyc = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $fatima, 'kyc');
        $this->assertSame('pending', $kyc['status']);
        $this->assertNull($kyc['submission']);

        // An approved submission autopasses regardless of the mirror column.
        KycSubmission::create([
            'user_id' => $fatima->user_id,
            'type' => 'personal',
            'status' => KycSubmission::STATUS_APPROVED,
            'tier' => 1,
            'country_code' => 'NER',
            'submitted_at' => now()->subDay(),
        ]);
        $approved = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $fatima, 'kyc');
        $this->assertSame('verified', $approved['status']);
        $this->assertSame('approved', $approved['submission']['status']);
    }

    public function test_profile_transactions_are_scoped_to_the_agent(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $sani = $this->agent($this->ibrahim, 'AGT-00433');

        $aminuTx = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $aminu, 'transactions');
        $saniTx = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $sani, 'transactions');

        $this->assertSame(8, $aminuTx['total']);
        $this->assertSame(5, $saniTx['total']); // 4 posted + 1 failed (honest: all rows shown)
        foreach ($aminuTx['rows'] as $op) {
            $this->assertSame($aminu->id, $op->agent_id);
        }
    }

    public function test_profile_commissions_are_scoped_and_totalled_per_currency(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $commissions = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $aminu, 'commissions');

        $this->assertSame(8, $commissions['total']);
        $this->assertArrayHasKey('XOF', $commissions['totals']);
        // Seeded Aminu commissions: 1200+800+1000+600+1800+1500+1200+1300 = 9,400
        $this->assertSame('9400.00', $commissions['totals']['XOF']);
        foreach ($commissions['rows'] as $entry) {
            $this->assertSame('agent', $entry->beneficiary_type);
            $this->assertSame($aminu->id, $entry->beneficiary_id);
        }
    }

    public function test_profile_risk_shows_agent_alerts(): void
    {
        $danladi = $this->agent($this->ibrahim, 'AGT-00475');
        $risk = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $danladi, 'risk');

        $this->assertSame(1, $risk['total']);
        $this->assertSame('agent_restricted', $risk['rows'][0]->category);
        $this->assertSame($danladi->id, $risk['rows'][0]->entity_id);

        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $this->assertSame(0, app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $aminu, 'risk')['total']);
    }

    public function test_devices_tab_is_an_honest_empty_state(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $devices = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $aminu, 'devices');

        $this->assertSame([], $devices['devices']);
        $this->assertStringContainsString('not recorded', $devices['note']);
    }

    public function test_profile_audit_trail_contains_registration_events(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $audit = app(AggregatorAgentsService::class)->agentProfile($this->ibrahim, $aminu, 'audit');

        $this->assertGreaterThanOrEqual(1, $audit['total']);
        $actions = collect($audit['rows'])->pluck('action')->all();
        $this->assertContains('agent.registered', $actions);
    }

    // ── Performance score (§17) — explainable, honest ───────────────────

    public function test_performance_score_is_explainable_and_authoritative(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $score = app(AggregatorAgentsService::class)->performance($aminu);

        $this->assertNotNull($score);
        $this->assertSame('authoritative', $score['basis']);
        $this->assertGreaterThanOrEqual(0, $score['score']);
        $this->assertLessThanOrEqual(100, $score['score']);
        $this->assertContains($score['label'], ['Strong', 'Good', 'Needs attention', 'At risk']);

        $labels = collect($score['components'])->pluck('label')->all();
        $this->assertContains('Activity · ops in 30 days', $labels);
        $this->assertContains('Posted volume · 30 days', $labels);
        $this->assertContains('Success rate · 30 days', $labels);

        // Every component explains itself in plain language.
        foreach ($score['components'] as $component) {
            $this->assertNotEmpty($component['explanation']);
            $this->assertGreaterThan(0, $component['weight']);
            $this->assertBetween(0, 100, $component['points']);
        }
    }

    public function test_performance_score_returns_null_with_no_signal(): void
    {
        $user = User::factory()->withRole('agent')->create();
        $emptyAgent = Agent::create([
            'user_id' => $user->id,
            'agent_code' => 'AGT-00000',
            'aggregator_id' => $this->ibrahim->id,
            'status' => Agent::STATUS_ACTIVE,
            'tier' => 'bronze',
            'country_iso2' => 'NE',
            'kyc_status' => 'unverified',
        ]);

        // No ops, no risk score → nothing to score: honest null (UI renders
        // "insufficient data" instead of a fabricated number).
        $this->assertNull(app(AggregatorAgentsService::class)->performance($emptyAgent));
    }

    // ── Dormancy (§19) — measured, estimates labelled ───────────────────

    public function test_dormancy_flags_agents_without_recent_posted_activity(): void
    {
        $user = User::factory()->withRole('agent')->create();
        $agent = Agent::create([
            'user_id' => $user->id,
            'agent_code' => 'AGT-00999',
            'aggregator_id' => $this->ibrahim->id,
            'status' => Agent::STATUS_ACTIVE,
            'tier' => 'bronze',
            'country_iso2' => 'NE',
            'kyc_status' => 'verified',
        ]);

        // One posted operation 40 days ago → dormant. Timestamps are set
        // explicitly (not mass-assignable) so the inactivity window is real.
        $op = new AgencyOperation([
            'agent_id' => $agent->id,
            'aggregator_id' => $this->ibrahim->id,
            'customer_user_id' => $this->ibrahim->user_id,
            'operation_type' => 'cash_in',
            'currency_code' => 'XOF',
            'amount' => '50000',
            'fee' => '250',
            'commission_amount' => '60',
            'status' => 'posted',
            'reference' => 'KPA-DORM-TEST-1',
            'idempotency_key' => 'dorm-seed-1',
        ]);
        $op->created_at = now()->subDays(40);
        $op->updated_at = now()->subDays(40);
        $op->save();

        $dormancy = app(AggregatorAgentsService::class)->dormancy($agent);
        $this->assertNotNull($dormancy);
        $this->assertSame('dormant', $dormancy['status']);
        $this->assertGreaterThanOrEqual(30, $dormancy['days_since_last_activity']);
        $this->assertSame('estimate', $dormancy['estimate']['label']); // labelled, never a promise

        // A freshly active agent (Aminu, ops today) is not dormant.
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $this->assertNull(app(AggregatorAgentsService::class)->dormancy($aminu));

        // Suspended agents are excluded — their status IS the explanation.
        $danladi = $this->agent($this->ibrahim, 'AGT-00475');
        $this->assertNull(app(AggregatorAgentsService::class)->dormancy($danladi));
    }

    // ── Recruitment (§20) — capture only, no activation ─────────────────

    public function test_recruit_creates_pending_agent_without_activation(): void
    {
        $service = app(AggregatorAgentsService::class);
        $actor = $this->aggUser($this->ibrahim);

        $agent = $service->recruit($this->ibrahim, [
            'name' => 'Halima Kante',
            'email' => 'halima.kante@example.com',
            'phone' => '+22791112233',
            'country_iso2' => 'NE',
            'region' => 'Tillabéri',
            'city' => 'Tillabéri',
            'tier' => 'silver',
        ], $actor->id);

        $this->assertSame(Agent::STATUS_PENDING, $agent->status);
        $this->assertSame('unverified', $agent->kyc_status);
        $this->assertSame($this->ibrahim->id, $agent->aggregator_id);

        // Float ledger provisioned for the agent's country currency.
        $this->assertNotNull(LedgerAccount::query()
            ->where('owner_type', 'agent')->where('owner_id', $agent->id)
            ->where('currency_code', 'XOF')->first());

        // Audited end-to-end.
        $this->assertTrue(AuditLog::where('action', 'agent.registered')
            ->where('user_id', $agent->user_id)->exists());
        $this->assertTrue(AuditLog::where('action', 'agent.assigned.aggregator')
            ->where('metadata->agent_id', $agent->id)->exists());

        // Now visible in the directory as a recruited/pending agent.
        $payload = $service->directory($this->ibrahim, []);
        $this->assertSame(6, $payload['total']);
        $pipeline = $service->pipeline($this->ibrahim);
        $stages = collect($pipeline['stages'])->keyBy('key');
        $this->assertSame(1, $stages['recruited']['count']);
    }

    public function test_recruit_rejects_duplicate_email_and_phone(): void
    {
        $service = app(AggregatorAgentsService::class);

        try {
            $service->recruit($this->ibrahim, [
                'name' => 'Duplicate',
                'email' => 'aminu.musa@koriepay.test', // already seeded
                'phone' => '+22700000001',
                'country_iso2' => 'NE',
            ], null);
            $this->fail('Duplicate email should be rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }

        $seededAgent = $this->agent($this->ibrahim, 'AGT-00391');
        try {
            $service->recruit($this->ibrahim, [
                'name' => 'Duplicate Phone',
                'email' => 'fresh@example.com',
                'phone' => $seededAgent->user->phone_number,
                'country_iso2' => 'NE',
            ], null);
            $this->fail('Duplicate phone should be rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('phone', $e->errors());
        }
    }

    public function test_recruit_form_via_livewire_captures_without_activating(): void
    {
        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(RecruitAgent::class)
            ->set('name', 'Moussa Ide')
            ->set('email', 'moussa.ide@example.com')
            ->set('phone', '+22790001122')
            ->set('country', 'NE')
            ->set('region', 'Maradi')
            ->set('city', 'Guidan Roumdji')
            ->set('tier', 'bronze')
            ->call('submit')
            ->assertSet('created', true);

        $agent = Agent::whereHas('user', fn ($q) => $q->where('email', 'moussa.ide@example.com'))->firstOrFail();
        $this->assertSame('Moussa Ide', $agent->user->name);
        $this->assertSame(Agent::STATUS_PENDING, $agent->status);
        $this->assertSame('unverified', $agent->kyc_status);
        $this->assertSame($this->ibrahim->id, $agent->aggregator_id);
    }

    public function test_recruit_form_rejects_invalid_payload(): void
    {
        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(RecruitAgent::class)
            ->set('name', 'X')
            ->set('email', 'not-an-email')
            ->set('country', 'US')
            ->call('submit')
            ->assertHasErrors(['name', 'email', 'country']);

        $this->assertSame(6, Agent::count()); // 5 (Ibrahim) + 1 (Chidi) — nothing new was created
    }

    // ── Status changes — backend-controlled, permission-gated (§15) ─────

    public function test_suspension_goes_through_backend_and_is_audited(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');

        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(AgentProfile::class, ['agent' => $aminu])
            ->call('suspend')
            ->assertDispatched('toast');

        $aminu->refresh();
        $this->assertSame(Agent::STATUS_SUSPENDED, $aminu->status);
        $this->assertTrue(AuditLog::where('action', 'agent.suspended')->exists());

        // Reactivation is also backend + audited.
        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(AgentProfile::class, ['agent' => $aminu])
            ->call('reactivate')
            ->assertDispatched('toast');

        $aminu->refresh();
        $this->assertSame(Agent::STATUS_ACTIVE, $aminu->status);
        $this->assertTrue(AuditLog::where('action', 'agent.reactivated')->exists());
    }

    public function test_customer_cannot_reach_profile_or_suspend(): void
    {
        $aminu = $this->agent($this->ibrahim, 'AGT-00391');
        $customer = User::factory()->withRole('customer')->create();

        // Route-level permission gate (§83) keeps customers out entirely.
        $this->actingAs($customer)->get('/aggregator/agents/AGT-00391')->assertForbidden();

        // And the Gate itself denies the capability — defense in depth.
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($customer)->allows('agent.suspend'));
        $this->assertSame(Agent::STATUS_ACTIVE, $aminu->refresh()->status);
    }

    // ── Page renders (§145) ─────────────────────────────────────────────

    public function test_agents_pages_render_for_authenticated_aggregator(): void
    {
        $this->actingAs($this->aggUser($this->ibrahim))
            ->get('/aggregator/agents')->assertOk()
            ->assertSee('Agents')
            ->assertSee('AGT-00391');

        $this->actingAs($this->aggUser($this->ibrahim))
            ->get('/aggregator/agents/AGT-00391')->assertOk()
            ->assertSee('Aminu Musa')
            ->assertSee('AGT-00391');
    }

    public function test_directory_livewire_search_updates_rows(): void
    {
        Livewire::actingAs($this->aggUser($this->ibrahim))
            ->test(Agents::class)
            ->set('search', 'Fatima')
            ->assertSet('page', 1)
            ->assertSee('Fatima Adamou')
            ->assertDontSee('Aminu Musa');
    }

    // ── helper ──────────────────────────────────────────────────────────

    private function assertBetween(int $min, int $max, int $value): void
    {
        $this->assertGreaterThanOrEqual($min, $value);
        $this->assertLessThanOrEqual($max, $value);
    }
}
