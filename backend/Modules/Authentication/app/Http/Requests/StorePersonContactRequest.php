<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "StorePersonContactRequest",
    title: "Store Person Contact Request",
    required: ["person_id", "name", "country_code", "phone_number"],
    properties: [
        new OA\Property(property: "person_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "country_code", type: "string", example: "+966"),
        new OA\Property(property: "phone_number", type: "string", example: "500000000"),
        new OA\Property(property: "relation", type: "string", example: "Father", nullable: true)
    ]
)]
class StorePersonContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => 'required|exists:people,id',
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:50',
            'relation' => 'nullable|string|max:255',
        ];
    }
}
