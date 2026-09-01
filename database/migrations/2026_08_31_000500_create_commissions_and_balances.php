<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 — Configurable commission engine + balance snapshots for
 * ledger↔projection reconciliation.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Commission rules are DATA, never code. A rule matches a transaction
        // profile and yields splits; the engine resolves the highest-priority
        // matching rule (see CommissionEngine in Phase 6).
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_iso2', 2)->nullable();     // null = all countries
            $table->string('transaction_type', 40)->nullable(); // cash_in, cash_out, p2p, cross_border, withdrawal…
            $table->string('channel', 40)->nullable();         // agent, customer, aggregator
            $table->string('agent_tier', 20)->nullable();      // bronze|silver|gold
            $table->string('customer_segment', 20)->nullable();
            $table->decimal('min_amount', 20, 2)->nullable();
            $table->decimal('max_amount', 20, 2)->nullable();
            $table->decimal('rate', 8, 4)->nullable();         // % of principal
            $table->decimal('flat_amount', 20, 2)->nullable(); // or flat per tx
            $table->unsignedTinyInteger('priority')->default(100); // lower = wins
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_iso2', 'transaction_type', 'is_active']);
        });

        // A resolved commission split for one beneficiary.
        Schema::create('commission_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ledger_transaction_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('beneficiary_id')->nullable(); // agent/aggregator user id
            $table->string('beneficiary_type', 20);            // agent|aggregator|platform
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('currency_code', 3);
            $table->decimal('amount', 20, 2);
            $table->string('status', 20)->default('accrued'); // accrued|paid|reversed
            $table->timestamps();

            $table->index(['beneficiary_id', 'beneficiary_type', 'status']);
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('commission_accruals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commission_entry_id');
            $table->unsignedBigInteger('ledger_account_id');   // accrual (liability) account
            $table->timestamps();
        });

        // Periodic frozen view of projected balances, used to reconcile
        // projection vs ledger-derived balance.
        Schema::create('balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->decimal('projected_balance', 20, 2);
            $table->decimal('derived_balance', 20, 2);
            $table->decimal('difference', 20, 2);
            $table->string('status', 20);                     // MATCHED|MISMATCH
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->index(['account_id', 'snapshot_at']);
            $table->index(['status', 'snapshot_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_snapshots');
        Schema::dropIfExists('commission_accruals');
        Schema::dropIfExists('commission_entries');
        Schema::dropIfExists('commission_rules');
    }
};
