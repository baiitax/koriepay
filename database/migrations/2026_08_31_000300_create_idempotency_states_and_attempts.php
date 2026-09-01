<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Idempotency, transaction state machine, and attempts.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Every money-moving operation must resolve through an idempotency key.
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('endpoint', 255);
            $table->string('request_hash', 64);            // sha256 of canonical request
            $table->json('response')->nullable();          // cached response to return on replay
            $table->unsignedBigInteger('ledger_transaction_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('expires_at')->nullable();

            $table->index(['user_id', 'endpoint']);
        });

        // Explicit, auditable transaction states (never a boolean).
        Schema::create('transaction_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('from_state', 20)->nullable();
            $table->string('to_state', 20);
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable(); // user/system making the transition
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'created_at']);
            $table->index('to_state');
        });

        Schema::create('transaction_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('status', 20);                  // pending|success|failed|unknown|timeout
            $table->text('response_summary')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'attempt_number']);
            $table->index(['provider', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_attempts');
        Schema::dropIfExists('transaction_states');
        Schema::dropIfExists('idempotency_keys');
    }
};
