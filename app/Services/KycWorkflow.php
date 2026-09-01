<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KycSubmission;
use App\Models\User;

/**
 * KYC/KYB decision workflow (Phase 4).
 *
 * Single canonical path for verification decisions: it keeps the formal
 * kyc_submissions record AND the user.kyc_status mirror in sync, records a
 * canonical audit entry, and never lets a rule label anyone "fraud" — a
 * decision is a risk indicator until formally reviewed (directive §31).
 */
class KycWorkflow
{
    public static function approve(User $user, ?User $reviewer, array $context = []): KycSubmission
    {
        $submission = static::pendingOrNew($user);

        $submission->forceFill([
            'status' => KycSubmission::STATUS_APPROVED,
            'tier' => $context['tier'] ?? 'tier2',
            'reviewer_id' => $reviewer?->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $user->forceFill([
            'kyc_status' => 'verified',
            'kyc_tier' => $context['tier'] === 'tier3' ? 3 : 2,
        ])->save();

        AuditLog::record(
            'kyc.approved',
            $reviewer?->id,
            $user->id,
            [
                'event_type' => 'compliance',
                'description' => "KYC approved for {$user->name} (tier {$submission->tier}).",
                'metadata' => ['submission_id' => $submission->id, 'tier' => $submission->tier],
            ] + $context,
        );

        return $submission;
    }

    public static function reject(User $user, ?User $reviewer, string $reason, array $context = []): KycSubmission
    {
        $submission = static::pendingOrNew($user);

        $submission->forceFill([
            'status' => KycSubmission::STATUS_REJECTED,
            'reviewer_id' => $reviewer?->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $user->forceFill([
            'kyc_status' => 'rejected',
            'kyc_tier' => 0,
        ])->save();

        AuditLog::record(
            'kyc.rejected',
            $reviewer?->id,
            $user->id,
            [
                'event_type' => 'compliance',
                'description' => "KYC rejected for {$user->name}: {$reason}",
                'metadata' => ['submission_id' => $submission->id, 'reason' => $reason],
            ] + $context,
        );

        return $submission;
    }

    public static function markForManualReview(User $user, ?User $reviewer, ?string $note = null): KycSubmission
    {
        $submission = static::pendingOrNew($user);

        $submission->forceFill([
            'status' => KycSubmission::STATUS_MANUAL_REVIEW,
            'reviewer_id' => $reviewer?->id,
            'reviewed_at' => $note !== null ? now() : $submission->reviewed_at,
        ])->save();

        AuditLog::record(
            'kyc.manual_review',
            $reviewer?->id,
            $user->id,
            [
                'event_type' => 'compliance',
                'description' => "KYC flagged for manual review for {$user->name}.".
                    ($note !== null ? " Note: {$note}" : ''),
                'metadata' => ['submission_id' => $submission->id, 'note' => $note],
            ],
        );

        return $submission;
    }

    public static function expire(User $user, array $context = []): KycSubmission
    {
        $submission = static::pendingOrNew($user);

        $submission->forceFill([
            'status' => KycSubmission::STATUS_EXPIRED,
            'reviewed_at' => now(),
        ])->save();

        AuditLog::record(
            'kyc.expired',
            null,
            $user->id,
            [
                'event_type' => 'compliance',
                'description' => "KYC submission expired for {$user->name}.",
                'metadata' => ['submission_id' => $submission->id],
            ] + $context,
        );

        return $submission;
    }

    /** Latest submission or a fresh pending one (idempotent per user). */
    private static function pendingOrNew(User $user): KycSubmission
    {
        return $user->kycSubmissions()
            ->where('status', KycSubmission::STATUS_PENDING)
            ->latest('id')
            ->first()
            ?? $user->kycSubmissions()->create([
                'type' => KycSubmission::TYPE_PERSONAL,
                'status' => KycSubmission::STATUS_PENDING,
                'tier' => 'tier1',
                'country_code' => $user->country_code,
                'submitted_at' => now(),
            ]);
    }
}
