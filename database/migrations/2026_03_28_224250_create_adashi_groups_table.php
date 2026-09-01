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
    Schema::create('adashi_groups', function (Blueprint $table) {
        $table->id();
        $table->foreignId('creator_id')->constrained('users'); // The group admin
        $table->string('name'); // e.g., "Kano Market Traders Pool"
        $table->string('currency')->default('NGN');
        $table->decimal('contribution_amount', 15, 2); // Amount each pays
        $table->integer('max_members');
        $table->enum('frequency', ['daily', 'weekly', 'monthly']);
        $table->timestamp('start_date')->nullable();
        $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adashi_groups');
    }
};
