<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An asynchronous report generation job (§62, §65).
 *
 * Status flow: queued → processing → ready | failed. The generation itself
 * runs in a queued job; every state change is audited. `file_path` points at
 * the produced artifact (CSV/XLSX/PDF) once ready.
 */
class ReportJob extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    public const TYPES = [
        'agent', 'transaction', 'commission', 'liquidity',
        'settlement', 'risk', 'kyc', 'network_growth',
    ];

    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    protected $fillable = [
        'reference', 'aggregator_id', 'type', 'format', 'date_from', 'date_to',
        'status', 'file_path', 'row_count', 'error', 'requested_by',
        'requested_at', 'completed_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'row_count' => 'integer',
    ];

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
