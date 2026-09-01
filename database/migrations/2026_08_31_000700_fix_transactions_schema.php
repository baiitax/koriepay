<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 corrective migration — align the `transactions` table with the
 * production schema (as exported in koriepay_lara878.sql) and with every
 * writer in the codebase. Additive only; no destructive changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'receiver_id')) {
                $table->unsignedBigInteger('receiver_id')->nullable()->after('sender_id');
            }
            if (! Schema::hasColumn('transactions', 'fee_charged')) {
                $table->decimal('fee_charged', 20, 2)->default(0)->after('destination_amount');
            }
            if (! Schema::hasColumn('transactions', 'type')) {
                $table->string('type')->default('cross_border')->after('status');
            }
            if (! Schema::hasColumn('transactions', 'description')) {
                $table->string('description')->nullable()->after('type');
            }
            if (! Schema::hasColumn('transactions', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('description');
            }
            if (! Schema::hasColumn('transactions', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('transactions', 'account_name')) {
                $table->string('account_name')->nullable()->after('account_number');
            }
            if (! Schema::hasColumn('transactions', 'auth_code')) {
                // Legacy OTP column — Phase 6 removes it in favor of hashed,
                // expiring server-side OTPs. Kept additive for schema parity.
                $table->string('auth_code')->nullable()->after('account_name');
            }
        });
    }

    public function down(): void
    {
        // Additive migration — no automatic rollback of added columns.
    }
};
