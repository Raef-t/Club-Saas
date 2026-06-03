<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\MemberManager\Models\Member;
use Modules\MemberManager\Models\PlayerUnavailability;
use Modules\MemberManager\Http\Requests\StorePlayerUnavailabilityRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class PlayerUnavailabilityController extends BaseController
{
    #[OA\Get(
        path: '/v1/members/{member}/unavailabilities',
        summary: '🗓️ List Player Unavailabilities',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    public function index(Member $member)
    {
        $unavailabilities = $member->unavailabilities()->get();
        return $this->successResponse($unavailabilities, __('Player unavailabilities retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/members/{member}/unavailabilities',
        summary: '➕ Add Player Unavailability',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    public function store(StorePlayerUnavailabilityRequest $request, Member $member)
    {
        $unavailability = $member->unavailabilities()->create($request->validated());
        return $this->successResponse($unavailability, __('Player unavailability created successfully'), 201);
    }

    #[OA\Delete(
        path: '/v1/members/{member}/unavailabilities/{unavailability}',
        summary: '🗑️ Delete Player Unavailability',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    public function destroy(Member $member, PlayerUnavailability $unavailability)
    {
        if ($unavailability->member_id !== $member->id) {
            return $this->errorResponse(__('Unavailability does not belong to this member'), 403);
        }

        $unavailability->delete();
        return $this->successResponse(null, __('Player unavailability deleted successfully'));
    }
}
