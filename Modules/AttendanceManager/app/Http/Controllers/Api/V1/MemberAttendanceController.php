<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\AttendanceManager\Models\Attendance;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;
use Modules\AttendanceManager\Http\Requests\MemberCheckInRequest;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class MemberAttendanceController extends BaseController
{
    public function __construct(protected UnifiedAttendanceService $attendanceService) {}

    #[OA\Get(
        path: '/v1/member-attendances',
        summary: '📋 عرض حضور وانصراف الأعضاء',
        description: 'استرجاع سجل حضور وانصراف جميع الأعضاء.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع البيانات بنجاح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))]))]
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $records = Attendance::where('attendable_type', 'member')
            ->orderByDesc('check_in_at')
            ->paginate($perPage);

        return $this->successResponse(
            AttendanceResource::collection($records),
            __('Member attendance retrieved successfully')
        );
    }

    #[OA\Get(path: '/v1/member-attendances/{id}', summary: '🔍 تفاصيل حضور عضو', tags: ['Member Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅', content: new OA\JsonContent())]
    public function show($id)
    {
        try {
            $record = Attendance::where('attendable_type', 'member')->findOrFail($id);
            return $this->successResponse(new AttendanceResource($record), __('Retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    #[OA\Post(path: '/v1/member-attendances', summary: '➕ إضافة حضور يدوي للعضو', tags: ['Member Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Response(response: 201, description: '✅ تم الإنشاء', content: new OA\JsonContent())]
    public function store(Request $request)
    {
        try {
            $data     = $request->all();
            $memberId = $data['member_id'] ?? null;
            $clubId   = $data['club_id']   ?? null;
            $branchId = $data['branch_id'] ?? null;

            $record = Attendance::create([
                'club_id'         => $clubId,
                'attendable_type' => 'member',
                'attendable_id'   => $memberId,
                'branch_id'       => $branchId,
                'check_in_at'     => $data['check_in_at'] ?? now(),
                'check_out_at'    => $data['check_out_at'] ?? null,
                'status'          => $data['status'] ?? 'checked_in',
                'metadata'        => $data['metadata'] ?? [],
            ]);

            return $this->successResponse(new AttendanceResource($record), __('Created successfully'), 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Put(path: '/v1/member-attendances/{id}', summary: '✏️ تعديل حضور عضو', tags: ['Member Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم التعديل', content: new OA\JsonContent())]
    public function update(Request $request, $id)
    {
        try {
            $record = Attendance::where('attendable_type', 'member')->findOrFail($id);
            $record->update($request->only(['check_in_at', 'check_out_at', 'status', 'metadata']));
            return $this->successResponse(new AttendanceResource($record), __('Updated successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Delete(path: '/v1/member-attendances/{id}', summary: '🗑️ حذف حضور عضو', tags: ['Member Attendance'], security: [['bearerAuth' => []]])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم الحذف', content: new OA\JsonContent())]
    public function destroy($id)
    {
        try {
            Attendance::where('attendable_type', 'member')->findOrFail($id)->delete();
            return $this->successResponse(null, __('Deleted successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/v1/my-activities',
        summary: '🎯 نشاطاتي',
        description: 'استرجاع نشاطات وحضور العضو المسجّل للدخول مع إحصائيات.',
        tags: ['Member App'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'period', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'weekly'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅', content: new OA\JsonContent())]
    public function myActivities(Request $request)
    {
        $user   = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $period  = $request->input('period', 'weekly');
        $perPage = $request->input('per_page', 15);
        $range   = $this->getDateRange($period);

        $baseQuery = Attendance::where('attendable_type', 'member')
            ->where('attendable_id', $member->id);

        $statsQuery = (clone $baseQuery)
            ->whereBetween('check_in_at', [$range['start'], $range['end']]);

        $totalAttendance = (clone $statsQuery)->count();

        $trainingMinutes = (clone $statsQuery)
            ->whereNotNull('check_out_at')
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, check_in_at, check_out_at)) as total_minutes')
            ->value('total_minutes') ?? 0;

        $items = (clone $baseQuery)
            ->whereBetween('check_in_at', [$range['start'], $range['end']])
            ->orderByDesc('check_in_at')
            ->paginate($perPage);

        $formattedItems = $items->getCollection()->map(function ($record) {
            $checkIn      = Carbon::parse($record->check_in_at);
            $durationHours = null;

            if ($record->check_out_at) {
                $durationHours = round($checkIn->diffInMinutes(Carbon::parse($record->check_out_at)) / 60, 1);
            }

            return [
                'id'             => $record->id,
                'title'          => $record->metadata['activity_name'] ?? __('Training Session'),
                'date'           => $checkIn->toDateString(),
                'day'            => $checkIn->format('d'),
                'month'          => $checkIn->translatedFormat('F'),
                'time_label'     => $checkIn->format('h:i A'),
                'duration_hours' => $durationHours,
                'duration_label' => $durationHours ? $durationHours . ' ' . __('hours') : null,
            ];
        });

        return $this->successResponse([
            'stats' => [
                'total_attendance' => $totalAttendance,
                'training_hours'   => round($trainingMinutes / 60, 1),
            ],
            'items'      => $formattedItems,
            'pagination' => [
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
            ],
        ], __('Activities retrieved successfully'));
    }

    protected function getDateRange(string $period): array
    {
        return match ($period) {
            'monthly' => ['start' => Carbon::now()->startOfMonth(), 'end' => Carbon::now()->endOfMonth()],
            'yearly'  => ['start' => Carbon::now()->startOfYear(),  'end' => Carbon::now()->endOfYear()],
            default   => ['start' => Carbon::now()->startOfWeek(),  'end' => Carbon::now()->endOfWeek()],
        };
    }

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
