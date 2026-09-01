<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;

trait HandlesPinSecurity
{
    public function verifyPin($user, $inputPin)
    {
        // 1. Check if currently locked
        if ($user->pin_locked_until && now()->lt($user->pin_locked_until)) {
            $remaining = now()->diffInMinutes($user->pin_locked_until);
            $this->addError('transaction_pin', "Account locked. Try again in {$remaining} minutes.");
            return false;
        }

        // 2. Verify PIN
        if (Hash::check($inputPin, $user->transaction_pin)) {
            // Success: Reset attempts
            $user->update([
                'failed_pin_attempts' => 0,
                'pin_locked_until' => null
            ]);
            return true;
        }

        // 3. Failure: Increment attempts
        $attempts = $user->failed_pin_attempts + 1;
        $lockUntil = null;

        if ($attempts >= 3) {
            $lockUntil = now()->addHours(24); // 24-hour lockout
            $message = "3 failed attempts. Your vault is locked for 24 hours.";
        } else {
            $remaining = 3 - $attempts;
            $message = "Invalid PIN. {$remaining} attempts remaining before lockout.";
        }

        $user->update([
            'failed_pin_attempts' => $attempts,
            'pin_locked_until' => $lockUntil
        ]);

        $this->addError('transaction_pin', $message);
        return false;
    }
}