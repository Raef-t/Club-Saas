<?php

namespace Modules\Authentication\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    protected function createUser(array $attributes = []): User
    {
        if (empty($attributes['person_id'])) {
            $person = \Modules\Authentication\Models\Person::create([
                'full_name'  => 'Test User',
                'first_name' => 'Test',
                'last_name'  => 'User',
                'gender'     => 'male',
                'type'       => 'staff',
            ]);
            $attributes['person_id'] = $person->id;
        }

        if (empty($attributes['username'])) {
            $attributes['username'] = 'testuser_' . uniqid();
        }

        if (empty($attributes['password'])) {
            $attributes['password'] = bcrypt('secret123');
        }

        $user = User::create($attributes);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $user->assignRole($superAdminRole);

        return $user;
    }

    public function test_can_list_roles_with_name_ar_and_is_visible(): void
    {
        $user = $this->createUser();

        $role = Role::firstOrCreate([
            'name'       => 'test_role',
            'guard_name' => 'sanctum',
        ], [
            'name_ar'    => 'دور اختباري',
            'is_visible' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'roles' => [
                        '*' => ['id', 'name', 'name_ar', 'is_visible', 'permissions_count', 'is_protected'],
                    ],
                    'total',
                ],
            ])
            ->assertJsonMissing(['permissions' => []]);
    }

    public function test_can_create_role_with_name_ar_and_is_visible(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/roles', [
            'name'       => 'supervisor_role',
            'name_ar'    => 'مشرف عام',
            'is_visible' => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.role.name', 'supervisor_role')
            ->assertJsonPath('data.role.name_ar', 'مشرف عام')
            ->assertJsonPath('data.role.is_visible', false);

        $this->assertDatabaseHas('roles', [
            'name'       => 'supervisor_role',
            'name_ar'    => 'مشرف عام',
            'is_visible' => 0,
            'guard_name' => 'sanctum',
        ]);
    }

    public function test_can_update_role_name_ar_and_is_visible(): void
    {
        $user = $this->createUser();

        $role = Role::create([
            'name'       => 'custom_manager',
            'name_ar'    => 'مدير مخصص',
            'is_visible' => true,
            'guard_name' => 'sanctum',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/roles/{$role->id}", [
            'name_ar'    => 'مدير مخصص معدل',
            'is_visible' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.role.name_ar', 'مدير مخصص معدل')
            ->assertJsonPath('data.role.is_visible', false);

        $this->assertDatabaseHas('roles', [
            'id'         => $role->id,
            'name_ar'    => 'مدير مخصص معدل',
            'is_visible' => 0,
        ]);
    }

    public function test_auth_me_returns_roles_and_permissions(): void
    {
        $user = $this->createUser();
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name'       => 'test-module.view',
            'guard_name' => 'sanctum',
        ]);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.username', $user->username)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'username',
                    'is_active',
                    'person',
                    'roles',
                    'permissions' => [
                        '*' => ['id', 'name', 'module'],
                    ],
                ],
            ]);

        $this->assertContains('super_admin', $response->json('data.roles'));
        $permissions = collect($response->json('data.permissions'));
        $this->assertTrue($permissions->contains('name', 'test-module.view'));
        $this->assertTrue($permissions->contains('module', 'test-module'));
    }
}

