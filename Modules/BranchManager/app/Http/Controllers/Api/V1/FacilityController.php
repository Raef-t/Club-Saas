<?php

namespace Modules\BranchManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\BranchManager\Http\Requests\StoreFacilityRequest;
use Modules\BranchManager\Http\Requests\UpdateFacilityRequest;
use Modules\BranchManager\Http\Resources\FacilityResource;
use Modules\BranchManager\Services\FacilityService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class FacilityController extends BaseController
{
    protected $facilityService;

    public function __construct(FacilityService $facilityService)
    {
        $this->facilityService = $facilityService;
    }

    #[OA\Get(
        path: '/v1/facilities',
        summary: '🏊 List all facilities',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ List of facilities')]
    public function index()
    {
        $facilities = $this->facilityService->getAllFacilities();
        return $this->successResponse(FacilityResource::collection($facilities), __('Facilities retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/facilities',
        summary: '➕ Create a new facility',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreFacilityRequest'))
    )]
    #[OA\Response(response: 201, description: '✅ Facility created')]
    #[OA\Response(response: 422, description: '❌ Validation error')]
    public function store(StoreFacilityRequest $request)
    {
        $facility = $this->facilityService->createFacility($request->validated());
        return $this->successResponse(new FacilityResource($facility), __('Facility created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/facilities/{id}',
        summary: '🔍 Get facility details',
        tags: ['Facility Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Facility details')]
    #[OA\Response(response: 404, description: '❌ Facility not found')]
    public function show($id)
    {
        $facility = $this->facilityService->getFacilityById($id);
        return $this->successResponse(new FacilityResource($facility), __('Facility details retrieved'));
    }

    #[OA\Put(
        path: '/v1/facilities/{id}',
        summary: '📝 Update facility',
        tags: ['Facility Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Facility updated')]
    #[OA\Response(response: 404, description: '❌ Facility not found')]
    public function update(UpdateFacilityRequest $request, $id)
    {
        $facility = $this->facilityService->updateFacility($id, $request->validated());
        return $this->successResponse(new FacilityResource($facility), __('Facility updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/facilities/{id}',
        summary: '🗑️ Delete facility',
        tags: ['Facility Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Facility deleted')]
    #[OA\Response(response: 404, description: '❌ Facility not found')]
    public function destroy($id)
    {
        $this->facilityService->deleteFacility($id);
        return $this->successResponse(null, __('Facility deleted successfully'));
    }

    #[OA\Patch(
        path: '/v1/facilities/{id}/toggle-status',
        summary: '🔄 Toggle facility active status',
        tags: ['Facility Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Facility status updated')]
    public function toggleStatus($id)
    {
        $facility = $this->facilityService->toggleStatus($id);
        return $this->successResponse(new FacilityResource($facility), __('Facility status updated'));
    }
}
