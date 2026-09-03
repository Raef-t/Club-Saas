<?php

use Illuminate\Database\Migrations\Migration;
use Modules\NotificationManager\Models\NotificationTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        NotificationTemplate::updateOrCreate(
            ['system_key' => 'payroll_due_reminder'],
            [
                'name' => 'تنبيه استحقاق مسير الرواتب',
                'system_key' => 'payroll_due_reminder',
                'subject' => 'تنبيه: تم توليد  الرواتب لفرع {اسم_الفرع} 💰',
                'body' => 'إدارة النادي الكريمة، اليوم هو موعد توليد رواتب شهر {شهر} لفرع {اسم_الفرع}. يرجى مراجعة الرواتب واعتمادها.',
                'variables' => ['اسم_الفرع', 'شهر', 'تاريخ_الاستحقاق'],
                'is_active' => true,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        NotificationTemplate::where('system_key', 'payroll_due_reminder')->delete();
    }
};
