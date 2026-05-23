<?php
namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Services\ClubService;
use Modules\ClubManager\Http\Requests\StoreClubRequest;
use Modules\ClubManager\Http\Requests\UpdateClubRequest;
use Modules\ClubManager\Http\Resources\ClubResource;

class ClubController extends BaseController
{
    protected $service;

    public function __construct(ClubService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->successResponse(ClubResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    public function store(StoreClubRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new ClubResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new ClubResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdateClubRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new ClubResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
