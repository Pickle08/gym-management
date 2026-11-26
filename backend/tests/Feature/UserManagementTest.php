<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFixtures;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, CreatesFixtures;

    public function test_admin_cannot_demote_or_deactivate_self(): void
    {
        $admin = $this->actingAsRole('admin');

        $this->putJson("/api/users/{$admin->id}", ['role' => 'staff'])->assertStatus(422);
        $this->putJson("/api/users/{$admin->id}", ['is_active' => false])->assertStatus(422);

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_deactivating_user_revokes_their_tokens(): void
    {
        $this->actingAsRole('admin');
        $target = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $target->createToken('api');

        $this->assertSame(1, $target->tokens()->count());

        $this->putJson("/api/users/{$target->id}", ['is_active' => false])->assertOk();

        $this->assertSame(0, $target->tokens()->count());
    }

    public function test_changing_role_revokes_tokens(): void
    {
        $this->actingAsRole('admin');
        $target = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $target->createToken('api');

        $this->putJson("/api/users/{$target->id}", ['role' => 'trainer'])->assertOk();

        $this->assertSame(0, $target->tokens()->count());
    }

    public function test_password_is_stored_hashed_and_validated(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/api/users', [
            'name' => 'Baru',
            'email' => 'baru@gym.test',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
            'role' => 'staff',
        ])->assertStatus(422);

        $this->postJson('/api/users', [
            'name' => 'Baru',
            'email' => 'baru@gym.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'staff',
        ])->assertCreated();

        $this->assertNotSame('password123', User::where('email', 'baru@gym.test')->value('password'));
    }

    public function test_staff_cannot_manage_users(): void
    {
        $this->actingAsRole('staff');

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [])->assertForbidden();
    }
}
