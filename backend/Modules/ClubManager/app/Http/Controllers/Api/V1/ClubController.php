<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Services\ClubService;
use Modules\ClubManager\Http\Requests\StoreClubRequest;
use Modules\ClubManager\Http\Requests\UpdateClubRequest;
use Modules\ClubManager\Http\Requests\UpdateClubLogoRequest;
use Modules\ClubManager\Http\Resources\ClubResource;
use OpenApi\Attributes as OA;

class ClubController extends BaseController
{
    protected $service;

    public function __construct(ClubService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/clubs',
        summary: '🏢 عرض جميع الأندية',
        description: 'استرجاع قائمة بجميع الأندية المسجلة في النظام مع شعاراتها وحالاتها.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة الأندية',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال الذهبي'),
                    new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'storage/clubs/logos/sample.png'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request)
    {
        return $this->successResponse(ClubResource::collection($this->service->getAll($request->all())), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/clubs',
        summary: '➕ إنشاء نادي جديد',
        description: 'إضافة نادي جديد إلى النظام مع إمكانية رفع صورة الشعار (الحد الأقصى 2MB).',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: 'اسم النادي', example: 'نادي الأبطال الذهبي'),
                    new OA\Property(
                        property: 'logo',
                        type: 'string',
                        format: 'binary',
                        description: 'صورة الشعار (jpeg, png, jpg, webp, svg) - الحد الأقصى 2 ميجابايت (2MB)',
                        nullable: true
                    ),
                    new OA\Property(property: 'logo_url', type: 'string', description: 'رابط مباشر للشعار (اختياري في حال عدم رفع ملف)', nullable: true)
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء النادي بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال الذهبي'),
                    new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'storage/clubs/logos/sample.png'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z')
                ])
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات (مثل تجاوز حجم الصورة 2MB أو صيغة غير مدعومة)', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreClubRequest $request)
    {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new ClubResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/clubs/{id}',
        summary: '🔍 تفاصيل النادي',
        description: 'استرجاع جميع تفاصيل نادي محدد عن طريق المعرف بما فيها الشعار والحالة.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل النادي',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال الذهبي'),
                    new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'storage/clubs/logos/sample.png'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z')
                ])
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على النادي', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        return $this->successResponse(new ClubResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/clubs/{id}',
        summary: '📝 تعديل بيانات النادي',
        description: 'تعديل البيانات الأساسية للنادي (الاسم وحالة التفعيل). لتحديث الشعار استخدم المسار المنفصل: POST /v1/clubs/{id}/logo',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'اسم النادي', example: 'نادي الأبطال الماسي', nullable: true),
                new OA\Property(property: 'is_active', type: 'boolean', description: 'حالة تفعيل النادي', example: true, nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال الماسي'),
                    new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'storage/clubs/logos/sample.png'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z')
                ])
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على النادي', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateClubRequest $request, $id)
    {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new ClubResource($record), 'Updated successfully');
    }

    #[OA\Post(
        path: '/v1/clubs/{id}/logo',
        summary: '🖼️ تحديث شعار النادي (مسار منفصل)',
        description: 'مسار مخصص ومستقل لرفع وتحديث شعار النادي بصيغة multipart/form-data. يتم حذف الشعار القديم واستبداله بالشعار الجديد (الحد الأقصى 2MB).',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['logo'],
                properties: [
                    new OA\Property(
                        property: 'logo',
                        type: 'string',
                        format: 'binary',
                        description: 'ملف صورة الشعار (jpeg, png, jpg, webp, svg) - الحد الأقصى 2 ميجابايت (2MB)'
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الشعار بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Club logo updated successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال الذهبي'),
                    new OA\Property(property: 'logo_url', type: 'string', nullable: true, example: 'storage/clubs/logos/new_logo.png'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-08-18T10:00:00.000000Z')
                ])
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على النادي', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صورة الشعار (مثل تجاوز 2MB أو نوع ملف غير صالح)', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The logo field must be an image.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function updateLogo(UpdateClubLogoRequest $request, $id)
    {
        $club = $this->service->updateClubLogo($id, $request->file('logo'));
        return $this->successResponse(new ClubResource($club), __('Club logo updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/clubs/{id}',
        summary: '🗑️ حذف النادي (Soft Delete)',
        description: 'حذف النادي بالكامل من النظام مع كافة الفروع والمشتركين والمدربين التابعين له. يتطلب إرسال كلمة التأكيد "delete".',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
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
        $confirmation = $request->input('confirmation', '');
        $this->service->delete((int) $id, (string) $confirmation);
        return $this->successResponse(null, __('Deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/clubs/trashed',
        summary: '🗑️ عرض الأندية المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بالأندية التي تم حذفها.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم جلب الأندية المحذوفة بنجاح')]
    public function trashed(Request $request)
    {
        $clubs = $this->service->getTrashed($request->all());
        return $this->successResponse(ClubResource::collection($clubs), __('Trashed clubs retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/clubs/{id}/restore',
        summary: '♻️ استرجاع نادي محذوف',
        description: 'استرجاع النادي وكافة الفروع والمشتركين التابعين له من سلة المهملات.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع النادي بنجاح')]
    public function restore($id)
    {
        $club = $this->service->restoreClub($id);
        return $this->successResponse(new ClubResource($club), __('Club restored successfully'));
    }
    
}
