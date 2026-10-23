<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_is_always_created_as_user(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'user@example.com', 'role' => 'user']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'used@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Nguyễn Văn B',
            'email' => 'used@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
    }

    public function test_login_success_and_failure(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com', 'password' => Hash::make('password123')]);

        $this->from(route('login'))->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_authentication(): void
    {
        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_guests_are_blocked_from_account_and_admin(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_is_forbidden_from_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))->assertOk()->assertSee('PhoneStore Admin');
    }

    public function test_user_can_update_name_but_cannot_change_role(): void
    {
        $user = User::factory()->create(['name' => 'Tên cũ', 'role' => 'user']);

        $this->actingAs($user)->put(route('account.update'), [
            'name' => 'Tên mới',
            'role' => 'admin',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Tên mới', 'role' => 'user']);
    }
}
