<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "StoreLockerRequest",
    required: ["branch_id", "facility_id", "locker_number"],
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1),
        new OA\Property(property: "facility_id", type: "integer", example: 1),
        new OA\Property(property: "locker_number", type: "string", example: "L-101"),
    ]
)]
class StoreLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'facility_id' => 'required|exists:facilities,id',
            'locker_number' => 'required|string|max:50',
        ];
    }
}
