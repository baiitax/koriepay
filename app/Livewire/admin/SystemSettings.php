<?php

namespace App\Livewire\admin;

use App\Models\Setting;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class SystemSettings extends Component
{
    public $activeTab = 'general';

    public $siteName;
    public $maintenanceMode;
    public $maxTransactionLimit;
    public $platformFee;

    public function mount()
    {
        // Load settings from DB, fallback to defaults if they don't exist yet
        $this->siteName = Setting::where('key', 'siteName')->value('value') ?? 'KoriePay';
        $this->maintenanceMode = Setting::where('key', 'maintenanceMode')->value('value') == 'true';
        $this->maxTransactionLimit = Setting::where('key', 'maxTransactionLimit')->value('value') ?? 5000000;
        $this->platformFee = Setting::where('key', 'platformFee')->value('value') ?? 1.5;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function saveSettings()
    {
        // Update or Create the keys in the database
        Setting::updateOrCreate(['key' => 'siteName'], ['value' => $this->siteName]);
        Setting::updateOrCreate(['key' => 'maintenanceMode'], ['value' => $this->maintenanceMode ? 'true' : 'false']);
        Setting::updateOrCreate(['key' => 'maxTransactionLimit'], ['value' => $this->maxTransactionLimit]);
        Setting::updateOrCreate(['key' => 'platformFee'], ['value' => $this->platformFee]);

        // Automatically log this critical system change
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'System Admin',
            'action' => 'SYSTEM_CONFIG_UPDATED',
            'event_type' => 'security',
            'metadata' => 'Global configurations synchronized.',
            'ip_address' => request()->ip()
        ]);

        session()->flash('status', 'System Configuration Synchronized.');
    }

    public function render()
    {
        return view('livewire.admin.system-settings');
    }
}