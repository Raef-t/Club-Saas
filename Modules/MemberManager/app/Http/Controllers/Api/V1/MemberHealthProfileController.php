<?php
namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Services\MemberHealthProfileService;
use Modules\MemberManager\Http\Requests\StoreMemberHealthProfileRequest;
use Modules\MemberManager\Http\Requests\UpdateMemberHealthProfileRequest;
use Modules\MemberManager\Http\Resources\MemberHealthProfileResource;

class MemberHealthProfileController extends BaseController
{
    protected $service;

    public function __construct(MemberHealthProfileService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(MemberHealthProfileResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreMemberHealthProfileRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new MemberHealthProfileResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new MemberHealthProfileResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateMemberHealthProfileRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new MemberHealthProfileResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
