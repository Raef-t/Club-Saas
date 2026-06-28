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
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'branch_id', 'age'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
                    new OA\Property(property: 'gender', type: 'string', example: 'male'),
                    new OA\Property(property: 'age', type: 'integer', example: 30),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1990-01-01'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '500000000'),
                    new OA\Property(property: 'country_code', type: 'string', example: '+966'),
                    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                    new OA\Property(property: 'employment_type', type: 'string', example: 'fixed_salary'),
                    new OA\Property(property: 'base_salary', type: 'number', example: 5000),
                    new OA\Property(property: 'specialization', type: 'string', example: 'Bodybuilding'),
                    new OA\Property(property: 'experience_years', type: 'integer', example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Coach created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation errors'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function store(StoreCoachRequest $request)
    {
        $coach = $this->coachService->createCoach($request->validated());

        return response()->json([
            'data' => new CoachResource($coach),
            'message' => 'Coach created successfully'
        ], 201);
    }

    #[OA\Get(
        path: '/v1/coaches',
        summary: 'Get All Coaches',
        description: 'Get all coaches with optional filters.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'branch_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of coaches'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function index(Request $request)
    {
        $filters = $request->only(['branch_id']);
        
        $coaches = $this->coachService->getAllCoaches($filters);

        return CoachResource::collection($coaches);
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
            new OA\Response(response: 200, description: 'Coach details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach not found'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function show($id)
    {
        $coach = $this->coachService->getSingleCoach($id);
        return new CoachResource($coach);
    }

    #[OA\Patch(
        path: '/v1/coaches/{id}',
        summary: 'Update Coach Basic Info',
        description: 'Update base salary, employment type, status, etc.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'base_salary', type: 'number', example: 6000),
                    new OA\Property(property: 'employment_type', type: 'string', example: 'hybrid'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Coach updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach not found'),
            new OA\Response(response: 422, description: 'Validation errors'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function updateBasicInfo(UpdateCoachBasicInfoRequest $request, $id)
    {
        $coach = $this->coachService->updateBasicInfo($id, $request->validated());

        return response()->json([
            'data' => new CoachResource($coach),
            'message' => 'Coach updated successfully'
        ]);
    }

    #[OA\Patch(
        path: '/v1/coaches/{id}/details',
        summary: 'Update Coach Details',
        description: 'Update specialization, bio, experience, etc.',
        tags: ['Coach Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'specialization', type: 'string', example: 'CrossFit'),
                    new OA\Property(property: 'experience_years', type: 'integer', example: 7),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Coach details updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach not found'),
            new OA\Response(response: 422, description: 'Validation errors'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function updateDetails(UpdateCoachDetailsRequest $request, $id)
    {
        $details = $this->coachService->updateDetails($id, $request->validated());

        return response()->json([
            'data' => $details,
            'message' => 'Coach details updated successfully'
        ]);
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
            new OA\Response(response: 200, description: 'Activities assigned successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach not found'),
            new OA\Response(response: 422, description: 'Validation errors'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function assignActivities(AssignCoachActivitiesRequest $request, $id)
    {
        $validated = $request->validated();
        $coach = $this->coachService->assignActivities($id, $validated['activity_ids']);

        return response()->json([
            'data' => $coach->activities,
            'message' => 'Activities assigned successfully'
        ]);
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
            new OA\Response(response: 200, description: 'Activity removed successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach or Activity not found'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function removeActivity($id, $activityId)
    {
        $this->coachService->removeActivity($id, $activityId);
        return response()->json(['message' => 'Activity removed successfully']);
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
            new OA\Response(response: 201, description: 'Certification uploaded successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach not found'),
            new OA\Response(response: 422, description: 'Validation errors'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function uploadCertification(UploadCoachCertificationRequest $request, $id)
    {
        $validated = $request->validated();

        if (empty($validated['file']) && empty($validated['document_url'])) {
            return response()->json(['message' => 'Please provide either a file or a document_url'], 422);
        }

        $certification = $this->coachService->uploadCertification($id, $validated);

        return response()->json([
            'data' => $certification,
            'message' => 'Certification uploaded successfully'
        ], 201);
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
            new OA\Response(response: 200, description: 'List of certifications'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach not found'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function getCertifications($id)
    {
        $coach = $this->coachService->getSingleCoach($id);
        
        $certifications = $coach->coachDetail ? $coach->coachDetail->certifications : [];

        return response()->json(['data' => $certifications]);
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
            new OA\Response(response: 200, description: 'Coach deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Coach not found'),
            new OA\Response(response: 500, description: 'Server Error'),
        ]
    )]
    public function destroy($id)
    {
        $this->coachService->deleteCoach($id);
        return response()->json(['message' => 'Coach deleted successfully']);
    }
}
