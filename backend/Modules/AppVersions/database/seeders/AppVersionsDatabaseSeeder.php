<?php

namespace Modules\AppVersions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppVersionsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds for AppVersions permissions.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'app-version.view-any',
            'app-version.view',
            'app-version.create',
            'app-version.update',
            'app-version.delete',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'sanctum',
            ]);
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // Assign to super_admin and admin roles if they exist
        foreach (['super_admin', 'admin'] as $roleName) {
            foreach (['sanctum', 'web'] as $guard) {
                $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
                if ($role) {
                    $role->givePermissionTo($permissions);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
