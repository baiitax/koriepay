<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 6 — Agency Banking.
 *
 * Agents and aggregators are the KoriePay distribution network. Money for
 * agent-mediated operations lives in the LEDGER (never a balance field):
 *   - agent float  → ledger_accounts owner_type='agent'    (liability)
 *   - aggregator   → ledger_accounts owner_type='aggregator' (liability)
 *   - commissions  → ledger postings DR Commission Expense / CR accrual
 *
 * agency_operations is the auditable record of every cash-in / cash-out the
 * network executes — the metrics source for Stage 3 liquidity and Stage 4
 * network intelligence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('agent_code', 20)->unique();
            $table->string('status', 20)->default('pending'); // pending|active|suspended|inactive|terminated
            $table->string('tier', 20)->default('bronze');    // bronze|silver|gold
            $table->string('country_iso2', 2);
            $table->string('region', 60)->nullable();
            $table->string('city', 60)->nullable();
            $table->unsignedBigInteger('aggregator_id')->nullable();
            $table->string('kyc_status', 20)->default('unverified'); // unverified|pending|verified|rejected
            $table->decimal('risk_score', 5, 2)->nullable();   // 0–100, set by risk layer (Phase 7)
            $table->decimal('commission_override_rate', 8, 4)->nullable(); // % override; null = use rules
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('aggregator_id')->references('id')->on('aggregators');
            $table->index(['status', 'country_iso2']);
            $table->index('aggregator_id');
        });

        Schema::create('aggregators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('status', 20)->default('pending'); // pending|active|suspended|inactive
            $table->string('country_iso2', 2);
            $table->string('region', 60)->nullable();
            $table->string('city', 60)->nullable();
            $table->string('kyc_status', 20)->default('unverified');
            $table->decimal('commission_override_rate', 8, 4)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['status', 'country_iso2']);
        });

        Schema::create('agency_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('aggregator_id')->nullable();
            $table->unsignedBigInteger('customer_user_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('operation_type', 20);              // cash_in|cash_out
            $table->string('currency_code', 3);
            $table->decimal('amount', 20, 2);
            $table->decimal('fee', 20, 2)->default(0);
            $table->decimal('commission_amount', 20, 2)->default(0);
            $table->string('status', 20)->default('posted');   // posted|failed
            $table->string('reference', 64)->unique();
            $table->string('idempotency_key', 64)->unique();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents');
            $table->foreign('aggregator_id')->references('id')->on('aggregators');
            $table->foreign('customer_user_id')->references('id')->on('users');
            $table->index(['agent_id', 'created_at']);
            $table->index(['aggregator_id', 'created_at']);
            $table->index(['operation_type', 'created_at']);
        });

        // Phase 6 RBAC extensions — additive to the 000600 matrix.
        $extras = [
            'admin' => ['agency.view', 'agency.manage', 'agent.approve', 'agent.suspend', 'agent.reactivate', 'aggregator.approve', 'aggregator.manage', 'commission.manage'],
            'manager' => ['agency.view', 'agent.approve'],
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
        Schema::dropIfExists('agency_operations');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('aggregators');
    }
};
