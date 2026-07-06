<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\ClubManager\Models\Branch;

class RealPlansAndActivitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 0. Get the first branch
        $branch = Branch::first();
        $branchId = $branch ? $branch->id : 1;

        // 1. أولاً: نقوم بإنشاء أنواع الأنشطة الرياضية (Activity Types)
        $fitnessType = ActivityType::create([
            'name' => 'اللياقة البدنية والصالة',
            'branch_id' => $branchId,
            'is_active' => true,
        ]);

        $swimmingType = ActivityType::create([
            'name' => 'الألعاب المائية',
            'branch_id' => $branchId,
            'is_active' => true,
        ]);

        $classType = ActivityType::create([
            'name' => 'الحصص الجماعية',
            'branch_id' => $branchId,
            'is_active' => true,
        ]);

        // 2. ثانياً: نقوم بإنشاء الأنشطة الرياضية (Activities) وتعيين النوع لها
        $ironActivity = Activity::create([
            'name' => 'صالة الحديد والأجهزة',
            'description' => 'دخول صالة كمال الأجسام والأجهزة الرياضية العامة',
            'activity_type_id' => $fitnessType->id,
            'branch_id' => $branchId,
            'default_capacity' => 100,
            'is_private_equipment' => false,
            'gender_allowed' => 'both',
            'is_active' => true,
        ]);

        $poolActivity = Activity::create([
            'name' => 'المسبح الأولمبي',
            'description' => 'استخدام المسبح للسباحة الحرة',
            'activity_type_id' => $swimmingType->id,
            'branch_id' => $branchId,
            'default_capacity' => 20,
            'is_private_equipment' => false,
            'gender_allowed' => 'both',
            'is_active' => true,
        ]);

        $crossFitActivity = Activity::create([
            'name' => 'كلاس الكروس فيت',
            'description' => 'حصص جماعية مكثفة بإشراف مدرب',
            'activity_type_id' => $classType->id,
            'branch_id' => $branchId,
            'default_capacity' => 15,
            'is_private_equipment' => false,
            'gender_allowed' => 'both',
            'is_active' => true,
        ]);


        // 3. ثالثاً: نقوم بإنشاء خطط الاشتراك (Subscription Plans)

        // الخطة الأولى: اشتراك شامل (شهر واحد)
        $goldPlan = SubscriptionPlan::create([
            'name' => 'الاشتراك الذهبي الشامل (شهر)',
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
            'name' => 'باقة 10 حصص كروس فيت',
            'type' => 'session', // اشتراك يعتمد على عدد الجلسات
            'duration_days' => 90, // الجلسات صالحة للاستخدام خلال 90 يوم
            'session_count' => 10, // 10 جلسات
            'max_freeze_count' => 0,
            'max_freeze_days' => 0,
            'base_price' => 150.00,
            'is_active' => true,
        ]);


        // 4. رابعاً: نربط خطط الاشتراك بالأنشطة (Plan Activities)

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
