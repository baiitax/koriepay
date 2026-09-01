<?php

use Illuminate\Support\Facades\{Route, Auth, Session, Redirect};
use Illuminate\Http\Request;
use Livewire\Volt\Volt;
use App\Livewire\Customer\Adashi\ManagePool;
use App\Http\Controllers\PaystackWebhookController;


// -----------------------------------------------------------------
// COMPONENT IMPORTS
// -----------------------------------------------------------------

// Customer Components
use App\Livewire\Customer\{
    Dashboard as CustomerDashboard, SendLiquidity, CashHub, 
    History, Beneficiaries, KycVerification, Profile, 
    SecuritySettings as CustomerSecurity, LinkedVaults, AgentSupport,
    FundVault, WithdrawVault, TransactionReceipt, PayHub
};

// Admin Components
use App\Livewire\Admin\{
    Dashboard as AdminDashboard, FxRates, TreasuryVault, KycHub, 
    MasterLedger, AuditLogs as AdminAuditLogs, AgentDirectory, 
    SecuritySettings as AdminSecurity, Network, KycQueue, 
    LiquidityWallets, RevenueLedger, RevenueAnalytics, SystemSettings,
    SettlementDashboard, NodeManager // <-- ADDED THIS
};

// Customer Adashi Components
use App\Livewire\Customer\Adashi\{
    Dashboard as AdashiDashboard, CreatePool, JoinPool, GroupLedger
};

// Manager Components
use App\Livewire\Manager\{
    Dashboard as ManagerDashboard, Compliance, Forecaster, 
    AuditLogs as ManagerAuditLogs, ActivityFeed
};
use App\Http\Controllers\Manager\ReportController;

// -----------------------------------------------------------------
// GUEST ZONE
// -----------------------------------------------------------------

// NOTE (Phase 0): The former unauthenticated GET /clear-cache route was
// REMOVED — it let anyone flush application caches (availability risk).
// Cache control is now admin-only via artisan commands / deployment tooling.

require __DIR__.'/auth.php'; 

// -----------------------------------------------------------------
// GLOBAL AUTH ZONE (Language & Session)
// -----------------------------------------------------------------
Route::middleware(['auth'])->group(function () {

    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');

    Route::get('/lang/{locale}', function ($locale) {
        if (in_array($locale, ['en', 'fr', 'ha'])) {
            Session::put('locale', $locale);
        }
        return Redirect::back();
    })->name('lang.switch');

    // Webhook Gateway (Public, secured by HMAC Signature)
    Route::post('/webhook/paystack', [PaystackWebhookController::class, 'handle'])->name('webhook.paystack');

    // Default Fallback Router
    // Find this in routes/web.php
Route::get('/dashboard', function () {
    $user = Auth::user();

    return match($user->role) {
        'superadmin' => redirect()->route('admin.dashboard'),
        'manager'    => redirect()->route('manager.dashboard'),
        'regional_agent'      => redirect()->intended(route('regional.dashboard')),
        'agent'      => redirect()->route('agent.dashboard'),
        'customer'   => redirect()->route('customer.dashboard'),
        default      => abort(403, 'Unauthorized Role Detected.')
    };
})->middleware(['auth'])->name('dashboard');
});

// -----------------------------------------------------------------
// CUSTOMER PORTAL (The Liquidity Hub)
// -----------------------------------------------------------------
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    
    // Core Vault Actions
    Route::get('/dashboard', CustomerDashboard::class)->name('dashboard');
    Route::get('/pay', PayHub::class)->name('pay');
    Route::get('/fund', FundVault::class)->name('fund');
    Route::get('/send', SendLiquidity::class)->name('send');
    Route::get('/withdraw', WithdrawVault::class)->name('withdraw');

    Route::get('/settings', \App\Livewire\Customer\Settings::class)->name('settings');

    // Ledgers & History
    Route::get('/history', History::class)->name('history');
    Route::get('/cash-hub', CashHub::class)->name('cash-hub');
    Route::get('/vaults', LinkedVaults::class)->name('vaults');
    Route::get('/contacts', Beneficiaries::class)->name('contacts');
    
    // TIER-1 FIX: Everyday Payments (VAS) moved OUTSIDE the Adashi group!
    Route::get('/bills/{category?}', \App\Livewire\Customer\BillPayment::class)->name('bills');
    Route::get('/cards', \App\Livewire\Customer\Cards::class)->name('cards');
    Route::get('/referrals', \App\Livewire\Customer\Referrals::class)->name('referrals');
    
    // Adashi (Community Rings)
    Route::prefix('adashi')->name('adashi.')->group(function () {
        Route::get('/', AdashiDashboard::class)->name('dashboard');
        Route::get('/create', CreatePool::class)->name('create');
        Route::get('/join', JoinPool::class)->name('join');
        Route::get('/{groupId}/manage', ManagePool::class)->name('manage');
        Route::get('/{groupId}/ledger', GroupLedger::class)->name('ledger');
    });

    // Profile & Security
    Route::get('/kyc', KycVerification::class)->name('kyc');
    Route::get('/kyc-center', \App\Livewire\Customer\KycCenter::class)->name('kyc-center');
    Route::get('/profile', Profile::class)->name('profile');
    Route::get('/support', AgentSupport::class)->name('support');
    Route::get('/security', \App\Livewire\Customer\Security::class)->name('security');

}); // <-- THE CUSTOMER GROUP CLOSES HERE

