<?php

namespace Tests\Feature;

use App\Domain\Accounting\Exceptions\IllegalStateTransitionException;
use App\Domain\Risk\ApprovalService;
use App\Domain\Risk\RiskService;
use App\Models\Agent;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\RiskAlert;
use App\Models\RiskRule;
use App\Models\Transaction;
use App\Models\TransactionHold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PHASE 7 — Risk layer.
 *
 * Data-driven rules → auditable alerts (risk indicators, never fraud labels),
 * entity risk scoring, transaction holds that move the state machine, and the
 * maker–checker approval inbox (maker can never approve own request).
 */
class RiskLayerTest extends TestCase
{
    use RefreshDatabase;

    private RiskService $risk;
    private ApprovalService $approval;

    protected function setUp(): void
    {
        parent::setUp();

        $this->risk = app(RiskService::class);
        $this->approval = app(ApprovalService::class);
    }

    // ── Rules & alerts ────────────────────────────────────────────────────

    public function test_amount_rule_raises_alert_and_dedupes(): void
    {
        RiskRule::create([
            'code' => 'TX_HIGH_VALUE',
            'name' => 'High-value transaction',
            'category' => 'aml',
            'entity_type' => 'transaction',
            'condition_type' => 'amount_exceeds',
            'condition_config' => ['amount' => '500000.00'],
            'severity' => 'P1',
            'risk_score' => 25,
            'dedupe_window_minutes' => null,
        ]);

        $alerts = $this->risk->evaluate('transaction', ['amount' => '600000.00'], entityId: 1);

        $this->assertCount(1, $alerts);
        $this->assertSame('P1', $alerts[0]->severity);
        $this->assertSame('aml', $alerts[0]->category);
        $this->assertSame(25.0, (float) $alerts[0]->risk_score);
        $this->assertSame('open', $alerts[0]->status);

        // Same entity+rule already open → dedupe, no second alert.
        $again = $this->risk->evaluate('transaction', ['amount' => '700000.00'], entityId: 1);
        $this->assertCount(0, $again);
        $this->assertSame(1, RiskAlert::count());
    }

    public function test_velocity_rule_matches_facts(): void
    {
        RiskRule::create([
            'code' => 'AGENT_VELOCITY',
            'name' => 'Abnormal agent velocity',
            'category' => 'velocity',
            'entity_type' => 'agent',
            'condition_type' => 'velocity_count_exceeds',
            'condition_config' => ['count' => 10],
            'severity' => 'P2',
            'risk_score' => 15,
            'dedupe_window_minutes' => 60,
        ]);

        $this->assertCount(1, $this->risk->evaluate('agent', ['velocity_count' => 12], entityId: 7));
        $this->assertCount(0, $this->risk->evaluate('agent', ['velocity_count' => 5], entityId: 8));
    }

    public function test_rule_within_dedupe_window_does_not_repeat(): void
    {
        RiskRule::create([
            'code' => 'AGENT_FAILURES',
            'name' => 'Repeated failed attempts',
            'category' => 'fraud',
            'entity_type' => 'agent',
            'condition_type' => 'failed_attempts_exceed',
            'condition_config' => ['count' => 5],
            'severity' => 'P2',
            'risk_score' => 15,
            'dedupe_window_minutes' => 60,
        ]);

        $this->risk->evaluate('agent', ['failed_attempts' => 6], entityId: 42);
        $this->risk->evaluate('agent', ['failed_attempts' => 9], entityId: 42); // same window

        $this->assertSame(1, RiskAlert::count());
    }

    public function test_alert_lifecycle_is_audited(): void
    {
        $actor = User::factory()->create(['name' => 'Risk Officer']);
        $target = User::factory()->create(['name' => 'Target']);

        $rule = RiskRule::create([
            'code' => 'TX_HIGH_VALUE',
            'name' => 'High-value',
            'category' => 'aml',
            'entity_type' => 'transaction',
            'condition_type' => 'amount_exceeds',
            'condition_config' => ['amount' => '1000.00'],
            'severity' => 'P1',
            'risk_score' => 25,
        ]);
        [$alert] = $this->risk->evaluate('transaction', ['amount' => '5000.00'], entityId: $target->id);
        $this->assertNotNull($alert);

        $this->risk->acknowledgeAlert($alert, actorId: $actor->id);
        $this->risk->resolveAlert($alert, actorId: $actor->id, note: 'No evidence');

        $fresh = $alert->fresh();
        $this->assertSame('resolved', $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
        $this->assertSame('No evidence', $fresh->resolution_note);

        $this->assertDatabaseHas('audit_logs', ['action' => 'risk.alert.acknowledged']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'risk.alert.resolved']);
    }

    public function test_risk_score_updates_agent_projection(): void
    {
        $agentUser = User::factory()->create(['name' => 'Agent']);
        $agent = Agent::create([
            'user_id' => $agentUser->id,
            'agent_code' => 'AGT-TEST01',
            'status' => 'active',
            'tier' => 'bronze',
            'country_iso2' => 'NG',
        ]);

        RiskRule::create([
            'code' => 'A1', 'name' => 'P1 rule', 'category' => 'fraud', 'entity_type' => 'agent',
            'condition_type' => 'failed_attempts_exceed', 'condition_config' => ['count' => 3],
            'severity' => 'P1', 'risk_score' => 25, 'dedupe_window_minutes' => null,
        ]);
        RiskRule::create([
            'code' => 'A2', 'name' => 'P0 rule', 'category' => 'fraud', 'entity_type' => 'agent',
            'condition_type' => 'velocity_count_exceeds', 'condition_config' => ['count' => 20],
            'severity' => 'P0', 'risk_score' => 40, 'dedupe_window_minutes' => null,
        ]);

        $this->risk->evaluate('agent', ['failed_attempts' => 4], entityId: $agent->id);
        $this->risk->evaluate('agent', ['velocity_count' => 25], entityId: $agent->id);

        $score = $this->risk->scoreEntity('agent', $agent->id);

        // P1 (25) + P0 (40) = 65, persisted to agents.risk_score.
        $this->assertSame('65.00', $score);
        $this->assertSame('65.00', (string) $agent->fresh()->risk_score);
    }

