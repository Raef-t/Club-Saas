<?php

namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "StorePlayerUnavailabilityRequest",
    required: ["day_of_week", "start_time", "end_time"],
    properties: [
        new OA\Property(property: "day_of_week", type: "integer", description: "0 for Sunday, 6 for Saturday", example: 1),
        new OA\Property(property: "start_time", type: "string", format: "time", example: "09:00:00"),
        new OA\Property(property: "end_time", type: "string", format: "time", example: "12:00:00"),
    ]
)]
class StorePlayerUnavailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i|before:end_time',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ];
    }
}
