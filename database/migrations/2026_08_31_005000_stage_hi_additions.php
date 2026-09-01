<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AGGREGATOR CONSOLE — Stages H & I (support, documents, reports,
 * analytical read model, permissions). Purely additive — never drops or
 * rewrites existing columns. Safe to run on a populated database.
 *
 * Stage H (§59–63):
 *   - support_tickets  : + aggregator_id (tenant), SLA fields, resolution
 *                        timestamps — the aggregator console and the customer
 *                        portal share this table.
 *   - support_replies  : ticket thread (audited replies + internal notes).
 *   - aggregator_documents : authorized document center (own docs + system
 *                        docs published to every aggregator).
 *   - report_jobs      : async report generation ledger (queued → processing
 *                        → ready|failed) with file output + audit trail.
 * Stage I (§100–110):
 *   - aggregator_daily_metrics : analytical read model (DERIVED from real
 *                        records; snapshot writes are idempotent per date).
 *   - RBAC: document.view / document.manage for the aggregator role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'aggregator_id')) {
                $table->unsignedBigInteger('aggregator_id')->nullable()->after('user_id');
                $table->index('aggregator_id');
            }
            if (! Schema::hasColumn('support_tickets', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('priority');
                $table->index(['status', 'sla_due_at']);
            }
            if (! Schema::hasColumn('support_tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('sla_due_at');
            }
            if (! Schema::hasColumn('support_tickets', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            }
        });

        Schema::create('support_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->boolean('is_internal')->default(false); // aggregator-internal note, not customer-facing
            $table->timestamps();

            $table->foreign('support_ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('support_ticket_id');
        });

        Schema::create('aggregator_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aggregator_id')->nullable(); // null = system-published doc
            $table->string('category', 40);
            $table->string('title');
            $table->string('file_path')->nullable();       // storage-relative path; null while not materialized
            $table->string('file_name')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('visibility', 20)->default('network'); // network|internal
            $table->boolean('is_system')->default(false);  // published by KoriePay to all aggregators
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('aggregator_id')->references('id')->on('aggregators');
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->index(['aggregator_id', 'category']);
        });

        Schema::create('report_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();     // e.g. RPT-AGG-ABC123
            $table->unsignedBigInteger('aggregator_id');
            $table->string('type', 40);                    // agent|transaction|commission|liquidity|settlement|risk|kyc|network_growth
            $table->string('format', 8)->default('csv');   // csv|xlsx|pdf
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('status', 20)->default('queued'); // queued|processing|ready|failed
            $table->string('file_path')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('aggregator_id')->references('id')->on('aggregators');
            $table->foreign('requested_by')->references('id')->on('users');
            $table->index(['aggregator_id', 'status']);
            $table->index('status');
        });

        Schema::create('aggregator_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aggregator_id');
            $table->date('metric_date');
            $table->unsignedInteger('total_ops')->default(0);
            $table->unsignedInteger('posted_ops')->default(0);
            $table->unsignedInteger('failed_ops')->default(0);
            $table->decimal('volume', 20, 2)->default(0);        // posted operation value
            $table->decimal('commission_accrued', 20, 2)->default(0);
            $table->unsignedInteger('active_agents')->default(0);
            $table->unsignedInteger('new_agents')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->decimal('failure_rate', 5, 2)->default(0);
            $table->unsignedInteger('settlements_created')->default(0);
            $table->decimal('settlement_value', 20, 2)->default(0);
            $table->boolean('is_empty')->default(false);         // date inside window but zero activity
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->foreign('aggregator_id')->references('id')->on('aggregators');
            $table->unique(['aggregator_id', 'metric_date']);
            $table->index('metric_date');
        });

        // Stage H/I RBAC — additive (updateOrInsert).
        foreach (['document.view', 'document.manage'] as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => 'aggregator', 'permission' => $permission],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Additive only — no destructive rollback (matches console convention).
    }
};
