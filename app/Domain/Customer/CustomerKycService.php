<?php

namespace App\Domain\Customer;

use App\Models\KycSubmission;
use App\Models\User;

/**
 * CUSTOMER BANKING — Stage 5 (KYC center).
 *
 * Honest revaluation of a customer's verification state from the REAL record
 * table (kyc_submissions) + the denormalized users.kyc_status mirror:
 *
 *   - any APPROVED (verified) submission  ⇒ status = verified (autopass),
 *     regardless of what the users column says;
 *   - otherwise the users.kyc_status mirror is the authority (pending /
 *     unverified / etc.) and the center reports it as informational;
 *   - never invents a verification: no submission ⇒ "unverified".
 *
 * Tier upgrades are RECOMMENDATIONS only — the center explains what the
 * next tier unlocks and what the customer must do, but never fakes a tier.
 */
class CustomerKycService
{
    public function revaluate(User $user): array
    {
        $submission = KycSubmission::query()
            ->where('user_id', $user->id)
            ->latest('submitted_at')
            ->first();

        $approved = $submission !== null && $submission->status === KycSubmission::STATUS_APPROVED;

        if ($approved) {
            $status = 'verified';
            $label = 'Verified';
        } elseif ($submission !== null && $submission->status === KycSubmission::STATUS_PENDING) {
            $status = 'pending';
            $label = 'Review in progress';
        } else {
            $status = strtolower((string) $user->kyc_status) === 'pending'
                ? 'pending'
                : 'unverified';
            $label = match ($status) {
                'pending' => 'Review in progress',
                default => 'Unverified',
            };
        }

        $tier = (int) ($submission?->tier ?? $user->kyc_tier ?? 0);

        return [
            'status' => $status,
            'label' => $label,
            'tier' => $tier,
            'source' => $approved ? 'kyc_submissions.approved' : 'users.kyc_status',
            'has_submission' => $submission !== null,
            'last_submission' => $submission !== null ? [
                'status' => $submission->status,
                'type' => $submission->type,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
            ] : null,
            'recommendation' => $this->recommendation($status, $tier),
            'verification_notes' => $approved
                ? 'Your identity has been verified. All available limits are unlocked.'
                : 'No approved identity verification is on file. Upgrade to unlock higher limits.',
        ];
    }

    /**
     * Tier recommendation — descriptive only. The center never claims a tier
     * the records do not support.
     */
    protected function recommendation(string $status, int $tier): array
    {
        if ($status === 'verified') {
            return [
                'action' => 'none',
                'title' => 'You are fully verified',
                'detail' => 'No action needed. Your tier '.max($tier, 1).' limits are active.',
            ];
        }

        if ($status === 'pending') {
            return [
                'action' => 'wait',
                'title' => 'Review in progress',
                'detail' => 'Your verification is being reviewed. You will be notified when a decision is made.',
            ];
        }

        return [
            'action' => 'upgrade',
            'title' => 'Upgrade to Tier 1',
            'detail' => 'Submit a valid government-issued ID (NIN, BVN, passport or driver’s license) to unlock higher daily limits and currency exchange.',
        ];
    }
}
