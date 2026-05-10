<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class PeopleController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/v1/people",
     *     summary="Register a new Person (Player, Coach, or Staff)",
     *     tags={"People Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="X-Tenant-ID",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name", "type", "mobile_1"},
     *             @OA\Property(property="full_name", type="string", example="Mohamed Ahmed"),
     *             @OA\Property(property="type", type="string", enum={"player", "coach", "staff"}, example="player"),
     *             @OA\Property(property="mobile_1", type="string", example="0512345678"),
     *             @OA\Property(property="email", type="string", example="player@example.com"),
     *             @OA\Property(property="profile_data", type="object", description="Additional data based on type")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Person registered successfully")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'type' => 'required|in:player,coach,staff',
            'mobile_1' => 'required|string|max:20',
            'email' => 'nullable|email',
            'username' => 'nullable|string|unique:authentication_users,username',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 1. Create Person
                $person = Person::create($request->only([
                    'full_name', 'gender', 'type', 'dob', 'national_id', 
                    'address', 'mobile_1', 'mobile_2', 'email'
                ]));

                // 2. Create Profile based on type
                $this->createProfile($person, $request->input('profile_data', []));

                // 3. Create User Account (Inactive by default)
                $username = $request->username ?? $this->generateUsername($person);
                $user = User::create([
                    'person_id' => $person->id,
                    'username' => $username,
                    'password' => Hash::make($request->input('password', '123456')),
                    'is_active' => false, // Management activation required
                ]);

                return $this->successResponse([
                    'person_id' => $person->id,
                    'username' => $username,
                    'type' => $person->type
                ], __('Person registered successfully'), 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    private function createProfile($person, $data)
    {
        switch ($person->type) {
            case 'player':
                $person->playerProfile()->create([
                    'tenant_id' => $person->tenant_id,
                    'qr_code' => 'QR-' . strtoupper(Str::random(10)),
                    'blood_type' => $data['blood_type'] ?? null,
                    'medical_conditions' => $data['medical_conditions'] ?? [],
                    'emergency_contact' => $data['emergency_contact'] ?? [],
                ]);
                break;
            case 'coach':
                $person->coachProfile()->create([
                    'tenant_id' => $person->tenant_id,
                    'specialization' => $data['specialization'] ?? 'General',
                    'experience_years' => $data['experience_years'] ?? 0,
                ]);
                break;
            case 'staff':
                $person->staffProfile()->create([
                    'tenant_id' => $person->tenant_id,
                    'job_title' => $data['job_title'] ?? 'Employee',
                ]);
                break;
        }
    }

    private function generateUsername($person)
    {
        $base = strtolower(explode(' ', $person->full_name)[0]) . $person->id;
        return $base;
    }
}
