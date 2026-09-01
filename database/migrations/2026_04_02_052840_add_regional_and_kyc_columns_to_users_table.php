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
        Schema::table('users', function (Blueprint $table) {
            // Check and add region_id if missing
            if (!Schema::hasColumn('users', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('role');
            }
            
            // Check and add kyc_status if missing
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status')->default('pending')->after('region_id');
            }
            
            // Check and add id_document_path if missing
            if (!Schema::hasColumn('users', 'id_document_path')) {
                $table->string('id_document_path')->nullable();
            }
            
            // Check and add utility_bill_path if missing
            if (!Schema::hasColumn('users', 'utility_bill_path')) {
                $table->string('utility_bill_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'region_id')) {
                $table->dropColumn('region_id');
            }
            if (Schema::hasColumn('users', 'kyc_status')) {
                $table->dropColumn('kyc_status');
            }
            if (Schema::hasColumn('users', 'id_document_path')) {
                $table->dropColumn('id_document_path');
            }
            if (Schema::hasColumn('users', 'utility_bill_path')) {
                $table->dropColumn('utility_bill_path');
            }
        });
    }
};