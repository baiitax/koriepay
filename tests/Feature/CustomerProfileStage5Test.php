<?php

namespace Tests\Feature;

use App\Domain\Customer\CustomerKycService;
use App\Domain\Customer\CustomerSecurityService;
use App\Models\CustomerWalletConfig;
use App\Models\Device;
use App\Models\KycSubmission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CUSTOMER BANKING APP — Stage 5 (profile / security / branding).
 *
 * Hard guards this stage exists for:
 *   - the customer app NEVER persists credentials — no PIN hashes, no
 *     biometric material; state is session-only or transient;
 *   - security data is honest — devices come from the real `devices` table,
 *     limits from the real wallet config, daily spend from real transactions;
 *   - KYC revaluation comes from the real kyc_submissions records (approved
 *     submission ⇒ autopass) and never invents verification;
 *   - locale switching is en/fr/ha with session persistence.
 */
class CustomerProfileStage5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CustomerWalletConfig::updateOrCreate(
            ['country_iso2' => 'NE', 'currency_code' => 'NGN'],
            ['is_available' => 1, 'is_primary_default' => 0, 'min_kyc_tier' => 1,
                'daily_send_limit' => '1000000.00', 'daily_exchange_limit' => '1000000.00',
                'exchange_fee_flat' => '500', 'exchange_fee_rate' => '0.5000'],
        );
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->withRole('customer')->create(array_merge([
            'name' => 'Aminatou Niger',
            'country_code' => 'NER',
            'kyc_tier' => 2,
            'kyc_status' => 'unverified',
            'phone_number' => '+22790000001',
            'is_active' => true,
        ], $overrides));
    }

    // ── Language switching ────────────────────────────────────────────────

    public function test_language_switcher_defaults_to_english(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\LanguageSwitcher::class)
            ->assertSet('locale', 'en');
    }

    public function test_language_switcher_persists_locale_in_session(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\LanguageSwitcher::class)
            ->call('switchTo', 'fr')
            ->assertSet('locale', 'fr')
            ->assertSessionHas('locale', 'fr')
            ->assertDispatched('locale-changed');

        $this->assertSame('fr', app()->getLocale());
    }

    public function test_language_switcher_supports_hausa_and_rejects_unknown(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\LanguageSwitcher::class)
            ->call('switchTo', 'ha')
            ->assertSessionHas('locale', 'ha')
            ->call('switchTo', 'xx')
            ->assertSessionHas('locale', 'ha'); // unchanged
    }

    // ── KYC revaluation (honest) ──────────────────────────────────────────

    public function test_kyc_revaluation_without_submission_is_unverified(): void
    {
        $user = $this->user(['kyc_tier' => 0]);

        $state = app(CustomerKycService::class)->revaluate($user);

        $this->assertSame('unverified', $state['status']);
        $this->assertSame('users.kyc_status', $state['source']);
        $this->assertFalse($state['has_submission']);
        $this->assertSame('upgrade', $state['recommendation']['action']);
        $this->assertSame(0, $state['tier']);
    }

    public function test_kyc_revaluation_with_pending_submission_is_pending(): void
    {
        $user = $this->user();
        KycSubmission::create([
            'user_id' => $user->id, 'type' => 'personal', 'status' => 'pending',
            'tier' => 1, 'country_code' => 'NER', 'data' => [],
            'submitted_at' => now(),
        ]);

        $state = app(CustomerKycService::class)->revaluate($user);

        $this->assertSame('pending', $state['status']);
        $this->assertSame('wait', $state['recommendation']['action']);
        $this->assertSame(1, $state['tier']);
    }

    public function test_kyc_revaluation_approved_submission_autopasses(): void
    {
        // Denormalized mirror says unverified — the approved submission wins.
        $user = $this->user(['kyc_status' => 'unverified', 'kyc_tier' => 0]);
        KycSubmission::create([
            'user_id' => $user->id, 'type' => 'personal', 'status' => 'approved',
            'tier' => 2, 'country_code' => 'NER', 'data' => ['dob' => '1990-01-01'],
            'reviewed_at' => now(), 'submitted_at' => now()->subDay(),
        ]);

        $state = app(CustomerKycService::class)->revaluate($user);

        $this->assertSame('verified', $state['status']);
        $this->assertSame('kyc_submissions.approved', $state['source']);
        $this->assertSame(2, $state['tier']);
        $this->assertSame('none', $state['recommendation']['action']);
    }

    // ── Device / biometric / PIN (session-only) ───────────────────────────

    public function test_biometric_endpoint_is_session_only_and_never_persisted(): void
    {
        $user = $this->user();
        $token = $user->createToken('s5')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/customer/profile/security/biometric', ['is_enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.biometric_enabled', true)
            ->assertJsonPath('data.persisted', false);

        // Nothing written to the database.
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'biometric_enabled' => 1]);

        // Toggle back off.
        $this->withToken($token)
            ->postJson('/api/v1/customer/profile/security/biometric', ['is_enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.biometric_enabled', false);
    }

    public function test_biometric_endpoint_validates_boolean(): void
    {
        $user = $this->user();

        $this->withToken($user->createToken('s5')->plainTextToken)
            ->postJson('/api/v1/customer/profile/security/biometric', ['is_enabled' => 'yes'])
            ->assertUnprocessable();
    }

    public function test_pin_enroll_endpoint_refuses_to_store_pin(): void
    {
        $user = $this->user();

        $this->withToken($user->createToken('s5')->plainTextToken)
            ->postJson('/api/v1/customer/profile/pin/enroll', ['pin' => '123456'])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'pin_storage_not_supported');

        // The legacy transaction_pin column must never be populated by the
        // customer app.
        $this->assertNull($user->fresh()->transaction_pin);
    }

    public function test_profile_pin_flow_never_persists_anything(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\Profile::class)
            ->call('confirmPin')
            ->assertDispatched('toast');

        $this->assertNull($user->fresh()->transaction_pin);
        $this->assertDatabaseCount('users', 1);
    }

    // ── Security center data honesty ──────────────────────────────────────

    public function test_security_devices_empty_state_is_honest(): void
    {
        $user = $this->user();

        $devices = app(CustomerSecurityService::class)->devices($user);

        $this->assertSame([], $devices['items']);
        $this->assertSame('insufficient_usage_data', $devices['empty_reason']);
    }

    public function test_security_devices_list_real_rows(): void
    {
        $user = $this->user();
        Device::create([
            'user_id' => $user->id, 'device_id' => hash('sha256', 'ip|ua'),
            'platform' => 'Android', 'browser' => 'Chrome',
            'is_current' => true, 'is_trusted' => true, 'last_seen_at' => now(),
        ]);

        $devices = app(CustomerSecurityService::class)->devices($user);

        $this->assertCount(1, $devices['items']);
        $this->assertSame('Android', $devices['items'][0]['platform']);
        $this->assertTrue($devices['items'][0]['is_current']);
        $this->assertNull($devices['empty_reason']);
    }

    public function test_wallet_limits_carry_config_values_and_real_daily_spend(): void
    {
        $user = $this->user();
        app(\App\Domain\Customer\CustomerWalletService::class)->provision($user);

        // Real money-out today in XOF.
        Transaction::create([
            'sender_id' => $user->id, 'receiver_id' => $user->id, 'type' => 'transfer',
            'source_currency' => 'XOF', 'destination_currency' => 'XOF',
            'source_amount' => '25000.00', 'destination_amount' => '25000.00',
            'exchange_rate' => '1.0000', 'fee_charged' => '0.00', 'status' => 'settled',
            'reference' => 'KP-SPEND-01', 'description' => 'today spend',
            'provider' => 'ledger', 'rail' => 'internal', 'idempotency_key' => 'sp-1',
        ]);

        $limits = app(CustomerSecurityService::class)->walletLimits($user);
        $xof = collect($limits)->firstWhere('currency', 'XOF');

        $this->assertNotNull($xof);
        $this->assertSame('1000000.00', $xof['daily_send_limit']);
        $this->assertSame('1000000.00', $xof['daily_exchange_limit']);
        $this->assertSame('25000.00', $xof['daily_spent_today']);
        $this->assertSame('NE', $xof['config_country']);
    }

    public function test_session_limit_edits_are_session_only(): void
    {
        $user = $this->user();
        $service = app(CustomerSecurityService::class);

        $this->assertSame([], $service->sessionLimitEdits($user));

        $service->saveLimitEdit($user, 'XOF', 'send', '2000000.00');

        $edits = $service->sessionLimitEdits($user);
        $this->assertSame('2000000.00', $edits['XOF']['send']);

        // Config tables untouched.
        $this->assertSame(
            '1000000.00',
            CustomerWalletConfig::where('country_iso2', 'NE')->where('currency_code', 'XOF')->value('daily_send_limit')
        );
    }

    // ── Page smoke + identity ─────────────────────────────────────────────

    public function test_stage5_pages_render_for_authenticated_user(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/customer/profile')->assertOk();
        $this->actingAs($user)->get('/customer/security')->assertOk();
        $this->actingAs($user)->get('/customer/kyc-center')->assertOk();
    }

    public function test_kyc_center_saves_digital_identity(): void
    {
        $user = $this->user(['email' => 'aminatou@example.com']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\KycCenter::class)
            ->set('displayName', 'Aminatou Oumarou')
            ->set('displayEmail', 'aminatou.new@example.com')
            ->call('saveIdentity')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Aminatou Oumarou', $user->name);
        $this->assertSame('aminatou.new@example.com', $user->email);
    }

    public function test_kyc_center_rejects_taken_email(): void
    {
        $user = $this->user(['email' => 'aminatou@example.com']);
        $this->user(['email' => 'taken@example.com', 'phone_number' => '+22790000002']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\KycCenter::class)
            ->set('displayEmail', 'taken@example.com')
            ->call('saveIdentity')
            ->assertHasErrors('displayEmail');

        $this->assertSame('aminatou@example.com', $user->fresh()->email);
    }
}
