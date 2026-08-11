<?php

namespace Modules\MemberManager\Observers;

use Modules\MemberManager\Models\Member;

class MemberObserver
{
    /**
     * Handle the Member "creating" event.
     */
    public function creating(Member $member): void
    {
        if (empty($member->member_number)) {
            $member->member_number = $this->generateUniqueMemberNumber();
        }
    }

    /**
     * Generate a unique member number.
     * Example format: MEM-2024-0001
     */
    protected function generateUniqueMemberNumber(): string
    {
        $year = date('Y');
        $prefix = "MEM-{$year}-";
        
        $lastMember = Member::withTrashed()
            ->where('member_number', 'like', "{$prefix}%")
            ->orderBy('member_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastMember) {
            $lastNumber = (int) \Illuminate\Support\Str::afterLast($lastMember->member_number, '-');
            $sequence = $lastNumber + 1;
        }

        do {
            $candidateNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = Member::withTrashed()->where('member_number', $candidateNumber)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        return $candidateNumber;
    }
}

