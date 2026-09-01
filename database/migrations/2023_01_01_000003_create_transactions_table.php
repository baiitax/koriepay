<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
        $table->string('receiver_name');
        $table->string('source_currency', 3); // e.g., NGN
        $table->string('destination_currency', 3); // e.g., XOF
        $table->decimal('source_amount', 15, 2);
        $table->decimal('exchange_rate', 18, 6);
        $table->decimal('destination_amount', 15, 2);
        $table->string('status')->default('pending'); // pending, completed, failed
        $table->string('reference')->unique();
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};