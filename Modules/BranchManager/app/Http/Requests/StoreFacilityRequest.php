<?php

namespace Modules\BranchManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "StoreFacilityRequest",
    required: ["branch_id", "name"],
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "object", example: '{"ar": "صالة الملاكمة", "en": "Boxing Hall"}'),
        new OA\Property(property: "capacity", type: "integer", example: 20),
        new OA\Property(property: "gender_restriction", type: "string", enum: ["male", "female", "mixed"], example: "male"),
    ]
)]
class StoreFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'gender_restriction' => 'nullable|in:male,female,mixed',
        ];
    }
}
