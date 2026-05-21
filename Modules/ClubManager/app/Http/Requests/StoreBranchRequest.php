<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "StoreBranchRequest",
    required: ["name"],
    properties: [
        new OA\Property(property: "name", type: "object", example: '{"ar": "فرع دبي", "en": "Dubai Branch"}'),
        new OA\Property(property: "gender_restriction", type: "string", enum: ["male", "female", "mixed"], example: "mixed"),
        new OA\Property(property: "address", type: "string", example: "Street 10, Dubai"),
        new OA\Property(property: "phone", type: "string", example: "0501234567"),
    ]
)]
class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ];
    }
}
