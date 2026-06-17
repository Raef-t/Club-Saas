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
use OpenApi\Attributes as OA;

class MemberAttendanceController extends BaseController
{
    protected $attendanceService;

    public function __construct(MemberAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    #[OA\Get(
        path: '/v1/member-attendance',
        summary: '📋 عرض حضور وانصراف الأعضاء',
        description: 'استرجاع سجل حضور وانصراف جميع الأعضاء.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع البيانات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member attendance retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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

    #[OA\Get(
        path: '/v1/member-attendance/{id}',
        summary: '🔍 تفاصيل الحضور والانصراف للعضو',
        description: 'استرجاع تفاصيل سجل حضور وانصراف محدد.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل السجل',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        try {
            $record = $this->attendanceService->getById($id);
            return $this->successResponse(new AttendanceResource($record), __('Retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    #[OA\Post(
        path: '/v1/member-attendance',
        summary: '➕ إضافة سجل حضور للعضو',
        description: 'إضافة سجل حضور يدوياً للعضو.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'check_in_at'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1),
                new OA\Property(property: 'check_in_at', type: 'string', format: 'date-time', example: '2023-10-01T08:00:00Z'),
                new OA\Property(property: 'check_out_at', type: 'string', format: 'date-time', example: '2023-10-01T17:00:00Z'),
                new OA\Property(property: 'club_id', type: 'integer', example: 1),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الإنشاء بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Error creating record.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(Request $request)
    {
        try {
            $record = $this->attendanceService->create($request->all());
            return $this->successResponse(new AttendanceResource($record), __('Created successfully'), 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Put(
        path: '/v1/member-attendance/{id}',
        summary: '✏️ تعديل سجل الحضور للعضو',
        description: 'تعديل بيانات سجل حضور وانصراف العضو.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'check_out_at', type: 'string', format: 'date-time', example: '2023-10-01T18:00:00Z')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Error updating record.')]))]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(Request $request, $id)
    {
        try {
            $record = $this->attendanceService->update($id, $request->all());
            return $this->successResponse(new AttendanceResource($record), __('Updated successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Delete(
        path: '/v1/member-attendance/{id}',
        summary: '🗑️ حذف سجل الحضور للعضو',
        description: 'حذف سجل الحضور والانصراف للعضو من النظام.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Error deleting record.')]))]
    #[OA\Response(response: 404, description: '🚫 غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        try {
            $this->attendanceService->delete($id);
            return $this->successResponse(null, __('Deleted successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/members/check-in',
        summary: '✅ تسجيل الدخول اليدوي للعضو',
        description: 'تسجيل دخول العضو في النادي أو الفرع يدوياً.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'club_id', 'branch_id'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1),
                new OA\Property(property: 'club_id', type: 'integer', example: 1),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(property: 'facility_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الدخول بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member checked in successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member already checked in.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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

    #[OA\Post(
        path: '/v1/member-attendance/{attendanceId}/check-out',
        summary: '🚪 تسجيل الانصراف للعضو',
        description: 'تسجيل خروج العضو.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, description: 'معرف سجل الحضور', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الانصراف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member checked out successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member already checked out.')]))]
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function checkOut(Request $request, $attendanceId)
    {
        try {
            $attendance = $this->attendanceService->checkOut((int)$attendanceId);
            return $this->successResponse(new AttendanceResource($attendance), __('Member checked out successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/v1/members/{memberId}/attendance-history',
        summary: '📆 سجل حضور وانصراف عضو',
        description: 'استرجاع تاريخ الحضور والانصراف لعضو محدد مع إمكانية الفلترة بالتواريخ.',
        tags: ['Member Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'memberId', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'from', in: 'query', required: false, description: 'تاريخ البداية (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2023-10-01'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, description: 'تاريخ النهاية (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2023-10-31'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع السجل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Member attendance history retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function history(Request $request, $memberId)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $perPage = $request->input('per_page', 15);
        
        $query = $this->attendanceService->getHistory((int)$memberId, $from, $to);
        $history = $query->paginate($perPage);

        return $this->successResponse(AttendanceResource::collection($history), __('Member attendance history retrieved'));
    }

    #[OA\Get(
        path: '/v1/my-activities',
        summary: '🎯 نشاطاتي',
        description: 'استرجاع نشاطات وحضور العضو المسجل للدخول مع إحصائيات لفترة معينة.',
        tags: ['Member App'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'period', in: 'query', required: false, description: 'الفترة (weekly, monthly, yearly)', schema: new OA\Schema(type: 'string', example: 'weekly'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع النشاطات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Activities retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'stats', type: 'object', properties: [
                        new OA\Property(property: 'total_attendance', type: 'integer', example: 5),
                        new OA\Property(property: 'training_hours', type: 'number', example: 7.5)
                    ]),
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'pagination', type: 'object')
                ])
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي للعضو غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
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
