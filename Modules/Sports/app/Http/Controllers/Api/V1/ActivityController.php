<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Sports\Models\Activity;
use Modules\Sports\Http\Resources\ActivityResource;
use Modules\ClubManager\Models\Facility;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Modules\Sports\Http\Requests\StoreActivityRequest;
use Modules\Sports\Http\Requests\UpdateActivityRequest;

class ActivityController extends BaseController
{
    #[OA\Get(
        path: '/v1/activities',
        summary: '🏋️ List all activities',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index(Request $request)
    {
        $query = Activity::query();

        if ($request->has('facility_id')) {
            $facility = Facility::find($request->facility_id);
            if ($facility && $facility->gender_restriction !== 'mixed') {
                $query->whereIn('gender_allowed', [$facility->gender_restriction, 'mixed']);
            }
        } elseif ($request->has('gender_allowed')) {
            $query->where('gender_allowed', $request->gender_allowed);
        }

        $activities = $query->orderBy('id')->get();
        return $this->successResponse(ActivityResource::collection($activities), __('Activities retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/activities',
        summary: '➕ Create a new activity',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Activity created')
        ]
    )]
    public function store(StoreActivityRequest $request)
    {
        $data = $request->validated();

        $activity = Activity::create($data);
        return $this->successResponse(new ActivityResource($activity), __('Activity created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/activities/{id}',
        summary: '🔍 Get activity details',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show(int $id)
    {
        $activity = Activity::findOrFail($id);
        return $this->successResponse(new ActivityResource($activity), __('Activity retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/activities/{id}',
        summary: '✏️ Update an activity',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Activity updated')
        ]
    )]
    public function update(UpdateActivityRequest $request, int $id)
    {
        $data = $request->validated();

        $activity = Activity::findOrFail($id);
        $activity->update($data);
        return $this->successResponse(new ActivityResource($activity), __('Activity updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/activities/{id}',
        summary: '🗑 Delete an activity',
        tags: ['Sports & Activities'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Activity deleted')
        ]
    )]
    public function destroy(int $id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();
        return $this->successResponse(null, __('Activity deleted successfully'));
    }
}
