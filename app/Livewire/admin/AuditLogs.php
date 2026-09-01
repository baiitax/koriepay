<?php

namespace App\Livewire\admin;

use App\Models\AuditLog; // Ensure you have this model
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class AuditLogs extends Component
{
    use WithPagination;

    public $search = '';
    public $filterEvent = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditLog::query()
            ->when($this->search, function($query) {
                $query->where('user_name', 'like', '%' . $this->search . '%')
                      ->orWhere('action', 'like', '%' . $this->search . '%')
                      ->orWhere('metadata', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterEvent, function($query) {
                $query->where('event_type', $this->filterEvent);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.audit-logs', [
            'logs' => $logs
        ]);
    }
}