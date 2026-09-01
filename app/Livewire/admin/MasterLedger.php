<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class MasterLedger extends Component
{
    public function render()
    {
        return view('livewire.admin.master-ledger');
    }
}
