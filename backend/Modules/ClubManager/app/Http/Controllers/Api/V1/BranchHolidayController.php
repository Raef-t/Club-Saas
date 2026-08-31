<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Models\BranchHoliday;
use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\StoreBranchHolidayRequest;
use Modules\ClubManager\Http\Requests\UpdateBranchHolidayRequest;
use Modules\ClubManager\Http\Resources\BranchHolidayResource;
use OpenApi\Attributes as OA;

class BranchHolidayController extends BaseController
{
    #[OA\Get(
        path: '/v1/branches/{branch}/holidays',
        summary: '🗓️ عرض عطلات الفرع',
        description: 'استرجاع قائمة بجميع أيام العطل الخاصة بفرع محدد.',
        tags: ['Branch Holidays'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع العطلات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Holidays retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BranchHolidayResource'))
            ]
        )
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (الافتراضي: 15)', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function index(\Illuminate\Http\Request $request, $branchId)
    {
        $perPage = $this->getPerPage($request);
        $holidays = BranchHoliday::where('branch_id', $branchId)->paginate($perPage);
        return $this->successResponse(
            BranchHolidayResource::collection($holidays),
            __('Holidays retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/branches/{branch}/holidays',
        summary: '➕ إضافة عطلة جديدة',
        description: 'إضافة يوم عطلة جديد لفرع محدد.',
        tags: ['Branch Holidays'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['type'],
            properties: [
                new OA\Property(property: 'type', type: 'string', description: 'نوع العطلة: weekly, specific_dates'),
                new OA\Property(property: 'day_of_week', type: 'integer', description: 'يوم العطلة (0=Sunday, 6=Saturday) في حال كان النوع weekly', nullable: true),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'تاريخ البداية (في حال specific_dates)', nullable: true),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'تاريخ النهاية (في حال specific_dates)', nullable: true),
                new OA\Property(property: 'reason', type: 'string', description: 'السبب (في حال specific_dates)', nullable: true)
            ],
            examples: [
                new OA\Examples(
                    example: 'Weekly Holiday',
                    summary: 'عطلة أسبوعية متكررة (مثال: كل جمعة)',
                    value: ['type' => 'weekly', 'day_of_week' => 5]
                ),
                new OA\Examples(
                    example: 'Specific Dates Holiday',
                    summary: 'عطلة في تواريخ محددة',
                    value: ['type' => 'specific_dates', 'start_date' => '2026-08-10', 'end_date' => '2026-08-15']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إضافة العطلة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Holiday created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BranchHolidayResource')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function store(StoreBranchHolidayRequest $request, $branchId)
    {
        $holiday = BranchHoliday::create([
            'branch_id' => $branchId,
            'type' => $request->type,
            'day_of_week' => $request->type === 'weekly' ? $request->day_of_week : null,
            'start_date' => $request->type === 'specific_dates' ? $request->start_date : null,
            'end_date' => $request->type === 'specific_dates' ? $request->end_date : null,
            'reason' => $request->type === 'specific_dates' ? $request->reason : null,
        ]);

        return $this->successResponse(
            new BranchHolidayResource($holiday),
            __('Holiday created successfully'),
            201
        );
    }

    #[OA\Get(
        path: '/v1/holidays/{holiday}',
        summary: '🔍 تفاصيل العطلة',
        description: 'استرجاع تفاصيل عطلة محددة.',
        tags: ['Branch Holidays'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'holiday', in: 'path', required: true, description: 'معرف العطلة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع العطلة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Holiday retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BranchHolidayResource')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العطلة')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function show(BranchHoliday $holiday)
    {
        return $this->successResponse(
            new BranchHolidayResource($holiday),
            __('Holiday retrieved successfully')
        );
    }

    #[OA\Put(
        path: '/v1/holidays/{holiday}',
        summary: '📝 تعديل العطلة',
        description: 'تعديل بيانات عطلة مسجلة.',
        tags: ['Branch Holidays'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'holiday', in: 'path', required: true, description: 'معرف العطلة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'type', type: 'string', description: 'نوع العطلة: weekly, specific_dates'),
                new OA\Property(property: 'day_of_week', type: 'integer', description: 'يوم العطلة', nullable: true),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'تاريخ البداية', nullable: true),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'تاريخ النهاية', nullable: true),
                new OA\Property(property: 'reason', type: 'string', description: 'السبب', nullable: true)
            ],
            examples: [
                new OA\Examples(
                    example: 'Update to Weekly Holiday',
                    summary: 'تحويل العطلة إلى أسبوعية متكررة',
                    value: ['type' => 'weekly', 'day_of_week' => 5]
                ),
                new OA\Examples(
                    example: 'Update to Specific Dates',
                    summary: 'تحويل العطلة إلى تواريخ محددة',
                    value: ['type' => 'specific_dates', 'start_date' => '2026-08-10', 'end_date' => '2026-08-12']
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Holiday updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BranchHolidayResource')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في البيانات')]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العطلة')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function update(UpdateBranchHolidayRequest $request, BranchHoliday $holiday)
    {
        $data = $request->validated();
        if (isset($data['type'])) {
            if ($data['type'] === 'weekly') {
                $data['start_date'] = null;
                $data['end_date'] = null;
                $data['reason'] = null;
            } else {
                $data['day_of_week'] = null;
            }
        }
        $holiday->update($data);

        return $this->successResponse(
            new BranchHolidayResource($holiday),
            __('Holiday updated successfully')
        );
    }

    #[OA\Delete(
        path: '/v1/holidays/{holiday}',
        summary: '🗑️ حذف العطلة (Soft Delete)',
        description: 'إزالة عطلة من النظام. يتطلب إرسال كلمة التأكيد "delete" اختيارياً.',
        tags: ['Branch Holidays'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'holiday', in: 'path', required: true, description: 'معرف العطلة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'confirmation', type: 'string', description: 'تأكيد الحذف (delete)', example: '')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح')]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"')]
    public function destroy(Request $request, $id)
    {
        $confirm = strtolower(trim($request->input('confirm') ?? $request->input('confirmation') ?? $request->input('confirm_text') ?? ''));

        if ($confirm !== 'delete') {
            return $this->errorResponse(
                __('سيتم حذف هذه العطلة، هل أنت متأكد؟ أرسل "delete" للتأكيد.'),
                422
            );
        }

        $holiday = BranchHoliday::findOrFail($id);
        $holiday->delete();
        return $this->successResponse(null, __('Holiday deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/holidays/trashed',
        summary: '🗑️ عرض العطلات المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بأيام العطل المحذوفة.',
        tags: ['Branch Holidays'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (الافتراضي: 15)', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم جلب العطلات المحذوفة بنجاح')]
    public function trashed(Request $request)
    {
        $perPage = $this->getPerPage($request);
        $holidays = BranchHoliday::onlyTrashed()->paginate($perPage);
        return $this->successResponse(BranchHolidayResource::collection($holidays), __('Trashed holidays retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/holidays/{id}/restore',
        summary: '♻️ استرجاع عطلة محذوفة',
        description: 'استرجاع العطلة من سلة المهملات وإعادة تفعيلها.',
        tags: ['Branch Holidays'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف العطلة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع العطلة بنجاح')]
    public function restore($id)
    {
        $holiday = BranchHoliday::onlyTrashed()->findOrFail($id);
        $holiday->restore();
        return $this->successResponse(new BranchHolidayResource($holiday), __('Holiday restored successfully'));
    }
}
