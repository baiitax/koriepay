<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stage 1 Foundation — Command Center shell smoke test.
 * Every existing admin route must render inside the new command-center
 * layout (sidebar, topbar, palette) for a superadmin, without regressions.
 */
class CommandCenterShellTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->forceFill(['role' => 'superadmin'])->save();

        return $user;
    }

    public function test_shell_renders_command_center_chrome(): void
    {
        $user = $this->superadmin();

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('KoriePay Command Center');
        $response->assertSee('Command Center'); // sidebar group
        $response->assertSee('Risk & Compliance');
        $response->assertSee('Infrastructure');
        $response->assertSee('window.kpPaletteActions'); // palette payload injected
        $response->assertSee('All Countries'); // country switcher default
    }

    public function test_all_existing_admin_routes_render(): void
    {
        $user = $this->superadmin();

        $routes = [
            'admin.dashboard', 'admin.nodes', 'admin.transactions', 'admin.directory',
            'admin.treasury', 'admin.liquidity-wallets', 'admin.fx-rates', 'admin.settlements',
            'admin.master-ledger', 'admin.revenue-ledger', 'admin.revenue-analytics',
            'admin.kyc-hub', 'admin.kyc-queue', 'admin.network', 'admin.settings',
            'admin.security', 'admin.audit-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertOk("Route [{$route}] must render 200 in the command-center shell");
        }
    }

    public function test_non_superadmin_cannot_access_command_center(): void
    {
        $agent = User::factory()->create(['password' => bcrypt('password')]);
        $agent->forceFill(['role' => 'agent'])->save();

        $this->actingAs($agent)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_guest_cannot_access_command_center(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }
}
