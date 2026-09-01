<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class RevenueAnalytics extends Component
{
    public function render()
    {
        return view('livewire.admin.revenue-analytics');
    }
}
