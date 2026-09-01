<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Only add if they don't exist
        if (!Schema::hasColumn('users', 'username')) {
            $table->string('username')->unique()->nullable()->after('name');
        }
        if (!Schema::hasColumn('users', 'account_locked')) {
            $table->boolean('account_locked')->default(false);
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
