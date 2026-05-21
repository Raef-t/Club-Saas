<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\StoreBranchRequest;
use Modules\ClubManager\Http\Requests\UpdateBranchRequest;
use Modules\ClubManager\Http\Resources\BranchResource;
use Modules\ClubManager\Services\BranchService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class BranchController extends BaseController
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    #[OA\Get(
        path: '/v1/branches',
        summary: '🏢 List all branches',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ List of branches')]
    public function index()
    {
        $branches = $this->branchService->getAllBranches();
        return $this->successResponse(BranchResource::collection($branches), __('Branches retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/branches',
        summary: '➕ Create a new branch',
        tags: ['Branch Management'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBranchRequest'))
    )]
    #[OA\Response(response: 201, description: '✅ Branch created')]
    #[OA\Response(response: 422, description: '❌ Validation error')]
    public function store(StoreBranchRequest $request)
    {
        $branch = $this->branchService->createBranch($request->validated());
        return $this->successResponse(new BranchResource($branch), __('Branch created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/branches/{id}',
        summary: '🔍 Get branch details',
        tags: ['Branch Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Branch details')]
    #[OA\Response(response: 404, description: '❌ Branch not found')]
    public function show($id)
    {
        $branch = $this->branchService->getBranchById($id);
        return $this->successResponse(new BranchResource($branch), __('Branch details retrieved'));
    }

    #[OA\Put(
        path: '/v1/branches/{id}',
        summary: '📝 Update branch',
        tags: ['Branch Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Branch updated')]
    #[OA\Response(response: 404, description: '❌ Branch not found')]
    public function update(UpdateBranchRequest $request, $id)
    {
        $branch = $this->branchService->updateBranch($id, $request->validated());
        return $this->successResponse(new BranchResource($branch), __('Branch updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/branches/{id}',
        summary: '🗑️ Delete branch',
        tags: ['Branch Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Branch deleted')]
    #[OA\Response(response: 404, description: '❌ Branch not found')]
    public function destroy($id)
    {
        $this->branchService->deleteBranch($id);
        return $this->successResponse(null, __('Branch deleted successfully'));
    }

    #[OA\Patch(
        path: '/v1/branches/{id}/toggle-status',
        summary: '🔄 Toggle branch active status',
        tags: ['Branch Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    )]
    #[OA\Response(response: 200, description: '✅ Branch status updated')]
    public function toggleStatus($id)
    {
        $branch = $this->branchService->toggleStatus($id);
        return $this->successResponse(new BranchResource($branch), __('Branch status updated'));
    }
}
