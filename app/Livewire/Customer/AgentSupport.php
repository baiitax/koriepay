<?php

namespace App\Livewire\Customer;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class AgentSupport extends Component
{
    public $subject = '';
    public $message = '';
    public $category = 'General';
    public $showAiSuggestion = false;
    public $suggestionText = '';

    // Automated Triage Logic
    public function updatedMessage()
    {
        $input = strtolower($this->message);

        if (str_contains($input, 'pin') || str_contains($input, 'password')) {
            $this->showAiSuggestion = true;
            $this->suggestionText = "💡 It looks like you're having security issues. You can reset your Transaction PIN instantly in Security Settings.";
        } elseif (str_contains($input, 'failed') || str_contains($input, 'transaction')) {
            $this->showAiSuggestion = true;
            $this->suggestionText = "🔍 Searching for failed transactions... Please include your SP-Reference number for faster resolution.";
        } else {
            $this->showAiSuggestion = false;
        }
    }

    public function createTicket()
    {
        $this->validate([
            'subject' => 'required|min:5',
            'message' => 'required|min:10',
            'category' => 'required'
        ]);

        SupportTicket::create([
            'user_id' => Auth::id(),
            'ticket_id' => 'SHLP-' . rand(1000, 9999),
            'category' => $this->category,
            'subject' => $this->subject,
            'message' => $this->message,
        ]);

        $this->reset(['subject', 'message', 'category']);
        session()->flash('success', 'Ticket deployed to the support grid.');
    }

    public function render()
    {
        return view('livewire.customer.agent-support', [
            'myTickets' => Auth::user()->supportTickets()->latest()->get()
        ]);
    }
}