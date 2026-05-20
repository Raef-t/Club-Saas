<?php

namespace Modules\MemberManager\Observers;

use Modules\MemberManager\Models\Member;
use Illuminate\Support\Str;

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

        if (empty($member->barcode_qr_code)) {
            $member->barcode_qr_code = $this->generateUniqueBarcode($member->member_number);
        }
    }

    /**
     * Generate a unique barcode based on member number.
     */
    protected function generateUniqueBarcode($memberNumber): string
    {
        // Simple professional format: MEM-RAND
        $random = strtoupper(Str::random(6));
        return "{$memberNumber}-{$random}";
    }

    /**
     * Generate a unique member number.
     * Example format: MEM-2024-0001
     */
    protected function generateUniqueMemberNumber(): string
    {
        $year = date('Y');
        $prefix = "MEM-{$year}-";
        
        $lastMember = Member::withoutGlobalScopes()
            ->where('member_number', 'like', "{$prefix}%")
            ->orderBy('member_number', 'desc')
            ->first();

        if ($lastMember) {
            $lastNumber = (int) Str::afterLast($lastMember->member_number, '-');
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }
}