// -----------------------------------------------------------------
// TRANSACTION RECEIPT (Placed OUTSIDE the customer. name group)
// -----------------------------------------------------------------
Route::middleware(['auth'])->get('/receipt/{transaction:reference}', function (\App\Models\Transaction $transaction) {
    
    // Tier-1 Security: Ensure the user is either the sender or receiver
    if (auth()->id() !== $transaction->sender_id && auth()->id() !== $transaction->receiver_id) {
        abort(403, 'Unauthorized access to this receipt.');
    }
    
    // Render the view (Ensure this matches the filename we created: transaction-receipt.blade.php)
    return view('livewire.customer.transaction-receipt', [
        'transaction' => $transaction
    ]);
    
})->name('transaction.receipt'); // Now this perfectly matches what the buttons are looking for!

// -----------------------------------------------------------------
// REGIONAL MANAGER PORTAL (Scoped by Territory)
// -----------------------------------------------------------------
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    // Volt Modules
    Volt::route('/dashboard', 'manager.dashboard')->name('dashboard');
    Volt::route('/agents', 'manager.agents')->name('agents');
    Volt::route('/kyc', 'manager.kyc')->name('kyc');
    Volt::route('/agents/{user}', 'manager.agent-detail')->name('agent-detail');
    Volt::route('/ledger', 'manager.ledger')->name('ledger');
    Volt::route('/treasury', 'manager.treasury')->name('treasury');
    Volt::route('/risk', 'manager.risk')->name('risk');

    // Strategic Analytics & Livewire Components
    Route::get('/compliance', Compliance::class)->name('compliance');
    Route::get('/forecaster', Forecaster::class)->name('forecaster');
    Route::get('/export-report', [ReportController::class, 'exportHealthReport'])->name('export-report');

    // Security & Audit
    Route::get('/audit-logs', ManagerAuditLogs::class)->name('audit-logs');
    Route::get('/activity-feed', ActivityFeed::class)->name('activity-feed');
});
// -----------------------------------------------------------------
// SUPER ADMIN PORTAL (System Command Center)
// -----------------------------------------------------------------
Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    
    // ACTIVE NODES
    Route::get('/dashboard', \App\Livewire\admin\Dashboard::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/nodes', \App\Livewire\admin\NodeManager::class)->middleware('permission:system.view')->name('nodes');
    
    // COMMAND CENTER ROUTE (Notice the name is just 'transactions' now)
    Route::get('/transactions', \App\Livewire\admin\TransactionLedger::class)->middleware('permission:transaction.view')->name('transactions');
    
    // OFFLINE NODES
    Route::get('/directory', \App\Livewire\admin\AgentDirectory::class)->middleware('permission:agent.view')->name('directory');
    Route::get('/treasury', \App\Livewire\admin\TreasuryVault::class)->middleware('permission:wallet.view')->name('treasury');
    Route::get('/liquidity', \App\Livewire\admin\LiquidityWallets::class)->middleware('permission:wallet.view')->name('liquidity-wallets');
    Route::get('/fx-rates', \App\Livewire\admin\FxRates::class)->middleware('permission:fx.view')->name('fx-rates');
    Route::get('/settlements', \App\Livewire\admin\SettlementDashboard::class)->middleware('permission:settlement.view')->name('settlements');
    Route::get('/master-ledger', \App\Livewire\admin\MasterLedger::class)->middleware('permission:ledger.view')->name('master-ledger');
    Route::get('/revenue', \App\Livewire\admin\RevenueLedger::class)->middleware('permission:revenue.view')->name('revenue-ledger');
    Route::get('/analytics', \App\Livewire\admin\RevenueAnalytics::class)->middleware('permission:revenue.view')->name('revenue-analytics');
    Route::get('/kyc-hub', \App\Livewire\admin\KycHub::class)->middleware('permission:kyc.view')->name('kyc-hub');
    Route::get('/kyc-queue', \App\Livewire\admin\KycQueue::class)->middleware('permission:kyc.view')->name('kyc-queue');
    Route::get('/network-health', \App\Livewire\admin\Network::class)->middleware('permission:network.view')->name('network');
    Route::get('/settings', \App\Livewire\admin\SystemSettings::class)->middleware('permission:settings.view')->name('settings');
    Route::get('/security', \App\Livewire\admin\SecuritySettings::class)->middleware('permission:security.view')->name('security');
    Route::get('/audit-logs', \App\Livewire\admin\AuditLogs::class)->middleware('permission:audit.view')->name('audit-logs');
    
    // (I removed the duplicate route that was down here at the bottom)
});

