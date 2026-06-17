<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\AttendanceManager\Services\MemberAttendanceService;
use Modules\AttendanceManager\Http\Requests\MemberCheckInRequest;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Exception;
use Modules\AttendanceManager\Models\Attendance;

class MemberAttendanceController extends BaseController
{
    protected $attendanceService;

    public function __construct(MemberAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $records = Attendance::where('attendable_type', 'player_subscription')
            ->orderBy('check_in_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse(
            AttendanceResource::collection($records),
            __('Member attendance retrieved successfully')
        );
    }

    public function show($id)
    {
        try {
            $record = $this->attendanceService->getById($id);
            return $this->successResponse(new AttendanceResource($record), __('Retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $record = $this->attendanceService->create($request->all());
            return $this->successResponse(new AttendanceResource($record), __('Created successfully'), 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $record = $this->attendanceService->update($id, $request->all());
            return $this->successResponse(new AttendanceResource($record), __('Updated successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $this->attendanceService->delete($id);
            return $this->successResponse(null, __('Deleted successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function checkIn(MemberCheckInRequest $request)
    {
        try {
            $memberId = $request->input('member_id');
            $facilityId = $request->input('facility_id');
            $clubId = $request->input('club_id');
            $branchId = $request->input('branch_id');

            $attendance = $this->attendanceService->checkIn((int)$memberId, (int)$clubId, (int)$branchId, $facilityId);
            
            return $this->successResponse(new AttendanceResource($attendance), __('Member checked in successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function checkOut(Request $request, $attendanceId)
    {
        try {
            $attendance = $this->attendanceService->checkOut((int)$attendanceId);
            return $this->successResponse(new AttendanceResource($attendance), __('Member checked out successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function history(Request $request, $memberId)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $perPage = $request->input('per_page', 15);
        
        $query = $this->attendanceService->getHistory((int)$memberId, $from, $to);
        $history = $query->paginate($perPage);

        return $this->successResponse(AttendanceResource::collection($history), __('Member attendance history retrieved'));
    }

    /**
     * Get the authenticated member's own activities with period filtering and stats.
     */
    public function myActivities(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $period = $request->input('period', 'weekly');
        $perPage = $request->input('per_page', 15);

        // Determine date range based on period
        $dateRange = $this->getDateRange($period);

        // Build base query for this member's attendance
        $baseQuery = Attendance::where('attendable_type', 'player_subscription')
            ->whereIn('attendable_id', function ($query) use ($member) {
                $query->select('id')
                    ->from('player_subscriptions')
                    ->where('member_id', $member->id);
            });

        // Stats for the selected period
        $statsQuery = (clone $baseQuery)
            ->where('check_in_at', '>=', $dateRange['start'])
            ->where('check_in_at', '<=', $dateRange['end']);

        $totalAttendance = (clone $statsQuery)->count();

        $trainingMinutes = (clone $statsQuery)
            ->whereNotNull('check_out_at')
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, check_in_at, check_out_at)) as total_minutes')
            ->value('total_minutes') ?? 0;

        $trainingHours = round($trainingMinutes / 60, 1);

        // Paginated activity list for the period
        $items = (clone $baseQuery)
            ->where('check_in_at', '>=', $dateRange['start'])
            ->where('check_in_at', '<=', $dateRange['end'])
            ->orderByDesc('check_in_at')
            ->paginate($perPage);

        $formattedItems = $items->getCollection()->map(function ($record) {
            $checkIn = Carbon::parse($record->check_in_at);
            $durationHours = null;
            if ($record->check_out_at) {
                $durationHours = round(
                    $checkIn->diffInMinutes(Carbon::parse($record->check_out_at)) / 60,
                    1
                );
            }

            return [
                'id' => $record->id,
                'title' => $record->metadata['activity_name'] ?? __('Training Session'),
                'date' => $checkIn->toDateString(),
                'day' => $checkIn->format('d'),
                'month' => $checkIn->translatedFormat('F'),
                'time_label' => $checkIn->format('h:i A'),
                'duration_hours' => $durationHours,
                'duration_label' => $durationHours ? $durationHours . ' ' . __('hours') : null,
            ];
        });

        return $this->successResponse([
            'stats' => [
                'total_attendance' => $totalAttendance,
                'training_hours' => $trainingHours,
            ],
            'items' => $formattedItems,
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ],
        ], __('Activities retrieved successfully'));
    }

    /**
     * Resolve date range from period string.
     */
    protected function getDateRange(string $period): array
    {
        return match ($period) {
            'monthly' => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
            'yearly' => [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now()->endOfYear(),
            ],
            default => [ // weekly
                'start' => Carbon::now()->startOfWeek(),
                'end' => Carbon::now()->endOfWeek(),
            ],
        };
    }

    /**
     * Resolve the Member record from the authenticated user.
     */
    protected function resolveMember($user): ?object
    {
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user;
        }

        if (isset($user->person_id)) {
            return DB::table('members')
                ->where('person_id', $user->person_id)
                ->whereNull('deleted_at')
                ->first();
        }

        return null;
    }
}
