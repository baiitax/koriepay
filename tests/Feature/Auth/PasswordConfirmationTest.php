<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * The app's confirm-password screen hashes against Auth::user()->password and
 * routes to the user's role dashboard. Tests assert the REAL contract.
 */
class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->forceFill(['role' => 'customer'])->save();

        return $user;
    }

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $this->actingAs($this->customer())
            ->get('/confirm-password')
            ->assertOk()
            ->assertSeeVolt('pages.auth.confirm-password');
    }

    public function test_password_can_be_confirmed(): void
    {
        $this->actingAs($this->customer());

        $component = Volt::test('pages.auth.confirm-password')
            ->set('password', 'password');

        $component->call('confirmPassword');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('customer.dashboard'));

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $this->actingAs($this->customer());

        $component = Volt::test('pages.auth.confirm-password')
            ->set('password', 'wrong-password');

        $component->call('confirmPassword');

        $component
            ->assertHasErrors(['password'])
            ->assertNoRedirect();

        $this->assertNull(session('auth.password_confirmed_at'));
    }
}
