<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Modules\Authentication\Http\Requests\RegisterPersonRequest;
use Modules\Authentication\Http\Resources\PersonResource;
use Modules\Authentication\Services\PeopleService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class PeopleController extends BaseController
{
    protected $peopleService;

    public function __construct(PeopleService $peopleService)
    {
        $this->peopleService = $peopleService;
    }

    #[OA\Post(
        path: '/v1/people',
        summary: '👥 Register Person (Identity Only)',
        description: "## 📘 نظرة عامة\nهذا المسار مخصص لتسجيل الهوية الأساسية للأشخاص في النظام (لاعبين، مدربين، موظفين).\n\n### 🛡 المعمارية الجديدة:\nوظيفته تقتصر على إنشاء سجل الشخص (`Person`) فقط. العمليات التشغيلية اللاحقة (مثل إضافة بيانات طبية أو تعيين فرع) تتم عبر الموديولات المخصصة لها مثل `MemberManager`.",
        tags: ['People Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'X-Tenant-ID',
        in: 'header',
        description: 'المعرف الفريد للنادي.',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'البيانات الشخصية الأساسية.',
        content: new OA\JsonContent(
            required: ['full_name', 'type', 'mobile_1'],
            properties: [
                new OA\Property(property: 'full_name', type: 'string', example: 'Mohamed Ahmed'),
                new OA\Property(property: 'type', type: 'string', enum: ['player', 'coach', 'staff'], example: 'player'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', example: '2005-06-15'),
                new OA\Property(property: 'mobile_1', type: 'string', example: '0512345678'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Person registered successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Person')
            ]
        )
    )]
    public function store(RegisterPersonRequest $request)
    {
        $person = $this->peopleService->register($request->validated());

        return $this->successResponse(
            new PersonResource($person),
            __('Person registered successfully and user account created'),
            201
        );
    }
}
