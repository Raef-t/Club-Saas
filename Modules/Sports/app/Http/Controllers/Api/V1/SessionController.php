<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Sports\Services\SessionService;
use Modules\Sports\Http\Resources\SessionResource;
use Modules\Sports\Http\Requests\StoreSessionRequest;
use Modules\Sports\Http\Requests\UpdateSessionRequest;
use Modules\Sports\Http\Requests\GetWeeklyScheduleRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SessionController extends BaseController
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    #[OA\Get(
        path: '/v1/sessions',
        summary: '📅 List all sessions',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        $sessions = $this->sessionService->getAllSessions();
        return $this->successResponse(SessionResource::collection($sessions), __('Sessions retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/sessions',
        summary: '➕ Create a new session',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Session created')
        ]
    )]
    public function store(StoreSessionRequest $request)
    {
        $session = $this->sessionService->createSession($request->validated());
        return $this->successResponse(new SessionResource($session), __('Session created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/sessions/{id}',
        summary: '🔍 Get session details',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show(int $id)
    {
        $session = $this->sessionService->getSessionById($id);
        return $this->successResponse(new SessionResource($session), __('Session retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/sessions/{id}',
        summary: '✏️ Update a session',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Session updated')
        ]
    )]
    public function update(UpdateSessionRequest $request, int $id)
    {
        $data = $request->validated();

        $session = $this->sessionService->updateSession($id, $data);
        return $this->successResponse(new SessionResource($session), __('Session updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/sessions/{id}',
        summary: '🗑 Delete a session',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Session deleted')
        ]
    )]
    public function destroy(int $id)
    {
        $this->sessionService->deleteSession($id);
        return $this->successResponse(null, __('Session deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/sessions/weekly-schedule',
        summary: '📆 Get weekly schedule for a branch',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function weeklySchedule(GetWeeklyScheduleRequest $request)
    {
        $data = $request->validated();

        $sessions = $this->sessionService->getWeeklySchedule(
            $data['branch_id'],
            $data['start_date'] ?? null
        );

        return $this->successResponse(SessionResource::collection($sessions), __('Weekly schedule retrieved'));
    }
}
