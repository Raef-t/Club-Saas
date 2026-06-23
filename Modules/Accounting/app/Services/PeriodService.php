<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccPeriod;

class PeriodService
{
    /**
     * جلب الفترة المفتوحة الحالية
     */
    public function getCurrentOpenPeriod(): ?AccPeriod
    {
        return AccPeriod::where('status', 'open')
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->first();
    }

    /**
     * إغلاق فترة (يمنع القيود الجديدة لكن يمكن إعادة فتحها)
     */
    public function closePeriod(AccPeriod $period, ?int $userId = null): AccPeriod
    {
        if ($period->isLocked()) {
            throw new \Exception('الفترة مقفلة نهائياً ولا يمكن تغييرها.');
        }
        if ($period->status === 'closed') {
            throw new \Exception('الفترة مغلقة مسبقاً.');
        }

        $period->update([
            'status'    => 'closed',
            'closed_by' => $userId ?? Auth::id(),
            'closed_at' => now(),
        ]);

        return $period->refresh();
    }

    /**
     * قفل فترة نهائياً (لا يمكن فتحها أو التعديل عليها)
     */
    public function lockPeriod(AccPeriod $period, ?int $userId = null): AccPeriod
    {
        if ($period->isLocked()) {
            throw new \Exception('الفترة مقفلة مسبقاً.');
        }

        $period->update([
            'status'    => 'locked',
            'closed_by' => $userId ?? Auth::id(),
            'closed_at' => now(),
        ]);

        return $period->refresh();
    }

    /**
     * إعادة فتح فترة مغلقة (غير مقفلة)
     */
    public function reopenPeriod(AccPeriod $period): AccPeriod
    {
        if ($period->isLocked()) {
            throw new \Exception('الفترة مقفلة نهائياً. لا يمكن إعادة فتحها.');
        }

        $period->update([
            'status'    => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ]);

        return $period->refresh();
    }
}
