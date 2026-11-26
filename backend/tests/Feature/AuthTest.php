<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, CreatesFixtures;

    public function test_login_returns_token_and_user(): void
    {
        User::factory()->create(['email' => 'a@gym.test', 'password' => 'secret123', 'role' => 'admin']);

        $this->postJson('/api/auth/login', ['email' => 'a@gym.test', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_wrong_password_gets_generic_401(): void
    {
        User::factory()->create(['email' => 'a@gym.test', 'password' => 'secret123', 'role' => 'admin']);

        $this->postJson('/api/auth/login', ['email' => 'a@gym.test', 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'a@gym.test',
            'password' => 'secret123',
            'role' => 'staff',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', ['email' => 'a@gym.test', 'password' => 'secret123'])
            ->assertStatus(403);
    }

    public function test_unauthenticated_request_gets_json_401(): void
    {
        $this->getJson('/api/members')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_validation_error_uses_standard_shape(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_roles_are_enforced(): void
    {
        $this->actingAsRole('trainer');

        $this->getJson('/api/members')->assertOk();
        $this->postJson('/api/members', ['name' => 'X'])->assertForbidden();
        $this->getJson('/api/reports/revenue')->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_inactive_user_with_valid_token_is_blocked_by_role_middleware(): void
    {
        $user = $this->actingAsRole('staff');
        $user->update(['is_active' => false]);

        $this->getJson('/api/members')->assertForbidden();
    }
}
