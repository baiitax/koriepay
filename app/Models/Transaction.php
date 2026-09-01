<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /**
     * 1. TIER-1 CONSTANTS (Anti-Typo Architecture)
     * Never hardcode strings like 'pending' in your controllers. 
     * Use Transaction::STATUS_COMPLETED instead to prevent spelling errors that break ledgers.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';

    /**
     * 2. STRICT MASS ASSIGNMENT
     */
    protected $fillable = [
        'sender_id', 
        'receiver_id',
        'receiver_name', 
        'type',
        'source_currency', 
        'destination_currency', 
        'source_amount', 
        'destination_amount', 
        'exchange_rate', 
        'fee_charged',
        'status', 
        'reference',
        'description',
        // Phase 5 payment core
        'provider',
        'rail',
        'provider_reference',
        'country_code',
        'idempotency_key',
        'ledger_transaction_id',
        'error_reason',
    ];

//     protected $fillable = [
//     'sender_id', 'receiver_id', 'receiver_name', 'type', 
//     'currency', 'source_amount', 'destination_amount', 
//     'fee', 'status', 'reference', 'description'
// ];

    /**
     * 3. DATA TYPE CASTING
     */
    protected $casts = [
        'source_amount'      => 'decimal:2',
        'destination_amount' => 'decimal:2',
        'fee_charged'        => 'decimal:2',
        'exchange_rate'      => 'decimal:4',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    /**
     * 4. BOOT LIFECYCLE HOOKS
     * Automatically generates a secure, unique reference number BEFORE saving to the database.
     * This keeps your controllers completely clean.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                // Generates something like: KP-TRX-8A4B9C2DF1
                $transaction->reference = 'KP-TRX-' . strtoupper(Str::random(10));
            }
        });
    }

    /**
     * 5. SECURITY: ROUTE KEY BINDING
     * Forces Laravel to use the 'reference' column in URLs instead of the 'id'.
     * Example: koriepay.com/receipts/KP-TRX-8A4B9C2DF1 (Instead of /receipts/7)
     * This prevents hackers from guessing transaction IDs.
     */
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * 6. ELOQUENT RELATIONSHIPS
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * 7. VIRTUAL ATTRIBUTES
     */
    public function getIsCreditAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_DEPOSIT, self::TYPE_TRANSFER_IN]);
    }

    /**
     * 8. QUERY SCOPES (For incredibly clean controller logic)
     * Allows you to write: Transaction::completed()->whereSender($user)->get();
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}