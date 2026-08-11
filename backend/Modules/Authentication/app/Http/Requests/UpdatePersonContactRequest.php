<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdatePersonContactRequest",
    title: "Update Person Contact Request",
    properties: [
        new OA\Property(property: "person_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "country_code", type: "string", example: "+966"),
        new OA\Property(property: "phone_number", type: "string", example: "500000000"),
        new OA\Property(property: "relation", type: "string", example: "Father", nullable: true)
    ]
)]
class UpdatePersonContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => 'sometimes|exists:people,id',
            'name' => 'sometimes|string|max:255',
            'country_code' => 'sometimes|string|max:10',
            'phone_number' => 'sometimes|string|max:50',
            'relation' => 'nullable|string|max:255',
        ];
    }
}
