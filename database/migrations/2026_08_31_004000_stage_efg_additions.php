<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGGREGATOR CONSOLE — Stages E/F/G additive schema.
 *
 * 1. agency_operations: failure_reason + failure_details so failure
 *    intelligence (§44–51) can group failures by real recorded cause
 *    (rows without a recorded cause appear in an honest "cause not
 *    recorded" bucket — never fabricated).
 * 2. risk_alerts: assigned_to + assigned_at for the alert workflow
 *    (detected → assigned → investigating → resolved, §52–57).
 * 3. aggregator_settlements: aggregator-scoped settlement batches with the
 *    full breakdown (gross/fees/commission/adjustments/net) and expected
 *    vs actual reconciliation (§38–43, §66–67).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('agency_operations', 'failure_reason')) {
            Schema::table('agency_operations', function (Blueprint $table) {
                $table->string('failure_reason', 60)->nullable()->after('status');
                $table->json('failure_details')->nullable()->after('failure_reason');
            });
        }

        if (! Schema::hasColumn('risk_alerts', 'assigned_to')) {
            Schema::table('risk_alerts', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
                $table->timestamp('assigned_at')->nullable()->after('assigned_to');
            });
        }

        Schema::create('aggregator_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();               // ASL-XXXXXX
            $table->unsignedBigInteger('aggregator_id');
            $table->string('currency_code', 3);
            $table->decimal('gross_amount', 20, 2);                  // Σ commission accrued in period
            $table->decimal('fees', 20, 2)->default(0);              // platform/processing fees
            $table->decimal('commission_amount', 20, 2)->default(0); // platform commission share
            $table->decimal('adjustments', 20, 2)->default(0);       // +/- adjustments (reductions negative)
            $table->decimal('net_amount', 20, 2);                    // what the aggregator receives
            $table->decimal('expected_amount', 20, 2)->nullable();   // Σ accrued entries in the period
            $table->decimal('actual_amount', 20, 2)->nullable();     // what was actually paid
            $table->string('status', 20)->default('pending');        // pending|processing|settled|failed|under_review
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->unsignedBigInteger('ledger_transaction_id')->nullable(); // payout posting
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('aggregator_id')->references('id')->on('aggregators');
            $table->index(['aggregator_id', 'status']);
            $table->index(['status', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregator_settlements');

        if (Schema::hasColumn('agency_operations', 'failure_details')) {
            Schema::table('agency_operations', function (Blueprint $table) {
                $table->dropColumn(['failure_reason', 'failure_details']);
            });
        }

        if (Schema::hasColumn('risk_alerts', 'assigned_to')) {
            Schema::table('risk_alerts', function (Blueprint $table) {
                $table->dropColumn(['assigned_to', 'assigned_at']);
            });
        }
    }
};
