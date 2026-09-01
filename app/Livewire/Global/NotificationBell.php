<?php

namespace App\Livewire\Global;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public function markAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.global.notification-bell', [
            'notifications' => Auth::user()->notifications()->take(5)->get(),
            'unreadCount' => Auth::user()->unreadNotifications()->count()
        ]);
    }
}