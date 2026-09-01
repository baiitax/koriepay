<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adashi_groups', function (Blueprint $table) {
            // Add the column right after start_date, and make it unique
            $table->string('invite_code')->unique()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('adashi_groups', function (Blueprint $table) {
            $table->dropColumn('invite_code');
        });
    }
};