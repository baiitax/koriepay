<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 4 — IDENTITY LAYER
 *
 * Adds the identity substrate the platform requires for Nigeria & Niger
 * operations (and additive markets later):
 *
 *  1. users  — country_code (ISO3), is_active, last_login_at, kyc_tier
 *  2. devices — device verification / trust (super-admin session intelligence)
 *  3. kyc_submissions — the formal KYC/KYB record per user (status workflow,
 *     reviewer, rejection reason, aging is derived from submitted_at)
 *  4. login_events — login history (success/failure) powering the Security
 *     Center and session monitoring
 *  5. role_permissions — additive view permissions for the admin role so the
 *     dashboard can enforce granular, server-side authorization.
 *
 * Everything is additive and idempotent (hasColumn guards); it never mutates
 * existing columns or data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. users identity columns ────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'country_code')) {
                $table->string('country_code', 3)->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('users', 'kyc_tier')) {
                $table->unsignedTinyInteger('kyc_tier')->default(1)->after('kyc_status');
            }
        });

        // Index on country_code for country-scoped queries (data isolation).
        if (! collect(Schema::getIndexes('users'))->pluck('name')->contains('users_country_code_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('country_code');
            });
        }

        // ── 2. devices ───────────────────────────────────────────────────────
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 64)->unique();          // stable fingerprint
            $table->string('platform', 40)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_current')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // ── 3. kyc_submissions ───────────────────────────────────────────────
        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('personal');   // personal | business
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected|expired|manual_review
            $table->string('tier', 20)->default('tier1');
            $table->string('country_code', 3)->nullable();
            $table->json('data')->nullable();                  // submitted identity payload
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index('user_id');
        });

        // ── 4. login_events ──────────────────────────────────────────────────
        Schema::create('login_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('event', 24);                       // login_success | login_failed | logout | lockout
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_id', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('event');
        });

        // ── 5. additive role_permissions for the admin role ──────────────────
        // (superadmin keeps the '*' wildcard; these give the dashboard and
        // identity surfaces granular server-side authorization.)
        $adminViews = [
            'dashboard.view', 'audit.view', 'fx.view', 'network.view',
            'revenue.view', 'ledger.view', 'security.view', 'settings.view', 'system.view',
        ];
        foreach ($adminViews as $perm) {
            DB::table('role_permissions')->insertOrIgnore([
                'role' => 'admin',
                'permission' => $perm,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_events');
        Schema::dropIfExists('kyc_submissions');
        Schema::dropIfExists('devices');

        Schema::table('users', function (Blueprint $table) {
            // SQLite refuses to DROP COLUMN while an index references it.
            if (collect(Schema::getIndexes('users'))->pluck('name')->contains('users_country_code_index')) {
                $table->dropIndex('users_country_code_index');
            }

            foreach (['country_code', 'is_active', 'last_login_at', 'kyc_tier'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Permission rows are additive; left in place (consistent with 000600's
        // additive policy — removing them could orphan live authorizations).
    }
};
