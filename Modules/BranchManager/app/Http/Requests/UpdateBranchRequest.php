<?php

namespace Modules\BranchManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UpdateBranchRequest",
    properties: [
        new OA\Property(property: "name", type: "object", example: '{"ar": "فرع دبي المحدث", "en": "Updated Dubai Branch"}'),
        new OA\Property(property: "gender_restriction", type: "string", enum: ["male", "female", "mixed"]),
        new OA\Property(property: "address", type: "string"),
        new OA\Property(property: "phone", type: "string"),
        new OA\Property(property: "is_active", type: "boolean"),
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
            'name' => 'nullable|array',
            'name.ar' => 'nullable|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ];
    }
}