// -----------------------------------------------------------------
// -----------------------------------------------------------------
// AGGREGATOR CONSOLE (Network Command Center)
// -----------------------------------------------------------------
Route::middleware(['auth', 'role:aggregator'])->prefix('aggregator')->name('aggregator.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Aggregator\Dashboard::class)->name('dashboard');

    // Stage B — Agents (§14–22): directory, recruitment, profile.
    // Order matters: /agents/recruit must resolve before /agents/{agent}.
    Route::get('/agents', \App\Livewire\Aggregator\Agents::class)
        ->middleware('permission:agent.view')->name('agents');
    Route::get('/agents/recruit', \App\Livewire\Aggregator\RecruitAgent::class)
        ->middleware('permission:agent.recruit')->name('agents.recruit');
    Route::get('/agents/{agent:agent_code}', \App\Livewire\Aggregator\AgentProfile::class)
        ->middleware('permission:agent.profile.view')->name('agents.show');

    // Stage C — Liquidity (§23–28): command center + request workflow.
    Route::get('/liquidity', \App\Livewire\Aggregator\Liquidity::class)
        ->middleware('permission:liquidity.view')->name('liquidity');

    // Stage E — Commissions & settlement (§38–43, §66–67).
    Route::get('/commissions', \App\Livewire\Aggregator\Commissions::class)
        ->middleware('permission:commission.view')->name('commissions');
    Route::get('/settlements', \App\Livewire\Aggregator\Settlements::class)
        ->middleware('permission:settlement.view')->name('settlements');

    // Stage F — Network intelligence (§44–51).
    Route::get('/network', \App\Livewire\Aggregator\Network::class)
        ->middleware('permission:network.view')->name('network');

    // Stage G — Risk & alerts (§52–57, §142–143).
    Route::get('/risk', \App\Livewire\Aggregator\Risk::class)
        ->middleware('permission:risk.view')->name('risk');

    // Stage H — Support, documents, reports (§59–63).
    Route::get('/support', \App\Livewire\Aggregator\Support::class)
        ->middleware('permission:support.view')->name('support');
    Route::get('/documents', \App\Livewire\Aggregator\Documents::class)
        ->middleware('permission:document.view')->name('documents');
    Route::get('/documents/{document}/download', function (\App\Models\AggregatorDocument $document) {
        $aggregator = app(\App\Domain\Aggregator\AggregatorTenantService::class)->requireCurrent();
        $file = app(\App\Domain\Aggregator\AggregatorDocumentsService::class)->download($document, $aggregator, auth()->user());

        return response()->streamDownload(
            fn () => print((string) \Illuminate\Support\Facades\Storage::disk((string) config('filesystems.default'))->get($file['path'])),
            $file['name'],
            ['Content-Type' => $file['mime']]
        );
    })->middleware('permission:document.view')->name('documents.download');
    Route::get('/reports', \App\Livewire\Aggregator\Reports::class)
        ->middleware('permission:report.view')->name('reports');
    Route::get('/reports/{job}/download', function (\App\Models\ReportJob $job) {
        $aggregator = app(\App\Domain\Aggregator\AggregatorTenantService::class)->requireCurrent();
        $file = app(\App\Domain\Aggregator\AggregatorReportsService::class)->download($job, $aggregator, auth()->user());

        return response()->streamDownload(
            fn () => print((string) \Illuminate\Support\Facades\Storage::disk((string) config('filesystems.default'))->get($file['path'])),
            $file['name'],
            ['Content-Type' => match ($job->format) {
                'csv' => 'text/csv',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pdf' => 'application/pdf',
                default => 'application/octet-stream',
            }]
        );
    })->middleware('permission:report.view')->name('reports.download');

    // Stage I — Insights & EOD (§96–116), profile & limits (§64–65), data quality.
    Route::get('/insights', \App\Livewire\Aggregator\Insights::class)
        ->middleware('permission:network.view')->name('insights');
    Route::get('/profile', \App\Livewire\Aggregator\Profile::class)
        ->middleware('permission:aggregator.profile.view')->name('profile');
    Route::get('/data-quality', \App\Livewire\Aggregator\DataQuality::class)
        ->middleware('permission:network.view')->name('data-quality');

    // Future stages — placeholders render an honest "coming soon" state.
    Route::get('/performance', function () { return view('livewire.aggregator.coming-soon', ['module' => 'Performance']); })->name('performance');
    Route::get('/transactions', function () { return view('livewire.aggregator.coming-soon', ['module' => 'Transactions']); })->name('transactions');
});

