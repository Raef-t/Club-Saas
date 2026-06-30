<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sports\Models\Activity;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;

class RealPlansAndActivitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. أولاً: نقوم بإنشاء الأنشطة الرياضية (Activities) التي يقدمها النادي
        $ironActivity = Activity::create([
            'name' => ['ar' => 'صالة الحديد والأجهزة', 'en' => 'Gym & Iron'],
            'description' => 'دخول صالة كمال الأجسام والأجهزة الرياضية العامة',
            'type' => 'fitness',
            'default_capacity' => 100,
            'is_private_equipment' => false,
            'gender_allowed' => 'both', // أو male حسب النادي
            'is_active' => true,
        ]);

        $poolActivity = Activity::create([
            'name' => ['ar' => 'المسبح الأولمبي', 'en' => 'Olympic Pool'],
            'description' => 'استخدام المسبح للسباحة الحرة',
            'type' => 'swimming',
            'default_capacity' => 20,
            'is_private_equipment' => false,
            'gender_allowed' => 'both',
            'is_active' => true,
        ]);

        $crossFitActivity = Activity::create([
            'name' => ['ar' => 'كلاس الكروس فيت', 'en' => 'CrossFit Class'],
            'description' => 'حصص جماعية مكثفة بإشراف مدرب',
            'type' => 'class',
            'default_capacity' => 15,
            'is_private_equipment' => false,
            'gender_allowed' => 'both',
            'is_active' => true,
        ]);


        // 2. ثانياً: نقوم بإنشاء خطط الاشتراك (Subscription Plans)

        // الخطة الأولى: اشتراك شامل (شهر واحد)
        $goldPlan = SubscriptionPlan::create([
            'name' => ['ar' => 'الاشتراك الذهبي الشامل (شهر)', 'en' => 'Gold Full Access (1 Month)'],
            'type' => 'duration', // اشتراك يعتمد على المدة الزمنية
            'duration_days' => 30, // صالح لمدة 30 يوم
            'session_count' => null, 
            'max_freeze_count' => 1, // يسمح بتجميد الاشتراك مرة واحدة
            'max_freeze_days' => 7,  // التجميد يكون لمدة 7 أيام كحد أقصى
            'base_price' => 250.00,
            'is_active' => true,
        ]);

        // الخطة الثانية: اشتراك حصص فقط (10 جلسات كروس فيت)
        $sessionsPlan = SubscriptionPlan::create([
            'name' => ['ar' => 'باقة 10 حصص كروس فيت', 'en' => '10 CrossFit Sessions'],
            'type' => 'session', // اشتراك يعتمد على عدد الجلسات
            'duration_days' => 90, // الجلسات صالحة للاستخدام خلال 90 يوم
            'session_count' => 10, // 10 جلسات
            'max_freeze_count' => 0,
            'max_freeze_days' => 0,
            'base_price' => 150.00,
            'is_active' => true,
        ]);


        // 3. ثالثاً: نربط خطط الاشتراك بالأنشطة (Plan Activities)

        // بالنسبة للاشتراك الذهبي الشامل:
        // - يحق له دخول صالة الحديد بشكل "مفتوح" (غير محدود)
        SubscriptionPlanActivity::create([
            'plan_id' => $goldPlan->id,
            'activity_id' => $ironActivity->id,
            'is_unlimited' => true,
            'sessions_count' => null,
        ]);
        
        // - يحق له دخول المسبح بشكل "مفتوح"
        SubscriptionPlanActivity::create([
            'plan_id' => $goldPlan->id,
            'activity_id' => $poolActivity->id,
            'is_unlimited' => true,
            'sessions_count' => null,
        ]);

        // - لا يحق له دخول حصص الكروس فيت أبداً في هذا الاشتراك (لذلك لا نربطها)

        // ----------------------------------------------------

        // بالنسبة لاشتراك الـ 10 حصص:
        // - يحق له دخول الكروس فيت لـ 10 مرات فقط (محدود)
        SubscriptionPlanActivity::create([
            'plan_id' => $sessionsPlan->id,
            'activity_id' => $crossFitActivity->id,
            'is_unlimited' => false,
            'sessions_count' => 10,
        ]);

    }
}
