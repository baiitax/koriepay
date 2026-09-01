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
       Schema::create('treasury_vaults', function (Blueprint $table) {
    $table->id();
    $table->string('currency_code', 3)->unique(); // NGN or XOF
    $table->string('bank_name');
    $table->string('account_number');
    $table->decimal('physical_balance', 18, 6)->default(0);
    $table->timestamp('last_reconciled_at')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_vaults');
    }
};
