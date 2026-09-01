<?php

namespace App\Domain\Risk;

use App\Domain\Accounting\TransactionStateMachine;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\RiskAlert;
use App\Models\RiskRule;
use App\Models\Transaction;
use App\Models\TransactionHold;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * PHASE 7 — Risk layer.
 *
 * Rules are DATA (risk_rules) evaluated against caller-supplied facts; the
 * alert is always a RISK INDICATOR until formally reviewed (§31). Holds move
 * the transaction state machine (HELD → POSTED / CANCELLED) and never touch
 * balances directly.
 */
class RiskService
{
    /**
     * Severity weight used for the 0–100 entity risk projection.
     */
    private const SEVERITY_WEIGHT = ['P0' => 40, 'P1' => 25, 'P2' => 15, 'P3' => 5];

    /**
     * Evaluate active rules for an entity against the supplied facts and
     * create (deduplicated) alerts.
     *
     * @param  array<string, mixed>  $facts  e.g. ['amount' => '250000.00', 'failed_attempts' => 6, 'velocity_count' => 12, 'success_rate' => 93.5]
     * @return array<int, RiskAlert> created alerts (possibly empty)
     */
    public function evaluate(
        string $entityType,
        array $facts,
        ?int $entityId = null,
        ?Transaction $transaction = null,
        ?string $countryIso2 = null,
    ): array {
        $rules = RiskRule::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('country_iso2')->orWhere('country_iso2', $countryIso2))
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $created = [];

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $facts)) {
                continue;
            }
            if ($this->alreadyRaised($rule, $entityType, $entityId, $transaction?->id)) {
                continue;
            }

            $alert = RiskAlert::create([
                'reference' => 'ALRT-'.strtoupper(Str::random(10)),
                'rule_id' => $rule->id,
                'category' => $rule->category,
                'severity' => $rule->severity,
                'entity_type' => $entityType,
                'entity_id' => $entityId ?? 0,
                'transaction_id' => $transaction?->id,
                'country_iso2' => $countryIso2,
                'message' => $rule->name,
                'details' => $facts,
                'risk_score' => $rule->risk_score,
                'status' => RiskAlert::STATUS_OPEN,
            ]);

            $created[] = $alert;
        }

        return $created;
    }

    /**
     * 0–100 risk projection for an entity from its OPEN alerts. Persisted to
     * agents.risk_score / users.risk_score (Phase 6 column / 001300 column).
     */
    public function scoreEntity(string $entityType, int $entityId): string
    {
        $alerts = RiskAlert::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->whereIn('status', [RiskAlert::STATUS_OPEN, RiskAlert::STATUS_ACKNOWLEDGED, RiskAlert::STATUS_INVESTIGATING])
            ->get();

        $score = '0';
        foreach ($alerts as $alert) {
            $weight = self::SEVERITY_WEIGHT[$alert->severity] ?? 5;
            $score = bcadd($score, (string) $weight, 2);
        }

        $score = number_format(min((float) $score, 100.0), 2, '.', '');

        if ($entityType === 'agent') {
            Agent::whereKey($entityId)->update(['risk_score' => $score]);
        } elseif ($entityType === 'customer') {
            User::whereKey($entityId)->update(['risk_score' => $score]);
        }

        return $score;
    }

    // ── Transaction holds (state machine) ────────────────────────────────

    public function holdTransaction(Transaction $transaction, string $reason, ?int $actorId = null, ?string $reasonCode = null, ?int $slaHours = 24): TransactionHold
    {
        $transaction = $transaction->fresh() ?? $transaction;
        $transaction = $this->stateMachine()->transition(
            $transaction,
            TransactionStateMachine::HELD,
            reason: 'Hold: '.$reason,
            actorId: $actorId,
            context: ['reason_code' => $reasonCode],
        );

        AuditLog::record('transaction.held', $actorId, $transaction->sender_id ?? 0, [
            'description' => 'Transaction '.$transaction->reference.' held — '.$reason,
            'event_type' => 'risk',
            'metadata' => ['transaction_id' => $transaction->id, 'reference' => $transaction->reference, 'reason' => $reason, 'reason_code' => $reasonCode],
        ]);

        return TransactionHold::create([
            'transaction_id' => $transaction->id,
            'amount' => $transaction->source_amount,
            'currency_code' => $transaction->source_currency,
            'reason' => $reason,
            'reason_code' => $reasonCode,
            'status' => TransactionHold::STATUS_HELD,
            'held_by' => $actorId,
            'sla_due_at' => $slaHours !== null ? now()->addHours($slaHours) : null,
        ]);
    }

    public function releaseHold(Transaction $transaction, ?int $actorId = null, ?string $note = null): TransactionHold
    {
        $hold = $this->holdOrFail($transaction);
        $transaction = $transaction->fresh() ?? $transaction;

        $transaction = $this->stateMachine()->transition(
            $transaction,
            TransactionStateMachine::POSTED,
            reason: 'Hold released: '.($note ?? 'approved after review'),
            actorId: $actorId,
        );

        $hold->update([
            'status' => TransactionHold::STATUS_RELEASED,
            'decided_by' => $actorId,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        AuditLog::record('transaction.hold.released', $actorId, $transaction->sender_id ?? 0, [
            'description' => 'Transaction '.$transaction->reference.' hold released — '.($note ?? 'approved'),
            'event_type' => 'risk',
            'metadata' => ['transaction_id' => $transaction->id, 'note' => $note],
        ]);

        return $hold;
    }

    public function rejectHold(Transaction $transaction, ?int $actorId = null, ?string $note = null): TransactionHold
    {
        $hold = $this->holdOrFail($transaction);
        $transaction = $transaction->fresh() ?? $transaction;

        $transaction = $this->stateMachine()->transition(
            $transaction,
            TransactionStateMachine::CANCELLED,
            reason: 'Hold rejected: '.($note ?? 'declined after review'),
            actorId: $actorId,
        );

        $hold->update([
            'status' => TransactionHold::STATUS_REJECTED,
            'decided_by' => $actorId,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        AuditLog::record('transaction.hold.rejected', $actorId, $transaction->sender_id ?? 0, [
            'description' => 'Transaction '.$transaction->reference.' hold rejected — '.($note ?? 'declined'),
            'event_type' => 'risk',
            'metadata' => ['transaction_id' => $transaction->id, 'note' => $note],
        ]);

        return $hold;
    }

    // ── Alert lifecycle ──────────────────────────────────────────────────

    public function acknowledgeAlert(RiskAlert $alert, ?int $actorId = null): RiskAlert
    {
        return $this->setAlertStatus($alert, RiskAlert::STATUS_ACKNOWLEDGED, $actorId);
    }

    public function resolveAlert(RiskAlert $alert, ?int $actorId = null, ?string $note = null, bool $falsePositive = false): RiskAlert
    {
        return $this->setAlertStatus($alert, $falsePositive ? RiskAlert::STATUS_FALSE_POSITIVE : RiskAlert::STATUS_RESOLVED, $actorId, $note);
    }

    // ── Internals ────────────────────────────────────────────────────────

    protected function matches(RiskRule $rule, array $facts): bool
    {
        $config = (array) $rule->condition_config;

        return match ($rule->condition_type) {
            'amount_exceeds' => isset($facts['amount'])
                && bccomp((string) $facts['amount'], (string) ($config['amount'] ?? 0)) > 0,
            'failed_attempts_exceed' => isset($facts['failed_attempts'])
                && (int) $facts['failed_attempts'] > (int) ($config['count'] ?? 0),
            'velocity_count_exceeds' => isset($facts['velocity_count'])
                && (int) $facts['velocity_count'] > (int) ($config['count'] ?? 0),
            'success_rate_below' => isset($facts['success_rate'])
                && (float) $facts['success_rate'] < (float) ($config['rate'] ?? 0),
            default => false,
        };
    }

    protected function alreadyRaised(RiskRule $rule, string $entityType, ?int $entityId, ?int $transactionId): bool
    {
        $query = RiskAlert::query()
            ->where('rule_id', $rule->id)
            ->where('entity_type', $entityType);

        if ($transactionId !== null) {
            // One alert per transaction+rule, regardless of window.
            return $query->where('transaction_id', $transactionId)->exists();
        }

        $query->where('entity_id', $entityId ?? 0);

        if ($rule->dedupe_window_minutes !== null) {
            $query->where('created_at', '>=', now()->subMinutes($rule->dedupe_window_minutes));
        } else {
            $query->whereIn('status', [
                RiskAlert::STATUS_OPEN,
                RiskAlert::STATUS_ACKNOWLEDGED,
                RiskAlert::STATUS_INVESTIGATING,
            ]);
        }

        return $query->exists();
    }

    protected function holdOrFail(Transaction $transaction): TransactionHold
    {
        $hold = TransactionHold::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', TransactionHold::STATUS_HELD)
            ->first();

        if ($hold === null) {
            throw new \DomainException("No open hold on transaction [{$transaction->reference}].");
        }

        return $hold;
    }

    protected function setAlertStatus(RiskAlert $alert, string $status, ?int $actorId, ?string $note = null): RiskAlert
    {
        $alert->update([
            'status' => $status,
            'resolved_by' => $actorId,
            'resolved_at' => in_array($status, [RiskAlert::STATUS_RESOLVED, RiskAlert::STATUS_FALSE_POSITIVE], true) ? now() : null,
            'resolution_note' => $note,
        ]);

        AuditLog::record('risk.alert.'.$status, $actorId, $alert->entity_id, [
            'description' => 'Risk alert '.$alert->reference.' → '.$status.($note !== null ? " ({$note})" : ''),
            'event_type' => 'risk',
            'metadata' => ['alert_id' => $alert->id, 'status' => $status, 'note' => $note],
        ]);

        return $alert;
    }

    protected function stateMachine(): TransactionStateMachine
    {
        return app(TransactionStateMachine::class);
    }
}
