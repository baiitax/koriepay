<?php

namespace App\Livewire\Manager;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\{Component, WithPagination, Attributes\Layout};

#[Layout('layouts.app')]
class AuditLogs extends Component
{
    use WithPagination;

    public function render()
    {
        $logs = AuditLog::where('user_id', Auth::id())
            ->with('targetAgent') // Assuming you add this relationship to AuditLog model
            ->latest()
            ->paginate(20);

        return view('livewire.manager.audit-logs', compact('logs'));
    }
}