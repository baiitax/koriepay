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
    Schema::create('adashi_members', function (Blueprint $table) {
        $table->id();
        $table->foreignId('adashi_group_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->integer('payout_order'); // e.g., 1 gets paid week 1, 2 gets paid week 2
        $table->boolean('has_received_payout')->default(false);
        $table->enum('status', ['active', 'defaulted'])->default('active');
        $table->timestamps();
        
        // Ensure a user can only join a specific group once
        $table->unique(['adashi_group_id', 'user_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adashi_members');
    }
};
