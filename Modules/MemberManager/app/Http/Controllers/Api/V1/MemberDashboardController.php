<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\MemberManager\Services\Me\MemberDashboardService;

class MemberDashboardController extends BaseController
{
    protected MemberDashboardService $dashboardService;

    public function __construct(MemberDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse(__('Unauthorized'), 401);
        }

        // Resolve member from authenticated user (User → Person → Member)
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found for this user.'), 403);
        }

        $data = $this->dashboardService->getDashboardData(
            (int) $member->id,
            (int) $member->person_id
        );

        return $this->successResponse($data, __('Dashboard data retrieved successfully.'));
    }

    /**
     * Resolve the Member record from the authenticated user.
     * Supports both direct Member auth and User → Person → Member chain.
     */
    protected function resolveMember($user)
    {
        // If the user is a Member directly (custom guard)
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user;
        }

        // Standard flow: User → Person → Member
        if (isset($user->person_id)) {
            return DB::table('members')
                ->where('person_id', $user->person_id)
                ->whereNull('deleted_at')
                ->first();
        }

        return null;
    }
}
