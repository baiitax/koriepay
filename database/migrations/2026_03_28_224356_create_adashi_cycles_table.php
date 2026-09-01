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
    Schema::create('adashi_cycles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('adashi_group_id')->constrained();
        $table->integer('cycle_number'); // e.g., Week 1, Week 2
        $table->foreignId('recipient_id')->constrained('users'); // Who is getting the pot this cycle?
        $table->decimal('expected_total', 15, 2);
        $table->decimal('collected_total', 15, 2)->default(0);
        $table->timestamp('due_date');
        $table->enum('status', ['collecting', 'paid_out', 'failed'])->default('collecting');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adashi_cycles');
    }
};