    // ── Transaction holds ─────────────────────────────────────────────────

    public function test_hold_moves_state_machine_and_records_hold(): void
    {
        $tx = $this->makeTransaction('posted');
        $holder = User::factory()->create(['name' => 'Holder']);
        $checker = User::factory()->create(['name' => 'Checker']);

        $hold = $this->risk->holdTransaction($tx, 'AML review', actorId: $holder->id, reasonCode: 'aml_review', slaHours: 24);

        $this->assertSame('held', strtolower((string) $tx->fresh()->status));
        $this->assertSame(TransactionHold::STATUS_HELD, $hold->status);
        $this->assertNotNull($hold->sla_due_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transaction.held']);

        // Release → HELD → POSTED.
        $this->risk->releaseHold($tx, actorId: $checker->id, note: 'Cleared');
        $this->assertSame('posted', strtolower((string) $tx->fresh()->status));
        $this->assertSame(TransactionHold::STATUS_RELEASED, $hold->fresh()->status);
        $this->assertSame($checker->id, $hold->fresh()->decided_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transaction.hold.released']);
    }

    public function test_reject_hold_cancels_transaction(): void
    {
        $tx = $this->makeTransaction('posted');
        $holder = User::factory()->create(['name' => 'Holder']);
        $reviewer = User::factory()->create(['name' => 'Reviewer']);

        $this->risk->holdTransaction($tx, 'Fraud review', actorId: $holder->id);
        $this->risk->rejectHold($tx, actorId: $reviewer->id, note: 'Confirmed fraud pattern');

        $this->assertSame('cancelled', strtolower((string) $tx->fresh()->status));
        $this->assertSame(TransactionHold::STATUS_REJECTED, TransactionHold::first()->fresh()->status);
    }

    public function test_hold_on_unholdable_state_throws(): void
    {
        $tx = $this->makeTransaction('initiated');
        $holder = User::factory()->create(['name' => 'Holder']);

        $this->expectException(IllegalStateTransitionException::class);
        $this->risk->holdTransaction($tx, 'Review', actorId: $holder->id);
    }

    // ── Maker–checker approvals ───────────────────────────────────────────

    public function test_approval_submit_and_approve_by_checker(): void
    {
        $maker = User::factory()->create(['name' => 'Maker']);
        $checker = User::factory()->create(['name' => 'Checker']);

        $entity = User::factory()->create(['name' => 'Entity']);
        $request = $this->approval->submit(
            makerId: $maker->id,
            actionType: 'commission.change',
            reason: 'Adjust Kano agent commission',
            entityType: 'agent',
            entityId: $entity->id,
            payload: ['before' => '1.0000', 'after' => '1.5000'],
        );

        $this->assertSame(ApprovalRequest::STATUS_PENDING, $request->status);
        $this->assertStringStartsWith('APR-', $request->reference);

        $decided = $this->approval->decide($checker->id, $request->id, true, note: 'Approved per policy');

        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $decided->status);
        $this->assertSame($checker->id, $decided->checker_id);
        $this->assertSame($checker->id, $decided->decided_by);
        $this->assertNotNull($decided->decided_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'approval.approved']);
    }

    public function test_maker_cannot_approve_own_request(): void
    {
        $maker = User::factory()->create(['name' => 'Maker']);
        $entity = User::factory()->create(['name' => 'Entity']);
        $request = $this->approval->submit(
            makerId: $maker->id,
            actionType: 'risk.release',
            reason: 'Release hold',
            entityId: $entity->id,
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot approve own request');
        $this->approval->decide($maker->id, $request->id, true);
    }

    public function test_approval_cannot_be_decided_twice(): void
    {
        $maker = User::factory()->create(['name' => 'Maker']);
        $checker = User::factory()->create(['name' => 'Checker']);

        $entity = User::factory()->create(['name' => 'Entity']);
        $request = $this->approval->submit($maker->id, 'settlement.approve', 'Release settlement', entityId: $entity->id);
        $this->approval->decide($checker->id, $request->id, false, note: 'Insufficient cover');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already rejected');
        $this->approval->decide($checker->id, $request->id, true);
    }

    public function test_inbox_and_mine_separate_rails(): void
    {
        $maker = User::factory()->create(['name' => 'Maker']);
        $checker = User::factory()->create(['name' => 'Checker']);

        $entity = User::factory()->create(['name' => 'Entity']);
        $request = $this->approval->submit($maker->id, 'limit.change', 'Raise daily limit', entityId: $entity->id);

        $this->assertTrue($this->approval->mine($maker->id)->contains('id', $request->id));
        $this->assertFalse($this->approval->mine($checker->id)->contains('id', $request->id));

        $this->assertTrue($this->approval->inboxFor($checker->id)->contains('id', $request->id));
        $this->assertFalse($this->approval->inboxFor($maker->id)->contains('id', $request->id));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeTransaction(string $status): Transaction
    {
        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        return Transaction::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'receiver_name' => 'Receiver',
            'type' => 'deposit',
            'source_currency' => 'NGN',
            'destination_currency' => 'NGN',
            'source_amount' => '1000000.00',
            'destination_amount' => '1000000.00',
            'exchange_rate' => '1.0000',
            'status' => $status,
            'description' => 'Risk test transaction',
            'reference' => 'KP-RISK-'.strtoupper(substr(uniqid('', true), -8)),
        ]);
    }
}
