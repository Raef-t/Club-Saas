const SUPER_ADMIN_ROLE = "super_admin";

const ACCESSIBLE_LANDING_PATHS = [
  "/management",
  "/management/attendance",
  "/management/members",
  "/management/subscriptions",
  "/management/schedule",
  "/management/lockers",
  "/management/payroll",
  "/management/coaches",
  "/management/staff",
  "/management/activities",
  "/management/subscription-plans",
  "/management/users",
  "/management/roles",
  "/management/settings",
  "/management/clubs",
  "/management/branches",
  "/reports",
  "/accounting",
  "/accounting/safes",
  "/accounting/accounts",
  "/accounting/journals",
  "/accounting/revenues",
  "/accounting/expenses",
  "/accounting/salaries",
  "/accounting/periods",
  "/accounting/reports",
];

const ROUTE_ACCESS_RULES = [
  {
    path: "/management/coaches/create",
    all: [
      "coach.view-any",
      "branch.view-any",
      "activity.view-any",
      "subscription-plan.view-any",
      "branch.shift.view-any",
      "branch.settings.view",
    ],
    any: ["coach.create", "coach.update"],
  },
  {
    path: "/management/staff/create",
    all: ["staff.view-any", "branch.view-any", "branch.settings.view"],
    any: ["staff.create", "staff.update"],
  },
  {
    path: "/management/subscriptions/create",
    any: [
      "player-subscription.create",
      "player-subscription.update",
      "player-subscription.renew",
      "player-sub-item.create",
    ],
  },
  {
    path: "/management/subscription-plans/create",
    all: ["branch.view-any", "activity.view-any", "coach.view-any"],
    any: ["subscription-plan.create", "subscription-plan.update"],
  },
  {
    path: "/management/activities/create",
    all: ["branch.view-any", "activity-type.view-any"],
    any: ["activity.create", "activity.update"],
  },
  {
    path: "/management/branches/create",
    all: ["club.view-any"],
    any: ["branch.create", "branch.update"],
  },
  {
    path: "/management/clubs/create",
    any: ["club.create", "club.update"],
  },
  {
    path: "/management/members/create",
    any: ["member.create", "member.update"],
  },
  {
    path: "/management/lockers/create",
    any: ["locker.create", "locker.update"],
  },
  { path: "/management/roles", any: ["role.view-any", "role.view"] },
  { path: "/management/users", any: ["user.view-any"] },
  {
    path: "/management/settings",
    all: [
      "branch.view-any",
      "branch.settings.view",
      "branch.shift.view-any",
      "branch.holiday.view-any",
    ],
  },
  { path: "/management/branches", all: ["branch.view-any", "club.view-any"] },
  { path: "/management/clubs", any: ["club.view-any"] },
  {
    path: "/management/coaches",
    all: ["coach.view-any", "branch.view-any", "activity.view-any", "subscription-plan.view-any"],
  },
  { path: "/management/staff", all: ["staff.view-any", "branch.view-any"] },
  { path: "/management/payroll", any: ["payslip.view-any", "payroll.view-any", "payroll.view"] },
  {
    path: "/management/activities",
    all: ["activity.view-any", "branch.view-any", "activity-type.view-any"],
  },
  {
    path: "/management/subscription-plans",
    all: ["subscription-plan.view-any", "branch.view-any", "activity.view-any", "coach.view-any"],
  },
  {
    path: "/management/subscriptions",
    any: ["player-subscription.view-any"],
  },
  {
    path: "/management/members",
    any: ["member.view-any", "member.view"],
  },
  {
    path: "/management/lockers",
    any: ["locker.view-any"],
  },
  {
    path: "/management/attendance",
    any: [
      "attendance.history",
      "attendance.check-in",
      "attendance.check-out",
      "attendance.dashboard",
      "reception.qr-check-in",
      "reception.deduct-session",
    ],
  },
  {
    path: "/management/schedule",
    any: ["session-template.schedule"],
  },
  {
    path: "/management",
    any: ["attendance.dashboard"],
  },
  {
    path: "/reports",
    all: [
      "member.view-any",
      "coach.view-any",
      "player-subscription.view-any",
      "activity.view-any",
      "attendance.history",
    ],
  },
  {
    path: "/accounting/reports",
    all: [
      "accounting.period.view-any",
      "accounting.report.trial-balance",
      "accounting.report.income-statement",
      "accounting.report.balance-sheet",
    ],
  },
  { path: "/accounting/periods", any: ["accounting.period.view-any"] },
  {
    path: "/accounting/salaries",
    all: [
      "accounting.salary.view-any",
      "staff.view-any",
      "accounting.safe.view-any",
      "accounting.period.view-any",
    ],
  },
  {
    path: "/accounting/partners",
    all: ["accounting.partner.view-any", "accounting.safe.view-any"],
  },
  { path: "/accounting/counterparties", any: ["accounting.counterparty.view-any"] },
  {
    path: "/accounting/journals",
    all: ["accounting.journal.view-any", "accounting.account.view-any", "accounting.safe.view-any"],
  },
  {
    path: "/accounting/expenses",
    all: [
      "accounting.journal.view-any",
      "accounting.account.view-any",
      "accounting.safe.view-any",
      "accounting.counterparty.view-any",
    ],
  },
  {
    path: "/accounting/revenues",
    all: [
      "accounting.journal.view-any",
      "accounting.account.view-any",
      "accounting.safe.view-any",
      "accounting.counterparty.view-any",
    ],
  },
  { path: "/accounting/accounts", any: ["accounting.account.view-any"] },
  {
    path: "/accounting/safes",
    all: ["accounting.safe.view-any", "accounting.account.view-any"],
  },
  { path: "/accounting/cashbox", any: ["accounting.safe.view-any"] },
  { path: "/accounting/subscriptions", any: ["player-subscription.view-any"] },
  { path: "/accounting", any: ["accounting.report.dashboard"] },
].sort((a, b) => b.path.length - a.path.length);

