<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Validates requests to update who currently holds a locker key.
 */
#[OA\Schema(
    title: "UpdateLockerHolderRequest",
    description: "البيانات المطلوبة لتحديث حامل مفتاح الخزانة",
    required: ["holder_type", "holder_name"],
    properties: [
        new OA\Property(
            property: "holder_type", 
            type: "string", 
            enum: ["member", "staff", "guest"], 
            example: "staff",
            description: "نوع الشخص الذي يحمل المفتاح. الأنواع المسموحة:\n- `member`: عضو مسجل بالنادي (يتطلب `holder_id`)\n- `staff`: موظف أو كوتش (يتطلب `holder_id`)\n- `guest`: زائر خارجي (لا يتطلب `holder_id`، يكفي الاسم)"
        ),
        new OA\Property(property: "holder_id", type: "integer", nullable: true, example: 7, description: "معرف العضو أو الموظف في قاعدة البيانات (يترك فارغاً للزوار)"),
        new OA\Property(property: "holder_name", type: "string", example: "المدرب خالد", description: "اسم الحامل الفعلي للمفتاح (يستخدم لعرضه في الواجهة)")
    ]
)]
class UpdateLockerHolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'holder_type' => ['required', 'string', 'in:member,staff,guest'],
            // holder_id is required for member and staff, ignored for guest
            'holder_id'   => ['required_unless:holder_type,guest', 'nullable', 'integer'],
            'holder_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'holder_type.required'        => 'يجب تحديد نوع الحامل (member، staff، أو guest).',
            'holder_type.in'              => 'نوع الحامل يجب أن يكون member أو staff أو guest.',
            'holder_id.required_unless'   => 'معرّف الشخص مطلوب عند اختيار member أو staff.',
            'holder_name.required'        => 'يجب إدخال اسم الحامل.',
        ];
    }
}
