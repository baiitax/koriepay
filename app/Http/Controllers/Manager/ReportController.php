<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function exportHealthReport()
    {
        $user = Auth::user();
        $countryCode = $user->country_code;
        $currency = $countryCode === 'NGA' ? 'NGN' : 'XOF';

        // 1. Aggregate Data (Similar to our Dashboard logic)
        $data = [
            'report_date' => now()->format('d M, Y H:i'),
            'manager_name' => $user->name,
            'region' => $countryCode === 'NGA' ? 'Nigeria' : 'Niger',
            
            // Financials
            'total_liquidity' => Wallet::where('currency_code', $currency)
                ->whereHas('user', fn($q) => $q->where('country_code', $countryCode))
                ->sum('balance'),
                
            'active_agents' => User::where('role', 'agent')->where('country_code', $countryCode)->where('is_active', true)->count(),
            
            // Revenue MTD
            'monthly_revenue' => Transaction::whereHas('user', fn($q) => $q->where('country_code', $countryCode))
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('fee'),

            // Top 5 Terminals
            'top_agents' => User::where('role', 'agent')
                ->where('country_code', $countryCode)
                ->withSum(['transactions as volume' => fn($q) => $q->where('status', 'completed')], 'amount')
                ->orderByDesc('volume')
                ->take(5)
                ->get(),
        ];

        // 2. Load View and Generate PDF
        $pdf = Pdf::loadView('reports.regional-health', $data);

        // 3. Return stream for download
        return $pdf->download("SahelPay_Health_Report_{$countryCode}_" . now()->format('Y-m-d') . ".pdf");
    }
}