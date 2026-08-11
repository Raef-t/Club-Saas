<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply branch isolation if a user is logged in
        if (Auth::check()) {
            $user = Auth::user();
            if (isset($user->person_id)) {
                // Fetch branch_id directly from the staff table using DB builder to avoid direct model coupling
                $branchId = DB::table('staff')
                    ->join('staff_branches', 'staff.id', '=', 'staff_branches.staff_id')
                    ->where('staff.person_id', $user->person_id)
                    ->value('staff_branches.branch_id');

                if ($branchId) {
                    $builder->where($model->getTable() . '.branch_id', $branchId);
                }
            }
        }
    }
}
