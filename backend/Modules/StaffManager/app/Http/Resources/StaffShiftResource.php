<?php
namespace Modules\StaffManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaffShiftResource extends JsonResource
{
    public function toArray($request) {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'branch_shift_id' => $this->branch_shift_id,
            'branch_shift' => $this->whenLoaded('branchShift', function () {
                return [
                    'id' => $this->branchShift->id,
                    'day_of_week' => $this->branchShift->day_of_week,
                    'start_time' => $this->branchShift->start_time,
                    'end_time' => $this->branchShift->end_time,
                ];
            }),
        ];
    }
}