function normalizeName(item) {
  if (typeof item === "string") return item;
  if (item && typeof item === "object" && typeof item.name === "string") return item.name;
  return "";
}

export function getUserRoleNames(user) {
  return Array.from(
    new Set((Array.isArray(user?.roles) ? user.roles : []).map(normalizeName).filter(Boolean)),
  );
}

export function getUserPermissionNames(user) {
  return Array.from(
    new Set(
      (Array.isArray(user?.permissions) ? user.permissions : []).map(normalizeName).filter(Boolean),
    ),
  );
}

export function isSuperAdmin(user) {
  return getUserRoleNames(user).includes(SUPER_ADMIN_ROLE);
}

export function canAccessAllBranches(user) {
  if (!user) return false;
  const roles = getUserRoleNames(user);
  return roles.includes(SUPER_ADMIN_ROLE) || roles.includes("admin");
}

export function hasPermission(user, permission) {
  if (!permission) return true;
  if (isSuperAdmin(user)) return true;
  return getUserPermissionNames(user).includes(permission);
}

export function hasAnyPermission(user, permissions = []) {
  if (isSuperAdmin(user)) return true;
  return permissions.some((permission) => hasPermission(user, permission));
}

export function hasAllPermissions(user, permissions = []) {
  if (isSuperAdmin(user)) return true;
  return permissions.every((permission) => hasPermission(user, permission));
}

export function getRouteAccessRule(pathname) {
  const normalizedPath =
    String(pathname || "")
      .split("?", 1)[0]
      .replace(/\/$/, "") || "/";
  return (
    ROUTE_ACCESS_RULES.find(
      (rule) => normalizedPath === rule.path || normalizedPath.startsWith(`${rule.path}/`),
    ) || null
  );
}

export function canAccessPath(user, pathname) {
  const rule = getRouteAccessRule(pathname);
  if (!rule) return true;

  const hasRequiredPermissions = !rule.all || hasAllPermissions(user, rule.all);
  const hasOneAllowedPermission = !rule.any || hasAnyPermission(user, rule.any);

  return hasRequiredPermissions && hasOneAllowedPermission;
}

export function getFirstAccessiblePath(user) {
  return ACCESSIBLE_LANDING_PATHS.find((pathname) => canAccessPath(user, pathname)) || null;
}
