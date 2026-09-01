<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 5 — PAYMENT CORE
 *
 *  1. transactions — add the payment-core columns the orchestrator writes:
 *     provider, rail, provider_reference, country_code, idempotency_key,
 *     ledger_transaction_id, error_reason. All additive; the legacy columns
 *     are untouched so existing UI continues to work.
 *  2. Seed payment_providers + payment_rails with the INTERNAL LEDGER RAIL —
 *     a real, operational rail that moves money through LedgerService. This is
 *     the platform's own wallet rail (NGN & XOF); external providers
 *     (Paystack, DusuPay, …) are registered by configuration in later phases
 *     with real credentials — never fabricated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'provider')) {
                $table->string('provider', 40)->nullable()->after('type');
            }
            if (! Schema::hasColumn('transactions', 'rail')) {
                $table->string('rail', 40)->nullable()->after('provider');
            }
            if (! Schema::hasColumn('transactions', 'provider_reference')) {
                $table->string('provider_reference', 128)->nullable()->after('rail');
            }
            if (! Schema::hasColumn('transactions', 'country_code')) {
                $table->string('country_code', 3)->nullable()->after('provider_reference');
            }
            if (! Schema::hasColumn('transactions', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->after('country_code');
            }
            if (! Schema::hasColumn('transactions', 'ledger_transaction_id')) {
                $table->unsignedBigInteger('ledger_transaction_id')->nullable()->after('idempotency_key');
            }
            if (! Schema::hasColumn('transactions', 'error_reason')) {
                $table->text('error_reason')->nullable()->after('status');
            }
        });

        // Index for provider-performance queries (the Command Center reads these).
        // idempotency_key is UNIQUE (nullable — SQLite/MySQL allow multiple NULLs)
        // so concurrent duplicate submissions collapse onto one operational row.
        if (! collect(Schema::getIndexes('transactions'))->pluck('name')->contains('transactions_idempotency_key_unique')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unique('idempotency_key');
            });
        }
        if (! collect(Schema::getIndexes('transactions'))->pluck('name')->contains('transactions_provider_reference_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('provider_reference');
            });
        }

        // ── Internal ledger rail provider (real, operational) ───────────────
        DB::table('payment_providers')->insertOrIgnore([
            'code' => 'ledger',
            'name' => 'KoriePay Ledger Rail',
            'type' => 'wallet',
            'is_active' => true,
            'health_score' => 100,
            'capabilities' => json_encode(['transfer', 'deposit', 'withdraw']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payment_rails')->insertOrIgnore([
            ['code' => 'WALLET_NG', 'name' => 'Internal Wallet (NGN)', 'country_iso2' => 'NG', 'kind' => 'wallet', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'WALLET_NE', 'name' => 'Internal Wallet (XOF)', 'country_iso2' => 'NE', 'kind' => 'wallet', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the UNIQUE index on idempotency_key FIRST — SQLite refuses
            // to drop a column that is still referenced by a unique index.
            foreach ([
                'transactions_idempotency_key_unique',
                'transactions_provider_reference_index',
                'transactions_idempotency_key_index',
            ] as $index) {
                if (collect(Schema::getIndexes('transactions'))->pluck('name')->contains($index)) {
                    $table->dropIndex($index);
                }
            }
            foreach (['provider', 'rail', 'provider_reference', 'country_code', 'idempotency_key', 'ledger_transaction_id', 'error_reason'] as $col) {
                if (Schema::hasColumn('transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
