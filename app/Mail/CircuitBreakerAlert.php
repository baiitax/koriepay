<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CircuitBreakerAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public float $margin;
    public float $volume;
    public string $timestamp;

    public function __construct(float $margin, float $volume)
    {
        $this->margin = $margin;
        $this->volume = $volume;
        $this->timestamp = Carbon::now('Africa/Lagos')->format('Y-m-d H:i:s T');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CRITICAL: SahelPay Cross-Border Gateway Halted',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.circuit-breaker-alert',
        );
    }
}