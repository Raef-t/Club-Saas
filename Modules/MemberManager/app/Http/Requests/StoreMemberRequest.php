<?php

namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "StoreMemberRequest",
    required: ["person_id", "branch_id", "member_number"],
    properties: [
        new OA\Property(property: "person_id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "member_number", type: "string", example: "MEM-2026-001"),
        new OA\Property(property: "membership_status", type: "string", enum: ["active", "frozen"], default: "active"),
        new OA\Property(property: "join_date", type: "string", format: "date", example: "2026-05-11"),
    ]
)]
class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Person Info (Required if person_id is null)
            'person_id' => 'nullable|exists:people,id',
            'full_name' => 'required_without:person_id|string|max:255',
            'mobile_1' => 'required_without:person_id|string|max:20',
            'gender' => 'required_without:person_id|in:male,female',
            'dob' => 'nullable|date',

            // Member Info
            'branch_id' => 'required|exists:branches,id',
            'member_number' => 'nullable|string|unique:members,member_number',
            'membership_status' => 'nullable|string|in:active,inactive,frozen,expired',
            'join_date' => 'nullable|date',

            // Health Info (Optional)
            'health_profile' => 'nullable|array',
            'health_profile.blood_type' => 'nullable|string|max:5',
            'health_profile.organic_diseases' => 'nullable|string',
            'health_profile.physical_injuries' => 'nullable|string',
            'health_profile.emergency_contact_name' => 'nullable|string|max:100',
            'health_profile.emergency_contact_phone' => 'nullable|string|max:20',
        ];
    }
}