// -----------------------------------------------------------------
// AGENT PORTAL (Physical Liquidity Outposts)
Route::middleware(['auth', 'role:agent'])->prefix('agent')->name('agent.')->group(function () {
    
    // Core Terminal
    Route::get('/dashboard', \App\Livewire\Agent\Dashboard::class)->name('dashboard');
    
    // Future expansion routes (We will build these next)
    Route::get('/cash-in', \App\Livewire\Agent\CashIn::class)->name('cash-in');
    Route::get('/cash-out', \App\Livewire\Agent\CashOut::class)->name('cash-out');
    Route::get('/ledger', \App\Livewire\Agent\Ledger::class)->name('ledger');
    // PROFIT ENGINE: The Commission Hub
    Route::get('/commissions', \App\Livewire\Agent\CommissionDashboard::class)->name('commissions');
    Route::get('/cross-border', \App\Livewire\Agent\CrossBorder::class)->name('cross-border');
    // NEW: Command Core (Settings)
    Route::get('/settings', \App\Livewire\Agent\Settings::class)->name('settings');
    Route::get('/fund-wallet', \App\Livewire\Agent\FundWallet::class)->name('fund-wallet');
});

// -----------------------------------------------------------------
// REGIONAL AGENT PORTAL (Field Command)
// -----------------------------------------------------------------
Route::middleware(['auth', 'role:regional_agent'])->prefix('regional')->name('regional.')->group(function () {
    Route::get('/dashboard', \App\Livewire\Regional\Dashboard::class)->name('dashboard');
    Route::get('/capture', \App\Livewire\Regional\CaptureAgent::class)->name('capture');
    Route::get('/kyc-pipeline', \App\Livewire\Regional\KycPipeline::class)->name('kyc');
});


// ---------------------------------------------------------------
//  PUBLIC DOMAIN WEBSITE
// ---------------------------------------------------------------

// 1. CORE MARKETING & PRODUCT
Route::get('/', function () { return view('public.home'); })->name('home');
Route::get('/pricing', function () { return view('public.pricing'); })->name('pricing');
Route::get('/customers', function () { return view('public.customers'); })->name('customers');

Route::prefix('solutions')->name('solutions.')->group(function () {
    Route::get('/p2p-transfers', function () { return view('public.solutions.p2p'); })->name('p2p');
    Route::get('/agency-banking', function () { return view('public.solutions.agency'); })->name('agency');
    Route::get('/adashi-pools', function () { return view('public.solutions.adashi'); })->name('adashi');
    Route::get('/islamic-banking', function () { return view('public.solutions.islamic'); })->name('islamic');
});

// 2. DEVELOPER & TECHNICAL HUB
Route::prefix('developers')->name('developers.')->group(function () {
    Route::get('/docs', function () { return view('public.developers.docs'); })->name('docs');
    Route::get('/api-reference', function () { return view('public.developers.api'); })->name('api');
    // System status usually points to an external service like Atlassian Statuspage, but we route it here:
    Route::get('/status', function () { return view('public.developers.status'); })->name('status'); 
});

// 3. TRUST, SECURITY & COMPLIANCE
Route::prefix('trust')->name('trust.')->group(function () {
    Route::get('/security', function () { return view('public.trust.security'); })->name('security');
    Route::get('/compliance', function () { return view('public.trust.compliance'); })->name('compliance');
    Route::get('/privacy', function () { return view('public.trust.privacy'); })->name('privacy');
    Route::get('/terms', function () { return view('public.trust.terms'); })->name('terms');
});

// 4. COMPANY & CORPORATE IDENTITY
Route::prefix('company')->name('company.')->group(function () {
    Route::get('/about', function () { return view('public.company.about'); })->name('about');
    Route::get('/careers', function () { return view('public.company.careers'); })->name('careers');
    Route::get('/press', function () { return view('public.company.press'); })->name('press');
});

// 5. SUPPORT & RESOURCES
Route::prefix('support')->name('support.')->group(function () {
    Route::get('/help', function () { return view('public.support.help'); })->name('help');
    Route::get('/contact', function () { return view('public.support.contact'); })->name('contact');
    Route::get('/blog', function () { return view('public.support.blog'); })->name('blog');
});