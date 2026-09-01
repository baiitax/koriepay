<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    // 1. Settlement Banks (The Nodes)
    Schema::create('bank_nodes', function (Blueprint $table) {
        $table->id();
        $table->string('bank_name');
        $table->string('account_no');
        $table->string('currency', 3); // NGN, XOF, USD
        $table->decimal('balance', 20, 2)->default(0);
        $table->string('api_status')->default('online');
        $table->timestamp('last_sync')->nullable();
        $table->timestamps();
    });

    // 2. FX Rate History (The Pulse)
    Schema::create('fx_rates', function (Blueprint $table) {
        $table->id();
        $table->string('pair', 7); // NGN/XOF
        $table->decimal('mid_market_rate', 18, 6);
        $table->decimal('corporate_spread', 5, 2);
        $table->decimal('volatility_buffer', 5, 2);
        $table->decimal('effective_rate', 18, 6);
        $table->timestamps();
    });

    // 3. Revenue Ledger (The Profit)
    Schema::create('revenue_logs', function (Blueprint $table) {
        $table->id();
        $table->string('entry_id')->unique(); // REV-XXXX
        $table->string('source'); // FX Spread, Flat Fee
        $table->string('node_path'); // NGN/XOF Interface
        $table->decimal('amount_usd', 15, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sovereign_grid_tables');
    }
};
