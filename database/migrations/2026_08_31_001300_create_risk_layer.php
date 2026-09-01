<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 7 — Risk layer.
 *
 * Data-driven risk (rules are DATA, never code), auditable alerts, a
 * transaction-hold lifecycle that moves the state machine (HELD → POSTED /
 * CANCELLED), and the maker–checker approval inbox.
 *
 *   risk_rules          — rule definitions (category, entity, condition JSON)
 *   risk_alerts         — open/investigating/resolved alerts (P0–P3)
 *   transaction_holds   — holds on transactions (SLA + decision trail)
 *   approval_requests   — maker–checker inbox ("maker can never approve own")
 *   users.risk_score    — additive 0–100 projection fed by RiskService
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('category', 30);              // fraud|aml|velocity|geographic|anomaly
            $table->string('entity_type', 30);           // transaction|agent|customer|aggregator|provider
            $table->string('condition_type', 40);        // amount_exceeds|failed_attempts_exceed|velocity_count_exceeds|success_rate_below
            $table->json('condition_config');            // {"amount": 500000} | {"count": 5} | {"rate": 95} | {"count":10,"window_minutes":60}
            $table->string('severity', 3)->default('P2'); // P0|P1|P2|P3
            $table->decimal('risk_score', 5, 2)->default(20);
            $table->unsignedTinyInteger('priority')->default(100); // lower = checked first
            $table->string('country_iso2', 2)->nullable();
            $table->integer('dedupe_window_minutes')->nullable(); // null = dedupe per entity+rule forever
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['entity_type', 'is_active']);
            $table->index(['category', 'is_active']);
        });

        Schema::create('risk_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('category', 30);
            $table->string('severity', 3)->default('P2'); // P0|P1|P2|P3
            $table->string('entity_type', 30);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('country_iso2', 2)->nullable();
            $table->string('message');
            $table->json('details')->nullable();         // facts that matched
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->string('status', 20)->default('open'); // open|acknowledged|investigating|resolved|false_positive
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'status']);
            $table->index(['severity', 'status']);
            $table->index('transaction_id');
        });

        Schema::create('transaction_holds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->unique();
            $table->decimal('amount', 20, 2);
            $table->string('currency_code', 3);
            $table->string('reason');
            $table->string('reason_code', 40)->nullable(); // e.g. fraud_review|aml_review|compliance
            $table->string('status', 20)->default('held'); // held|released|rejected|reversed
            $table->unsignedBigInteger('held_by')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->timestamp('sla_due_at')->nullable();   // aging/SLA for the hold queue
            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->index(['status', 'sla_due_at']);
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->unsignedBigInteger('maker_id');
            $table->unsignedBigInteger('checker_id')->nullable();
            $table->string('action_type', 60);             // agent.approve|aggregator.approve|settlement.approve|adjustment|commission.change|limit.change|risk.release…
            $table->string('entity_type', 30)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload');                        // the requested change (before/after)
            $table->string('reason');
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamps();

            $table->foreign('maker_id')->references('id')->on('users');
            $table->index(['status', 'created_at']);
            $table->index('checker_id');
        });

        // Additive: user-level risk projection (0–100), fed by RiskService.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'risk_score')) {
                $table->decimal('risk_score', 5, 2)->nullable()->after('kyc_tier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'risk_score')) {
                $table->dropColumn('risk_score');
            }
        });
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('transaction_holds');
        Schema::dropIfExists('risk_alerts');
        Schema::dropIfExists('risk_rules');
    }
};
