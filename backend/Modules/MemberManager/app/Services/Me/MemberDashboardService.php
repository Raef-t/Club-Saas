<?php

namespace Modules\MemberManager\Services\Me;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\AttendanceManager\Models\Attendance;

class MemberDashboardService
{
    protected PersonSharedServiceInterface $personService;

    public function __construct(PersonSharedServiceInterface $personService)
    {
        $this->personService = $personService;
    }

    /**
     * Aggregate all dashboard data for a given member.
     */
    public function getDashboardData(int $memberId, int $personId): array
    {
        $steps = [
            'profile' => fn() => $this->getProfile($personId),
            'subscriptions' => fn() => $this->getSubscriptions($memberId),
            'stats' => fn() => $this->getStats($memberId),
            'recent_activities' => fn() => $this->getRecentActivities($memberId),
            'upcoming_events' => fn() => $this->getUpcomingEvents($memberId),
        ];

        $data = [];

        foreach ($steps as $key => $step) {
            try {
                \Illuminate\Support\Facades\Log::info("Running step: {$key}");
                $data[$key] = $step();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Error in Dashboard Step [{$key}]: " . $e->getMessage(), [
                    'member_id' => $memberId,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                $data[$key] = null;
                // Uncomment to rethrow and see the 500 error in the API response:
                throw $e;
            }
        }

        return $data;
    }

    /**
     * Profile header: name, avatar.
     */
    protected function getProfile(int $personId): array
    {
        $person = $this->personService->getPersonById($personId);

        if (!$person) {
            return [
                'first_name' => null,
                'full_name' => null,
                'avatar_url' => null,
            ];
        }

        $nameParts = explode(' ', $person->fullName, 2);

        return [
            'first_name' => $nameParts[0] ?? $person->fullName,
            'full_name' => $person->fullName,
            'avatar_url' => $person->photoUrl,
        ];
    }

