<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UpdateBranchRequest",
    properties: [
        new OA\Property(property: "club_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "فرع دبي المحدث"),
        new OA\Property(property: "gender_restriction", type: "string", enum: ["male", "female", "mixed"]),
        new OA\Property(property: "type", type: "string", example: "gym", description: "e.g. gym, pool, classroom"),
        new OA\Property(property: "address", type: "string"),
        new OA\Property(property: "country_code", type: "string", example: "+963"),
        new OA\Property(property: "phone", type: "string"),
    ]
)]
class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'club_id' => 'nullable|exists:clubs,id',
            'name' => 'nullable|string|max:255',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'type' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'country_code' => 'nullable|string|max:5',
            'phone' => 'nullable|string|max:20',
        ];
    }
}
