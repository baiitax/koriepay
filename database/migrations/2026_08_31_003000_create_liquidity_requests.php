<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AGGREGATOR CONSOLE — Stage C (liquidity request workflow, §23–28).
 *
 * Agent → aggregator liquidity requests. Lifecycle:
 *   pending → in_review → approved (earmarked on the ledger) → funded
 *   pending/in_review → rejected
 *   pending/approved → cancelled (earmark released)
 *
 * Approval EARMARKS operational cash on the ledger (DR Platform Cash /
 * CR Pending Liquidity); funding releases it to the agent float
 * (DR Pending Liquidity / CR Agent Float). Every transition is audited and
 * the posting is idempotent. Risk/limit checks are recorded at review time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidity_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();           // LRQ-XXXXXX
            $table->unsignedBigInteger('aggregator_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('currency_code', 3);
            $table->decimal('amount', 20, 2);
            $table->string('reason', 60)->default('cash_out_demand'); // cash_out_demand|restock|other
            $table->string('status', 20)->default('pending');    // pending|in_review|approved|rejected|funded|cancelled
            $table->string('risk_level', 10)->default('low');    // low|medium|high
            $table->json('risk_notes')->nullable();
            $table->string('requested_by_type', 20)->default('agent'); // agent|aggregator
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->unsignedBigInteger('ledger_transaction_id')->nullable(); // funding posting
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('aggregator_id')->references('id')->on('aggregators');
            $table->foreign('agent_id')->references('id')->on('agents');
            $table->index(['aggregator_id', 'status']);
            $table->index(['agent_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidity_requests');
    }
};
