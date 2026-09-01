<?php

namespace App\Domain\Risk;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * PHASE 7 — Maker–checker approval inbox.
 *
 * The maker SUBMITS a pending request; only a DIFFERENT user may approve or
 * reject it — the invariant "maker can never approve own request" is enforced
 * here, server-side, not in the frontend (§42 of the Command Center brief).
 * Every submission and decision is audited.
 */
class ApprovalService
{
    public function submit(
        int $makerId,
        string $actionType,
        string $reason,
        ?string $entityType = null,
        ?int $entityId = null,
        array $payload = [],
        ?int $slaHours = 48,
    ): ApprovalRequest {
        $request = ApprovalRequest::create([
            'reference' => 'APR-'.strtoupper(Str::random(10)),
            'maker_id' => $makerId,
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => $payload,
            'reason' => $reason,
            'status' => ApprovalRequest::STATUS_PENDING,
            'sla_due_at' => $slaHours !== null ? now()->addHours($slaHours) : null,
        ]);

        AuditLog::record('approval.submitted', $makerId, $entityId ?? $makerId, [
            'description' => 'Approval request '.$request->reference.' — '.$actionType.': '.$reason,
            'event_type' => 'operations',
            'metadata' => ['request_id' => $request->id, 'action_type' => $actionType, 'payload' => $payload],
        ]);

        return $request;
    }

    /**
     * @throws \DomainException when the request is not decidable (missing /
     *         already decided) or the approver is the maker.
     */
    public function decide(int $approverId, int $requestId, bool $approve, ?string $note = null): ApprovalRequest
    {
        $request = ApprovalRequest::find($requestId);

        if ($request === null) {
            throw new \DomainException("Approval request #{$requestId} not found.");
        }

        if ($request->status !== ApprovalRequest::STATUS_PENDING) {
            throw new \DomainException(
                "Approval request [{$request->reference}] already {$request->status}."
            );
        }

        if ($request->maker_id === $approverId) {
            throw new \DomainException(
                "Maker [{$approverId}] cannot approve own request [{$request->reference}]."
            );
        }

        $status = $approve ? ApprovalRequest::STATUS_APPROVED : ApprovalRequest::STATUS_REJECTED;

        $request->update([
            'status' => $status,
            'checker_id' => $approverId,
            'decided_by' => $approverId,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        AuditLog::record('approval.'.$status, $approverId, $request->entity_id ?? $request->maker_id, [
            'description' => 'Approval request '.$request->reference.' '.$status.' by user #'.$approverId.($note !== null ? " — {$note}" : ''),
            'event_type' => 'operations',
            'metadata' => ['request_id' => $request->id, 'note' => $note],
        ]);

        return $request;
    }

    /**
     * Requests awaiting THIS user's decision (i.e. not their own).
     */
    public function inboxFor(int $userId): Collection
    {
        return ApprovalRequest::query()
            ->where('status', ApprovalRequest::STATUS_PENDING)
            ->where('maker_id', '!=', $userId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * This user's own pending requests (the "MY PENDING REQUESTS" rail).
     */
    public function mine(int $userId): Collection
    {
        return ApprovalRequest::query()
            ->where('maker_id', $userId)
            ->where('status', ApprovalRequest::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();
    }
}
