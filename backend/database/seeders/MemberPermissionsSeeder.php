<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MemberPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates all permissions related to the MemberManager module.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // ─── Members (اللاعبون) ───────────────────────────────────────────
            'member.view-any',      // جلب قائمة الأعضاء
            'member.view',          // عرض عضو محدد
            'member.create',        // تسجيل عضو جديد
            'member.update',        // تعديل بيانات العضو
            'member.update-photo',  // تحديث صورة العضو
            'member.delete',        // حذف العضو
            'member.restore',       // استعادة العضو المحذوف
            'member.stats',         // عرض إحصائيات الأعضاء

            // ─── Health Profiles (الملفات الصحية) ─────────────────────────────
            'member.health-profile.view-any', // جلب قائمة الملفات الصحية
            'member.health-profile.view',     // عرض ملف صحي محدد
            'member.health-profile.create',   // إنشاء ملف صحي
            'member.health-profile.update',   // تعديل ملف صحي
            'member.health-profile.delete',   // حذف ملف صحي

            // ─── Measurements (القياسات) ───────────────────────────────────────
            'member.measurement.view-any', // جلب قائمة القياسات
            'member.measurement.view',     // عرض قياس محدد
            'member.measurement.create',   // إضافة قياس جديد
            'member.measurement.update',   // تعديل قياس
            'member.measurement.delete',   // حذف قياس
            'member.measurement.report',   // عرض تقرير القياسات

            // ─── Unavailabilities (أوقات عدم التوفر) ──────────────────────────
            'member.unavailability.view-any', // جلب أوقات عدم التوفر
            'member.unavailability.create',   // إضافة وقت عدم توفر
            'member.unavailability.delete',   // حذف وقت عدم توفر
        ];

        $created = 0;
        $existing = 0;

        foreach ($permissions as $permission) {
            $result = Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'sanctum',
            ]);

            $result->wasRecentlyCreated ? $created++ : $existing++;
        }

        $this->command->info("✅ MemberManager permissions done:");
        $this->command->info("   ├─ Created : {$created}");
        $this->command->info("   └─ Already existed: {$existing}");
        $this->command->info("   Total : " . count($permissions) . " permissions");
    }
}
