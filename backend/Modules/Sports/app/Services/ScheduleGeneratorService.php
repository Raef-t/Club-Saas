<?php

namespace Modules\Sports\Services;

use Modules\Sports\Models\SportSessionTemplate;
use Modules\Sports\Models\SportSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class ScheduleGeneratorService
{
    /**
     * Generate sports sessions from active templates for a given date range.
     *
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @param int|null $planId Optional filter
     * @return int Number of generated sessions
     */
    public function generateSessions(string $startDate, string $endDate, ?int $planId = null): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = SportSessionTemplate::active();
        if ($planId) {
            $query->where('plan_id', $planId);
        }
        $templates = $query->get();

        $generatedCount = 0;

        DB::transaction(function () use ($templates, $start, $end, &$generatedCount) {
            foreach ($templates as $template) {
                // Find all dates between start and end that match the template's day of week
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    if ($date->dayOfWeek === $template->day_of_week) {
                        // Determine start and end datetime
                        $sessionStartTime = Carbon::parse($date->toDateString() . ' ' . $template->start_time->format('H:i:s'));
                        $sessionEndTime = Carbon::parse($date->toDateString() . ' ' . $template->end_time->format('H:i:s'));

                        // Avoid duplicate generation
                        $exists = SportSession::where('template_id', $template->id)
                            ->where('start_time', $sessionStartTime)
                            ->exists();

                        if (!$exists) {
                            SportSession::create([
                                'plan_id' => $template->plan_id,
                                'facility_id' => $template->facility_id,
                                'start_time' => $sessionStartTime,
                                'end_time' => $sessionEndTime,
                                'max_players' => $template->max_players,
                                'gender_allowed' => $template->gender_allowed,
                                'status' => 'scheduled',
                                'template_id' => $template->id,
                            ]);
                            $generatedCount++;
                        }
                    }
                }
            }
        });

        return $generatedCount;
    }
}
