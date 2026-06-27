<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreCounterpartyRequest;
use Modules\Accounting\Http\Requests\UpdateCounterpartyRequest;
use Modules\Accounting\Http\Resources\AccCounterpartyResource;
use Modules\Accounting\Models\AccCounterparty;
use Modules\Shared\Traits\SuccessResponseTrait;

use OpenApi\Attributes as OA;

class CounterpartyController extends Controller
{
    use SuccessResponseTrait;

    #[OA\Get(
        path: '/accounting/counterparties',
        summary: '🗂️ عرض الأطراف (العملاء / الموردين / الموظفين)',
        description: 'يسترجع قائمة بكافة الأطراف الخارجية والداخلية المسجلين بالنظام مع إمكانية التصفية حسب نوع الطرف (عميل customer، مورد vendor، موظف employee، آخر other) أو حسب الكيان المرجعي المرتبط (Polymorphic Reference).',
        tags: ['Accounting - الأطراف والعملاء والموردين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'type', in: 'query', required: false, description: 'نوع الطرف للتصفية', schema: new OA\Schema(type: 'string', enum: ['customer', 'vendor', 'employee', 'other']))]
    #[OA\Parameter(name: 'reference_type', in: 'query', required: false, description: 'نوع الكيان الخارجي المرجعي (Player, Staff)', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'reference_id', in: 'query', required: false, description: 'معرف الكيان الخارجي المرجعي', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب قائمة الأطراف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب الأطراف'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AccCounterparty'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function index(Request $request)
    {
        try {
            $query = AccCounterparty::query();
            if ($request->has('type'))         $query->where('type', $request->type);
            if ($request->has('reference_type') && $request->has('reference_id')) {
                $query->byReference($request->reference_type, $request->reference_id);
            }
            $counterparties = $query->orderBy('name')->get();
            return $this->successResponse(AccCounterpartyResource::collection($counterparties), 'تم جلب الأطراف');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: '/accounting/counterparties',
        summary: '➕ إضافة طرف جديد للنظام',
        description: 'ينشئ طرفاً جديداً (عميل، مورد، موظف) ويربطه اختيارياً بحساب ذمم مدينة/دائنة في دليل الحسابات لضمان تسجيل القيود المحاسبية عليه بشكل آلي ودقيق.',
        tags: ['Accounting - الأطراف والعملاء والموردين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات إنشاء الطرف الجديد',
        content: new OA\JsonContent(
            required: ['name', 'type'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'اسم الطرف الكامل أو اسم الشركة للشركات الموردة', example: 'شركة مياه نقي للتوريدات'),
                new OA\Property(property: 'type', type: 'string', enum: ['customer', 'vendor', 'employee', 'other'], description: 'تصنيف نوع الكيان المالي', example: 'vendor'),
                new OA\Property(property: 'ar_account_id', type: 'integer', description: 'معرف الحساب المحاسبي للذمم المرتبط بالطرف في الدليل', nullable: true, example: 14),
                new OA\Property(property: 'country_code', type: 'string', description: 'رمز الدولة', nullable: true, example: '+963'),
                new OA\Property(property: 'phone', type: 'string', description: 'رقم هاتف الطرف', nullable: true, example: '0509998887'),
                new OA\Property(property: 'email', type: 'string', format: 'email', description: 'البريد الإلكتروني للطرف', nullable: true, example: 'sales@naqiwater.com'),
                new OA\Property(property: 'reference_type', type: 'string', description: 'نوع الموديل الخارجي المرتبط إن وجد (مثل Player)', nullable: true, example: null),
                new OA\Property(property: 'reference_id', type: 'integer', description: 'معرف السجل في جدول الموديل الخارجي', nullable: true, example: null),
                new OA\Property(property: 'notes', type: 'string', description: 'ملاحظات وتفاصيل إضافية حول الطرف', nullable: true, example: 'مورد مياه رسمي لصالات النادي الرياضية')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الطرف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إضافة الطرف بنجاح'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccCounterparty')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الحساب أو البيانات المحددة غير صالحة')]
    public function store(StoreCounterpartyRequest $request)
    {
        try {
            $cp = AccCounterparty::create($request->validated());
            return $this->successResponse(new AccCounterpartyResource($cp), 'تم إضافة الطرف بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: '/accounting/counterparties/{id}',
        summary: '🔍 تفاصيل طرف معين',
        description: 'يسترجع تفاصيل الطرف المالية والشخصية بواسطة معرفه الفريد (ID)، مع توضيح حسابه المرتبط ورقم هاتفه وإيميله وحالته.',
        tags: ['Accounting - الأطراف والعملاء والموردين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الطرف الفريد (ID)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب تفاصيل الطرف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم جلب بيانات الطرف'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccCounterparty')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الطرف المحدد غير موجود')]
    public function show($id)
    {
        try {
            $cp = AccCounterparty::findOrFail($id);
            return $this->successResponse(new AccCounterpartyResource($cp), 'تم جلب بيانات الطرف');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    #[OA\Put(
        path: '/accounting/counterparties/{id}',
        summary: '📝 تحديث بيانات طرف',
        description: 'يسمح بتعديل بيانات الطرف مثل الاسم، التلفون، الحساب المرتبط، والبريد الإلكتروني لتغيير تفاصيل التواصل أو التعديلات المحاسبية.',
        tags: ['Accounting - الأطراف والعملاء والموردين'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الطرف الفريد (ID)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'الاسم الجديد للطرف', example: 'شركة مياه نقي للتوريدات المحدودة'),
                new OA\Property(property: 'type', type: 'string', enum: ['customer', 'vendor', 'employee', 'other'], description: 'نوع الكيان المالي', example: 'vendor'),
                new OA\Property(property: 'ar_account_id', type: 'integer', description: 'معرف حساب الذمم المرتبط بالطرف', nullable: true, example: 14),
                new OA\Property(property: 'country_code', type: 'string', description: 'رمز الدولة', nullable: true, example: '+963'),
                new OA\Property(property: 'phone', type: 'string', description: 'رقم هاتف التواصل الجديد', nullable: true, example: '0509998887'),
                new OA\Property(property: 'email', type: 'string', format: 'email', description: 'البريد الإلكتروني الجديد للطرف', nullable: true, example: 'info@naqiwater.com'),
                new OA\Property(property: 'reference_type', type: 'string', description: 'نوع الموديل الخارجي المرتبط إن وجد', nullable: true, example: null),
                new OA\Property(property: 'reference_id', type: 'integer', description: 'معرف السجل الخارجي', nullable: true, example: null),
                new OA\Property(property: 'notes', type: 'string', description: 'ملاحظات إضافية حول التعديل', nullable: true, example: 'تم تعديل اسم الشركة بناءً على السجل التجاري الجديد')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الطرف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم تحديث بيانات الطرف'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AccCounterparty')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الطرف المحدد غير موجود')]
    public function update(UpdateCounterpartyRequest $request, $id)
    {
        try {
            $cp = AccCounterparty::findOrFail($id);
            $cp->update($request->validated());
            return $this->successResponse(new AccCounterpartyResource($cp), 'تم تحديث بيانات الطرف');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
