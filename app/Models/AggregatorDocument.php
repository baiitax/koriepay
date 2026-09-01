<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An authorized document in the aggregator document center (§63).
 *
 * "Authorized docs only" is enforced at query time: an aggregator can only
 * ever list/see (a) documents uploaded by their own tenant and (b)
 * system-published documents (`is_system = true`) issued by KoriePay for
 * every aggregator. Nothing else leaks across tenants (IDOR §133).
 */
class AggregatorDocument extends Model
{
    public const CATEGORIES = [
        'kyc', 'agent_onboarding', 'rate_card', 'settlement_statement',
        'compliance', 'training', 'other',
    ];

    public const VISIBILITY_NETWORK = 'network';
    public const VISIBILITY_INTERNAL = 'internal';

    protected $fillable = [
        'aggregator_id', 'category', 'title', 'file_path', 'file_name',
        'mime', 'size_bytes', 'visibility', 'is_system', 'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_system' => 'boolean',
    ];

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
