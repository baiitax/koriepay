<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Currency and Precision (Fintech standard: 20 digits, 2 decimals)
            $table->string('currency_code', 3); // NGN or XOF
            $table->decimal('balance', 20, 2)->default(0);
            
            // Virtual Account Details (For Priority 1 MIMO)
            $table->string('virtual_account_number')->nullable();
            $table->string('virtual_account_bank')->nullable();
            $table->string('virtual_account_name')->nullable();
            
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};