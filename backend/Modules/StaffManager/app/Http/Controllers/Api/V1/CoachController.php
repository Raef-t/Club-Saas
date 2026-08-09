<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StaffManager\Services\CoachService;
use Modules\StaffManager\Http\Requests\StoreCoachRequest;
use Modules\StaffManager\Http\Requests\UpdateCoachBasicInfoRequest;
use Modules\StaffManager\Http\Requests\UpdateCoachDetailsRequest;
use Modules\StaffManager\Http\Requests\AssignCoachActivitiesRequest;
use Modules\StaffManager\Http\Requests\UploadCoachCertificationRequest;
use Modules\StaffManager\Http\Resources\CoachResource;
use Modules\StaffManager\Http\Resources\StaffResource;
use Modules\StaffManager\Http\Resources\StaffShiftResource;
use Modules\StaffManager\Services\StaffService;
use Modules\StaffManager\Services\StaffShiftService;
use Modules\StaffManager\Http\Requests\SetStaffScheduleRequest;
use OpenApi\Attributes as OA;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

#[OA\Tag(name: 'Coach Management', description: 'API Endpoints for managing coaches')]
class CoachController extends Controller
{
    protected CoachService $coachService;
    protected StaffService $staffService;
    protected StaffShiftService $staffShiftService;

    public function __construct(
        CoachService $coachService,
        StaffService $staffService,
        StaffShiftService $staffShiftService
    ) {
        $this->coachService = $coachService;
        $this->staffService = $staffService;
        $this->staffShiftService = $staffShiftService;
    }

