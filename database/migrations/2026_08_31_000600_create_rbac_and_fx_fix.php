<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 foundation — RBAC (role_permissions) and a corrective migration
 * aligning fx_rates with the code that actually reads it (additive, preserves
 * legacy columns so nothing breaks).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 40);                      // superadmin, admin, manager, agent, aggregator, customer…
            $table->string('permission', 80);                // transaction.reverse, kyc.approve, wallet.freeze…
            $table->timestamps();

            $table->unique(['role', 'permission']);
            $table->index('role');
        });

        // Seed the baseline matrix (extend per market; never via code edits).
        $matrix = [
            'superadmin' => ['*'],
            'admin' => ['transaction.view','transaction.hold','transaction.reverse','transaction.approve','transaction.refund','agent.view','agent.approve','agent.suspend','agent.reactivate','kyc.view','kyc.approve','kyc.reject','wallet.view','wallet.freeze','wallet.unfreeze','settlement.view','settlement.approve','user.view','user.suspend','user.reset_pin'],
            'manager' => ['transaction.view','agent.view','kyc.view','kyc.approve','kyc.reject','settlement.view','user.view','user.suspend'],
            'aggregator' => ['agent.view','agent.recruit','network.view','commission.view'],
            'agent' => ['transaction.initiate','cash.in','cash.out','wallet.view','float.view','commission.view'],
            'customer' => ['transaction.initiate','wallet.view','kyc.submit'],
        ];

        foreach ($matrix as $role => $perms) {
            foreach ($perms as $perm) {
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'permission' => $perm,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // fx_rates: add the columns the code actually queries (rate, is_active,
        // base_currency, target_currency) while keeping the legacy pair columns.
        Schema::table('fx_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('fx_rates', 'base_currency')) {
                $table->string('base_currency', 3)->nullable()->after('id');
                $table->string('target_currency', 3)->nullable()->after('base_currency');
            }
            if (! Schema::hasColumn('fx_rates', 'rate')) {
                $table->decimal('rate', 18, 6)->nullable()->after('target_currency');
            }
            if (! Schema::hasColumn('fx_rates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        // fx_rates additions intentionally left in place (additive).
    }
};
