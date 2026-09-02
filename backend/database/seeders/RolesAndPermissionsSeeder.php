<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            ['name' => 'super_admin', 'name_ar' => 'مدير النظام العام', 'is_visible' => false],
            ['name' => 'admin',       'name_ar' => 'مدير النادي',       'is_visible' => true],
            ['name' => 'coach',       'name_ar' => 'مدرب',              'is_visible' => true],
            ['name' => 'player',      'name_ar' => 'مشترك / لاعب',     'is_visible' => true],
            ['name' => 'accountant',  'name_ar' => 'محاسب',             'is_visible' => true],
            ['name' => 'reception',   'name_ar' => 'موظف استقبال',      'is_visible' => true],
            ['name' => 'cleaner',     'name_ar' => 'عامل نظافة',        'is_visible' => true],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'web'],
                ['name_ar' => $roleData['name_ar'], 'is_visible' => $roleData['is_visible']]
            );
            Role::updateOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'sanctum'],
                ['name_ar' => $roleData['name_ar'], 'is_visible' => $roleData['is_visible']]
            );
        }
    }
}
