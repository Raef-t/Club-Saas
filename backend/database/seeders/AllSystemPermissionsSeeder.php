<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AllSystemPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds for all system permissions.
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

            // ─── Contacts ──────────────────────────────────────────────────
            'contact.view-any',
            'contact.view',
            'contact.create',
            'contact.update',
            'contact.delete',

            // ─── Members ───────────────────────────────────────────────────
            'member.view-any',
            'member.view-trashed',
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

            // ─── Coaches ───────────────────────────────────────────────────
            'coach.view-any',
            'coach.stats',
            'coach.view-trashed',
            'coach.view',
            'coach.create',
            'coach.update',
            'coach.update-photo',
            'coach.set-schedule',
            'coach.delete',
            'coach.restore',

            // ─── Staff ─────────────────────────────────────────────────────
            'staff.view-any',
            'staff.view-trashed',
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.update-photo',
            'staff.set-schedule',
            'staff.toggle-status',
            'staff.restore',
            'staff.delete',

            // ─── Payroll & Payslips ────────────────────────────────────────
            'payroll.view-any',
            'payroll.view',
            'payroll.create',
            'payroll.generate-payslips',
            'payroll.process',
            'payroll.rollback',
            'payroll.delete',
            'payslip.view-any',
            'payslip.generate',
            'payslip.confirm',
            'payslip.update',

            // ─── Staff Shifts ──────────────────────────────────────────────
            'staff-shift.view-any',
            'staff-shift.view',
            'staff-shift.create',
            'staff-shift.update',
            'staff-shift.delete',

            // ─── Activities ────────────────────────────────────────────────
            'activity.view-any',
            'activity.stats',
            'activity.view-trashed',
            'activity.view',
            'activity.create',
            'activity.update',
            'activity.delete',
            'activity.restore',

            // ─── Activity Types ────────────────────────────────────────────
            'activity-type.view-any',
            'activity-type.view',
            'activity-type.create',
            'activity-type.update',
            'activity-type.update-settings',
            'activity-type.delete',

            // ─── Session Templates ─────────────────────────────────────────
            'session-template.view-any',
            'session-template.schedule',
            'session-template.view',
            'session-template.create',
            'session-template.update',
            'session-template.cancel',
            'session-template.delete',

            // ─── Staff Commission Rules ────────────────────────────────────
            'staff-commission-rule.view-any',
            'staff-commission-rule.view',
            'staff-commission-rule.create',
            'staff-commission-rule.update',

            // ─── Subscription Plans ────────────────────────────────────────
            'subscription-plan.view-any',
            'subscription-plan.view-trashed',
            'subscription-plan.view',
            'subscription-plan.view-players',
            'subscription-plan.create',
            'subscription-plan.update',
            'subscription-plan.delete',
            'subscription-plan.restore',
            'subscription-plan.suspend',
            'subscription-plan.view-suspensions',
            'subscription-plan.delete-suspension',

            // ─── Player Subscriptions ──────────────────────────────────────
            'player-subscription.view-any',
            'player-subscription.view',
            'player-subscription.create',
            'player-subscription.update',
            'player-subscription.delete',
            'player-subscription.freeze',
            'player-subscription.unfreeze',
            'player-subscription.renew',
            'player-subscription.cancel',
            'player-subscription.restore',

            // ─── Offers ────────────────────────────────────────────────────
            'offer.view-any',
            'offer.view-trashed',
            'offer.view',
            'offer.create',
            'offer.update',
            'offer.delete',
            'offer.restore',
            'offer.subscribe',

            // ─── Sub-Plan Activities & Items ───────────────────────────────
            'sub-plan-activity.view-any',
            'sub-plan-activity.view-trashed',
            'sub-plan-activity.create',
            'sub-plan-activity.view',
            'sub-plan-activity.update',
            'sub-plan-activity.delete',
            'sub-plan-activity.restore',

            // ─── Subscription Reports ──────────────────────────────────────
            'report.subscriptions.renewal-status',
            'report.subscriptions.frozen-terminated',
            'report.sessions.time-capacity',
            'report.attendance.peak-hours',
            'report.shifts.attendance',
            'report.coaches.subscriptions',

            // ─── Payments ──────────────────────────────────────────────────
            'payment.record',
            'payment.view-invoices',
            'payment.view-reports',
            'payment.view-any',
            'payment.view-trashed',
            'payment.create',
            'payment.view',
            'payment.update',
            'payment.delete',
            'payment.restore',

            // ─── Clubs ─────────────────────────────────────────────────────
            'club.view-any',
            'club.view-trashed',
            'club.view',
            'club.create',
            'club.update',
            'club.update-logo',
            'club.delete',
            'club.restore',
            'club.settings.view',
            'club.settings.update',

            // ─── Branches ──────────────────────────────────────────────────
            'branch.view-any',
            'branch.stats',
            'branch.view-trashed',
            'branch.view',
            'branch.create',
            'branch.update',
            'branch.delete',
            'branch.restore',
            'branch.toggle-status',
            'branch.settings.view',
            'branch.settings.update',
            'branch.holiday.view-any',
            'branch.holiday.create',
            'branch.holiday.view-trashed',
            'branch.holiday.view',
            'branch.holiday.update',
            'branch.holiday.delete',
            'branch.holiday.restore',
            'branch.shift.view-any',
            'branch.shift.view-trashed',
            'branch.shift.create',
            'branch.shift.update',
            'branch.shift.delete',
            'branch.shift.restore',

            // ─── Lockers ───────────────────────────────────────────────────
            'locker.view-any',
            'locker.view-trashed',
            'locker.view',
            'locker.create',
            'locker.update',
            'locker.delete',
            'locker.restore',
            'locker.reserve',
            'locker.get-by-holder',
            'locker.release-reservation',
            'locker.transfer-reservation',

            // ─── System Backup ─────────────────────────────────────────────
            'system.backup.download',

            // ─── Attendance ────────────────────────────────────────────────
            'attendance.check-in',
            'attendance.check-out',
            'attendance.bulk-check-out',
            'attendance.history',
            'attendance.delete',
            'attendance.restore',
            'attendance.dashboard',
            'attendance.dashboard-stream',
            'attendance.qr-check-out',

            // ─── Reception ─────────────────────────────────────────────────
            'reception.view-member-subscriptions',
            'reception.deduct-session',
            'reception.rollback-attendance',
            'reception.qr-check-in',

            // ─── Notifications ─────────────────────────────────────────────
            'notification.view-any',
            'notification.unread-count',
            'notification.mark-all-read',
            'notification.view',
            'notification.mark-read',
            'notification.delete-my',
            'notification.admin.view-any',
            'notification.admin.view',
            'notification.admin.create',
            'notification.admin.send-to-users',
            'notification.admin.delete',

            // ─── Core & Audit ──────────────────────────────────────────────
            'audit.view-any',
            'audit.meta',
            'audit.view',
            'upload.create',

            // ─── Accounting: Periods ───────────────────────────────────────
            'accounting.period.view-any',
            'accounting.period.create',
            'accounting.period.view',
            'accounting.period.close',
            'accounting.period.lock',
            'accounting.period.reopen',

            // ─── Accounting: Chart of Accounts ────────────────────────────
            'accounting.account.view-any',
            'accounting.account.create',
            'accounting.account.view',
            'accounting.account.update',
            'accounting.account.ledger',

            // ─── Accounting: Safes ─────────────────────────────────────────
            'accounting.safe.view-any',
            'accounting.safe.create',
            'accounting.safe.view',
            'accounting.safe.update',
            'accounting.safe.delete',
            'accounting.safe.statement',

            // ─── Accounting: Partners ──────────────────────────────────────
            'accounting.partner.view-any',
            'accounting.partner.create',
            'accounting.partner.view',
            'accounting.partner.update',
            'accounting.partner.delete',
            'accounting.partner.statement',

            // ─── Accounting: Counterparties ────────────────────────────────
            'accounting.counterparty.view-any',
            'accounting.counterparty.create',
            'accounting.counterparty.view',
            'accounting.counterparty.update',

            // ─── Accounting: Journals ──────────────────────────────────────
            'accounting.journal.view-any',
            'accounting.journal.create',
            'accounting.journal.view',
            'accounting.journal.post',
            'accounting.journal.reverse',
            'accounting.journal.cancel',

            // ─── Accounting: Financial Reports ─────────────────────────────
            'accounting.report.trial-balance',
            'accounting.report.income-statement',
            'accounting.report.balance-sheet',
            'accounting.report.dashboard',

            // ─── Accounting: Reconciliations ───────────────────────────────
            'accounting.reconciliation.view-any',
            'accounting.reconciliation.create',
            'accounting.reconciliation.view',

            // ─── Accounting: Salary Payments ───────────────────────────────
            'accounting.salary.view-any',
            'accounting.salary.create',
            'accounting.salary.delete',
        ];

        // Ensure absolute uniqueness
        $permissions = array_values(array_unique($permissions));

        $created = 0;
        $existing = 0;

        foreach ($permissions as $permission) {
            $result = Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'sanctum',
            ]);

            $result->wasRecentlyCreated ? $created++ : $existing++;
        }

        // 1. Grant ALL permissions to 'admin' role by default
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->syncPermissions($permissions);

        // 2. Accountant permissions
        $accountantRole = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'sanctum']);
        $accountantRole->syncPermissions([
            'accounting.period.view-any',
            'accounting.period.create',
            'accounting.period.view',
            'accounting.period.close',
            'accounting.period.lock',
            'accounting.period.reopen',
            'accounting.account.view-any',
            'accounting.account.create',
            'accounting.account.view',
            'accounting.account.update',
            'accounting.account.ledger',
            'accounting.safe.view-any',
            'accounting.safe.create',
            'accounting.safe.view',
            'accounting.safe.update',
            'accounting.safe.delete',
            'accounting.safe.statement',
            'accounting.partner.view-any',
            'accounting.partner.create',
            'accounting.partner.view',
            'accounting.partner.update',
            'accounting.partner.delete',
            'accounting.partner.statement',
            'accounting.counterparty.view-any',
            'accounting.counterparty.create',
            'accounting.counterparty.view',
            'accounting.counterparty.update',
            'accounting.journal.view-any',
            'accounting.journal.create',
            'accounting.journal.view',
            'accounting.journal.post',
            'accounting.journal.reverse',
            'accounting.journal.cancel',
            'accounting.report.trial-balance',
            'accounting.report.income-statement',
            'accounting.report.balance-sheet',
            'accounting.report.dashboard',
            'accounting.reconciliation.view-any',
            'accounting.reconciliation.create',
            'accounting.reconciliation.view',
            'accounting.salary.view-any',
            'accounting.salary.create',
            'accounting.salary.delete',
            'payment.view-any',
            'payment.view',
            'payment.record',
            'payment.view-invoices',
            'payment.view-reports',
            'payroll.view-any',
            'payroll.view',
            'payslip.view-any',
        ]);

        // 3. Reception permissions subset
        $receptionRole = Role::firstOrCreate(['name' => 'reception', 'guard_name' => 'sanctum']);
        $receptionRole->syncPermissions([
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
            'notification.view',
            'notification.mark-read',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $totalCount = count($permissions);
        $this->command->info("✅ All {$totalCount} system permissions seeded successfully.");
        $this->command->info("   ├─ Created : {$created}");
        $this->command->info("   └─ Existed : {$existing}");
    }
}

