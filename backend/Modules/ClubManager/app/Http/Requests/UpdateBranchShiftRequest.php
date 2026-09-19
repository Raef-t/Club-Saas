<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|different:start_time',
            'gender_allowed' => 'sometimes|string|in:male,female,mixed',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->failed()) {
                $shiftId = $this->route('shift');
                $shift = \Modules\ClubManager\Models\BranchShift::findOrFail($shiftId);
                $startTime = $this->input('start_time', $shift->start_time);
                $endTime = $this->input('end_time', $shift->end_time);

                if ($startTime === $endTime) {
                    $validator->errors()->add('end_time', __('يجب أن يختلف وقت النهاية عن وقت البداية.'));
                    return;
                }

                $existingShifts = \Modules\ClubManager\Models\BranchShift::where('branch_id', $this->route('branch'))
                    ->where('id', '!=', $shiftId)
                    ->get();

                foreach ($existingShifts as $existingShift) {
                    if ($this->shiftsOverlap($startTime, $endTime, $existingShift->start_time, $existingShift->end_time)) {
                        $validator->errors()->add('start_time', __('يوجد تعارض في الوقت مع وردية أخرى في نفس اليوم للفرع.'));
                        break;
                    }
                }
            }
        });
    }

    /**
     * Determine if two daily shifts overlap, supporting overnight shifts crossing midnight.
     */
    private function shiftsOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $start1 = substr($start1, 0, 5);
        $end1 = substr($end1, 0, 5);
        $start2 = substr($start2, 0, 5);
        $end2 = substr($end2, 0, 5);

        $intervals1 = $this->toIntervals($start1, $end1);
        $intervals2 = $this->toIntervals($start2, $end2);

        foreach ($intervals1 as $i1) {
            foreach ($intervals2 as $i2) {
                if ($i1['start'] < $i2['end'] && $i2['start'] < $i1['end']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Splits a daily time range into standard comparable intervals.
     */
    private function toIntervals(string $start, string $end): array
    {
        if ($start < $end) {
            return [['start' => $start, 'end' => $end]];
        }

        return [
            ['start' => $start, 'end' => '24:00'],
            ['start' => '00:00', 'end' => $end],
        ];
    }
}
