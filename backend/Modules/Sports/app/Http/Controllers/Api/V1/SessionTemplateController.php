<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\Sports\Models\SportSessionTemplate;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

use Modules\SubscriptionManager\Enums\SubscriptionPlanStatus;

class SessionTemplateController extends BaseController
{
    #[OA\Get(
        path: '/v1/session-templates',
        summary: '📅 عرض قوالب الجلسات',
        description: 'استرجاع جميع قوالب الجلسات الأسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع القوالب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Templates retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $query = SportSessionTemplate::with(['subscriptionPlan']);

        if ($request->input('per_page') === 'all' || $request->boolean('all') || $request->input('paginate') === 'false') {
            $templates = $query->get();
        } else {
            $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
            $templates = $query->paginate($perPage);
        }

        return $this->successResponse($templates, __('Templates retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/session-templates/schedule',
        summary: '🗓️ جدول الجلسات',
        description: 'استرجاع جدول دوام الجلسات الأسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الجدول بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Session schedule retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function schedule(Request $request)
    {
        $branchId = $request->input('branch_id') ?? $request->input('branch');

        $sessions = SportSessionTemplate::active()
            ->whereHas('subscriptionPlan', function ($query) use ($branchId) {
                $query->where('status', '!=', SubscriptionPlanStatus::INACTIVE);
                if (!empty($branchId)) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->with([
                'facility',
                'subscriptionPlan.planActivities.staffActivity.activity',
                'subscriptionPlan.planActivities.staffActivity.staff.person'
            ])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $daysMap = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $schedule = [];

        foreach ($sessions as $session) {
            $dayName = $daysMap[$session->day_of_week] ?? 'Unknown Day';
            
            $planActivity = $session->subscriptionPlan ? $session->subscriptionPlan->planActivities->first() : null;
            $staffActivity = $planActivity ? $planActivity->staffActivity : null;
            
            $coach = $staffActivity ? $staffActivity->staff : null;
            $activity = $staffActivity ? $staffActivity->activity : null;

            $schedule[$dayName][] = [
                'id' => $session->id,
                'plan_id' => $session->plan_id,
                'activity_id' => $activity ? $activity->id : null,
                'coach_id' => $coach ? $coach->id : null,
                'start_time' => $session->start_time->format('H:i'),
                'end_time' => $session->end_time->format('H:i'),
                'plan_name' => $session->subscriptionPlan ? $session->subscriptionPlan->name : null,
                'facility' => $session->facility ? [
                    'id' => $session->facility->id,
                    'name' => $session->facility->name,
                ] : null,
                'coach' => $coach ? [
                    'id' => $coach->id,
                    'name' => $coach->person->full_name ?? '',
                ] : null,
                'activity' => $activity ? [
                    'id' => $activity->id,
                    'name' => $activity->name,
                ] : null,
            ];
        }

        return $this->successResponse($schedule, __('Session schedule retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/session-templates',
        summary: '➕ إنشاء قالب جلسة',
        description: 'إضافة قالب جلسة رياضية أسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['plan_id', 'day_of_week', 'start_time', 'end_time'],
            properties: [
                new OA\Property(property: 'plan_id', type: 'integer', example: 1),
                new OA\Property(property: 'facility_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'day_of_week', type: 'integer', description: '0=Sunday, 1=Monday, ..., 6=Saturday', example: 0),
                new OA\Property(property: 'start_time', type: 'string', format: 'time', example: '08:00'),
                new OA\Property(property: 'end_time', type: 'string', format: 'time', example: '09:00')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء القالب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Template created successfully'),
                new OA\Property(property: 'data', type: 'object'),
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(Request $request)
    {
        $data = $request->validate([
            'plan_id' => 'required|integer',
            'facility_id' => 'nullable|integer',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // Check facility overlap
        if (!empty($data['facility_id'])) {
            $facilityConflict = SportSessionTemplate::where('facility_id', $data['facility_id'])
                ->where('day_of_week', $data['day_of_week'])
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if ($facilityConflict) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'start_time' => __('يوجد تعارض في الوقت مع جلسة أخرى في نفس المرفق (القاعة).')
                ]);
            }
        }

        $template = SportSessionTemplate::create($data);
        return $this->successResponse($template, __('Template created successfully'), 201);
    }

    #[OA\Put(
        path: '/v1/session-templates/{id}',
        summary: '✏️ تعديل قالب جلسة',
        description: 'تحديث قالب جلسة رياضية أسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'plan_id', type: 'integer', nullable: true, example: 2),
                new OA\Property(property: 'facility_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'day_of_week', type: 'integer', example: 1),
                new OA\Property(property: 'start_time', type: 'string', example: '10:00'),
                new OA\Property(property: 'end_time', type: 'string', example: '11:00'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'activity_id', type: 'integer', nullable: true, example: 10),
                new OA\Property(property: 'coach_id', type: 'integer', nullable: true, example: 5)
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم التعديل بنجاح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'object')]))]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(Request $request, int $id)
    {
        $template = SportSessionTemplate::findOrFail($id);

        $data = $request->validate([
            'plan_id' => 'nullable|integer',
            'facility_id' => 'nullable|integer',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_active' => 'nullable|boolean',
            'activity_id' => 'nullable|integer',
            'coach_id' => 'nullable|integer',
        ]);

        $checkFacility = array_key_exists('facility_id', $data) ? $data['facility_id'] : $template->facility_id;
        $checkDay = $data['day_of_week'] ?? $template->day_of_week;
        $checkStart = $data['start_time'] ?? $template->start_time;
        $checkEnd = $data['end_time'] ?? $template->end_time;

        // Check facility overlap
        if (!empty($checkFacility)) {
            $facilityConflict = SportSessionTemplate::where('id', '!=', $id)
                ->where('facility_id', $checkFacility)
                ->where('day_of_week', $checkDay)
                ->where('start_time', '<', $checkEnd)
                ->where('end_time', '>', $checkStart)
                ->exists();

            if ($facilityConflict) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'start_time' => __('يوجد تعارض في الوقت مع جلسة أخرى في نفس المرفق (القاعة).')
                ]);
            }
        }

        $template->update($data);

        if (array_key_exists('activity_id', $data) || array_key_exists('coach_id', $data)) {
            if ($template->plan_id) {
                $planActivity = \Modules\SubscriptionManager\Models\SubscriptionPlanActivity::where('plan_id', $template->plan_id)->first();
                
                $currentActivityId = $planActivity && $planActivity->staffActivity ? $planActivity->staffActivity->activity_id : null;
                $currentCoachId = $planActivity && $planActivity->staffActivity ? $planActivity->staffActivity->staff_id : null;

                $newActivityId = array_key_exists('activity_id', $data) ? $data['activity_id'] : $currentActivityId;
                $newCoachId = array_key_exists('coach_id', $data) ? $data['coach_id'] : $currentCoachId;

                if ($newActivityId && $newCoachId) {
                    $staffActivity = \Modules\Sports\Models\StaffActivity::firstOrCreate([
                        'activity_id' => $newActivityId,
                        'staff_id' => $newCoachId,
                    ]);

                    if ($planActivity) {
                        $planActivity->update(['staff_activity_id' => $staffActivity->id]);
                    } else {
                        \Modules\SubscriptionManager\Models\SubscriptionPlanActivity::create([
                            'plan_id' => $template->plan_id,
                            'staff_activity_id' => $staffActivity->id,
                        ]);
                    }
                }
            }
        }

        return $this->successResponse($template, __('Template updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/session-templates/{id}',
        summary: '🗑️ حذف قالب جلسة',
        description: 'حذف قالب جلسة رياضية أسبوعية. لا يمكن حذفه إذا كان مرتبطاً باستثناءات أو إعفاءات مسجلة.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'confirm', in: 'query', required: true, description: 'كلمة تأكيد الحذف (delete)', schema: new OA\Schema(type: 'string', example: 'delete'))]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string', example: 'Template deleted successfully')]))]
    #[OA\Response(
        response: 409, 
        description: '🚫 لا يمكن الحذف — القالب مرتبط ببيانات أخرى', 
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'), 
                new OA\Property(property: 'message', type: 'string', example: 'لا يمكن حذف القالب لوجود 2 استثناءات/تعديلات مرتبطة به. يمكنك تعطيله بدلاً من حذفه.')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(Request $request, int $id)
    {
        $confirm = strtolower(trim($request->input('confirm') ?? $request->input('confirmation') ?? $request->input('confirm_text') ?? ''));

        if ($confirm !== 'delete') {
            return $this->errorResponse(
                __('يرجى تأكيد الحذف بإرسال كلمة "delete" في حقل التأكيد (confirm).'),
                422
            );
        }

        $template = SportSessionTemplate::findOrFail($id);
        $template->delete();
        return $this->successResponse(null, __('Template deleted successfully'));
    }

    #[OA\Post(
        path: '/v1/session-templates/{id}/cancel',
        summary: '🚫 إلغاء جلسة',
        description: 'إلغاء جلسة معينة في تاريخ محدد.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف قالب الجلسة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['date'],
            properties: [
                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-07-20'),
                new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'اعتذار الكوتش'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إلغاء الجلسة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Session canceled successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function cancelSession(Request $request, int $id)
    {
        $template = SportSessionTemplate::with('subscriptionPlan')->findOrFail($id);
        
        $request->validate([
            'date' => 'required|date',
            'reason' => 'required|string',
        ]);

        $planActivity = \Modules\SubscriptionManager\Models\SubscriptionPlanActivity::where('plan_id', $template->plan_id)->first();
        $coachId = $planActivity?->staffActivity?->staff_id
            ?? \Modules\StaffManager\Models\Staff::where('person_id', $request->user()->person_id)->value('id');

        $exception = \Modules\Sports\Models\SessionException::create([
            'sport_session_template_id' => $template->id,
            'coach_id' => $coachId,
            'date' => $request->date,
            'reason' => $request->reason,
            'status' => 'canceled'
        ]);

        // إرسال الإشعار للمشتركين
        if ($template->plan_id) {
            $members = \Illuminate\Support\Facades\DB::table('player_subscriptions')
                ->join('members', 'player_subscriptions.member_id', '=', 'members.id')
                ->join('people', 'members.person_id', '=', 'people.id')
                ->where('player_subscriptions.plan_id', $template->plan_id)
                ->where('player_subscriptions.status', 'active')
                ->where('player_subscriptions.end_date', '>=', now()->toDateString())
                ->select('members.user_id', 'people.first_name', 'people.last_name')
                ->get();

            if ($members->isNotEmpty()) {
                $notificationTemplate = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'session_canceled')->first();
                $notificationService = app(\Modules\NotificationManager\Services\NotificationService::class);
                
                $coachName = $request->user()->person->full_name ?? 'المدرب';
                $planName = $template->subscriptionPlan->name ?? 'الاشتراك';
                $dayName = \Carbon\Carbon::parse($request->date)->locale('ar')->translatedFormat('l');

                foreach ($members as $member) {
                    if (!$member->user_id) continue;
                    
                    $playerName = trim($member->first_name . ' ' . $member->last_name);
                    
                    if ($notificationTemplate) {
                        $body = $notificationTemplate->parseBody([
                            'اسم اللاعب' => $playerName,
                            'اسم الاشتراك' => $planName,
                            'اليوم' => $dayName,
                            'التاريخ' => $request->date,
                            'اسم الكوتش' => $coachName,
                            'السبب' => $request->reason,
                        ]);
                        $title = $notificationTemplate->subject ?? 'اعتذار عن إلغاء جلسة تمرين ⚠️';
                    } else {
                        $title = 'اعتذار عن إلغاء جلسة تمرين ⚠️';
                        $body = "أهلاً بك {$playerName}، نعتذر منك عن إلغاء جلستك الخاصة باشتراك {$planName} والمقررة يوم {$dayName} بتاريخ {$request->date} مع الكوتش {$coachName}. سبب الإلغاء: {$request->reason}. نتمنى تفهمك ونراك قريباً!";
                    }

                    $notificationService->createNotification([
                        'title' => $title,
                        'body' => $body,
                        'user_ids' => [$member->user_id],
                        'sender_type' => 'system'
                    ]);
                }
            }
        }

        return $this->successResponse($exception, __('Session canceled successfully for the specified date.'));
    }
}
