<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_cannot_manage_staff(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_deactivate_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'api');

        $id = $this->postJson('/api/v1/admin/users', [
            'name'     => 'Manager',
            'email'    => 'manager@mail.ru',
            'password' => 'secret123',
            'role'     => 'manager',
        ])->assertCreated()
            ->assertJsonPath('data.role', 'manager')
            ->assertJsonMissingPath('data.password')
            ->json('data.id');

        $this->postJson('/api/v1/admin/users', ['name' => 'X', 'email' => 'manager@mail.ru', 'password' => 'secret123', 'role' => 'boss'])
            ->assertJsonValidationErrors(['email', 'role']);

        $this->putJson("/api/v1/admin/users/{$id}", ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->getJson('/api/v1/admin/users')->assertJsonCount(2, 'data');

        $this->deleteJson("/api/v1/admin/users/{$id}")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $id, 'is_active' => false]);
    }

    public function test_admin_cannot_deactivate_or_demote_self(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'api');

        $this->deleteJson("/api/v1/admin/users/{$admin->id}")->assertStatus(422);
        $this->putJson("/api/v1/admin/users/{$admin->id}", ['role' => 'manager'])->assertStatus(422);
        $this->putJson("/api/v1/admin/users/{$admin->id}", ['is_active' => false])->assertStatus(422);
        $this->putJson("/api/v1/admin/users/{$admin->id}", ['name' => 'New name'])->assertOk();
    }

    public function test_deactivated_staff_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['email' => 'old@mail.ru', 'password' => 'secret123']);
        User::factory()->create(['email' => 'ok@mail.ru', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/admin/login', ['email' => 'old@mail.ru', 'password' => 'secret123'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/admin/login', ['email' => 'ok@mail.ru', 'password' => 'secret123'])->assertOk()->assertJsonStructure(['token']);
    }
}
