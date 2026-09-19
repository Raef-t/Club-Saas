<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\CoachDetail;

class CoachPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $coach;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $adminPerson = Person::create([
            'full_name' => 'Admin Test',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin_test_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);
        $this->actingAs($this->adminUser, 'sanctum');

        $club = Club::create(['name' => 'Gym Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);

        $this->setupCoachWithPhoto();
    }

    protected function setupCoachWithPhoto(): void
    {
        Storage::disk('public')->put('people/photos/old_photo.jpg', 'old content');

        $person = Person::create([
            'full_name' => 'Coach Ahmed',
            'gender' => 'male',
            'type' => 'staff',
            'photo_url' => 'people/photos/old_photo.jpg',
        ]);

        $this->coach = Staff::create([
            'person_id' => $person->id,
            'role' => 'coach',
            'employment_type' => 'fixed_salary',
            'work_status' => 'active',
        ]);

        CoachDetail::create([
            'staff_id' => $this->coach->id,
            'experience_years' => 5,
        ]);
    }

    public function test_can_upload_new_coach_photo_and_old_photo_is_deleted_from_disk(): void
    {
        $file = UploadedFile::fake()->create('new_coach.jpg', 10, 'image/jpeg');

        $response = $this->postJson("/api/v1/coaches/{$this->coach->id}/photo", [
            'photo' => $file,
        ]);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNotNull($person->getRawOriginal('photo_url'));
        $this->assertNotEquals('people/photos/old_photo.jpg', $person->getRawOriginal('photo_url'));
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
        Storage::disk('public')->assertExists($person->getRawOriginal('photo_url'));
    }

    public function test_can_remove_coach_photo_with_null_photo(): void
    {
        $response = $this->postJson("/api/v1/coaches/{$this->coach->id}/photo", [
            'photo' => null,
        ]);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNull($person->getRawOriginal('photo_url'));
        $this->assertNull($person->photo_url);
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
    }

    public function test_can_remove_coach_photo_with_delete_photo_flag(): void
    {
        $response = $this->postJson("/api/v1/coaches/{$this->coach->id}/photo", [
            'delete_photo' => true,
        ]);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNull($person->getRawOriginal('photo_url'));
        $this->assertNull($person->photo_url);
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
    }

    public function test_can_remove_coach_photo_with_empty_post(): void
    {
        $response = $this->post("/api/v1/coaches/{$this->coach->id}/photo", []);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNull($person->getRawOriginal('photo_url'));
        $this->assertNull($person->photo_url);
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
    }

    public function test_can_remove_coach_photo_with_empty_string_photo(): void
    {
        $response = $this->post("/api/v1/coaches/{$this->coach->id}/photo", ['photo' => '']);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNull($person->getRawOriginal('photo_url'));
        $this->assertNull($person->photo_url);
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
    }

    public function test_can_remove_coach_photo_with_string_null(): void
    {
        $response = $this->post("/api/v1/coaches/{$this->coach->id}/photo", ['photo' => 'null']);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNull($person->getRawOriginal('photo_url'));
        $this->assertNull($person->photo_url);
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
    }

    public function test_can_remove_coach_photo_via_patch_update_with_delete_photo(): void
    {
        $response = $this->patchJson("/api/v1/coaches/{$this->coach->id}", [
            'reason' => 'حذف صورة المدرب',
            'delete_photo' => true,
        ]);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNull($person->getRawOriginal('photo_url'));
        $this->assertNull($person->photo_url);
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
    }

    public function test_can_remove_coach_photo_via_patch_update_with_null_photo(): void
    {
        $response = $this->patchJson("/api/v1/coaches/{$this->coach->id}", [
            'reason' => 'حذف صورة المدرب عبر photo null',
            'photo' => null,
        ]);

        $response->assertStatus(200);
        $person = $this->coach->fresh()->person;
        $this->assertNull($person->getRawOriginal('photo_url'));
        $this->assertNull($person->photo_url);
        Storage::disk('public')->assertMissing('people/photos/old_photo.jpg');
    }
}
