<?php

namespace App\Observers;

use App\Models\FxRate;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class FxRateObserver
{
    /**
     * Handle the FxRate "updated" event.
     */
    public function updated(FxRate $fxRate): void
    {
        // Calculate what changed
        $changes = $fxRate->getChanges();
        unset($changes['updated_at']); // We don't need to log the timestamp change itself

        if (!empty($changes)) {
            AuditLog::create([
                'user_id' => Auth::id() ?? 1, // Fallback to System ID if run from console/jobs
                'user_name' => Auth::user()->name ?? 'System Engine',
                'action' => 'FX_RATE_ADJUST',
                'event_type' => 'treasury',
                'metadata' => 'Pair ' . $fxRate->pair . ' updated: ' . json_encode($changes),
                'ip_address' => request()->ip() ?? '127.0.0.1'
            ]);
        }
    }
}