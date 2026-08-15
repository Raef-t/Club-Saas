<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AllSystemPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds for all 188 system permissions.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // ─── Auth & Users ──────────────────────────────────────────────
            'user.view-any',
            'user.update-profile',
            'profile.update',
            'permission.view-any',
            'role.view-any',
            'role.create',
            'role.view',
            'role.sync-permissions',
            'role.delete',
            'user-role.view',
            'user-role.assign',
            'user-role.revoke',


            // ─── Contacts (5) ───────────────────────────────────────────────
            'contact.view-any',
            'contact.view',
            'contact.create',
            'contact.update',
            'contact.delete',

            // ─── Members (22) ───────────────────────────────────────────────
            'member.view-any',
            'member.view',
            'member.create',
            'member.update',
            'member.update-photo',
            'member.delete',
            'member.restore',
            'member.stats',
            'member.health-profile.view-any',
            'member.health-profile.view',
            'member.health-profile.create',
            'member.health-profile.update',
            'member.health-profile.delete',
            'member.measurement.view-any',
            'member.measurement.view',
            'member.measurement.create',
            'member.measurement.update',
            'member.measurement.delete',
            'member.measurement.report',
            'member.unavailability.view-any',
            'member.unavailability.create',
            'member.unavailability.delete',

            // ─── Coaches (9) ────────────────────────────────────────────────
            'coach.view-any',
            'coach.stats',
            'coach.view',
            'coach.create',
            'coach.update',
            'coach.update-photo',
            'coach.set-schedule',
            'coach.delete',
            'coach.restore',

            // ─── Staff (8) ──────────────────────────────────────────────────
            'staff.view-any',
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.update-photo',
            'staff.set-schedule',
            'staff.toggle-status',
            'staff.restore',

            // ─── Payroll & Payslips (9) ─────────────────────────────────────
            'payroll.view-any',
            'payroll.view',
            'payroll.create',
            'payroll.generate-payslips',
            'payroll.process',
            'payslip.view-any',
            'payslip.generate',
            'payslip.confirm',
            'payslip.update',

            // ─── Staff Shifts (4) ───────────────────────────────────────────
            'staff-shift.view-any',
            'staff-shift.create',
            'staff-shift.update',
            'staff-shift.delete',

            // ─── Activities (7) ─────────────────────────────────────────────
            'activity.view-any',
            'activity.stats',
            'activity.view',
            'activity.create',
            'activity.update',
            'activity.delete',
            'activity.restore',

            // ─── Activity Types (5) ─────────────────────────────────────────
            'activity-type.view-any',
            'activity-type.create',
            'activity-type.update',
            'activity-type.update-settings',
            'activity-type.delete',

            // ─── Session Templates (6) ──────────────────────────────────────
            'session-template.view-any',
            'session-template.schedule',
            'session-template.create',
            'session-template.update',
            'session-template.cancel',
            'session-template.delete',

            // ─── Subscription Plans (6) ─────────────────────────────────────
            'subscription-plan.view-any',
            'subscription-plan.view',
            'subscription-plan.create',
            'subscription-plan.update',
            'subscription-plan.delete',
            'subscription-plan.restore',

            // ─── Player Subscriptions (9) ───────────────────────────────────
            'player-subscription.view-any',
            'player-subscription.view',
            'player-subscription.create',
            'player-subscription.delete',
            'player-subscription.freeze',
            'player-subscription.unfreeze',
            'player-subscription.renew',
            'player-subscription.cancel',
            'player-subscription.restore',

            // ─── Offers (7) ─────────────────────────────────────────────────
            'offer.view-any',
            'offer.view',
            'offer.create',
            'offer.update',
            'offer.delete',
            'offer.restore',
            'offer.subscribe',

            // ─── Sub-Plan Activities & Items (7) ────────────────────────────
            'sub-plan-activity.view-any',
            'sub-plan-activity.create',
            'sub-plan-activity.update',
            'sub-plan-activity.delete',
            'player-sub-item.view-any',
            'player-sub-item.create',
            'player-sub-item.delete',

            // ─── Subscription Reports (6) ───────────────────────────────────
            'report.subscriptions.renewal-status',
            'report.subscriptions.frozen-terminated',
            'report.sessions.time-capacity',
            'report.attendance.peak-hours',
            'report.shifts.attendance',
            'report.coaches.subscriptions',

            // ─── Payments (7) ───────────────────────────────────────────────
            'payment.record',
            'payment.view-invoices',
            'payment.view-reports',
            'payment.view-any',
            'payment.view',
            'payment.delete',
            'payment.restore',

            // ─── Clubs (8) ──────────────────────────────────────────────────
            'club.view-any',
            'club.view',
            'club.create',
            'club.update',
            'club.delete',
            'club.restore',
            'club.settings.view',
            'club.settings.update',

            // ─── Branches (16) ──────────────────────────────────────────────
            'branch.view-any',
            'branch.stats',
            'branch.view',
            'branch.create',
            'branch.update',
            'branch.delete',
            'branch.restore',
            'branch.toggle-status',
            'branch.settings.update',
            'branch.holiday.view-any',
            'branch.holiday.create',
            'branch.holiday.delete',
            'branch.shift.view-any',
            'branch.shift.create',
            'branch.shift.update',
            'branch.shift.delete',

            // ─── Lockers (10) ───────────────────────────────────────────────
            'locker.view-any',
            'locker.view',
            'locker.create',
            'locker.update',
            'locker.delete',
            'locker.restore',
            'locker.reserve',
            'locker.get-by-holder',
            'locker.release-reservation',
            'locker.transfer-reservation',

            // ─── System Backup (1) ──────────────────────────────────────────
            'system.backup.download',

            // ─── Attendance (9) ─────────────────────────────────────────────
            'attendance.check-in',
            'attendance.check-out',
            'attendance.bulk-check-out',
            'attendance.history',
            'attendance.delete',
            'attendance.restore',
            'attendance.dashboard',
            'attendance.dashboard-stream',
            'attendance.qr-check-out',

            // ─── Reception (4) ──────────────────────────────────────────────
            'reception.view-member-subscriptions',
            'reception.deduct-session',
            'reception.rollback-attendance',
            'reception.qr-check-in',

            // ─── Notifications (9) ──────────────────────────────────────────
            'notification.view-any',
            'notification.unread-count',
            'notification.mark-all-read',
            'notification.mark-read',
            'notification.delete-my',
            'notification.admin.view-any',
            'notification.admin.create',
            'notification.admin.send-to-users',
            'notification.admin.delete',

            // ─── Core & Audit (4) ───────────────────────────────────────────
            'audit.view-any',
            'audit.meta',
            'audit.view',
            'upload.create',
        ];

        $created = 0;
        $existing = 0;

        foreach ($permissions as $permission) {
            $result = Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'sanctum',
            ]);

            $result->wasRecentlyCreated ? $created++ : $existing++;
        }

        // Grant ALL permissions to 'admin' role by default
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->givePermissionTo($permissions);

        // Reception permissions subset
        $receptionRole = Role::firstOrCreate(['name' => 'reception', 'guard_name' => 'sanctum']);
        $receptionRole->givePermissionTo([
            'attendance.check-in',
            'attendance.check-out',
            'attendance.bulk-check-out',
            'attendance.history',
            'attendance.qr-check-out',
            'reception.view-member-subscriptions',
            'reception.deduct-session',
            'reception.rollback-attendance',
            'reception.qr-check-in',
            'member.view-any',
            'member.view',
            'member.create',
            'member.update',
            'player-subscription.view-any',
            'player-subscription.view',
            'player-subscription.create',
            'player-subscription.renew',
            'locker.view-any',
            'locker.reserve',
            'locker.get-by-holder',
            'locker.release-reservation',
            'notification.view-any',
            'notification.unread-count',
            'notification.mark-all-read',
            'notification.mark-read',
        ]);

        $this->command->info("✅ All 188 system permissions seeded successfully.");
        $this->command->info("   ├─ Created : {$created}");
        $this->command->info("   └─ Existed : {$existing}");
    }
}
