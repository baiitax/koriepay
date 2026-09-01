<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * AGGREGATOR CONSOLE — Stage A (RBAC expansion).
 *
 * The aggregator role gets the granular permission set the command center
 * needs (§83 of the Aggregator brief). Purely additive — existing rows are
 * never deleted; re-running is safe (updateOrInsert).
 *
 * Every aggregator-facing capability maps to a server-side permission so the
 * UI can never be the authorization boundary.
 */
return new class extends Migration
{
    public function up(): void
    {
        $perms = [
            // Command center
            'dashboard.view',
            // Agents
            'agent.view', 'agent.recruit', 'agent.manage', 'agent.activate',
            'agent.suspend', 'agent.reactivate', 'agent.profile.view',
            // Network & performance
            'network.view', 'network.analytics',
            // Liquidity
            'liquidity.view', 'liquidity.request', 'liquidity.review',
            // Transactions & investigation
            'transaction.view', 'transaction.investigate',
            // Commissions & settlement
            'commission.view', 'settlement.view',
            // Risk & alerts
            'risk.view', 'risk.alert.resolve',
            // Support
            'support.view', 'support.manage',
            // Reports
            'report.view', 'report.generate',
            // Profile
            'aggregator.profile.view',
        ];

        foreach ($perms as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => 'aggregator', 'permission' => $permission],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Additive only — no destructive rollback for permissions.
    }
};
