<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * Map of Route URI patterns & HTTP Methods to permission names.
     */
    protected array $routePermissionMap = [
        // ─── Auth & Users ─────────────────────────────────────────────────────
        'GET:v1/users'                                                  => 'user.view-any',
        'PUT:v1/auth/profile'                                           => 'profile.update',
        'GET:v1/permissions'                                            => 'permission.view-any',

        'GET:v1/roles'                                                  => 'role.view-any',
        'POST:v1/roles'                                                 => 'role.create',
        'GET:v1/roles/{id}'                                             => 'role.view',
        'PUT:v1/roles/{id}/permissions'                                 => 'role.sync-permissions',
        'DELETE:v1/roles/{id}'                                          => 'role.delete',
        'GET:v1/users/{userId}/roles'                                   => 'user-role.view',
        'POST:v1/users/{userId}/roles'                                  => 'user-role.assign',
        'DELETE:v1/users/{userId}/roles'                                => 'user-role.revoke',


        // ─── Contacts ─────────────────────────────────────────────────────────
        'GET:v1/contacts'                                               => 'contact.view-any',
        'GET:v1/contacts/{contact}'                                     => 'contact.view',
        'POST:v1/contacts'                                              => 'contact.create',
        'PUT:v1/contacts/{contact}'                                     => 'contact.update',
        'DELETE:v1/contacts/{contact}'                                  => 'contact.delete',

        // ─── Member Stats & Dashboard ──────────────────────────────────────────
        'GET:v1/members/stats'                                          => 'member.stats',
        'GET:v1/member/dashboard'                                       => 'member.stats',

        // ─── Members CRUD ──────────────────────────────────────────────────────
        'GET:v1/members'                                                => 'member.view-any',
        'POST:v1/members/register'                                      => 'member.create',
        'GET:v1/members/{id}'                                           => 'member.view',
        'PUT:v1/members/{id}'                                           => 'member.update',
        'POST:v1/members/{id}/photo'                                    => 'member.update-photo',
        'DELETE:v1/members/{id}'                                        => 'member.delete',
        'POST:v1/members/{id}/restore'                                  => 'member.restore',

        // ─── Health Profiles ───────────────────────────────────────────────────
        'GET:v1/member/health-profiles'                                 => 'member.health-profile.view-any',
        'POST:v1/member/health-profiles'                                => 'member.health-profile.create',
        'GET:v1/member/health-profiles/{id}'                            => 'member.health-profile.view',
        'PUT:v1/member/health-profiles/{id}'                            => 'member.health-profile.update',
        'DELETE:v1/member/health-profiles/{id}'                         => 'member.health-profile.delete',

        // ─── Measurements ────────────────────────────────────────────────────
        'GET:v1/member/measurements'                                    => 'member.measurement.view-any',
        'GET:v1/member/measurements/report'                             => 'member.measurement.report',
        'POST:v1/member/measurements'                                   => 'member.measurement.create',
        'GET:v1/member/measurements/{id}'                               => 'member.measurement.view',
        'PUT:v1/member/measurements/{id}'                               => 'member.measurement.update',
        'DELETE:v1/member/measurements/{id}'                            => 'member.measurement.delete',

        // ─── Unavailabilities ─────────────────────────────────────────────────
        'GET:v1/members/{member}/unavailabilities'                      => 'member.unavailability.view-any',
        'POST:v1/members/{member}/unavailabilities'                     => 'member.unavailability.create',
        'DELETE:v1/members/{member}/unavailabilities/{unavailability}' => 'member.unavailability.delete',
        'DELETE:v1/members/{member}/unavailabilities/{id}'              => 'member.unavailability.delete',

        // ─── Coaches ──────────────────────────────────────────────────────────
        'GET:v1/coaches'                                                => 'coach.view-any',
        'GET:v1/coaches/stats'                                          => 'coach.stats',
        'GET:v1/coaches/{id}'                                           => 'coach.view',
        'POST:v1/coaches'                                               => 'coach.create',
        'PATCH:v1/coaches/{id}'                                         => 'coach.update',
        'PUT:v1/coaches/{id}'                                           => 'coach.update',
        'POST:v1/coaches/{id}/photo'                                    => 'coach.update-photo',
        'POST:v1/coaches/{id}/schedule'                                 => 'coach.set-schedule',
        'DELETE:v1/coaches/{id}'                                        => 'coach.delete',
        'POST:v1/coaches/{id}/restore'                                  => 'coach.restore',

        // ─── Staff ────────────────────────────────────────────────────────────
        'GET:v1/staff'                                                  => 'staff.view-any',
        'GET:v1/staff/{id}'                                             => 'staff.view',
        'POST:v1/staff'                                                 => 'staff.create',
        'PUT:v1/staff/{id}'                                             => 'staff.update',
        'POST:v1/staff/{id}/photo'                                      => 'staff.update-photo',
        'POST:v1/staff/{id}/schedule'                                   => 'staff.set-schedule',
        'PATCH:v1/staff/{id}/toggle-status'                             => 'staff.toggle-status',
        'POST:v1/staff/{id}/restore'                                    => 'staff.restore',

        // ─── Payroll & Payslips ───────────────────────────────────────────────
        'GET:v1/payroll-runs'                                           => 'payroll.view-any',
        'GET:v1/payroll-runs/{id}'                                      => 'payroll.view',
        'POST:v1/payroll-runs'                                          => 'payroll.create',
        'POST:v1/payroll-runs/{id}/generate-payslips'                  => 'payroll.generate-payslips',
        'POST:v1/payroll-runs/{id}/process'                             => 'payroll.process',
        'GET:v1/payslips'                                               => 'payslip.view-any',
        'POST:v1/payslips/generate'                                     => 'payslip.generate',
        'POST:v1/payslips/confirm'                                      => 'payslip.confirm',
        'PUT:v1/payslips/{payslip}'                                     => 'payslip.update',

        // ─── Staff Shifts ─────────────────────────────────────────────────────
        'GET:v1/staff-shifts'                                           => 'staff-shift.view-any',
        'POST:v1/staff-shifts'                                          => 'staff-shift.create',
        'PUT:v1/staff-shifts/{shift}'                                   => 'staff-shift.update',
        'DELETE:v1/staff-shifts/{shift}'                                => 'staff-shift.delete',

        // ─── Activities ───────────────────────────────────────────────────────
        'GET:v1/activities'                                             => 'activity.view-any',
        'GET:v1/activities/stats'                                       => 'activity.stats',
        'GET:v1/activities/{id}'                                        => 'activity.view',
        'POST:v1/activities'                                            => 'activity.create',
        'PUT:v1/activities/{id}'                                        => 'activity.update',
        'DELETE:v1/activities/{id}'                                     => 'activity.delete',
        'POST:v1/activities/{id}/restore'                               => 'activity.restore',

        // ─── Activity Types ───────────────────────────────────────────────────
        'GET:v1/activity-types'                                         => 'activity-type.view-any',
        'POST:v1/activity-types'                                        => 'activity-type.create',
        'PUT:v1/activity-types/{id}'                                    => 'activity-type.update',
        'PATCH:v1/activity-types/{id}/settings'                         => 'activity-type.update-settings',
        'DELETE:v1/activity-types/{id}'                                 => 'activity-type.delete',

        // ─── Session Templates ────────────────────────────────────────────────
        'GET:v1/session-templates'                                      => 'session-template.view-any',
        'GET:v1/session-templates/schedule'                             => 'session-template.schedule',
        'POST:v1/session-templates'                                     => 'session-template.create',
        'PUT:v1/session-templates/{id}'                                 => 'session-template.update',
        'POST:v1/session-templates/{id}/cancel'                         => 'session-template.cancel',
        'DELETE:v1/session-templates/{id}'                              => 'session-template.delete',

        // ─── Subscription Plans ───────────────────────────────────────────────
        'GET:v1/subscription-plans'                                     => 'subscription-plan.view-any',
        'GET:v1/subscription-plans/{id}'                                => 'subscription-plan.view',
        'POST:v1/subscription-plans'                                    => 'subscription-plan.create',
        'PUT:v1/subscription-plans/{id}'                                => 'subscription-plan.update',
        'DELETE:v1/subscription-plans/{id}'                             => 'subscription-plan.delete',
        'POST:v1/subscription-plans/{id}/restore'                      => 'subscription-plan.restore',

        // ─── Player Subscriptions ─────────────────────────────────────────────
        'GET:v1/player-subscriptions'                                   => 'player-subscription.view-any',
        'GET:v1/player-subscriptions/{id}'                               => 'player-subscription.view',
        'POST:v1/player-subscriptions'                                  => 'player-subscription.create',
        'DELETE:v1/player-subscriptions/{id}'                            => 'player-subscription.delete',
        'POST:v1/player-subscriptions/{id}/freeze'                     => 'player-subscription.freeze',
        'POST:v1/player-subscriptions/{id}/unfreeze'                   => 'player-subscription.unfreeze',
        'POST:v1/player-subscriptions/{id}/renew'                      => 'player-subscription.renew',
        'POST:v1/player-subscriptions/{id}/cancel'                     => 'player-subscription.cancel',
        'POST:v1/player-subscriptions/{id}/restore'                    => 'player-subscription.restore',

        // ─── Offers ───────────────────────────────────────────────────────────
        'GET:v1/offers'                                                 => 'offer.view-any',
        'GET:v1/offers/{id}'                                            => 'offer.view',
        'POST:v1/offers'                                                => 'offer.create',
        'PUT:v1/offers/{id}'                                            => 'offer.update',
        'DELETE:v1/offers/{id}'                                         => 'offer.delete',
        'POST:v1/offers/{id}/restore'                                   => 'offer.restore',
        'POST:v1/offers/{id}/subscribe'                                 => 'offer.subscribe',

        // ─── Sub-Plan Activities & Items ─────────────────────────────────────
        'GET:v1/subscription-plan-activities'                          => 'sub-plan-activity.view-any',
        'POST:v1/subscription-plan-activities'                         => 'sub-plan-activity.create',
        'PUT:v1/subscription-plan-activities/{id}'                     => 'sub-plan-activity.update',
        'DELETE:v1/subscription-plan-activities/{id}'                  => 'sub-plan-activity.delete',
        'GET:v1/player-subscription-items'                              => 'player-sub-item.view-any',
        'POST:v1/player-subscription-items'                             => 'player-sub-item.create',
        'DELETE:v1/player-subscription-items/{id}'                      => 'player-sub-item.delete',

        // ─── Subscription Reports ─────────────────────────────────────────────
        'GET:v1/reports/subscriptions/renewal-status'                  => 'report.subscriptions.renewal-status',
        'GET:v1/reports/subscriptions/frozen-terminated'                => 'report.subscriptions.frozen-terminated',
        'GET:v1/reports/sessions/time-capacity'                        => 'report.sessions.time-capacity',
        'GET:v1/reports/attendance/peak-hours'                         => 'report.attendance.peak-hours',
        'GET:v1/reports/shifts/attendance'                             => 'report.shifts.attendance',
        'GET:v1/reports/coaches/subscriptions'                         => 'report.coaches.subscriptions',

        // ─── Payments ─────────────────────────────────────────────────────────
        'POST:v1/player-subscriptions/{id}/payment'                    => 'payment.record',
        'GET:v1/my-invoices'                                            => 'payment.view-invoices',
        'GET:v1/reports/subscriptions'                                  => 'payment.view-reports',
        'GET:v1/payments'                                               => 'payment.view-any',
        'GET:v1/payments/{id}'                                          => 'payment.view',
        'DELETE:v1/payments/{id}'                                       => 'payment.delete',
        'POST:v1/payments/{id}/restore'                                 => 'payment.restore',

        // ─── Clubs ────────────────────────────────────────────────────────────
        'GET:v1/clubs'                                                  => 'club.view-any',
        'GET:v1/clubs/{id}'                                             => 'club.view',
        'POST:v1/clubs'                                                 => 'club.create',
        'PUT:v1/clubs/{id}'                                             => 'club.update',
        'DELETE:v1/clubs/{id}'                                          => 'club.delete',
        'POST:v1/clubs/{id}/restore'                                    => 'club.restore',
        'GET:v1/clubs/{club}/settings'                                  => 'club.settings.view',
        'PUT:v1/clubs/{club}/settings'                                  => 'club.settings.update',

        // ─── Branches ─────────────────────────────────────────────────────────
        'GET:v1/branches'                                               => 'branch.view-any',
        'GET:v1/branches/stats'                                         => 'branch.stats',
        'GET:v1/branches/{id}'                                          => 'branch.view',
        'POST:v1/branches'                                              => 'branch.create',
        'PUT:v1/branches/{id}'                                          => 'branch.update',
        'DELETE:v1/branches/{id}'                                       => 'branch.delete',
        'POST:v1/branches/{id}/restore'                                 => 'branch.restore',
        'PATCH:v1/branches/{id}/toggle-status'                          => 'branch.toggle-status',
        'PUT:v1/branches/{branch}/settings'                             => 'branch.settings.update',
        'GET:v1/branches/{branch}/holidays'                             => 'branch.holiday.view-any',
        'POST:v1/branches/{branch}/holidays'                            => 'branch.holiday.create',
        'DELETE:v1/holidays/{id}'                                       => 'branch.holiday.delete',
        'GET:v1/branches/{branch}/shifts'                               => 'branch.shift.view-any',
        'POST:v1/branches/{branch}/shifts'                              => 'branch.shift.create',
        'PUT:v1/branches/{branch}/shifts/{shift}'                       => 'branch.shift.update',
        'DELETE:v1/branches/{branch}/shifts/{shift}'                    => 'branch.shift.delete',

        // ─── Lockers ──────────────────────────────────────────────────────────
        'GET:v1/lockers'                                                => 'locker.view-any',
        'GET:v1/lockers/{id}'                                           => 'locker.view',
        'POST:v1/lockers'                                               => 'locker.create',
        'PUT:v1/lockers/{id}'                                           => 'locker.update',
        'DELETE:v1/lockers/{id}'                                        => 'locker.delete',
        'POST:v1/lockers/{id}/restore'                                  => 'locker.restore',
        'POST:v1/lockers/{locker}/reservations'                         => 'locker.reserve',
        'GET:v1/lockers/holder/active'                                  => 'locker.get-by-holder',
        'DELETE:v1/lockers/{locker}/reservations/current'              => 'locker.release-reservation',
        'PATCH:v1/locker-reservations/{reservation}/holder'            => 'locker.transfer-reservation',

        // ─── System Backup ────────────────────────────────────────────────────
        'GET:v1/system/backup/download'                                 => 'system.backup.download',

        // ─── Attendance ───────────────────────────────────────────────────────
        'POST:v1/attendances/check-in'                                  => 'attendance.check-in',
        'POST:v1/attendances/check-out/{id}'                            => 'attendance.check-out',
        'POST:v1/attendances/bulk-check-out'                            => 'attendance.bulk-check-out',
        'GET:v1/attendances/history'                                    => 'attendance.history',
        'DELETE:v1/attendances/{id}'                                    => 'attendance.delete',
        'POST:v1/attendances/{id}/restore'                              => 'attendance.restore',
        'GET:v1/attendance-manager/dashboard-stats'                    => 'attendance.dashboard',
        'GET:v1/attendance-manager/dashboard-stats-stream'             => 'attendance.dashboard-stream',
        'POST:v1/qr/check-out'                                          => 'attendance.qr-check-out',

        // ─── Reception ────────────────────────────────────────────────────────
        'GET:v1/reception/members/{memberId}/subscriptions'             => 'reception.view-member-subscriptions',
        'POST:v1/reception/attendances/{id}/deduct'                     => 'reception.deduct-session',
        'DELETE:v1/reception/attendances/{id}/rollback'                 => 'reception.rollback-attendance',
        'POST:v1/qr/check-in'                                           => 'reception.qr-check-in',

        // ─── Notifications ────────────────────────────────────────────────────
        'GET:v1/notifications'                                          => 'notification.view-any',
        'GET:v1/notifications/unread/count'                            => 'notification.unread-count',
        'POST:v1/notifications/mark-all-as-read'                        => 'notification.mark-all-read',
        'PATCH:v1/notifications/{id}/read'                              => 'notification.mark-read',
        'DELETE:v1/notifications/{id}'                                  => 'notification.delete-my',
        'GET:v1/admin/notifications'                                    => 'notification.admin.view-any',
        'POST:v1/admin/notifications'                                   => 'notification.admin.create',
        'POST:v1/admin/notifications/send-to-users'                    => 'notification.admin.send-to-users',
        'DELETE:v1/admin/notifications/{id}'                            => 'notification.admin.delete',

        // ─── Core & Audit ─────────────────────────────────────────────────────
        'GET:v1/audits'                                                 => 'audit.view-any',
        'GET:v1/audits/meta'                                            => 'audit.meta',
        'GET:v1/audits/{id}'                                            => 'audit.view',
        'POST:v1/upload'                                                => 'upload.create',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        // 1. Super Admin bypasses all permission checks
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // 2. If explicit permission passed to middleware (e.g. check.permission:member.create)
        if ($permission) {
            if (!$user->can($permission)) {
                return $this->forbiddenResponse($permission);
            }
            return $next($request);
        }

        // 3. Dynamic resolution based on route URI & method
        $resolvedPermission = $this->resolvePermissionFromRequest($request);

        if ($resolvedPermission) {
            if (!$user->can($resolvedPermission)) {
                return $this->forbiddenResponse($resolvedPermission);
            }
        }

        return $next($request);
    }

    /**
     * Resolve the required permission name based on current route URI and HTTP method.
     */
    protected function resolvePermissionFromRequest(Request $request): ?string
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        $method = $request->method();

        // 1. Clean URI (e.g. "api/v1/members/{id}" or "/v1/members/{id}/") -> "v1/members/{id}"
        $uri = ltrim($route->uri(), '/');
        $uri = preg_replace('#^api/#', '', $uri);
        $uri = rtrim($uri, '/');

        $key = "{$method}:{$uri}";

        // 2. Direct lookup in route map
        if (isset($this->routePermissionMap[$key])) {
            return $this->routePermissionMap[$key];
        }

        // 3. Fallback: Check with "api/" prefix just in case
        $apiKey = "{$method}:api/{$uri}";
        if (isset($this->routePermissionMap[$apiKey])) {
            return $this->routePermissionMap[$apiKey];
        }

        return null;
    }

    /**
     * Standard 403 Forbidden Response
     */
    protected function forbiddenResponse(string $permission): Response
    {
        return response()->json([
            'status'     => 'error',
            'message'    => 'عذراً، ليس لديك الصلاحية الكافية لإتمام هذا الإجراء.',
            'permission' => $permission,
        ], 403);
    }
}
