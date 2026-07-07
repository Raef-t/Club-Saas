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
use OpenApi\Attributes as OA;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

#[OA\Tag(name: 'Coach Management', description: 'API Endpoints for managing coaches')]
class CoachController extends Controller
{
    protected CoachService $coachService;

    public function __construct(CoachService $coachService)
    {
        $this->coachService = $coachService;
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
                    required: ['first_name', 'last_name', 'branch_ids', 'age'],
                    properties: [
                        new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
                        new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
                        new OA\Property(property: 'gender', type: 'string', example: 'male'),
                        new OA\Property(property: 'age', type: 'integer', example: 30),
                        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1990-01-01'),
                        new OA\Property(property: 'phone_number', type: 'string', example: '500000000'),
                        new OA\Property(property: 'country_code', type: 'string', example: '+966'),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة المدرب', nullable: true),
                        new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                        new OA\Property(property: 'employment_type', description: 'نوع التوظيف', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'fixed_salary'),
                        new OA\Property(property: 'base_salary', type: 'number', example: 5000),
                        new OA\Property(property: 'default_commission_rate', type: 'number', format: 'float', description: 'نسبة العمولة الثابتة للمدرب (مئوية)', example: 20.5),
                        new OA\Property(property: 'specialization', type: 'string', example: 'Bodybuilding'),
                        new OA\Property(property: 'experience_years', type: 'integer', example: 5),
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
            $filters = $request->only(['branch_id', 'activity_id']);
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
        description: 'Update base salary, employment type, status, specialization, bio, experience, etc.',
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
                        new OA\Property(property: '_method', type: 'string', example: 'PATCH', description: 'يجب إرسال هذه القيمة عند رفع ملفات عبر POST method spoofing'),
                        new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
                        new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
                        new OA\Property(property: 'gender', type: 'string', example: 'male'),
                        new OA\Property(property: 'age', type: 'integer', example: 30),
                        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1990-01-01'),
                        new OA\Property(property: 'phone_number', type: 'string', example: '500000000'),
                        new OA\Property(property: 'country_code', type: 'string', example: '+966'),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة المدرب', nullable: true),
                        new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer', example: 1)),
                        new OA\Property(property: 'employment_type', description: 'نوع التوظيف', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'hybrid'),
                        new OA\Property(property: 'base_salary', type: 'number', example: 6000),
                        new OA\Property(property: 'default_commission_rate', type: 'number', format: 'float', description: 'نسبة العمولة الثابتة للمدرب (مئوية)', example: 25.0),
                        new OA\Property(property: 'specialization', type: 'string', example: 'CrossFit'),
                        new OA\Property(property: 'experience_years', type: 'integer', example: 7),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
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

    #[OA\Delete(
        path: '/v1/coaches/{id}',
        summary: 'Delete Coach',
        description: 'Soft delete a coach.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Coach deleted successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Coach deleted successfully')
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
}
