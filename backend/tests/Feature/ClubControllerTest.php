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

        $user = User::create([
            'username' => 'admin_test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

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

        $response->assertStatus(200)
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
}
