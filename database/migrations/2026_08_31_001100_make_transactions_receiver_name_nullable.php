<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 5 — Payment Core.
 *
 * Withdrawals (wallet → external/platform) have no receiver: the column must
 * be nullable. Existing rows all carry a value, so the change is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('receiver_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Only safe while no NULL rows exist (true on dev/scratch databases).
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('receiver_name')->nullable(false)->change();
        });
    }
};
