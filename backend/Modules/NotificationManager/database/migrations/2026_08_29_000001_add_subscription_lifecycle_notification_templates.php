<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\NotificationManager\Models\NotificationTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = [
            [
                'name' => 'حذف الاشتراك واسترداد المبلغ',
                'system_key' => 'subscription_deleted_refunded',
                'subject' => 'تم حذف اشتراكك واسترداد المبلغ 💰',
                'body' => 'أهلاً بك {اسم اللاعب}، نود إعلامك بأنه تم حذف اشتراكك "{اسم الاشتراك}" واسترداد المبلغ بنجاح.{السبب}',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'السبب'],
                'is_active' => true,
            ],
            [
                'name' => 'حذف الاشتراك',
                'system_key' => 'subscription_deleted',
                'subject' => 'تم حذف اشتراكك 🗑️',
                'body' => 'أهلاً بك {اسم اللاعب}، نود إعلامك بأنه تم حذف اشتراكك "{اسم الاشتراك}".{السبب}',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'السبب'],
                'is_active' => true,
            ],
            [
                'name' => 'استرجاع الاشتراك',
                'system_key' => 'subscription_restored',
                'subject' => 'تم استرجاع اشتراكك ♻️',
                'body' => 'أهلاً بك {اسم اللاعب}، تم استرجاع وتفعيل اشتراكك "{اسم الاشتراك}" بنجاح. نتمنى لك تدريباً ممتعاً!',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك'],
                'is_active' => true,
            ],
            [
                'name' => 'تفعيل اشتراك جديد',
                'system_key' => 'subscription_created',
                'subject' => 'تم تفعيل اشتراكك بنجاح 🎉',
                'body' => 'أهلاً بك {اسم اللاعب}، يسعدنا انضمامك! تم تفعيل اشتراكك "{اسم الاشتراك}" بنجاح حتى تاريخ {تاريخ الانتهاء}. نتمنى لك تدريباً ممتعاً وموفقاً!',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'تاريخ الانتهاء'],
                'is_active' => true,
            ],
            [
                'name' => 'تجديد الاشتراك',
                'system_key' => 'subscription_renewed',
                'subject' => 'تم تجديد اشتراكك بنجاح 🔄',
                'body' => 'أهلاً بك {اسم اللاعب}، تم تجديد اشتراكك "{اسم الاشتراك}" بنجاح حتى تاريخ {تاريخ الانتهاء}. نشكر ثقتك واستمرارك معنا!',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'تاريخ الانتهاء'],
                'is_active' => true,
            ],
            [
                'name' => 'إلغاء الاشتراك',
                'system_key' => 'subscription_cancelled',
                'subject' => 'تم إلغاء اشتراكك ❌',
                'body' => 'أهلاً بك {اسم اللاعب}، نود إعلامك بأنه تم إلغاء اشتراكك "{اسم الاشتراك}".{السبب}',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'السبب'],
                'is_active' => true,
            ],
            [
                'name' => 'تسجيل دفعة مالية للاشتراك',
                'system_key' => 'subscription_payment_recorded',
                'subject' => 'تم تسجيل دفعة مالية 💳',
                'body' => 'أهلاً بك {اسم اللاعب}، تم استلام دفعة مالية بقيمة {المبلغ} لاشتراكك "{اسم الاشتراك}". المبلغ المتبقي: {المبلغ المتبقي}.',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'المبلغ', 'المبلغ المتبقي'],
                'is_active' => true,
            ],
            [
                'name' => 'الاشتراك في عرض',
                'system_key' => 'subscription_offer_created',
                'subject' => 'تم الاشتراك في العرض بنجاح 🎉',
                'body' => 'أهلاً بك {اسم اللاعب}، تم تفعيل اشتراكك في عرض "{اسم العرض}" بنجاح. نتمنى لك تجربة رياضية مميزة!',
                'variables' => ['اسم اللاعب', 'اسم العرض'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['system_key' => $template['system_key']],
                $template
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'subscription_deleted_refunded',
            'subscription_deleted',
            'subscription_restored',
            'subscription_created',
            'subscription_renewed',
            'subscription_cancelled',
            'subscription_payment_recorded',
            'subscription_offer_created',
        ];

        NotificationTemplate::whereIn('system_key', $keys)->delete();
    }
};
