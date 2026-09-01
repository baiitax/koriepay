<?php

namespace Tests\Unit\Domain;

use App\Domain\Accounting\Exceptions\IllegalStateTransitionException;
use App\Domain\Accounting\TransactionStateMachine;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Explicit state machine: only legal transitions, every step audited.
 */
class TransactionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(string $status = null): Transaction
    {
        $sender = \App\Models\User::factory()->create();
        $receiver = \App\Models\User::factory()->create();

        return Transaction::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'receiver_name' => 'Bob',
            'source_currency' => 'NGN',
            'destination_currency' => 'NGN',
            'source_amount' => 1000.00,
            'destination_amount' => 1000.00,
            'exchange_rate' => 1.0,
            'fee_charged' => 0.0,
            'status' => $status ?? 'initiated',
            'reference' => 'ST-'.strtoupper(uniqid()),
            'type' => 'p2p_transfer',
        ]);
    }

    public function test_happy_path_reaches_settled(): void
    {
        $tx = $this->makeTransaction();

        $sm = app(TransactionStateMachine::class);
        $sm->transition($tx, 'PROCESSING');
        $sm->transition($tx->fresh(), 'AUTHORIZED');
        $sm->transition($tx->fresh(), 'POSTED');
        $sm->transition($tx->fresh(), 'SETTLED');

        $this->assertSame('settled', $tx->fresh()->status);
        $this->assertSame(4, DB::table('transaction_states')->where('transaction_id', $tx->id)->count());
    }

    public function test_failure_branch(): void
    {
        $tx = $this->makeTransaction();

        $sm = app(TransactionStateMachine::class);
        $sm->transition($tx, 'PROCESSING');
        $sm->transition($tx->fresh(), 'FAILED', 'provider_rejected');

        $this->assertSame('failed', $tx->fresh()->status);
        $state = DB::table('transaction_states')->where('transaction_id', $tx->id)
            ->where('to_state', 'FAILED')->first();
        $this->assertSame('provider_rejected', $state->reason);
    }

    public function test_illegal_transition_throws_and_is_not_persisted(): void
    {
        $tx = $this->makeTransaction('initiated');

        $this->expectException(IllegalStateTransitionException::class);

        app(TransactionStateMachine::class)->transition($tx, 'SETTLED'); // skip stages
    }

    public function test_terminal_state_cannot_move(): void
    {
        $tx = $this->makeTransaction('reversed');

        $this->expectException(IllegalStateTransitionException::class);

        app(TransactionStateMachine::class)->transition($tx, 'PROCESSING');
    }

    public function test_hold_and_release(): void
    {
        $tx = $this->makeTransaction();

        $sm = app(TransactionStateMachine::class);
        $sm->transition($tx, 'PROCESSING');
        $sm->transition($tx->fresh(), 'HELD', 'risk_hold');
        $sm->transition($tx->fresh(), 'POSTED', 'risk_cleared');

        $this->assertSame('posted', $tx->fresh()->status);
        $transitions = DB::table('transaction_states')
            ->where('transaction_id', $tx->id)
            ->orderBy('id')
            ->pluck('to_state')
            ->all();
        $this->assertSame(['PROCESSING', 'HELD', 'POSTED'], $transitions);
    }

    public function test_transition_audit_records_actor_and_context(): void
    {
        $tx = $this->makeTransaction();

        app(TransactionStateMachine::class)->transition(
            $tx, 'PROCESSING', 'started', actorId: 42, context: ['queue' => 'default']
        );

        $row = DB::table('transaction_states')->where('transaction_id', $tx->id)->first();
        $this->assertSame(42, (int) $row->actor_id);
        $this->assertSame('started', $row->reason);
        $this->assertStringContainsString('queue', (string) $row->context);
    }

    public function test_static_helpers(): void
    {
        $this->assertTrue(TransactionStateMachine::canTransition('INITIATED', 'PROCESSING'));
        $this->assertFalse(TransactionStateMachine::canTransition('SETTLED', 'AUTHORIZED'));
        $this->assertTrue(TransactionStateMachine::isTerminal('REVERSED'));
        $this->assertTrue(in_array('SETTLED', TransactionStateMachine::allowedFrom('POSTED'), true));
    }
}
