<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CUSTOMER BANKING APP — Stage 2 (Money movement).
 *
 * 1. users.koriepay_id        — public receive/send identity (§128 receive
 *    journey: "Share your KoriePay ID to receive"). Backfilled for existing
 *    users as KP-<base36 user id>.
 * 2. customer_wallet_configs  — transfer_fee_flat / transfer_fee_rate
 *    (data-driven, sender's currency config; same pattern as exchange fees).
 *    Transfer fee accrues to Platform Revenue through the ledger — never a
 *    balance field.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Public identity ───────────────────────────────────────────────
        if (! Schema::hasColumn('users', 'koriepay_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('koriepay_id', 24)->nullable()->unique()->after('id');
            });
        }

        // Backfill existing users deterministically (idempotent).
        DB::table('users')
            ->whereNull('koriepay_id')
            ->orderBy('id')
            ->each(function ($user) {
                $kp = 'KP-'.strtoupper(base_convert((string) $user->id, 10, 36));
                DB::table('users')->where('id', $user->id)->update(['koriepay_id' => $kp]);
            });

        // ── Transfer fees (sender's country+currency config) ──────────────
        if (! Schema::hasColumn('customer_wallet_configs', 'transfer_fee_flat')) {
            Schema::table('customer_wallet_configs', function (Blueprint $table) {
                $table->decimal('transfer_fee_flat', 20, 2)->default(0)->after('exchange_fee_rate');
                $table->decimal('transfer_fee_rate', 8, 4)->default(0)->after('transfer_fee_flat');
            });
        }

        // Seed dev transfer fees: small flat fees per primary currency.
        DB::table('customer_wallet_configs')
            ->where('country_iso2', 'NE')->where('currency_code', 'XOF')
            ->update(['transfer_fee_flat' => '50', 'transfer_fee_rate' => '0.0000']);

        DB::table('customer_wallet_configs')
            ->where('country_iso2', 'NG')->where('currency_code', 'NGN')
            ->update(['transfer_fee_flat' => '20', 'transfer_fee_rate' => '0.0000']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'koriepay_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['koriepay_id']);
                $table->dropColumn('koriepay_id');
            });
        }

        if (Schema::hasColumn('customer_wallet_configs', 'transfer_fee_flat')) {
            Schema::table('customer_wallet_configs', function (Blueprint $table) {
                $table->dropColumn(['transfer_fee_flat', 'transfer_fee_rate']);
            });
        }
    }
};
