<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UpdateFacilityRequest",
    properties: [
        new OA\Property(property: "name", type: "string", example: "الصالة المحدثة"),
        new OA\Property(property: "capacity", type: "integer"),
        new OA\Property(property: "gender_restriction", type: "string", enum: ["male", "female", "mixed"]),
        new OA\Property(property: "is_active", type: "boolean"),
    ]
)]
class UpdateFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'is_active' => 'nullable|boolean',
        ];
    }
}
