<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Sports\Models\Activity;
use Modules\Sports\Http\Resources\ActivityResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

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
    public function index()
    {
        $activities = Activity::orderBy('id')->get();
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|array',
            'name.ar' => 'required|string|max:150',
            'name.en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'type' => 'nullable|in:open_gym,group_class,personal_training',
            'default_capacity' => 'nullable|integer|min:1',
            'is_private_equipment' => 'nullable|boolean',
            'gender_allowed' => 'nullable|in:male,female,mixed',
        ]);

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
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name' => 'nullable|array',
            'name.ar' => 'nullable|string|max:150',
            'name.en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'type' => 'nullable|in:open_gym,group_class,personal_training',
            'default_capacity' => 'nullable|integer|min:1',
            'is_private_equipment' => 'nullable|boolean',
            'gender_allowed' => 'nullable|in:male,female,mixed',
            'is_active' => 'nullable|boolean',
        ]);

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
