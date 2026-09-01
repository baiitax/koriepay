<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 4 — AUDIT LOG SCHEMA CORRECTION (additive).
 *
 * The original audit_logs table (2026_03_06_031824) only has
 * admin_id, user_id, action, description, ip_address, user_agent — yet the
 * AuditLog model and every KYC admin action write target_id / payload /
 * user_name / event_type / metadata. Those writes were silently broken
 * (SQLite would reject unknown columns). This migration adds the missing
 * columns additively so the audit trail actually persists.
 *
 * Contract going forward (documented in DATABASE.md):
 *   admin_id   → the ACTING user (NOT NULL, kept from original schema)
 *   user_id    → the primary TARGET (NOT NULL, kept)
 *   target_id  → secondary target when different from user_id (nullable)
 *   action     → machine action key, e.g. kyc.approved
 *   description→ human-readable summary (nullable)
 *   event_type → compliance | financial | security | operations | system
 *   metadata   → JSON string of structured before/after context
 *   payload    → legacy JSON field kept for backward compatibility
 *   user_name  → denormalized actor name (audit resilience)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'target_id')) {
                $table->foreignId('target_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('audit_logs', 'user_name')) {
                $table->string('user_name')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('audit_logs', 'event_type')) {
                $table->string('event_type', 30)->nullable()->after('action');
            }
            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->text('metadata')->nullable()->after('description');
            }
            if (! Schema::hasColumn('audit_logs', 'payload')) {
                $table->text('payload')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['target_id', 'user_name', 'event_type', 'metadata', 'payload'] as $col) {
                if (Schema::hasColumn('audit_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