    #[OA\Post(
        path: '/v1/coaches',
        summary: 'Create a new coach',
        description: 'Creates a staff record (role = coach), user, and coach details automatically. Auto-generates username and qr_code.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['first_name', 'last_name', 'branch_ids[]'],
                    properties: [
                        new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
                        new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
                        new OA\Property(property: 'gender', type: 'string', example: 'male'),
                        new OA\Property(property: 'age', type: 'integer', example: 30),
                        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1990-01-01'),
                        new OA\Property(property: 'phone_number', type: 'string', example: '500000000'),
                        new OA\Property(property: 'country_code', type: 'string', example: '+966'),
                        new OA\Property(property: 'national_id', type: 'string', description: 'الرقم الوطني', example: '1234567890', nullable: true),
                        new OA\Property(property: 'address', type: 'string', description: 'العنوان', example: 'شارع الملك فهد، الرياض', nullable: true),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة المدرب', nullable: true),
                        new OA\Property(property: 'branch_ids[]', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                        new OA\Property(property: 'employment_type', description: 'نوع التوظيف', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'fixed_salary'),
                        new OA\Property(property: 'base_salary', type: 'number', example: 5000),
                        new OA\Property(property: 'default_commission_rate', type: 'number', format: 'float', description: 'نسبة العمولة الثابتة للمدرب (مئوية)', example: 20.5),
                        new OA\Property(property: 'work_types[]', type: 'array', items: new OA\Items(type: 'string', enum: ['equipment', 'activities'], example: 'equipment'), description: 'أنواع عمل المدرب (أجهزة: equipment، فعاليات/حصص: activities)'),
                        new OA\Property(property: 'experience_years', type: 'integer', example: 5),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-16'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'activity_ids[]', type: 'array', items: new OA\Items(type: 'integer', example: 1), description: 'مصفوفة معرفات الأنشطة (اختياري)'),
                        new OA\Property(property: 'shifts[]', type: 'array', items: new OA\Items(type: 'integer', example: 1), description: 'مصفوفة معرفات الشفتات (اختياري - مسموح فقط إذا كان النشاط تدريب جماعي أو خاص)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: 'Coach created successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CoachResource'),
                    new OA\Property(property: 'message', type: 'string', example: 'Coach created successfully')
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 409, 
                description: 'Conflict - Data already exists',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Conflict occurred while creating coach. The data might already exist.')
                ])
            ),
            new OA\Response(
                response: 422, 
                description: 'Validation errors (e.g., branch gender restriction mismatch)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'لا يمكن إضافة هذا المدرب/ة في هذا الفرع بسبب قيود الجنس الخاصة بالفرع.'),
                    new OA\Property(property: 'errors', type: 'object')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while creating the coach.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function store(StoreCoachRequest $request)
    {
        try {
            $coach = $this->coachService->createCoach($request->validated());

            return response()->json([
                'data' => new CoachResource($coach),
                'message' => 'Coach created successfully'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Conflict occurred while creating coach. The data might already exist.'
            ], 409);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the coach.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/v1/coaches',
        summary: 'Get All Coaches',
        description: 'Get all coaches with optional filters.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'branch_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'activity_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gender', in: 'query', required: false, description: 'تصفية حسب الجنس', schema: new OA\Schema(type: 'string', enum: ['male', 'female', 'mixed'])),
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'List of coaches',
                content: new OA\JsonContent(properties: [
                    new OA\Property(
                        property: 'data', 
                        type: 'array', 
                        items: new OA\Items(ref: '#/components/schemas/CoachResource')
                    )
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while retrieving coaches.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['branch_id', 'activity_id', 'gender']);
            $coaches = $this->coachService->getAllCoaches($filters);

            return CoachResource::collection($coaches);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving coaches.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/v1/coaches/stats',
        summary: 'Get Coaches Statistics',
        description: 'Get statistics about coaches including total, active, and employment types.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'branch_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Coaches statistics',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'total_coaches', type: 'integer'),
                        new OA\Property(property: 'active_coaches', type: 'integer'),
                        new OA\Property(property: 'fixed_salary_coaches', type: 'integer'),
                        new OA\Property(property: 'commission_based_coaches', type: 'integer'),
                        new OA\Property(property: 'hybrid_coaches', type: 'integer'),
                    ])
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while retrieving statistics.')
                ])
            ),
        ]
    )]
    public function stats(Request $request)
    {
        try {
            $filters = $request->only(['branch_id']);
            $stats = $this->coachService->getStats($filters);

            return response()->json([
                'data' => $stats
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving statistics.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/v1/coaches/{id}',
        summary: 'Get Single Coach',
        description: 'Returns staff data, coach details, certifications, and assigned activities.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Coach details',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CoachResource')
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 404, 
                description: 'Coach not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while retrieving the coach.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function show($id)
    {
        try {
            $coach = $this->coachService->getSingleCoach($id);
            return new CoachResource($coach);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving the coach.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Patch(
        path: '/v1/coaches/{id}',
        summary: 'Update Coach Data',
        description: 'Update base salary, employment type, status, specialization, bio, experience, etc. لتحديث الصورة استخدم endpoint منفصل: POST /v1/coaches/{id}/photo',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
                        new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
                        new OA\Property(property: 'gender', type: 'string', example: 'male'),
                        new OA\Property(property: 'age', type: 'integer', example: 30),
                        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1990-01-01'),
                        new OA\Property(property: 'phone_number', type: 'string', example: '500000000'),
                        new OA\Property(property: 'country_code', type: 'string', example: '+966'),
                        new OA\Property(property: 'national_id', type: 'string', description: 'الرقم الوطني', example: '1234567890', nullable: true),
                        new OA\Property(property: 'address', type: 'string', description: 'العنوان', example: 'شارع الملك فهد، الرياض', nullable: true),
                        new OA\Property(property: 'branch_ids[]', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                        new OA\Property(property: 'employment_type', description: 'نوع التوظيف', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'hybrid'),
                        new OA\Property(property: 'base_salary', type: 'number', example: 6000),
                        new OA\Property(property: 'default_commission_rate', type: 'number', format: 'float', description: 'نسبة العمولة الثابتة للمدرب (مئوية)', example: 25.0),
                        new OA\Property(property: 'work_types[]', type: 'array', items: new OA\Items(type: 'string', enum: ['equipment', 'activities'], example: 'equipment'), description: 'أنواع عمل المدرب (أجهزة: equipment، فعاليات/حصص: activities)'),
                        new OA\Property(property: 'experience_years', type: 'integer', example: 7),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-16'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'activity_ids[]', type: 'array', items: new OA\Items(type: 'integer', example: 1), description: 'مصفوفة معرفات الأنشطة (اختياري)'),
                        new OA\Property(property: 'shifts[]', type: 'array', items: new OA\Items(type: 'integer', example: 1), description: 'مصفوفة معرفات الشفتات (اختياري - مسموح فقط إذا كان النشاط تدريب جماعي أو خاص)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Coach updated successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CoachResource'),
                    new OA\Property(property: 'message', type: 'string', example: 'Coach updated successfully')
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 404, 
                description: 'Coach not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')
                ])
            ),
            new OA\Response(
                response: 409, 
                description: 'Conflict',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Conflict occurred while updating coach.')
                ])
            ),
            new OA\Response(
                response: 422, 
                description: 'Validation errors',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                    new OA\Property(property: 'errors', type: 'object')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while updating the coach.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function update(\Modules\StaffManager\Http\Requests\UpdateCoachRequest $request, $id)
    {

        try {
            $coach = $this->coachService->updateCoach($id, $request->validated());

            return response()->json([
                'data' => new CoachResource($coach),
                'message' => 'Coach updated successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Conflict occurred while updating coach.'
            ], 409);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the coach.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/v1/coaches/{id}/photo',
        summary: 'Update Coach Photo',
        description: 'Upload or update the profile photo of a coach. Use this dedicated endpoint instead of sending the photo in the general update request.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['photo'],
                    properties: [
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة المدرب'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Photo updated successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CoachResource'),
                    new OA\Property(property: 'message', type: 'string', example: 'Coach photo updated successfully')
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')])),
            new OA\Response(response: 404, description: 'Coach not found', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')])),
            new OA\Response(response: 422, description: 'Validation errors', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'), new OA\Property(property: 'errors', type: 'object')])),
            new OA\Response(response: 500, description: 'Server Error', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'An error occurred while updating the photo.')])),
        ]
    )]
    public function updatePhoto(\Modules\StaffManager\Http\Requests\UpdateCoachPhotoRequest $request, $id)
    {
        try {
            $coach = $this->coachService->updateCoachPhoto($id, $request->file('photo'));

            return response()->json([
                'data'    => new CoachResource($coach),
                'message' => 'Coach photo updated successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the photo.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/v1/coaches/{id}/activities',
        summary: 'Assign Activities to Coach',
        description: 'Assign multiple activities to a coach.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['activity_ids'],
                properties: [
                    new OA\Property(
                        property: 'activity_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer', example: 1)
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Activities assigned successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/CoachResource'),
                    new OA\Property(property: 'message', type: 'string', example: 'Activities assigned successfully')
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 404, 
                description: 'Coach not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')
                ])
            ),
            new OA\Response(
                response: 409, 
                description: 'Conflict',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Conflict occurred.')
                ])
            ),
            new OA\Response(
                response: 422, 
                description: '❌ خطأ في التحقق - لا يمكن الربط بسبب عدم توافق طبيعة عمل المدرب مع نوع الفعالية',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'لا يمكن الربط بسبب عدم توافق طبيعة عمل المدرب مع نوع الفعالية.'),
                    new OA\Property(property: 'errors', type: 'object', example: ['activity_ids' => ['لا يمكن الربط بسبب عدم توافق طبيعة عمل المدرب مع نوع الفعالية.']])
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while assigning activities.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function assignActivities(AssignCoachActivitiesRequest $request, $id)
    {
        try {
            $validated = $request->validated();
            $this->coachService->assignActivities($id, $validated['activity_ids']);
            $coach = $this->coachService->getSingleCoach($id);

            return response()->json([
                'data' => new CoachResource($coach),
                'message' => 'Activities assigned successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while assigning activities.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/v1/coaches/{id}/activities/{activityId}',
        summary: 'Remove Activity from Coach',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'activityId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Activity removed successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Activity removed successfully')
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 404, 
                description: 'Coach or Activity not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while removing the activity.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function removeActivity($id, $activityId)
    {
        try {
            $this->coachService->removeActivity($id, $activityId);
            return response()->json([
                'message' => 'Activity removed successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while removing the activity.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/v1/coaches/{id}/certifications',
        summary: 'Upload Coach Certification',
        description: 'Upload a certification or provide a document URL for a coach.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'issuer', type: 'string'),
                        new OA\Property(property: 'issue_date', type: 'string', format: 'date'),
                        new OA\Property(property: 'expiry_date', type: 'string', format: 'date'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'document_url', type: 'string'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: 'Certification uploaded successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'object'),
                    new OA\Property(property: 'message', type: 'string', example: 'Certification uploaded successfully')
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Please provide either a file or a document_url')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 404, 
                description: 'Coach not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')
                ])
            ),
            new OA\Response(
                response: 422, 
                description: 'Validation errors',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                    new OA\Property(property: 'errors', type: 'object')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while uploading certification.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function uploadCertification(UploadCoachCertificationRequest $request, $id)
    {
        try {
            $validated = $request->validated();

            if (empty($validated['file']) && empty($validated['document_url'])) {
                return response()->json([
                    'message' => 'Please provide either a file or a document_url'
                ], 422);
            }

            $certification = $this->coachService->uploadCertification($id, $validated);

            return response()->json([
                'data' => $certification,
                'message' => 'Certification uploaded successfully'
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            // CoachService throws Exception for missing details
            if ($e->getMessage() === 'Coach details not found.' || $e->getMessage() === 'A document file or URL is required.') {
                return response()->json([
                    'message' => $e->getMessage()
                ], 400);
            }
            return response()->json([
                'message' => 'An error occurred while uploading certification.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/v1/coaches/{id}/certifications',
        summary: 'Get Coach Certifications',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'List of certifications',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'message', type: 'string', example: 'Certifications retrieved successfully')
                ])
            ),
            new OA\Response(
                response: 400, 
                description: 'Bad Request',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
                ])
            ),
            new OA\Response(
                response: 401, 
                description: 'Unauthenticated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                ])
            ),
            new OA\Response(
                response: 403, 
                description: 'Forbidden',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.')
                ])
            ),
            new OA\Response(
                response: 404, 
                description: 'Coach not found',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while retrieving certifications.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function getCertifications($id)
    {
        try {
            $coach = $this->coachService->getSingleCoach($id);
            $certifications = $coach->coachDetail ? $coach->coachDetail->certifications : [];

            return response()->json([
                'data' => $certifications,
                'message' => 'Certifications retrieved successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving certifications.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    #[OA\Post(
        path: '/v1/coaches/{id}/schedule',
        summary: 'Set Coach Schedule',
        description: 'Assigns a full schedule (multiple shifts) to a coach, overwriting existing ones.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['shifts'],
                properties: [
                    new OA\Property(property: 'shifts', type: 'array', items: new OA\Items(type: 'integer', example: 1), description: 'Array of branch_shift IDs')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Schedule updated successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))
        ]
    )]
    public function setSchedule(SetStaffScheduleRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $staff = $this->staffService->setStaffSchedule($id, $data['shifts']);
            return response()->json([
                'data' => new StaffResource($staff),
                'message' => 'Schedule updated successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coach not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/v1/coaches/{id}/shifts',
        summary: 'Add a single Shift to Coach',
        description: 'Assigns a single specific shift to a coach on a specific date.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['branch_shift_id', 'date'],
                properties: [
                    new OA\Property(property: 'branch_shift_id', type: 'integer', example: 1),
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2023-11-01')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: 'Shift created successfully', 
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(
                response: 422, 
                description: 'Validation errors (e.g., invalid branch_shift_id)', 
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'The selected branch shift id is invalid.'),
                    new OA\Property(property: 'errors', type: 'object', example: ['branch_shift_id' => ['The selected branch shift id is invalid.']])
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error', 
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            )
        ]
    )]
    public function addShift(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'branch_shift_id' => 'required|integer|exists:branch_shifts,id',
                'date' => 'required|date'
            ]);
            $data['staff_id'] = $id;
            
            $record = $this->staffShiftService->create($data);
            return response()->json([
                'data' => new StaffShiftResource($record),
                'message' => 'Shift created successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Put(
        path: '/v1/coaches/{id}/shifts/{shiftId}',
        summary: 'Update Coach Shift',
        description: 'Updates a specific assigned shift for a coach.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'shiftId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2023-11-02')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Shift updated successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))
        ]
    )]
    public function updateShift(Request $request, $id, $shiftId)
    {
        try {
            $data = $request->validate([
                'date' => 'sometimes|date',
                'branch_shift_id' => 'sometimes|integer'
            ]);
            $record = $this->staffShiftService->update($shiftId, $data);
            return response()->json([
                'data' => new StaffShiftResource($record),
                'message' => 'Shift updated successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/v1/coaches/{id}/shifts/{shiftId}',
        summary: 'Remove Coach Shift',
        description: 'Removes a specific shift assigned to a coach.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'shiftId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Shift deleted successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))
        ]
    )]
    public function removeShift($id, $shiftId)
    {
        try {
            $this->staffShiftService->delete($shiftId);
            return response()->json([
                'message' => 'Shift deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/v1/coaches/{id}/shifts',
        summary: 'Get Coach Shifts',
        description: 'Returns all shifts assigned to a specific coach.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of shifts retrieved successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')), new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 404, description: 'Coach not found', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 500, description: 'Server Error', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'error', type: 'string')]))
        ]
    )]
    public function getShifts($id)
    {
        try {
            $coach = $this->coachService->getSingleCoach($id);
            $shifts = $coach->shifts()->with('branchShift')->get();
            return response()->json([
                'data' => StaffShiftResource::collection($shifts),
                'message' => 'Shifts retrieved successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coach not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/v1/coaches/{id}/activities',
        summary: 'Get Coach Activities',
        description: 'Returns all activities associated with a specific coach, including full activity details.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of activities retrieved successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')), new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 404, description: 'Coach not found', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])),
            new OA\Response(response: 500, description: 'Server Error', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'error', type: 'string')]))
        ]
    )]
    public function getActivities($id)
    {
        try {
            $coach = $this->coachService->getSingleCoach($id);
            $activities = $coach->activities()->get();
            
            return response()->json([
                'data' => \Modules\Sports\Http\Resources\ActivityResource::collection($activities),
                'message' => 'Activities retrieved successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coach not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/v1/coaches/{id}',
        summary: '🗑️ حذف مدرب (Soft Delete)',
        description: 'حذف مدرب من النظام. يتطلب إرسال كلمة التأكيد "delete" ضمن جسم الطلب.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف المدرب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'confirmation', in: 'query', required: false, description: 'كلمة تأكيد الحذف (delete)', schema: new OA\Schema(type: 'string', example: ''))]
    #[OA\Response(response: 200, description: '✅ تم حذف المدرب بنجاح')]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"')]
    #[OA\Response(response: 404, description: '🚫 المدرب غير موجود')]
    public function destroy(Request $request, $id)
    {
        $confirmation = $request->input('confirmation', '');
        $this->coachService->deleteCoach((int) $id, (string) $confirmation);
        return response()->json([
            'status'  => 'success',
            'message' => __('Coach deleted successfully')
        ], 200);
    }

    #[OA\Get(
        path: '/v1/coaches/trashed',
        summary: '🗑️ عرض المدربين المحذوفين (سلة المهملات)',
        description: 'جلب قائمة بالمدربين المحذوفين.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200, 
        description: '✅ تم جلب المدربين المحذوفين بنجاح',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'status', type: 'string', example: 'success'),
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CoachResource')),
            new OA\Property(property: 'message', type: 'string', example: 'Trashed coaches retrieved successfully')
        ])
    )]
    public function trashed(Request $request)
    {
        $coaches = $this->coachService->getTrashedCoaches($request->all());
        return response()->json([
            'status' => 'success',
            'data' => CoachResource::collection($coaches),
            'message' => __('Trashed coaches retrieved successfully')
        ], 200);
    }

    #[OA\Post(
        path: '/v1/coaches/{id}/restore',
        summary: '♻️ استرجاع مدرب محذوف',
        description: 'استرجاع مدرب من سلة المهملات وإعادة تفعيل حسابه.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف المدرب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200, 
        description: '✅ تم استرجاع المدرب بنجاح',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'status', type: 'string', example: 'success'),
            new OA\Property(property: 'data', ref: '#/components/schemas/CoachResource'),
            new OA\Property(property: 'message', type: 'string', example: 'Coach restored successfully')
        ])
    )]
    #[OA\Response(response: 404, description: '🚫 المدرب غير موجود')]
    public function restore($id)
    {
        try {
            $coach = $this->coachService->restoreCoach((int) $id);
            return response()->json([
                'status' => 'success',
                'data' => new CoachResource($coach),
                'message' => __('Coach restored successfully')
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Coach not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }
}
