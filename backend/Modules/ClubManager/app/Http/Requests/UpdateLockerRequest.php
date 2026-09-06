<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ClubManager\Models\Locker;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UpdateLockerRequest",
    properties: [
        new OA\Property(property: "reason", type: "string", nullable: true, description: "سبب التعديل (اختياري)", example: "تغيير المفتاح التالف وتحديث الحالة"),
        new OA\Property(property: "locker_number", type: "string", example: "L-101"),
        new OA\Property(property: "key_number", type: "string", nullable: true, example: "K-101"),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["available", "with_member", "with_staff", "with_coach", "maintenance"],
            description: "حالة الخزانة: available (متاحة), with_member (مع لاعب), with_staff (مع موظف), with_coach (مع مدرب), maintenance (صيانة / معطلة)",
            example: "available"
        ),
        new OA\Property(property: "holder_id", type: "integer", nullable: true, example: 5),
        new OA\Property(property: "holder_type", type: "string", nullable: true, enum: ["member", "staff", "coach"], example: "member"),
        new OA\Property(property: "holder_name", type: "string", nullable: true, example: "أحمد محمد"),
    ]
)]
class UpdateLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lockerParam = $this->route('locker') ?? $this->route('id');
        $lockerId = is_object($lockerParam) ? $lockerParam->id : $lockerParam;

        $branchId = $this->branch_id;
        if (!$branchId && $lockerId) {
            $branchId = Locker::where('id', $lockerId)->value('branch_id');
        }

        return [
            'reason'        => ['nullable', 'string', 'max:500'],
            'locker_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('lockers', 'locker_number')
                    ->where(fn ($query) => $query->where('branch_id', $branchId)->whereNull('deleted_at'))
                    ->ignore($lockerId),
            ],
            'key_number'    => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('lockers', 'key_number')
                    ->where(fn ($query) => $query->where('branch_id', $branchId)->whereNull('deleted_at'))
                    ->ignore($lockerId),
            ],
            'status'        => 'sometimes|in:available,with_member,with_staff,with_coach,maintenance',
            'holder_id'     => 'nullable|integer',
            'holder_type'   => 'nullable|in:member,staff,coach',
            'holder_name'   => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'locker_number.unique' => __('Locker number :num already exists in this branch.', ['num' => $this->locker_number]),
            'key_number.unique'    => __('Key number :num already exists in this branch.', ['num' => $this->key_number]),
        ];
    }
}
