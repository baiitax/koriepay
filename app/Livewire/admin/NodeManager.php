<?php

namespace App\Livewire\admin;

use App\Models\BankNode;
use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Http;

#[Layout('layouts.admin')]
class NodeManager extends Component
{
    use WithPagination;

    public $search = '';
    public $isSyncing = false;

    // The simulated API Client
    public function forceSync($id)
    {
        $node = BankNode::findOrFail($id);
        
        // 1. THE ORACLE CALL: In production, this is your Http::get('zenith.com/api/balance')
        // We simulate network delay and real-world bank fluctuations (-₦50k to +₦250k)
        usleep(rand(300000, 800000)); // Simulate 300-800ms HTTP latency
        $fluctuation = rand(-50000, 250000); 
        $pingMs = rand(12, 85); 
        
        $node->update([
            'balance' => $node->balance + $fluctuation,
            'last_sync' => now(),
            'api_status' => 'online',
            // Note: If you want to store ping permanently, add 'ping_ms' to your DB. 
            // For now, we simulate the live feel.
        ]);

        AuditLog::create([
            'user_id' => auth()->id() ?? 1,
            'user_name' => auth()->user()->name ?? 'System Admin',
            'action' => 'API_SYNC_EXECUTED',
            'event_type' => 'treasury',
            'metadata' => "{$node->bank_name} responded in {$pingMs}ms. Delta: {$fluctuation}",
            'ip_address' => request()->ip()
        ]);

        session()->flash('success', "{$node->bank_name} uplinked. Ping: {$pingMs}ms.");
    }

    // Enterprise Feature: Sync the entire grid at once
    public function syncAllNodes()
    {
        $this->isSyncing = true;
        $nodes = BankNode::where('api_status', 'online')->get();
        
        foreach($nodes as $node) {
            $this->forceSync($node->id);
        }
        
        $this->isSyncing = false;
        session()->flash('success', 'Global network synchronization complete.');
    }

    public function toggleStatus($id)
    {
        $node = BankNode::findOrFail($id);
        $newStatus = $node->api_status === 'online' ? 'maintenance' : 'online';
        $node->update(['api_status' => $newStatus]);
    }

    public function render()
    {
        return view('livewire.admin.node-manager', [
            'nodes' => BankNode::query()
                ->when($this->search, fn($q) => $q->where('bank_name', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
            'ngnPool' => BankNode::where('currency', 'NGN')->sum('balance'),
            'xofPool' => BankNode::where('currency', 'XOF')->sum('balance'),
            'onlineNodes' => BankNode::where('api_status', 'online')->count(),
            'totalNodes' => BankNode::count(),
        ]);
    }
}