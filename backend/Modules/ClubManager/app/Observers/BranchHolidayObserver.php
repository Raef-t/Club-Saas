<?php

namespace Modules\ClubManager\Observers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\ClubManager\Models\BranchHoliday;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Services\NotificationService;
use Modules\MemberManager\Models\Member;
use Modules\StaffManager\Models\Staff;
use Modules\Authentication\Models\User;

class BranchHolidayObserver
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the BranchHoliday "created" event.
     */
    public function created(BranchHoliday $holiday): void
    {
        // نرسل إشعار فقط للإجازات ذات التواريخ المحددة
        if ($holiday->type === 'specific_dates') {
            $this->sendHolidayNotification($holiday);
        }
    }

    protected function sendHolidayNotification(BranchHoliday $holiday): void
    {
        try {
            $template = NotificationTemplate::where('system_key', 'club_closure')->first();

            if (!$template) {
                Log::warning('لم يتم العثور على قالب الإشعار club_closure لإرسال إشعار العطلة.');
                return;
            }

            // تجهيز المتغيرات للقالب
            // نأخذ اسم النادي من اسم الفرع بناءً على ملاحظة العميل
            $branchName = $holiday->branch->name ?? 'الفرع';
            
            // استخدام Carbon لمعرفة اسم اليوم
            $startDate = Carbon::parse($holiday->start_date);
            $endDate = Carbon::parse($holiday->end_date);
            
            // تحويل أسماء الأيام للعربية إذا لم تكن لغة التطبيق عربية (نعتمد على locale أو نكتب مصفوفة بسيطة)
            // استخدام دالة translatedFormat إذا كان الـ locale مدعوماً، أو الاعتماد على dayName
            $startDay = $startDate->locale('ar')->translatedFormat('l');
            $endDay = $endDate->locale('ar')->translatedFormat('l');

            $body = $template->parseBody([
                'اسم النادي' => $branchName,
                'بداية تاريخ' => $holiday->start_date->format('Y-m-d'),
                'يوم البداية' => $startDay,
                'نهاية التاريخ' => $holiday->end_date->format('Y-m-d'),
                'يوم النهاية' => $endDay,
                'السبب' => $holiday->reason ?? 'عطلة رسمية',
            ]);

            // جلب المستخدمين التابعين لهذا الفرع (لاعبين وموظفين)
            $memberPersonIds = Member::where('branch_id', $holiday->branch_id)->pluck('person_id');
            
            $staffPersonIds = Staff::whereHas('branches', function ($q) use ($holiday) {
                $q->where('branch_id', $holiday->branch_id);
            })->pluck('person_id');

            $allPersonIds = $memberPersonIds->merge($staffPersonIds)->unique();
            
            if ($allPersonIds->isEmpty()) {
                return;
            }

            $userIds = User::whereIn('person_id', $allPersonIds)->where('is_active', true)->pluck('id')->toArray();

            if (empty($userIds)) {
                return;
            }

            // إرسال الإشعار عبر NotificationService
            $this->notificationService->createNotification([
                'title' => $template->subject ?? 'إغلاق النادي',
                'body' => $body,
                'sender_id' => null, // من النظام
                'sender_type' => 'system',
                'user_ids' => $userIds,
            ]);

            Log::info("تم إرسال إشعار العطلة بنجاح لفرع: {$branchName}", ['users_count' => count($userIds)]);

        } catch (\Exception $e) {
            Log::error('خطأ أثناء إرسال إشعار العطلة: ' . $e->getMessage());
        }
    }
}
