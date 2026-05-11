<?php

namespace Modules\BranchManager\Http\Controllers\Api\V1;

use Modules\BranchManager\Http\Requests\StoreLockerRequest;
use Modules\BranchManager\Http\Resources\LockerResource;
use Modules\BranchManager\Services\LockerService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class LockerController extends BaseController
{
    protected $lockerService;

    public function __construct(LockerService $lockerService)
    {
        $this->lockerService = $lockerService;
    }

    #[OA\Get(
        path: '/v1/lockers',
        summary: '🔐 List all lockers',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ List of lockers')]
    public function index()
    {
        $lockers = $this->lockerService->getAllLockers();
        return $this->successResponse(LockerResource::collection($lockers), __('Lockers retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/lockers',
        summary: '➕ Create a new locker',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreLockerRequest'))
    )]
    #[OA\Response(response: 201, description: '✅ Locker created')]
    public function store(StoreLockerRequest $request)
    {
        $locker = $this->lockerService->createLocker($request->validated());
        return $this->successResponse(new LockerResource($locker), __('Locker created successfully'), 201);
    }

    #[OA\Delete(
        path: '/v1/lockers/{id}',
        summary: '🗑️ Delete locker',
        tags: ['Locker Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Locker deleted')]
    public function destroy($id)
    {
        $this->lockerService->deleteLocker($id);
        return $this->successResponse(null, __('Locker deleted successfully'));
    }

    #[OA\Patch(
        path: '/v1/lockers/{id}/toggle-status',
        summary: '🔄 Toggle locker active status',
        tags: ['Locker Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Locker status updated')]
    public function toggleStatus($id)
    {
        $locker = $this->lockerService->toggleStatus($id);
        return $this->successResponse(new LockerResource($locker), __('Locker status updated'));
    }
}
