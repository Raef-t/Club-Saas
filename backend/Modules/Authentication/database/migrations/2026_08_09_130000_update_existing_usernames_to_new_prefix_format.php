<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\Authentication\Services\UsernameGeneratorService;

return new class extends Migration
{
    public function up(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $role = 'staff';

            // Check Spatie role first
            $spatieRole = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', User::class)
                ->value('roles.name');

            if ($spatieRole) {
                $role = $spatieRole;
            } else {
                $isMember = DB::table('members')->where('person_id', $user->person_id)->exists();
                if ($isMember) {
                    $role = 'player';
                } else {
                    $staff = DB::table('staff')->where('person_id', $user->person_id)->first();
                    if ($staff && !empty($staff->role)) {
                        $role = $staff->role;
                    }
                }
            }

            $newUsername = UsernameGeneratorService::generateForRole($role);
            $user->update(['username' => $newUsername]);
        }
    }

    public function down(): void
    {
        // No reverse action needed
    }
};
