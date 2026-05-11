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
    #[OA\Post(
        path: '/v1/people',
        summary: '👥 Universal Registration: Players, Coaches, & Staff',
        description: "## 📘 Overview\nThis is a highly dynamic and intelligent endpoint designed to manage the entire human resource registration of a club. It handles the creation of three distinct entities in a single atomic database transaction:\n\n1. **The Person Entity**: Basic personal information (Name, Mobile, Gender, DOB) stored in the central `people` table.\n2. **The Specialized Profile**: Depending on the `type` provided, it creates a specific record in either `player_profiles`, `coach_profiles`, or `staff_profiles` with unique metadata.\n3. **The User Account**: Automatically provisions a system account (linked to the person) with a generated username and an 'Inactive' status, ensuring no one can log in until administrative activation.\n\n### 🛠 Dynamic Logic by Type:\n- **Player**: Generates a unique, permanent **QR Code** for attendance and assigns medical/emergency data.\n- **Coach**: Captures specialization (e.g., 'Karate', 'Swimming') and experience level.\n- **Staff**: Registers the administrative job title (e.g., 'Accountant', 'Manager').\n\n### 🛡 Security & Tenancy:\nEvery registration is strictly scoped to the `X-Tenant-ID` provided in the header. This ensures that even if two clubs register the same person, their profiles and user accounts remain completely isolated in their respective club contexts.",
        tags: ['People Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'X-Tenant-ID',
        in: 'header',
        description: 'CRITICAL: The unique identifier for the club. All created data will be locked to this tenant.',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'Comprehensive registration payload. Note that the structure of "profile_data" must align with the "type" field.',
        content: new OA\JsonContent(
            required: ['full_name', 'type', 'mobile_1'],
            properties: [
                new OA\Property(property: 'full_name', type: 'string', description: 'Full legal name (e.g., Ahmed Mohamed Ali)', example: 'Mohamed Ahmed'),
                new OA\Property(property: 'type', type: 'string', enum: ['player', 'coach', 'staff'], description: 'Determines which specialized profile table will be populated.', example: 'player'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', description: 'Date of Birth (YYYY-MM-DD). Used for age-based group assignment.', example: '2005-06-15'),
                new OA\Property(property: 'mobile_1', type: 'string', description: 'Primary contact number (Primary identifier in many systems).', example: '0512345678'),
                new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Optional contact email.', example: 'player@example.com'),
                new OA\Property(property: 'password', type: 'string', description: 'Initial login password. If omitted, it defaults to "123456".', example: 'password123'),
                new OA\Property(
                    property: 'profile_data',
                    type: 'object',
                    description: 'Schema-less object for specialized metadata.',
                    properties: [
                        new OA\Property(property: 'blood_type', type: 'string', description: '[For Players] Critical for medical safety.', example: 'A+'),
                        new OA\Property(property: 'specialization', type: 'string', description: '[For Coaches] The primary sport or skill they teach.', example: 'Swimming'),
                        new OA\Property(property: 'job_title', type: 'string', description: '[For Staff] Official administrative title.', example: 'Accountant'),
                        new OA\Property(property: 'medical_conditions', type: 'array', items: new OA\Items(type: 'string'), description: '[For Players] List of allergies or chronic conditions.', example: ["Asthma"]),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ Person & Profile Created Successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'person_id', type: 'integer', example: 50),
                        new OA\Property(property: 'username', type: 'string', example: 'mohamed50'),
                        new OA\Property(property: 'type', type: 'string', example: 'player'),
                        new OA\Property(property: 'qr_code', type: 'string', description: 'Generated QR code for players', example: 'QR-X1Y2Z3'),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ Validation Error', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse'))]
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
                $profile = $this->createProfile($person, $request->input('profile_data', []));

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
                    'type' => $person->type,
                    'qr_code' => $profile->qr_code ?? null
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
                return $person->playerProfile()->create([
                    'tenant_id' => $person->tenant_id,
                    'qr_code' => 'QR-' . strtoupper(Str::random(10)),
                    'blood_type' => $data['blood_type'] ?? null,
                    'medical_conditions' => $data['medical_conditions'] ?? [],
                    'emergency_contact' => $data['emergency_contact'] ?? [],
                ]);
            case 'coach':
                return $person->coachProfile()->create([
                    'tenant_id' => $person->tenant_id,
                    'specialization' => $data['specialization'] ?? 'General',
                    'experience_years' => $data['experience_years'] ?? 0,
                ]);
            case 'staff':
                return $person->staffProfile()->create([
                    'tenant_id' => $person->tenant_id,
                    'job_title' => $data['job_title'] ?? 'Employee',
                ]);
        }
    }

    private function generateUsername($person)
    {
        $base = strtolower(explode(' ', $person->full_name)[0]) . $person->id;
        return $base;
    }
}
