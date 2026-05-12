<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\Person;

class PeopleService
{
    /**
     * Register a new Person (Identity only).
     * Operational data (Profiles) will be handled by specialized modules later.
     */
    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Create the basic Human record
            return Person::create([
                'tenant_id' => session('tenant_id'),
                'full_name' => $data['full_name'],
                'mobile_1' => $data['mobile_1'],
                'gender' => $data['gender'] ?? 'male',
                'dob' => $data['dob'] ?? null,
                'email' => $data['email'] ?? null,
                'type' => $data['type'], // Used to identify the intended role
            ]);
        });
    }
}
