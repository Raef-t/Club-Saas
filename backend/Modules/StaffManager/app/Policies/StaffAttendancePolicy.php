<?php

namespace Modules\StaffManager\Policies;

use Modules\AttendanceManager\Contracts\AttendancePolicy;
use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\DTOs\AttendanceDecision;
use Modules\StaffManager\Models\Staff;
use Modules\ClubManager\Models\Branch;

class StaffAttendancePolicy implements AttendancePolicy
{
    /**
     * Authorize staff check-in based on club ownership and branch assignment.
     *
     * @param CheckInAttempt $attempt
     * @return AttendanceDecision
     */
    public function authorize(CheckInAttempt $attempt): AttendanceDecision
    {
        $staff = Staff::find($attempt->attendableId);

        if (!$staff) {
            return AttendanceDecision::deny("Staff member not found.");
        }

        if (!$staff->is_active) {
            return AttendanceDecision::deny("Staff member is inactive.");
        }

        // Verify that the check-in branch belongs to the specified club
        $checkInBranch = Branch::find($attempt->branchId);
        if (!$checkInBranch || $checkInBranch->club_id != $attempt->clubId) {
            return AttendanceDecision::deny("The check-in branch does not belong to the specified club.");
        }

        $branchIds = $staff->branches()->pluck('staff_branches.branch_id')->toArray();

        // Verify that the staff member is assigned to the attempt's check-in branch
        if (!in_array($attempt->branchId, $branchIds)) {
            return AttendanceDecision::deny("Staff member is not assigned to this check-in branch.");
        }

        return AttendanceDecision::allow([
            'role' => $staff->role,
            'employment_type' => $staff->employment_type,
        ]);
    }
}
