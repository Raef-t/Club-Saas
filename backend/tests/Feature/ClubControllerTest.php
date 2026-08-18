<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class ClubControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $person = \Modules\Authentication\Models\Person::create([
            'full_name' => 'Admin Test',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $user->assignRole($role);

        Sanctum::actingAs($user);
    }

    public function test_can_list_clubs(): void
    {
        Club::create(['name' => 'Club One', 'is_active' => true]);
        Club::create(['name' => 'Club Two', 'is_active' => false]);

        $response = $this->getJson('/api/v1/clubs');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');
    }

    public function test_can_create_club(): void
    {
        $payload = [
            'name' => 'New Premium Club',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/clubs', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.name', 'New Premium Club');

        $this->assertDatabaseHas('clubs', ['name' => 'New Premium Club']);
    }

    public function test_can_show_club(): void
    {
        $club = Club::create(['name' => 'Specific Club', 'is_active' => true]);

        $response = $this->getJson("/api/v1/clubs/{$club->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.id', $club->id)
                 ->assertJsonPath('data.name', 'Specific Club');
    }

    public function test_can_update_club(): void
    {
        $club = Club::create(['name' => 'Old Name', 'is_active' => true]);

        $payload = [
            'name' => 'Updated Name',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/v1/clubs/{$club->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'name' => 'Updated Name']);
    }

    public function test_cannot_delete_club_without_confirmation(): void
    {
        $club = Club::create(['name' => 'Club To Delete', 'is_active' => true]);

        $response = $this->deleteJson("/api/v1/clubs/{$club->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('clubs', ['id' => $club->id, 'deleted_at' => null]);
    }

    public function test_can_delete_club_with_confirmation(): void
    {
        $club = Club::create(['name' => 'Club To Delete', 'is_active' => true]);

        $response = $this->deleteJson("/api/v1/clubs/{$club->id}", ['confirmation' => 'delete']);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('clubs', ['id' => $club->id]);
    }

    public function test_can_get_trashed_clubs(): void
    {
        $club = Club::create(['name' => 'Trashed Club', 'is_active' => true]);
        $club->delete();

        $response = $this->getJson('/api/v1/clubs/trashed');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');
    }

    public function test_can_restore_club(): void
    {
        $club = Club::create(['name' => 'Club To Restore', 'is_active' => true]);
        $club->delete();

        $this->assertSoftDeleted('clubs', ['id' => $club->id]);

        $response = $this->postJson("/api/v1/clubs/{$club->id}/restore");

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        $this->assertNotSoftDeleted('clubs', ['id' => $club->id]);
    }

    public function test_can_create_and_update_club_with_logo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('club_logo.png', 200, 200);

        $response = $this->postJson('/api/v1/clubs', [
            'name' => 'Logo Club',
            'logo' => $file,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.name', 'Logo Club');

        $club = Club::where('name', 'Logo Club')->first();
        $this->assertNotNull($club);
        $this->assertNotNull($club->logo_url);
        $this->assertStringContainsString('clubs/logos', $club->getRawOriginal('logo_url'));

        // Update logo via dedicated endpoint
        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new_logo.jpg', 300, 300);
        $updateResponse = $this->postJson("/api/v1/clubs/{$club->id}/logo", [
            'logo' => $newFile,
        ]);

        $updateResponse->assertStatus(200)
                       ->assertJsonPath('status', 'success')
                       ->assertJsonPath('data.id', $club->id);

        $club->refresh();
        $this->assertStringContainsString('clubs/logos', $club->getRawOriginal('logo_url'));
    }
}
