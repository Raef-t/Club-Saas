<?php

namespace Modules\StaffManager\Domain\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\StaffManager\Models\StaffShift;

class NoOverlappingShiftsRule implements Rule
{
    protected $staffId;
    protected $dayOfWeek;
    protected $startTime;
    protected $endTime;

    public function __construct($staffId, $dayOfWeek, $startTime, $endTime)
    {
        $this->staffId = $staffId;
        $this->dayOfWeek = $dayOfWeek;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function passes($attribute, $value): bool
    {
        // Check if there is any shift for the same staff on the same day that overlaps
        return !StaffShift::where('staff_id', $this->staffId)
            ->where('day_of_week', $this->dayOfWeek)
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->startTime, $this->endTime])
                      ->orWhereBetween('end_time', [$this->startTime, $this->endTime])
                      ->orWhere(function ($q) {
                          $q->where('start_time', '<=', $this->startTime)
                            ->where('end_time', '>=', $this->endTime);
                      });
            })
            ->exists();
    }

    public function message(): string
    {
        return __('This shift overlaps with an existing shift for this staff member.');
    }
}
