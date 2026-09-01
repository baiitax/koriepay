<?php

namespace App\Livewire\Customer;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class FindAgent extends Component
{
    public $userLat;
    public $userLng;
    public $radius = 50; // Search radius in kilometers
    public $search = '';

    /**
     * This method is triggered by JavaScript once the browser 
     * provides the user's GPS coordinates.
     */
    public function updateLocation($lat, $lng)
    {
        $this->userLat = $lat;
        $this->userLng = $lng;
    }

    public function render()
    {
        $agents = collect();

        $agents = User::where('is_agent', true)
            ->where('is_online', true) // <--- CRITICAL: Only show agents who are "Live"
            ->where('kyc_status', 'verified')
            ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [
                $this->userLat, $this->userLng, $this->userLat
            ]);

        if ($this->userLat && $this->userLng) {
            // Haversine Formula: Calculates distance between two points on a sphere
            $agents = User::where('is_agent', true)
                ->where('is_online', true) // <--- CRITICAL: Only show agents who are "Live"
                ->where('kyc_status', 'verified') // Only show verified nodes
                ->when($this->search, function($query) {
                    $query->where('business_name', 'like', '%' . $this->search . '%');
                })
                ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [
                    $this->userLat, $this->userLng, $this->userLat
                ])
                ->having("distance", "<", $this->radius)
                ->orderBy("distance")
                ->get();
        }

        return view('livewire.customer.find-agent', [
            'agents' => $agents
        ]);
    }
}