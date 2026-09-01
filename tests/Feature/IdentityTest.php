<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\KycSubmission;
use App\Models\LoginEvent;
use App\Models\User;
use App\Services\KycWorkflow;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * PHASE 4 — IDENTITY LAYER
 *
 * Customers / agents / aggregators / RBAC / KYC / devices / sessions.
 * Verifies the corrected identity contract end-to-end:
 *   - registration persists phone_number + country_code + is_active
 *     (the legacy $fillable defect is fixed)
 *   - granular RBAC is enforced server-side (permission middleware + Gate)
 *   - KYC decisions flow through the canonical workflow with a real audit trail
 *   - device trust + login telemetry records
 *   - country data isolation scope
 */
class IdentityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $extra = []): User
    {
        $user = User::factory()->create($extra);
        $user->forceFill(['role' => $extra['role'] ?? 'customer'])->save();

        return $user;
    }

    // ── 1. Registration persists identity fields (fillable fix) ────────────

    public function test_registration_persists_phone_country_and_active_flag(): void
    {
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('phone_number', '08012345678')
            ->set('country_code', 'NGA')
            ->set('password', 'Str0ng!Passw0rd')
            ->set('password_confirmation', 'Str0ng!Passw0rd')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame('08012345678', $user->phone_number, 'phone_number must persist (was silently dropped)');
        $this->assertSame('NGA', $user->country_code, 'country_code must persist');
        $this->assertTrue((bool) $user->is_active, 'is_active must persist');
        $this->assertSame('customer', $user->role, 'role defaults to customer (never mass-assigned)');
        $this->assertTrue(
            User::where('phone_number', '08012345678')->exists(),
            'a registered user must be findable by phone for phone-login'
        );
    }

    // ── 2. RBAC: permission middleware + Gate wiring ───────────────────────

    public function test_permission_middleware_is_enforced_server_side(): void
    {
        // Use a permission NOT granted to the admin role in the seed matrix,
        // so the grant/deny cases are unambiguous.
        Route::middleware('permission:superadmin.only')
            ->get('/_test/perm', fn () => 'ok');

        // Superadmin has the wildcard → allowed.
        $this->actingAs($this->makeUser(['role' => 'superadmin']))
            ->get('/_test/perm')
            ->assertOk();

        // Admin WITHOUT the permission → 403 (no client-side bypass).
        $this->actingAs($this->makeUser(['role' => 'admin']))
            ->get('/_test/perm')
            ->assertForbidden();

        // Admin AFTER an explicit grant → allowed.
        $admin = $this->makeUser(['role' => 'admin']);
        DB::table('role_permissions')->insertOrIgnore([
            'role' => 'admin', 'permission' => 'superadmin.only',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($admin)->get('/_test/perm')->assertOk();

        // Manager (no such permission) → 403.
        $this->actingAs($this->makeUser(['role' => 'manager']))
            ->get('/_test/perm')
            ->assertForbidden();
    }

    public function test_admin_routes_are_doubly_guarded(): void
    {
        $super = $this->makeUser(['role' => 'superadmin']);
        $this->actingAs($super)->get(route('admin.audit-logs'))->assertOk();
        $this->actingAs($super)->get(route('admin.kyc-hub'))->assertOk();

        // A plain agent cannot reach any command-center surface.
        $this->actingAs($this->makeUser(['role' => 'agent']))
            ->get(route('admin.audit-logs'))
            ->assertForbidden();
    }

    // ── 3. KYC workflow ────────────────────────────────────────────────────

    public function test_kyc_approve_creates_submission_mirrors_user_and_audits(): void
    {
        $customer = $this->makeUser();
        $reviewer = $this->makeUser(['role' => 'superadmin']);

        $submission = KycWorkflow::approve($customer, $reviewer, ['tier' => 'tier2']);

        $this->assertSame(KycSubmission::STATUS_APPROVED, $submission->status);
        $this->assertSame('verified', $customer->fresh()->kyc_status);
        $this->assertSame(2, $customer->fresh()->kyc_tier);

        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $reviewer->id,
            'user_id' => $customer->id,
            'action' => 'kyc.approved',
            'event_type' => 'compliance',
        ]); // canonical audit row must exist with real columns
    }

    public function test_kyc_reject_sets_reason_and_tier_zero(): void
    {
        $customer = $this->makeUser();
        $reviewer = $this->makeUser(['role' => 'superadmin']);

        KycWorkflow::reject($customer, $reviewer, 'Document does not match BVN.');

        $this->assertSame('rejected', $customer->fresh()->kyc_status);
        $this->assertSame(0, $customer->fresh()->kyc_tier);

        $submission = $customer->kycSubmissions()->latest('id')->first();
        $this->assertSame(KycSubmission::STATUS_REJECTED, $submission->status);
        $this->assertSame('Document does not match BVN.', $submission->rejection_reason);
    }

    public function test_kyc_hub_component_flows_through_workflow(): void
    {
        $customer = $this->makeUser(); // role customer, kyc_status pending by default
        $super = $this->makeUser(['role' => 'superadmin']);

        Livewire::actingAs($super)
            ->test(\App\Livewire\admin\KycHub::class)
            ->call('approve', $customer->id);

        $this->assertSame('verified', $customer->fresh()->kyc_status);
        $this->assertDatabaseHas('kyc_submissions', [
            'user_id' => $customer->id,
            'status' => 'approved',
        ]);
        // The pre-fix component wrote non-existent audit columns; prove the
        // canonical row now persists.
        $this->assertDatabaseHas('audit_logs', ['action' => 'kyc.approved']);
    }

    // ── 4. Devices ─────────────────────────────────────────────────────────

    public function test_device_touch_creates_and_refreshes_trust_record(): void
    {
        $user = $this->makeUser();

        $device = Device::register($user, '41.79.10.1', 'Mozilla/5.0 (Linux; Android 13) Chrome/120 Safari/537.36');
        $deviceId = $device->device_id;

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'platform' => 'android',
            'browser' => 'chrome',
        ]);

        // Second touch refreshes instead of duplicating.
        Device::register($user, '41.79.10.1', 'Mozilla/5.0 (Linux; Android 13) Chrome/120 Safari/537.36');
        $this->assertSame(1, Device::where('user_id', $user->id)->count(), 'device rows must be idempotent');
    }

    // ── 5. Login telemetry (sessions intelligence) ─────────────────────────

    public function test_login_and_failed_events_are_recorded(): void
    {
        $user = $this->makeUser();

        Event::dispatch(new Login('web', $user, false));
        Event::dispatch(new Failed('web', $user, ['phone' => '08012345678']));

        $this->assertDatabaseHas('login_events', [
            'user_id' => $user->id,
            'event' => 'login_success',
        ]);
        $this->assertDatabaseHas('login_events', [
            'user_id' => $user->id,
            'event' => 'login_failed',
        ]);

        // last_login_at stamped by the listener.
        $this->assertNotNull($user->fresh()->last_login_at);

        // Failed-login meta must NOT contain the password/credential value.
        $failed = LoginEvent::where('event', 'login_failed')->first();
        $this->assertArrayNotHasKey('credentials', (array) $failed->meta);
    }

    // ── 6. Country isolation scope ─────────────────────────────────────────

    public function test_country_scope_isolates_data(): void
    {
        $ng = User::factory()->count(3)->create(['country_code' => 'NGA']);
        $ne = User::factory()->count(2)->create(['country_code' => 'NER']);

        $ngaIds = User::forCountry('NGA')->pluck('id')->all();
        $nerIds = User::forCountry('NER')->pluck('id')->all();

        $this->assertCount(3, $ngaIds);
        $this->assertCount(2, $nerIds);
        $this->assertEmpty(array_intersect($ngaIds, $nerIds), 'no cross-country leakage');

        // 'all' returns everything.
        $this->assertSame(5, User::forCountry('all')->count());
    }

    // ── 7. Audit canonical record ──────────────────────────────────────────

    public function test_audit_canonical_record_writes_real_columns(): void
    {
        $actor = $this->makeUser(['role' => 'superadmin']);
        $target = $this->makeUser();

        $log = AuditLog::record(
            'user.suspended',
            $actor->id,
            $target->id,
            ['event_type' => 'security', 'description' => 'Account suspended.', 'metadata' => ['reason' => 'KYC']],
        );

        $fresh = AuditLog::find($log->id);
        $this->assertSame('user.suspended', $fresh->action);
        $this->assertSame('security', $fresh->event_type);
        $this->assertSame('Account suspended.', $fresh->description);
        $this->assertSame('KYC', $fresh->metadata['reason']);
    }
}
