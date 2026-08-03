<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All tables in the application that support soft deletes.
     */
    private array $tables = [
        'clubs',
        'club_settings',
        'branches',
        'branch_settings',
        'branch_holidays',
        'branch_shifts',
        'facilities',
        'facility_working_hours',
        'lockers',
        'locker_reservations',
        'members',
        'member_health_profiles',
        'member_measurements',
        'player_unavailabilities',
        'member_evaluations',
        'staff',
        'coach_details',
        'coach_certifications',
        'staff_contracts',
        'staff_shifts',
        'staff_branches',
        'staff_unavailabilities',
        'staff_leaves',
        'staff_activities',
        'staff_commission_rules',
        'payroll_runs',
        'payslips',
        'payslip_adjustments',
        'subscription_plans',
        'plan_activities',
        'subscription_plan_activities',
        'player_subscriptions',
        'player_subscription_items',
        'subscription_freezes',
        'offers',
        'offer_subscription_plan',
        'invoices',
        'payments',
        'activities',
        'activity_types',
        'sports_sessions',
        'sport_session_templates',
        'session_exceptions',
        'sport_session_bookings',
        'attendances',
        'attendance_consumptions',
        'gate_devices',
        'people',
        'person_contacts',
        'wallets',
        'wallet_transactions',
        'notifications',
        'notification_attachments',
        'notification_recipients',
        'notification_logs',
        'notification_templates',
        'formulas',
        'formula_variables',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropSoftDeletes();
                });
            }
        }
    }
};
