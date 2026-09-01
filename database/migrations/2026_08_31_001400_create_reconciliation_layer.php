<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 8 — Reconciliation & settlement.
 *
 * Settlement = batch records of money the platform owes/moves via a provider
 * or rail; reconciliation = matching INTERNAL records (transactions with a
 * provider reference) against PROVIDER records (transaction_attempts with a
 * provider reference + amount). The match never invents data: both sides are
 * real persisted records. balance_snapshots (000500) stores the projected vs
 * derived comparison — this phase adds the service that computes it.
 *
 * Additive on 000300: transaction_attempts.amount so provider-side records
 * carry the amount for mismatch detection.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Provider-side records need amounts for mismatch detection.
        Schema::table('transaction_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_attempts', 'amount')) {
                $table->decimal('amount', 20, 2)->nullable()->after('provider_reference');
            }
        });

        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();         // STL-…
            $table->string('provider_code', 40);
            $table->string('rail_code', 40)->nullable();
            $table->string('country_iso2', 2);
            $table->string('currency_code', 3);
            $table->decimal('amount', 20, 2);
            $table->decimal('settled_amount', 20, 2)->nullable();
            $table->string('status', 20)->default('scheduled'); // scheduled|pending|processing|settled|failed|cancelled
            $table->string('provider_reference', 128)->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('scheduled_at')->nullable();     // "Next Settlement"
            $table->timestamp('settled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['provider_code', 'status']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['country_iso2', 'currency_code']);
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('settlement_id');
            $table->unsignedBigInteger('transaction_id');
            $table->decimal('amount', 20, 2);
            $table->string('currency_code', 3);
            $table->string('status', 20)->default('pending'); // pending|included|excluded|reversed
            $table->timestamps();

            $table->unique(['settlement_id', 'transaction_id']);
            $table->index('transaction_id');
        });

        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();         // REC-…
            $table->string('provider_code', 40)->nullable();   // null = all providers
            $table->string('country_iso2', 2)->nullable();     // null = all countries
            $table->string('currency_code', 3)->nullable();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->integer('internal_count')->default(0);
            $table->integer('provider_count')->default(0);
            $table->integer('matched_count')->default(0);
            $table->integer('unmatched_internal_count')->default(0);
            $table->integer('unmatched_provider_count')->default(0);
            $table->integer('amount_mismatch_count')->default(0);
            $table->integer('duplicate_count')->default(0);
            $table->decimal('internal_amount', 20, 2)->default(0);
            $table->decimal('provider_amount', 20, 2)->default(0);
            $table->decimal('difference', 20, 2)->default(0);
            $table->decimal('health_score', 5, 2)->default(0);
            $table->string('status', 20)->default('completed'); // running|completed|failed
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('run_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['provider_code', 'period_start', 'period_end']);
        });

        Schema::create('reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('provider', 40)->nullable();
            $table->string('provider_reference', 128)->nullable();
            $table->string('match_key', 128);
            $table->string('status', 30); // matched|unmatched_internal|unmatched_provider|amount_mismatch|duplicate
            $table->decimal('internal_amount', 20, 2)->nullable();
            $table->decimal('provider_amount', 20, 2)->nullable();
            $table->decimal('discrepancy', 20, 2)->nullable();
            $table->string('resolution', 20)->nullable();       // null|accepted|rejected|adjusted
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['run_id', 'status']);
            $table->index(['status', 'resolved_at']);
        });

        // Phase 8 RBAC extensions — additive to 000600/001200.
        $extras = [
            'admin' => ['reconciliation.view', 'reconciliation.resolve', 'settlement.approve'],
            'manager' => ['reconciliation.view', 'settlement.view'],
        ];
        foreach ($extras as $role => $perms) {
            foreach ($perms as $perm) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'permission' => $perm],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_items');
        Schema::dropIfExists('reconciliation_runs');
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');

        Schema::table('transaction_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_attempts', 'amount')) {
                $table->dropColumn('amount');
            }
        });
    }
};
