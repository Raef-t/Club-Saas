<?php

namespace Modules\ClubManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ClubManager\Models\Locker;

class LockerSeeder extends Seeder
{
    /**
     * Seed 55 lockers for branch_id = 5.
     * locker_number always equals key_number.
     */
    public function run(): void
    {
        $branchId = 5;

        for ($i = 1; $i <= 55; $i++) {
            Locker::updateOrCreate(
                [
                    'branch_id'     => $branchId,
                    'locker_number' => (string) $i,
                ],
                [
                    'key_number' => (string) $i,
                    'status'     => 'available',
                ]
            );
        }
    }
}
