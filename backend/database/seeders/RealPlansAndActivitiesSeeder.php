<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\StaffActivity;
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
            'is_active' => true,
        ]);

        $swimmingType = ActivityType::create([
            'name' => 'الألعاب المائية',
            'is_active' => true,
        ]);

        $classType = ActivityType::create([
            'name' => 'الحصص الجماعية',
            'is_active' => true,
        ]);

        // 2. ثانياً: نقوم بإنشاء الأنشطة الرياضية (Activities) وتعيين النوع لها
        $ironActivity = Activity::create([
            'name' => 'صالة الحديد والأجهزة',
            'description' => 'دخول صالة كمال الأجسام والأجهزة الرياضية العامة',
            'activity_type_id' => $fitnessType->id,
            'branch_id' => $branchId,
            'is_private_equipment' => false,
            'is_active' => true,
        ]);

        $poolActivity = Activity::create([
            'name' => 'المسبح الأولمبي',
            'description' => 'استخدام المسبح للسباحة الحرة',
            'activity_type_id' => $swimmingType->id,
            'branch_id' => $branchId,
            'is_private_equipment' => false,
            'is_active' => true,
        ]);

        $crossFitActivity = Activity::create([
            'name' => 'كلاس الكروس فيت',
            'description' => 'حصص جماعية مكثفة بإشراف مدرب',
            'activity_type_id' => $classType->id,
            'branch_id' => $branchId,
            'is_private_equipment' => false,
            'is_active' => true,
        ]);

        $staff = \Modules\StaffManager\Models\Staff::first();
        $staffId = $staff ? $staff->id : 1;

        $ironStaffActivity = StaffActivity::firstOrCreate(['activity_id' => $ironActivity->id, 'staff_id' => $staffId]);
        $poolStaffActivity = StaffActivity::firstOrCreate(['activity_id' => $poolActivity->id, 'staff_id' => $staffId]);
        $crossFitStaffActivity = StaffActivity::firstOrCreate(['activity_id' => $crossFitActivity->id, 'staff_id' => $staffId]);


        // 3. ثالثاً: نقوم بإنشاء خطط الاشتراك (Subscription Plans)

        // الخطة الأولى: اشتراك شامل (شهر واحد)
        $goldPlan = SubscriptionPlan::create([
            'name' => 'الاشتراك الذهبي الشامل (شهر)',
            'branch_id' => $branchId,
            'session_count' => null,
            'base_price' => 250.00,
            'status' => 'active',
        ]);

        // الخطة الثانية: اشتراك حصص فقط (10 جلسات كروس فيت)
        $sessionsPlan = SubscriptionPlan::create([
            'name' => 'باقة 10 حصص كروس فيت',
            'branch_id' => $branchId,
            'session_count' => 10,
            'base_price' => 150.00,
            'status' => 'active',
        ]);


        // 4. رابعاً: نربط خطط الاشتراك بالأنشطة (Plan Activities)

        // بالنسبة للاشتراك الذهبي الشامل:
        // - يحق له دخول صالة الحديد بشكل "مفتوح" (غير محدود)
        SubscriptionPlanActivity::create([
            'plan_id' => $goldPlan->id,
            'staff_activity_id' => $ironStaffActivity->id,
        ]);
        
        // - يحق له دخول المسبح بشكل "مفتوح"
        SubscriptionPlanActivity::create([
            'plan_id' => $goldPlan->id,
            'staff_activity_id' => $poolStaffActivity->id,
        ]);

        // بالنسبة لاشتراك الـ 10 حصص:
        // - يحق له دخول الكروس فيت لـ 10 مرات فقط (محدود)
        SubscriptionPlanActivity::create([
            'plan_id' => $sessionsPlan->id,
            'staff_activity_id' => $crossFitStaffActivity->id,
        ]);

    }
}
