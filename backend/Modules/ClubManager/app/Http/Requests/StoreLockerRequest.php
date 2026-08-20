<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "StoreLockerRequest",
    required: ["branch_id", "locker_number"],
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, description: "معرف الفرع التابعة له الخزانة"),
        new OA\Property(property: "locker_number", type: "string", example: "L-101", description: "رقم الخزانة الفريد ضمن الفرع"),
        new OA\Property(property: "key_number", type: "string", nullable: true, example: "K-101", description: "رقم المفتاح الفريد ضمن الفرع (اختياري)"),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["available", "with_member", "with_staff", "with_coach", "maintenance"],
            description: "حالة الخزانة عند الإنشاء (افتراضياً: available):\n" .
                         "• `available`: متاحة وفارغة وجاهزة للاستخدام\n" .
                         "• `maintenance`: معطلة أو قيد الصيانة (خارج الخدمة)\n" .
                         "• `with_member`: مسندة لمشترك / لاعب\n" .
                         "• `with_coach`: مسندة لمدرب\n" .
                         "• `with_staff`: مسندة لموظف",
            example: "available"
        ),
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
            'branch_id'     => 'required|exists:branches,id',
            'locker_number' => 'required|string|max:50',
            'key_number'    => 'nullable|string|max:50',
            'status'        => 'sometimes|in:available,with_member,with_staff,with_coach,maintenance',
        ];
    }
}
