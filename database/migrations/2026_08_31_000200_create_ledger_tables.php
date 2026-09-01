<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — The immutable double-entry ledger (the core of the rebuild).
 *
 * Principle: NO monetary movement may ever depend on mutating a balance field
 * directly. Every movement is a set of balanced ledger entries. Wallet balances
 * are projections maintained from these entries.
 *
 * ledger_accounts      — chart of accounts (wallet accounts, float accounts,
 *                        revenue/commission accounts, clearing accounts…)
 * ledger_transactions  — one immutable posting (balanced batch of entries)
 * ledger_entries       — individual DR/CR lines; never updated, never deleted
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_type', 20);            // asset|liability|equity|income|expense
            $table->string('currency_code', 3);
            $table->string('owner_type')->nullable();      // App\Models\User, Agent, BankNode…
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name');
            $table->string('code', 40)->nullable()->unique();  // chart-of-accounts code e.g. "1100-NGN"
            $table->decimal('balance', 20, 2)->default(0); // maintained projection (see below)
            $table->boolean('is_system')->default(false);  // system accounts cannot be closed
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'currency_code']);
            $table->index(['account_type', 'currency_code']);
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();     // e.g. LEDGER-…, or links to tx reference
            $table->string('type', 40);                    // p2p_transfer, withdrawal, deposit, fee, commission, reversal, opening_balance…
            $table->unsignedBigInteger('related_transaction_id')->nullable(); // link to transactions.id
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // user who initiated (maker)
            $table->unsignedBigInteger('approved_by')->nullable(); // checker (maker-checker)
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['type', 'created_at']);
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ledger_transaction_id');
            $table->unsignedBigInteger('account_id');
            $table->enum('side', ['debit', 'credit']);
            $table->decimal('amount', 20, 2);
            $table->string('currency_code', 3);
            $table->timestamps();

            // Unique balance anchor: one DR + one CR per (tx, account, side, currency)
            $table->unique(['ledger_transaction_id', 'account_id', 'side', 'currency_code'], 'ledger_entries_anchor_unique');

            $table->foreign('ledger_transaction_id')->references('id')->on('ledger_transactions')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('ledger_accounts')->restrictOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');

            $table->index(['account_id', 'currency_code', 'created_at']);
        });

        // Immutability guard: ledger entries are append-only. In Postgres this
        // would be a trigger; for the current SQLite/MySQL targets we enforce it
        // at the service layer AND discourage UPDATE/DELETE via DB permissions.
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('ledger_accounts');
    }
};
