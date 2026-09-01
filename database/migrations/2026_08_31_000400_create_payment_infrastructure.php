<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 foundation — payment rails, providers, provider accounts,
 * webhook events (auditable, replay-safe).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_rails', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // NIP, BC_NIP, MOBILE_MONEY_NE, WALLET…
            $table->string('name');
            $table->string('country_iso2', 2);             // which market this rail serves
            $table->string('kind', 20);                    // bank|mobile_money|wallet|card|psp
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('country_iso2')->references('iso2')->on('countries');
        });

        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // paystack, dusupay, smileid, wema…
            $table->string('name');
            $table->string('type', 20);                    // psp|bank|mobile_money|kyc
            $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedTinyInteger('health_score')->default(100); // 0–100
            $table->json('capabilities')->nullable();      // ["transfer","resolve_account","webhook",…]
            $table->timestamps();
        });

        Schema::create('provider_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('environment', 10);             // sandbox|live
            $table->string('label')->nullable();
            $table->string('merchant_code')->nullable();
            $table->string('virtual_account_number')->nullable();
            $table->string('virtual_account_bank')->nullable();
            $table->string('currency_code', 3);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('payment_providers');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->unique(['provider_id', 'environment', 'currency_code', 'virtual_account_number']);
        });

        // Every provider webhook is persisted before processing, so retries and
        // replays are visible and idempotent.
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_id', 128)->nullable();
            $table->string('event_type', 80);
            $table->string('signature', 255)->nullable();
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->string('ip_address', 45)->nullable();
            $table->string('processing_status', 20)->default('received'); // received|processing|processed|failed|ignored
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->unsignedBigInteger('related_transaction_id')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
            $table->index(['processing_status', 'created_at']);
            $table->index(['provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('provider_accounts');
        Schema::dropIfExists('payment_providers');
        Schema::dropIfExists('payment_rails');
    }
};
