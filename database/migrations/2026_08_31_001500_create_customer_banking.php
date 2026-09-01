<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CUSTOMER BANKING APP — Stage 1 (Foundation).
 *
 * customer_wallets          — customer-facing read model over the LEDGER.
 *   Balance is NEVER stored here: available = ledger_account projection,
 *   pending = real in-flight transactions. wallet_id is the public identity
 *   (e.g. "wal_xof_001") the API/brief §90 uses.
 *
 * customer_wallet_configs   — data-driven country/KYC eligibility (§75).
 *   Which wallets a customer may hold/fund/send/receive, primary currency,
 *   min KYC tier, per-wallet daily limits, exchange eligibility.
 *
 * exchange_quotes           — server-authoritative, expiring quotes (§39/§91).
 *   Bound to user + source/destination wallets + amount + risk context.
 *   The frontend never computes the rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_wallet_configs', function (Blueprint $table) {
            $table->id();
            $table->string('country_iso2', 2);
            $table->string('currency_code', 3);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_primary_default')->default(false);
            $table->unsignedTinyInteger('min_kyc_tier')->default(1);
            $table->string('display_name')->nullable();       // e.g. "XOF Wallet"
            $table->decimal('daily_send_limit', 20, 2)->nullable();
            $table->decimal('daily_exchange_limit', 20, 2)->nullable();
            $table->decimal('exchange_fee_flat', 20, 2)->default(0);   // flat fee in wallet currency
            $table->decimal('exchange_fee_rate', 8, 4)->default(0);    // % of source amount
            $table->timestamps();

            $table->unique(['country_iso2', 'currency_code']);
        });

        // Seed: Niger → XOF primary; Nigeria → NGN primary. Secondary wallets
        // are NOT auto-enabled — ops enables via config when legal/operational
        // (brief §10, §75).
        DB::table('customer_wallet_configs')->insert([
            [
                'country_iso2' => 'NE', 'currency_code' => 'XOF',
                'is_available' => 1, 'is_primary_default' => 1, 'min_kyc_tier' => 1,
                'display_name' => 'XOF Wallet',
                'daily_send_limit' => '1000000.00', 'daily_exchange_limit' => '1000000.00',
                'exchange_fee_flat' => '500', 'exchange_fee_rate' => '0.5000',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'country_iso2' => 'NG', 'currency_code' => 'NGN',
                'is_available' => 1, 'is_primary_default' => 1, 'min_kyc_tier' => 1,
                'display_name' => 'NGN Wallet',
                'daily_send_limit' => '5000000.00', 'daily_exchange_limit' => '5000000.00',
                'exchange_fee_flat' => '100', 'exchange_fee_rate' => '0.5000',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        Schema::create('customer_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('wallet_id', 40)->unique();         // wal_xof_001 (public)
            $table->unsignedBigInteger('user_id');
            $table->string('currency_code', 3);
            $table->string('display_name', 40);
            $table->boolean('is_primary')->default(false);
            $table->string('status', 20)->default('active');   // active|inactive|unavailable
            $table->unsignedBigInteger('ledger_account_id');   // source of truth for balance
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('ledger_account_id')->references('id')->on('ledger_accounts');
            $table->unique(['user_id', 'currency_code']);
            $table->index('user_id');
        });

        Schema::create('exchange_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_id', 40)->unique();          // Q-…
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('from_wallet_id');
            $table->unsignedBigInteger('to_wallet_id');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('source_amount', 20, 2);
            $table->decimal('destination_amount', 20, 2);
            $table->decimal('exchange_rate', 18, 6);
            $table->decimal('exchange_fee', 20, 2);
            $table->decimal('total_debit', 20, 2);
            $table->string('status', 20)->default('created');  // created|used|expired|cancelled
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['quote_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_quotes');
        Schema::dropIfExists('customer_wallets');
        Schema::dropIfExists('customer_wallet_configs');
    }
};
