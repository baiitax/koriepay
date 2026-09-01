<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FetchLiveFxRates extends Command
{
    protected $signature = 'fx:fetch';
    protected $description = 'Fetch live NGN/XOF rates and cache them with a platform spread';

    public function handle()
    {
        $this->info("Fetching Live FX Rates...");

        try {
            // In a real app, you would use an API like OpenExchangeRates or Flutterwave API here:
            // $response = Http::get('https://api.exchangerate-api.com/v4/latest/NGN');
            // $baseRate = $response->json()['rates']['XOF'];

            // For our simulation, we will mock a fluctuating rate between 0.40 and 0.45
            $baseRateNgnToXof = mt_rand(400, 450) / 1000; // e.g., 0.425
            
            // Apply 1.5% SahelPay Spread (We give them slightly less XOF for their NGN, and charge more NGN for XOF)
            $platformSpread = 0.015; 
            
            $customerRateNgnToXof = $baseRateNgnToXof * (1 - $platformSpread);
            $customerRateXofToNgn = (1 / $baseRateNgnToXof) * (1 + $platformSpread);

            // Store in Cache for 20 minutes (giving us a 5-minute overlap buffer before the next 15-min cron)
            Cache::put('FX_NGN_XOF', $customerRateNgnToXof, now()->addMinutes(20));
            Cache::put('FX_XOF_NGN', $customerRateXofToNgn, now()->addMinutes(20));

            $this->info("Rates updated! 1 NGN = {$customerRateNgnToXof} XOF");

        } catch (\Exception $e) {
            $this->error("Failed to fetch rates: " . $e->getMessage());
        }
    }
}