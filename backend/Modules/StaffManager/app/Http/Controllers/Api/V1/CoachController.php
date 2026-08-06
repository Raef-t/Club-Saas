<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StaffManager\Services\CoachService;
use Modules\StaffManager\Http\Requests\StoreCoachRequest;
use Modules\StaffManager\Http\Requests\UpdateCoachBasicInfoRequest;
use Modules\StaffManager\Http\Requests\UpdateCoachDetailsRequest;
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
            if ($e->getCode() == 23000 || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                return response()->json([
                    'message' => 'Conflict occurred while creating coach. The data might already exist.'
                ], 409);
            }
            return response()->json([
                'message' => 'An error occurred while creating the coach.',
                'error' => $e->getMessage()
            ], 500);
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

    #[OA\Delete(
        path: '/v1/coaches/{id}',
        summary: 'Delete Coach (Soft Delete)',
        description: 'Soft deletes a coach and all associated details, contracts, certifications, and shifts automatically.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Coach soft-deleted successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach deleted successfully')
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
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while deleting the coach.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function destroy($id)
    {
        try {
            $this->coachService->deleteCoach($id);
            return response()->json([
                'message' => 'Coach deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the coach.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/v1/coaches/{id}/restore',
        summary: 'Restore Soft Deleted Coach',
        description: 'Restores a soft-deleted coach and all associated details, contracts, certifications, and shifts automatically.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Coach restored successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach restored successfully')
                ])
            ),
            new OA\Response(
                response: 404, 
                description: 'Coach not found in trashed',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach not found.')
                ])
            ),
            new OA\Response(
                response: 500, 
                description: 'Server Error',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'An error occurred while restoring the coach.'),
                    new OA\Property(property: 'error', type: 'string', example: 'Error message details')
                ])
            ),
        ]
    )]
    public function restore($id)
    {
        try {
            $this->coachService->restoreCoach($id);
            return response()->json([
                'message' => 'Coach restored successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Coach not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while restoring the coach.',
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
        }
    }
}
