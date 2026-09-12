<?php

namespace Modules\Authentication\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserStatusAndSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    protected function createSuperAdmin(): User
    {
        $person = Person::create([
            'full_name' => 'Super Admin',
            'type'      => 'staff',
        ]);

        $user = User::create([
            'person_id' => $person->id,
            'username'  => 'superadmin_' . uniqid(),
            'password'  => bcrypt('secret123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $user->assignRole($role);

        return $user;
    }

    protected function createRegularUser(string $role = 'player', bool $isActive = true): User
    {
        $person = Person::create([
            'full_name' => 'Regular User ' . uniqid(),
            'type'      => $role,
        ]);

        $user = User::create([
            'person_id' => $person->id,
            'username'  => 'user_' . uniqid(),
            'password'  => bcrypt('secret123'),
            'role'      => $role,
            'is_active' => $isActive,
        ]);

        $spatieRole = Role::firstOrCreate(['name' => $role, 'guard_name' => 'sanctum']);
        $user->assignRole($spatieRole);

        return $user;
    }

    public function test_can_filter_users_by_is_active(): void
    {
        $admin = $this->createSuperAdmin();
        $activeUser = $this->createRegularUser('player', true);
        $inactiveUser = $this->createRegularUser('player', false);

        $responseActive = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users?is_active=1');
        $responseActive->assertOk();
        $usernames = collect($responseActive->json('data'))->pluck('username');
        $this->assertTrue($usernames->contains($activeUser->username));
        $this->assertFalse($usernames->contains($inactiveUser->username));

        $responseInactive = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users?is_active=0');
        $responseInactive->assertOk();
        $inactiveUsernames = collect($responseInactive->json('data'))->pluck('username');
        $this->assertFalse($inactiveUsernames->contains($activeUser->username));
        $this->assertTrue($inactiveUsernames->contains($inactiveUser->username));
    }

    public function test_can_toggle_user_status_and_revoke_tokens(): void
    {
        $admin = $this->createSuperAdmin();
        $targetUser = $this->createRegularUser('player', true);

        // Create a token for target user
        $token = $targetUser->createToken('test_token');
        $this->assertCount(1, $targetUser->fresh()->tokens);

        // Toggle status to inactive
        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/users/{$targetUser->id}/toggle-status");
        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($targetUser->fresh()->is_active);
        // Assert tokens revoked
        $this->assertCount(0, $targetUser->fresh()->tokens);

        // Toggle status back to active
        $response2 = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/users/{$targetUser->id}/toggle-status");
        $response2->assertOk()
            ->assertJsonPath('data.is_active', true);
        $this->assertTrue($targetUser->fresh()->is_active);
    }

    public function test_cannot_toggle_self_status(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/users/{$admin->id}/toggle-status");
        $response->assertStatus(422);
    }

    public function test_can_soft_delete_and_restore_user(): void
    {
        $admin = $this->createSuperAdmin();
        $targetUser = $this->createRegularUser('player', true);

        // Soft delete requires confirmation=delete
        $failResponse = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/users/{$targetUser->id}");
        $failResponse->assertStatus(422);

        $deleteResponse = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/users/{$targetUser->id}", [
            'confirmation' => 'delete',
        ]);
        $deleteResponse->assertOk();

        // User should not appear in regular index
        $indexResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users');
        $usernames = collect($indexResponse->json('data'))->pluck('username');
        $this->assertFalse($usernames->contains($targetUser->username));

        // User should appear in trashed list
        $trashedResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users/trashed');
        $trashedResponse->assertOk();
        $trashedUsernames = collect($trashedResponse->json('data'))->pluck('username');
        $this->assertTrue($trashedUsernames->contains($targetUser->username));

        // Restore user
        $restoreResponse = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/users/{$targetUser->id}/restore");
        $restoreResponse->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertNull($targetUser->fresh()->deleted_at);
        $this->assertTrue($targetUser->fresh()->is_active);
    }
}
