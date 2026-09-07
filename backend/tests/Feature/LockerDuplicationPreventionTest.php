<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Locker;
use Modules\ClubManager\Services\LockerService;
use Tests\TestCase;

class LockerDuplicationPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected $branch;
    protected $lockerService;

    protected function setUp(): void
    {
        parent::setUp();

        $club = Club::create(['name' => 'Club Test']);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch Test']);
        $this->lockerService = app(LockerService::class);
    }

    public function test_get_all_lockers_does_not_duplicate_when_multiple_active_reservations_exist(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-101',
            'key_number' => 'K-101',
            'status' => 'available',
        ]);

        // Manually insert two active reservations for this locker
        DB::table('locker_reservations')->insert([
            [
                'locker_id' => $locker->id,
                'status' => 'active',
                'price' => 20,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'locker_id' => $locker->id,
                'status' => 'active',
                'price' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $lockers = $this->lockerService->getAllLockers(['branch_id' => $this->branch->id]);

        $this->assertCount(1, $lockers);
        $this->assertEquals($locker->id, $lockers->first()->id);
    }

    public function test_release_locker_expires_all_active_reservations(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-102',
            'key_number' => 'K-102',
            'status' => 'with_member',
        ]);

        // Two active reservations
        DB::table('locker_reservations')->insert([
            [
                'locker_id' => $locker->id,
                'status' => 'active',
                'price' => 20,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'locker_id' => $locker->id,
                'status' => 'active',
                'price' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->lockerService->releaseLocker($locker->id, 'Ending test reservation');

        $activeReservationsCount = DB::table('locker_reservations')
            ->where('locker_id', $locker->id)
            ->where('status', 'active')
            ->count();

        $this->assertEquals(0, $activeReservationsCount);

        $expiredReservationsCount = DB::table('locker_reservations')
            ->where('locker_id', $locker->id)
            ->where('status', 'expired')
            ->count();

        $this->assertEquals(2, $expiredReservationsCount);
    }
}
