<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FundsReceived extends Notification
{
    use Queueable;

    public $amount;
    public $currency;
    public $senderName;

    public function __construct($amount, $currency, $senderName)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->senderName = $senderName;
    }

    public function via($notifiable): array
    {
        return ['database']; // You can add 'mail' or 'sms' (Nexmo/Twilio) here later
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Liquidity Received!',
            'message' => "Your vault has been credited with {$this->currency} " . number_format($this->amount, 2) . " from {$this->senderName}.",
            'icon' => 'plus-circle',
            'type' => 'credit',
        ];
    }
}