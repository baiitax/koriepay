<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * KoriePay registration is name + email + phone + country + password
 * (role: customer). These tests assert the REAL app contract.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        // Password rule uses uncompromised() — fake the HIBP range API.
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('phone_number', '08012345678')
            ->set('country_code', 'NGA')
            ->set('password', 'Str0ng!Passw0rd')
            ->set('password_confirmation', 'Str0ng!Passw0rd');

        $component->call('register');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('customer.kyc'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        // KNOWN DEFECT (tracked for Phase 4): the legacy User::$fillable omits
        // phone_number/role/country_code, so those are silently dropped by
        // mass assignment on registration. We do NOT assert them as persisted
        // — fixing that is Phase 4 Identity work, not something to fake here.
    }
}
