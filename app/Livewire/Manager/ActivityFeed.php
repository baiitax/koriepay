<?php

namespace App\Livewire\Manager;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ActivityFeed extends Component
{
    public $countryCode;

    public function mount()
    {
        $this->countryCode = Auth::user()->country_code;
    }

    /**
     * Fetch the latest 20 regional activities.
     * Polling will trigger this method automatically.
     */
    public function render()
    {
        $activities = Transaction::whereHas('user', function ($query) {
            $query->where('country_code', $this->countryCode);
        })
        ->with('user') // Eager load the agent identity
        ->latest()
        ->take(20)
        ->get();

        return view('livewire.manager.activity-feed', [
            'activities' => $activities
        ]);
    }
}