    /**
     * Subscriptions list: all subscriptions details.
     */
    public function getSubscriptions(int $memberId): array
    {
        $subscriptions = PlayerSubscription::with(['plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items'])
            ->where('member_id', $memberId)
            ->latest()
            ->get();

        if ($subscriptions->isEmpty()) {
            return [];
        }

        $member = DB::table('members')->where('id', $memberId)->first();

        return $subscriptions->map(function ($subscription) use ($member) {
            $planActivities = $subscription->plan ? $subscription->plan->planActivities : collect();

            $activities = $subscription->items->values()->map(function ($item, $index) use ($planActivities) {
                $planActivity = $planActivities->get($index);
                $staffActivity = $planActivity?->staffActivity;
                $activity = $staffActivity?->activity;
                $coach = $staffActivity?->staff;

                // Determine activity name
                $activityName = $activity->name ?? null;
                if (is_string($activityName) && json_decode($activityName)) {
                    $decoded = json_decode($activityName, true);
                    $activityName = $decoded[app()->getLocale()] ?? $decoded['ar'] ?? $decoded['en'] ?? $activityName;
                }

                $coachName = $coach?->person?->full_name ?? null;

                return [
                    'id' => $item->id,
                    'activity_name' => $activityName,
                    'coach_name' => $coachName,
                    'total_sessions' => $item->sessions_allocated,
                    'remaining_sessions' => $item->sessions_allocated - ($item->sessions_consumed ?? 0),
                    'is_unlimited' => (bool) $item->is_unlimited,
                ];
            });

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'subscription_number' => $subscription->plan->subscription_number ?? null,
                'plan_name' => $subscription->plan->name ?? null,
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
                'formatted_end_date' => $subscription->end_date?->format('d/m/Y'),
                'membership_number' => $member->member_number ?? null,
                'price' => (float) ($subscription->total_amount ?? $subscription->plan->base_price ?? 0),
                'formatted_price' => ($subscription->total_amount ?? $subscription->plan->base_price ?? 0) . '$',
                'remaining_sessions' => $activities->where('is_unlimited', false)->sum('remaining_sessions'),
                'activities' => $activities->toArray(),
            ];
        })->toArray();
    }

    /**
     * Stats grid: sessions, attendance, training hours, last payment.
     */
    protected function getStats(int $memberId): array
    {
        // Remaining sessions from active subscription
        $remainingSessions = \Illuminate\Support\Facades\DB::table('player_subscription_items')
            ->join('player_subscriptions', 'player_subscription_items.player_subscription_id', '=', 'player_subscriptions.id')
            ->where('player_subscriptions.member_id', $memberId)
            ->where('player_subscriptions.status', 'active')
            ->where('player_subscription_items.is_unlimited', false)
            ->sum(\Illuminate\Support\Facades\DB::raw('player_subscription_items.sessions_allocated - player_subscription_items.sessions_consumed'));

        // Total attendance count
        $totalAttendance = Attendance::where('attendable_type', 'player_subscription')
            ->whereIn('attendable_id', function ($query) use ($memberId) {
                $query->select('id')
                    ->from('player_subscriptions')
                    ->where('member_id', $memberId);
            })
            ->count();

        // Training hours (sum of durations)
        $trainingMinutes = Attendance::where('attendable_type', 'player_subscription')
            ->whereIn('attendable_id', function ($query) use ($memberId) {
                $query->select('id')
                    ->from('player_subscriptions')
                    ->where('member_id', $memberId);
            })
            ->whereNotNull('check_out_at')
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, check_in_at, check_out_at)) as total_minutes')
            ->value('total_minutes') ?? 0;

        $trainingHours = round($trainingMinutes / 60, 1);

        // Last payment
        $lastPayment = Payment::whereIn('invoice_id', function ($query) use ($memberId) {
                $query->select('id')
                    ->from('invoices')
                    ->where('member_id', $memberId);
            })
            ->where('status', 'completed')
            ->latest()
            ->first();

        return [
            'remaining_sessions' => (int) $remainingSessions,
            'total_attendance' => $totalAttendance,
            'training_hours' => $trainingHours,
            'last_payment' => $lastPayment ? (float) $lastPayment->amount : null,
            'formatted_last_payment' => $lastPayment ? $lastPayment->amount . '$' : null,
        ];
    }

    /**
     * Recent activities: last 5 attendance records.
     */
    protected function getRecentActivities(int $memberId): array
    {
        $records = Attendance::where('attendable_type', 'player_subscription')
            ->whereIn('attendable_id', function ($query) use ($memberId) {
                $query->select('id')
                    ->from('player_subscriptions')
                    ->where('member_id', $memberId);
            })
            ->orderByDesc('check_in_at')
            ->limit(5)
            ->get();

        return $records->map(function ($record) {
            $durationHours = null;
            if ($record->check_in_at && $record->check_out_at) {
                $durationHours = round(
                    Carbon::parse($record->check_in_at)->diffInMinutes(Carbon::parse($record->check_out_at)) / 60,
                    1
                );
            }

            return [
                'id' => $record->id,
                'title' => __('Training Session'),
                'description' => $record->check_in_at
                    ? Carbon::parse($record->check_in_at)->format('H:i')
                    : null,
                'duration' => $durationHours,
                'duration_label' => $durationHours ? $durationHours . ' ' . __('hours') : null,
                'created_at' => $record->check_in_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Upcoming events: next scheduled sessions.
     */
    protected function getUpcomingEvents(int $memberId): array
    {
        // Get active templates with their plans
        $templates = DB::table('sport_session_templates')
            ->join('subscription_plans', 'sport_session_templates.plan_id', '=', 'subscription_plans.id')
            ->where('sport_session_templates.is_active', true)
            ->select([
                'sport_session_templates.id',
                'subscription_plans.name as plan_name',
                'sport_session_templates.day_of_week',
                'sport_session_templates.start_time',
                'sport_session_templates.end_time',
                'subscription_plans.max_subscribers as max_players',
            ])
            ->get();

        // Get future canceled exceptions
        $exceptions = DB::table('session_exceptions')
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'canceled')
            ->get()
            ->groupBy('sport_session_template_id');

        $upcoming = collect();
        $startDate = now();

        // Generate sessions for the next 14 days based on templates
        foreach ($templates as $template) {
            for ($i = 0; $i < 14; $i++) {
                $date = $startDate->copy()->addDays($i);
                if ($date->dayOfWeek === (int)$template->day_of_week) {
                    $dateString = $date->toDateString();
                    
                    $hasException = false;
                    if ($exceptions->has($template->id)) {
                        foreach ($exceptions->get($template->id) as $exc) {
                            if ($exc->date === $dateString) {
                                $hasException = true;
                                break;
                            }
                        }
                    }

                    if (!$hasException) {
                        $startDateTime = Carbon::parse($dateString . ' ' . $template->start_time);
                        if ($startDateTime > now()) {
                            $upcoming->push((object)[
                                'id' => $template->id,
                                'plan_name' => $template->plan_name,
                                'start_time' => $startDateTime->toDateTimeString(),
                                'end_time' => Carbon::parse($dateString . ' ' . $template->end_time)->toDateTimeString(),
                                'max_players' => $template->max_players,
                                'booked_count' => 0,
                            ]);
                        }
                    }
                }
            }
        }

        // Sort by start_time and take top 5
        $sessions = $upcoming->sortBy('start_time')->take(5);

        return $sessions->map(function ($session) {
            $durationMinutes = Carbon::parse($session->start_time)
                ->diffInMinutes(Carbon::parse($session->end_time));

            $planName = $session->plan_name;
            if (is_string($planName) && json_decode($planName)) {
                $decoded = json_decode($planName, true);
                $planName = $decoded[app()->getLocale()] ?? $decoded['ar'] ?? $decoded['en'] ?? $planName;
            }

            return [
                'id' => $session->id,
                'title' => $planName,
                'exercises_count' => 0,
                'duration_minutes' => $durationMinutes,
                'calories' => 0,
                'available_spots' => max(0, ($session->max_players ?? 0) - ($session->booked_count ?? 0)),
            ];
        })->values()->toArray();
    }
}
