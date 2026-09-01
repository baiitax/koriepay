<?php

use App\Http\Controllers\Api\V1\Customer\DashboardController;
use App\Http\Controllers\Api\V1\Customer\ExchangeExecuteController;
use App\Http\Controllers\Api\V1\Customer\ExchangeQuoteController;
use App\Http\Controllers\Api\V1\Customer\ReceiveController;
use App\Http\Controllers\Api\V1\Customer\TransactionController;
use App\Http\Controllers\Api\V1\Customer\TransferController;
use App\Http\Controllers\Api\V1\Customer\WalletController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| KORIEPAY API — /api/v1 (Phase 5 Payment Core)
|--------------------------------------------------------------------------
|
| Transactional API surface, kept SEPARATE from the dashboard APIs
| (directive §78). All money-moving endpoints require:
|   - auth:sanctum (token or session)
|   - an Idempotency-Key header (1–64 chars) — mandatory
|
| Webhook receiver: POST /api/v1/webhooks/{provider} — HMAC fail-closed;
| the internal rail does not accept external webhooks.
*/

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::prefix('payments')->group(function () {
            Route::post('deposit', [PaymentController::class, 'deposit'])->name('api.payments.deposit');
            Route::post('withdraw', [PaymentController::class, 'withdraw'])->name('api.payments.withdraw');
            Route::post('transfer', [PaymentController::class, 'transfer'])->name('api.payments.transfer');
            Route::get('{reference}', [PaymentController::class, 'status'])->name('api.payments.status');
        });

        // CUSTOMER BANKING APP (§89–§92) — ownership enforced server-side.
        Route::prefix('customer')->name('api.customer.')->group(function () {
            Route::get('dashboard', DashboardController::class)->name('dashboard');
            Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
            Route::get('wallets/{walletId}', [WalletController::class, 'show'])->name('wallets.show');
            Route::get('wallets/{walletId}/balance', [WalletController::class, 'balance'])->name('wallets.balance');
            Route::post('wallets/{walletId}/select', [WalletController::class, 'select'])->name('wallets.select');
            Route::post('exchange/quote', [ExchangeQuoteController::class, 'quote'])->name('exchange.quote');
            Route::post('exchange/execute', [ExchangeExecuteController::class, 'execute'])->name('exchange.execute');

            // STAGE 5 — profile security (session-only; never persisted).
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::post('security/biometric', [\App\Http\Controllers\Api\V1\Customer\Profile\DeviceController::class, 'biometric'])->name('security.biometric');
                Route::post('pin/enroll', [\App\Http\Controllers\Api\V1\Customer\Profile\DeviceController::class, 'enrollPin'])->name('pin.enroll');
            });

            // STAGE 2 — money movement (§128 send/receive journeys).
            Route::prefix('transfers')->name('transfers.')->group(function () {
                Route::post('preview', [TransferController::class, 'preview'])->name('preview');
                Route::post('/', [TransferController::class, 'send'])->name('send');
                Route::get('{reference}', [TransferController::class, 'status'])->name('status');
            });
            Route::get('receive', [ReceiveController::class, 'identity'])->name('receive');

            // STAGE 4 — transaction history & receipt status.
            Route::prefix('transactions')->name('transactions.')->group(function () {
                Route::get('/', [TransactionController::class, 'index'])->name('index');
                Route::get('{reference}/verify', [TransactionController::class, 'verify'])->name('verify');
                Route::get('{reference}', [TransactionController::class, 'show'])->name('show');
            });
        });
    });

    // Webhook receiver — no auth guard; the provider signature IS the auth.
    Route::post('webhooks/{provider}', [WebhookController::class, 'handle'])
        ->name('api.webhooks.handle');
});
