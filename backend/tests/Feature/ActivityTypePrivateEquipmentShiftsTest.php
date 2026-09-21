<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Sports\Models\ActivityType;
use Spatie\Permission\Models\Role;

class ActivityTypePrivateEquipmentShiftsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_cannot_create_activity_type_with_private_equipment_and_shifts(): void
    {
        $response = $this->postJson('/api/v1/activity-types', [
            'name' => 'Private Gym',
            'is_private_equipment' => true,
            'has_shifts' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['has_shifts']);
    }

    public function test_cannot_create_activity_type_using_alias_is_private_equipments_and_shifts(): void
    {
        $response = $this->postJson('/api/v1/activity-types', [
            'name' => 'Private Gym Alias',
            'is_private_equipments' => true,
            'has_shifts' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['has_shifts']);
    }

    public function test_can_create_activity_type_with_private_equipment_and_shifts_false(): void
    {
        $response = $this->postJson('/api/v1/activity-types', [
            'name' => 'Private Gym Valid',
            'is_private_equipment' => true,
            'has_shifts' => false,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_private_equipment', true)
            ->assertJsonPath('data.has_shifts', false);
    }

    public function test_can_create_activity_type_with_private_equipment_omitting_shifts(): void
    {
        $response = $this->postJson('/api/v1/activity-types', [
            'name' => 'Private Gym Omitted Shifts',
            'is_private_equipment' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_private_equipment', true)
            ->assertJsonPath('data.has_shifts', false);
    }

    public function test_cannot_update_activity_type_with_private_equipment_and_shifts(): void
    {
        $type = ActivityType::create([
            'name' => 'Existing Private',
            'is_private_equipment' => true,
            'has_shifts' => false,
        ]);

        $response = $this->putJson("/api/v1/activity-types/{$type->id}", [
            'name' => 'Existing Private Updated',
            'has_shifts' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['has_shifts']);
    }

    public function test_cannot_update_settings_with_shifts_when_private_equipment_is_true(): void
    {
        $type = ActivityType::create([
            'name' => 'Existing Settings Test',
            'is_private_equipment' => true,
            'has_shifts' => false,
        ]);

        $response = $this->patchJson("/api/v1/activity-types/{$type->id}/settings", [
            'has_shifts' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['has_shifts']);
    }

    public function test_can_update_settings_with_private_equipment_and_shifts_false(): void
    {
        $type = ActivityType::create([
            'name' => 'General Type',
            'is_private_equipment' => false,
            'has_shifts' => true,
        ]);

        $response = $this->patchJson("/api/v1/activity-types/{$type->id}/settings", [
            'is_private_equipment' => true,
            'has_shifts' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_private_equipment', true)
            ->assertJsonPath('data.has_shifts', false);
    }

    public function test_updating_settings_to_private_equipment_automatically_disables_shifts(): void
    {
        $type = ActivityType::create([
            'name' => 'General Type to Private',
            'is_private_equipment' => false,
            'has_shifts' => true,
        ]);

        $response = $this->patchJson("/api/v1/activity-types/{$type->id}/settings", [
            'is_private_equipment' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_private_equipment', true)
            ->assertJsonPath('data.has_shifts', false);

        $this->assertFalse($type->fresh()->has_shifts);
    }

    public function test_model_saving_hook_enforces_has_shifts_false_when_private_equipment_is_true(): void
    {
        $type = ActivityType::create([
            'name' => 'Eloquent Direct Create',
            'is_private_equipment' => true,
            'has_shifts' => true,
        ]);

        $this->assertTrue($type->fresh()->is_private_equipment);
        $this->assertFalse($type->fresh()->has_shifts);

        // Also test updating existing model directly
        $general = ActivityType::create([
            'name' => 'General direct',
            'is_private_equipment' => false,
            'has_shifts' => true,
        ]);
        $this->assertTrue($general->fresh()->has_shifts);

        $general->update([
            'is_private_equipment' => true,
        ]);
        $this->assertFalse($general->fresh()->has_shifts);
    }
}
