<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Country & currency configuration.
 * The financial core must never hardcode Nigeria/NGN. These tables make the
 * platform country-aware so Niger, Nigeria, and later WAEMU markets can be
 * added by configuration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();             // NGN, XOF, USD…
            $table->string('name');
            $table->string('symbol', 8)->default('');
            $table->unsignedTinyInteger('minor_units')->default(2);
            $table->boolean('is_fiat')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->unique();
            $table->string('name');
            $table->string('calling_code', 10)->nullable();
            $table->string('currency_code', 3);
            $table->string('regulator')->nullable();          // e.g. "CBN", "BCEAO/UEMOA"
            $table->string('ecosystem')->nullable();          // e.g. "WAEMU"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        DB::table('currencies')->insert([
            ['code' => 'NGN', 'name' => 'Nigerian Naira',   'symbol' => '₦', 'minor_units' => 2, 'is_fiat' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'XOF', 'name' => 'West African CFA', 'symbol' => 'CFA', 'minor_units' => 0, 'is_fiat' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'USD', 'name' => 'US Dollar',        'symbol' => '$', 'minor_units' => 2, 'is_fiat' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('countries')->insert([
            ['iso2' => 'NG', 'iso3' => 'NGA', 'name' => 'Nigeria', 'calling_code' => '+234', 'currency_code' => 'NGN', 'regulator' => 'CBN', 'ecosystem' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['iso2' => 'NE', 'iso3' => 'NER', 'name' => 'Niger',   'calling_code' => '+227', 'currency_code' => 'XOF', 'regulator' => 'BCEAO', 'ecosystem' => 'UEMOA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('currencies');
    }
};
