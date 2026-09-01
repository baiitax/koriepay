<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * KoriePay profile page lives at /customer/profile (customer portal).
 * The Breeze-style Volt profile forms still exist and are exercised directly.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'customer'])->save();

        return $user;
    }

    public function test_customer_profile_page_is_displayed(): void
    {
        $this->actingAs($this->customer())
            ->get('/customer/profile')
            ->assertOk();
    }

    public function test_profile_requires_customer_role(): void
    {
        $agent = User::factory()->create();
        $agent->forceFill(['role' => 'agent'])->save();

        $this->actingAs($agent)
            ->get('/customer/profile')
            ->assertForbidden();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->customer();
        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($this->customer());

        $component = Volt::test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertTrue(Hash::check('new-password', auth()->user()->fresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $this->actingAs($this->customer());

        $component = Volt::test('profile.update-password-form')
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $component->assertHasErrors(['current_password']);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = $this->customer();
        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component->assertHasNoErrors();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
