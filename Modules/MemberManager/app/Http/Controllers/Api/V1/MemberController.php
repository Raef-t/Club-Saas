<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\MemberManager\Http\Requests\StoreMemberRequest;
use Modules\MemberManager\Http\Requests\RecordMeasurementRequest;
use Modules\MemberManager\Http\Resources\MemberResource;
use Modules\MemberManager\Services\MemberService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class MemberController extends BaseController
{
    protected $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    #[OA\Get(
        path: '/v1/members',
        summary: '👥 List all members',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        $members = $this->memberService->getAllMembers();
        return $this->successResponse(MemberResource::collection($members), __('Members retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/members',
        summary: '➕ Register a new member',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Member registered')
        ]
    )]
    public function store(StoreMemberRequest $request)
    {
        $member = $this->memberService->registerMember($request->validated());
        return $this->successResponse(new MemberResource($member), __('Member registered successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/members/{id}',
        summary: '🔍 Get member details',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation'),
            new OA\Response(response: 404, description: 'Member not found')
        ]
    )]
    public function show($id)
    {
        $member = $this->memberService->getMemberById($id);
        return $this->successResponse(new MemberResource($member), __('Member retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/members/{id}',
        summary: '📝 Update member info',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Member updated')
        ]
    )]
    public function update(StoreMemberRequest $request, $id)
    {
        $member = $this->memberService->updateMember($id, $request->validated());
        return $this->successResponse(new MemberResource($member), __('Member updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/members/{id}',
        summary: '🗑️ Delete member',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Member deleted')
        ]
    )]
    public function destroy($id)
    {
        $this->memberService->deleteMember($id);
        return $this->successResponse(null, __('Member deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/members/{id}/health-profile',
        summary: '🏥 Get member health profile',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function getHealthProfile($id)
    {
        $profile = $this->memberService->getHealthProfile($id);
        return $this->successResponse($profile, __('Health profile retrieved'));
    }

    #[OA\Get(
        path: '/v1/members/{id}/measurements',
        summary: '📏 List member measurements',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function getMeasurements($id)
    {
        $measurements = $this->memberService->getMeasurements($id);
        return $this->successResponse($measurements, __('Measurements retrieved'));
    }

    #[OA\Post(
        path: '/v1/members/{id}/measurements',
        summary: '⚖️ Record new measurement',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Measurement recorded')
        ]
    )]
    public function recordMeasurement(RecordMeasurementRequest $request, $id)
    {
        $measurement = $this->memberService->recordMeasurement($id, $request->validated());
        return $this->successResponse($measurement, __('Measurement recorded successfully'), 201);
    }
}
