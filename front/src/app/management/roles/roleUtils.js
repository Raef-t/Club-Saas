const ROLE_PRESENTATION = {
  super_admin: {
    label: "المدير الأعلى",
    description: "وصول كامل يتجاوز جميع فحوصات الصلاحيات في النظام.",
    tone: "red",
  },
  admin: {
    label: "مدير النظام",
    description: "إدارة كاملة لإعدادات النظام ومكوناته وصلاحياته.",
    tone: "blue",
  },
  accountant: {
    label: "المحاسب",
    description: "صلاحيات المحاسبة والسندات والفواتير والرواتب.",
    tone: "green",
  },
  reception: {
    label: "موظف الاستقبال",
    description: "الحضور والاشتراكات والخزائن وتسجيل الأعضاء.",
    tone: "orange",
  },
  coach: {
    label: "المدرب",
    description: "الجداول التدريبية والحصص والمشتركون التابعون.",
    tone: "purple",
  },
  player: {
    label: "اللاعب",
    description: "خدمات العضو الذاتية للاشتراكات والحضور والإشعارات.",
    tone: "green",
  },
  management_admin: {
    label: "مدير العمليات",
    description: "إدارة الفروع والعمليات التشغيلية اليومية.",
    tone: "blue",
  },
  cleaner: {
    label: "عامل النظافة",
    description: "وصول محدود إلى المهام التشغيلية المخصصة.",
    tone: "cyan",
  },
};

const MODULE_LABELS = {
  accounting: "المحاسبة والمالية",
  activity: "الأنشطة الرياضية",
  "activity-type": "أنواع الأنشطة",
  attendance: "الحضور والمغادرة",
  audit: "سجل التدقيق",
  branch: "الفروع",
  club: "الأندية",
  coach: "المدربون",
  contact: "بيانات الاتصال",
  locker: "الخزائن",
  member: "الأعضاء واللاعبون",
  notification: "الإشعارات",
  offer: "العروض",
  payment: "المدفوعات",
  payroll: "الرواتب",
  payslip: "قسائم الرواتب",
  permission: "الصلاحيات",
  "player-sub-item": "بنود اشتراك اللاعب",
  "player-subscription": "اشتراكات اللاعبين",
  profile: "الملف الشخصي",
  reception: "الاستقبال",
  report: "التقارير",
  role: "الأدوار",
  "session-template": "قوالب الحصص",
  staff: "الموظفون",
  "staff-commission-rule": "قواعد العمولات",
  "staff-shift": "مناوبات الموظفين",
  "sub-plan-activity": "أنشطة خطط الاشتراك",
  "subscription-plan": "خطط الاشتراك",
  system: "النظام",
  upload: "رفع الملفات",
  user: "المستخدمون",
  "user-role": "أدوار المستخدمين",
};

const ACTION_LABELS = {
  "view-any": "استعراض القائمة",
  view: "عرض التفاصيل",
  create: "إنشاء",
  update: "تعديل",
  delete: "حذف",
  restore: "استعادة",
  assign: "إسناد",
  revoke: "سحب",
  "sync-permissions": "مزامنة الصلاحيات",
  "update-photo": "تحديث الصورة",
  "view-trashed": "عرض المحذوفات",
  "toggle-status": "تغيير الحالة",
  report: "إنشاء تقرير",
  stats: "عرض الإحصائيات",
  suspend: "تعليق",
  cancel: "إلغاء",
  renew: "تجديد",
  freeze: "تجميد",
  unfreeze: "إلغاء التجميد",
  reserve: "حجز",
  confirm: "تأكيد",
  process: "معالجة",
  download: "تنزيل",
  "check-in": "تسجيل دخول",
  "check-out": "تسجيل خروج",
  "bulk-check-out": "تسجيل خروج جماعي",
  "qr-check-in": "تسجيل دخول عبر QR",
  "qr-check-out": "تسجيل خروج عبر QR",
  history: "سجل الحركات",
  dashboard: "لوحة التحكم",
  "dashboard-stream": "البث المباشر للوحة التحكم",
  "deduct-session": "خصم جلسة",
  "rollback-attendance": "التراجع عن تسجيل الحضور",
  "view-member-subscriptions": "عرض اشتراكات العضو",
  schedule: "جدول المواعيد",
  "get-by-holder": "جلب حسب المشترك",
  "transfer-reservation": "نقل الحجز",
  "generate-payslips": "توليد قسائم الرواتب",
  "view-invoices": "عرض الفواتير",
  "view-reports": "عرض التقارير",
  record: "تسجيل دفعة",
  subscribe: "اشتراك في العرض",
  "mark-read": "تحديد كمقروء",
  "health-profile.view": "عرض الملف الصحي",
  "health-profile.update": "تعديل الملف الصحي",
  "measurement.view-any": "استعراض القياسات",
  "measurement.view": "عرض القياس",
  "measurement.create": "إضافة قياس",
  "measurement.update": "تعديل القياس",
  "measurement.delete": "حذف القياس",
  "unavailability.view-any": "استعراض فترات عدم التوفر",
  "unavailability.create": "إضافة فترة عدم توفر",
  "unavailability.delete": "حذف فترة عدم التوفر",
};

const ROLE_NAME_PATTERN = /^[a-z][a-z0-9_]{2,49}$/;

// Permissions backed by routes and API calls that currently exist in the web dashboard.
// Mobile/self-service and backend-only features stay assigned to a role, but are not
// exposed by the web role editor until the dashboard implements their workflows.
const WEB_PERMISSION_RULES = {
  accounting: true,
  attendance: [
    "attendance.check-in",
    "attendance.check-out",
    "attendance.bulk-check-out",
    "attendance.history",
    "attendance.dashboard",
    "attendance.dashboard-stream",
    "attendance.qr-check-out",
  ],
  activity: [
    "activity.view-any",
    "activity.view",
    "activity.create",
    "activity.update",
    "activity.delete",
  ],
  "activity-type": ["activity-type.view-any"],
  branch: [
    "branch.view-any",
    "branch.view",
    "branch.create",
    "branch.update",
    "branch.delete",
    "branch.toggle-status",
    "branch.settings.view",
    "branch.settings.update",
    "branch.holiday.view-any",
    "branch.holiday.create",
    "branch.holiday.update",
    "branch.holiday.delete",
    "branch.shift.view-any",
    "branch.shift.create",
    "branch.shift.update",
    "branch.shift.delete",
  ],
  club: [
    "club.view-any",
    "club.view",
    "club.create",
    "club.update",
    "club.update-logo",
    "club.delete",
  ],
  coach: [
    "coach.view-any",
    "coach.view",
    "coach.create",
    "coach.update",
    "coach.update-photo",
    "coach.delete",
  ],
  locker: [
    "locker.view-any",
    "locker.view",
    "locker.create",
    "locker.update",
    "locker.delete",
    "locker.reserve",
    "locker.release-reservation",
  ],
  member: ["member.view-any", "member.view", "member.create", "member.update", "member.delete"],
  payslip: true,
  permission: ["permission.view-any"],
  "player-subscription": [
    "player-subscription.view-any",
    "player-subscription.view",
    "player-subscription.create",
    "player-subscription.update",
    "player-subscription.delete",
    "player-subscription.freeze",
    "player-subscription.unfreeze",
    "player-subscription.cancel",
  ],
  reception: true,
  report: ["report.shifts.attendance", "report.coaches.subscriptions"],
  role: true,
  "session-template": ["session-template.schedule"],
  staff: [
    "staff.view-any",
    "staff.view",
    "staff.create",
    "staff.update",
    "staff.update-photo",
    "staff.delete",
  ],
  "subscription-plan": [
    "subscription-plan.view-any",
    "subscription-plan.view",
    "subscription-plan.view-players",
    "subscription-plan.create",
    "subscription-plan.update",
    "subscription-plan.delete",
    "subscription-plan.suspend",
    "subscription-plan.delete-suspension",
  ],
  system: ["system.backup.download"],
  user: ["user.view-any"],
};

function humanizeIdentifier(value) {
  return String(value || "")
    .replace(/[._-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

export function getRoleCollection(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data?.roles)) return response.data.roles;
  if (Array.isArray(response?.roles)) return response.roles;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

export function getRoleRecord(response) {
  const candidates = [response?.data?.role, response?.role, response?.data?.data, response?.data];
  return (
    candidates.find(
      (candidate) => candidate && typeof candidate === "object" && !Array.isArray(candidate),
    ) || null
  );
}

export function normalizePermission(permission, fallbackModule = "other") {
  const name = typeof permission === "string" ? permission : permission?.name;
  if (!name) return null;

  const permissionModule =
    (typeof permission === "object" && permission?.module) ||
    String(name).split(".", 1)[0] ||
    fallbackModule;

  return {
    ...(typeof permission === "object" ? permission : {}),
    id: typeof permission === "object" ? permission.id : undefined,
    name: String(name),
    module: String(permissionModule),
  };
}

function flattenPermissionGroups(groups) {
  if (!groups || typeof groups !== "object" || Array.isArray(groups)) return [];

  return Object.entries(groups).flatMap(([permissionModule, items]) =>
    Array.isArray(items)
      ? items.map((permission) => normalizePermission(permission, permissionModule)).filter(Boolean)
      : [],
  );
}

export function getPermissionCollection(response) {
  const directCandidates = [
    response,
    response?.data?.permissions,
    response?.permissions,
    response?.data?.data,
    response?.data,
  ];
  const direct = directCandidates.find(Array.isArray);
  const nestedGroups = flattenPermissionGroups(response?.data?.grouped_by_module);
  const grouped = nestedGroups.length
    ? nestedGroups
    : flattenPermissionGroups(response?.grouped_by_module);
  const source = direct || grouped;
  const byName = new Map();

  for (const permission of source || []) {
    const normalized = normalizePermission(permission);
    if (normalized) byName.set(normalized.name, normalized);
  }

  return Array.from(byName.values()).sort((a, b) => a.name.localeCompare(b.name));
}

export function getRolePermissionNames(role) {
  return Array.from(
    new Set(
      (Array.isArray(role?.permissions) ? role.permissions : [])
        .map((permission) => normalizePermission(permission)?.name)
        .filter(Boolean),
    ),
  );
}

export function getRolePresentation(role) {
  const name = String(role?.name || "");
  return (
    ROLE_PRESENTATION[name] || {
      label: humanizeIdentifier(name) || "دور بدون اسم",
      description: "دور مخصص يمكن ضبط صلاحياته بحسب مسؤوليات المستخدمين.",
      tone: "yellow",
    }
  );
}

export function getModuleLabel(permissionModule) {
  return MODULE_LABELS[permissionModule] || humanizeIdentifier(permissionModule) || "صلاحيات أخرى";
}

export function getPermissionLabel(permission) {
  const normalized = normalizePermission(permission);
  if (!normalized) return "صلاحية غير معروفة";

  const segments = normalized.name.split(".");
  const compoundAction = segments.slice(1).join(".");
  const lastAction = segments.at(-1);
  return (
    ACTION_LABELS[compoundAction] ||
    ACTION_LABELS[lastAction] ||
    humanizeIdentifier(segments.slice(1).join(" "))
  );
}

export const SYSTEM_PERMISSIONS_CATALOG = [
  // Member
  { name: "member.view-any", module: "member" },
  { name: "member.view", module: "member" },
  { name: "member.create", module: "member" },
  { name: "member.update", module: "member" },
  { name: "member.delete", module: "member" },
  { name: "member.restore", module: "member" },
  { name: "member.stats", module: "member" },
  { name: "member.update-photo", module: "member" },
  { name: "member.health-profile.view", module: "member" },
  { name: "member.health-profile.update", module: "member" },
  { name: "member.measurement.view-any", module: "member" },
  { name: "member.measurement.view", module: "member" },
  { name: "member.measurement.create", module: "member" },
  { name: "member.measurement.update", module: "member" },
  { name: "member.measurement.delete", module: "member" },
  { name: "member.unavailability.view-any", module: "member" },
  { name: "member.unavailability.create", module: "member" },
  { name: "member.unavailability.delete", module: "member" },

  // Attendance
  { name: "attendance.check-in", module: "attendance" },
  { name: "attendance.check-out", module: "attendance" },
  { name: "attendance.bulk-check-out", module: "attendance" },
  { name: "attendance.history", module: "attendance" },
  { name: "attendance.dashboard", module: "attendance" },
  { name: "attendance.dashboard-stream", module: "attendance" },
  { name: "attendance.qr-check-out", module: "attendance" },
  { name: "attendance.delete", module: "attendance" },
  { name: "attendance.restore", module: "attendance" },
  { name: "attendance.stats", module: "attendance" },

  // Reception
  { name: "reception.qr-check-in", module: "reception" },
  { name: "reception.deduct-session", module: "reception" },
  { name: "reception.rollback-attendance", module: "reception" },
  { name: "reception.view-member-subscriptions", module: "reception" },

  // Player Subscription
  { name: "player-subscription.view-any", module: "player-subscription" },
  { name: "player-subscription.view", module: "player-subscription" },
  { name: "player-subscription.create", module: "player-subscription" },
  { name: "player-subscription.update", module: "player-subscription" },
  { name: "player-subscription.delete", module: "player-subscription" },
  { name: "player-subscription.freeze", module: "player-subscription" },
  { name: "player-subscription.unfreeze", module: "player-subscription" },
  { name: "player-subscription.cancel", module: "player-subscription" },
  { name: "player-subscription.renew", module: "player-subscription" },
  { name: "player-subscription.restore", module: "player-subscription" },

  // Player Sub Item
  { name: "player-sub-item.view-any", module: "player-sub-item" },
  { name: "player-sub-item.create", module: "player-sub-item" },
  { name: "player-sub-item.delete", module: "player-sub-item" },

  // Subscription Plan
  { name: "subscription-plan.view-any", module: "subscription-plan" },
  { name: "subscription-plan.view", module: "subscription-plan" },
  { name: "subscription-plan.view-players", module: "subscription-plan" },
  { name: "subscription-plan.create", module: "subscription-plan" },
  { name: "subscription-plan.update", module: "subscription-plan" },
  { name: "subscription-plan.delete", module: "subscription-plan" },
  { name: "subscription-plan.suspend", module: "subscription-plan" },
  { name: "subscription-plan.delete-suspension", module: "subscription-plan" },

  // Sub Plan Activity
  { name: "sub-plan-activity.view-any", module: "sub-plan-activity" },
  { name: "sub-plan-activity.create", module: "sub-plan-activity" },
  { name: "sub-plan-activity.update", module: "sub-plan-activity" },
  { name: "sub-plan-activity.delete", module: "sub-plan-activity" },

  // Session Template
  { name: "session-template.view-any", module: "session-template" },
  { name: "session-template.view", module: "session-template" },
  { name: "session-template.create", module: "session-template" },
  { name: "session-template.update", module: "session-template" },
  { name: "session-template.delete", module: "session-template" },
  { name: "session-template.cancel", module: "session-template" },
  { name: "session-template.schedule", module: "session-template" },

  // Coach
  { name: "coach.view-any", module: "coach" },
  { name: "coach.view", module: "coach" },
  { name: "coach.create", module: "coach" },
  { name: "coach.update", module: "coach" },
  { name: "coach.delete", module: "coach" },
  { name: "coach.update-photo", module: "coach" },
  { name: "coach.stats", module: "coach" },

  // Staff
  { name: "staff.view-any", module: "staff" },
  { name: "staff.view", module: "staff" },
  { name: "staff.create", module: "staff" },
  { name: "staff.update", module: "staff" },
  { name: "staff.delete", module: "staff" },
  { name: "staff.update-photo", module: "staff" },

  // Staff Shift & Commissions
  { name: "staff-shift.view-any", module: "staff-shift" },
  { name: "staff-shift.create", module: "staff-shift" },
  { name: "staff-shift.update", module: "staff-shift" },
  { name: "staff-shift.delete", module: "staff-shift" },
  { name: "staff-commission-rule.view-any", module: "staff-commission-rule" },
  { name: "staff-commission-rule.create", module: "staff-commission-rule" },
  { name: "staff-commission-rule.update", module: "staff-commission-rule" },
  { name: "staff-commission-rule.delete", module: "staff-commission-rule" },

  // Branch
  { name: "branch.view-any", module: "branch" },
  { name: "branch.view", module: "branch" },
  { name: "branch.create", module: "branch" },
  { name: "branch.update", module: "branch" },
  { name: "branch.delete", module: "branch" },
  { name: "branch.toggle-status", module: "branch" },
  { name: "branch.settings.view", module: "branch" },
  { name: "branch.settings.update", module: "branch" },
  { name: "branch.holiday.view-any", module: "branch" },
  { name: "branch.holiday.create", module: "branch" },
  { name: "branch.holiday.update", module: "branch" },
  { name: "branch.holiday.delete", module: "branch" },
  { name: "branch.shift.view-any", module: "branch" },
  { name: "branch.shift.create", module: "branch" },
  { name: "branch.shift.update", module: "branch" },
  { name: "branch.shift.delete", module: "branch" },

  // Club
  { name: "club.view-any", module: "club" },
  { name: "club.view", module: "club" },
  { name: "club.create", module: "club" },
  { name: "club.update", module: "club" },
  { name: "club.update-logo", module: "club" },
  { name: "club.delete", module: "club" },

  // Locker
  { name: "locker.view-any", module: "locker" },
  { name: "locker.view", module: "locker" },
  { name: "locker.create", module: "locker" },
  { name: "locker.update", module: "locker" },
  { name: "locker.delete", module: "locker" },
  { name: "locker.reserve", module: "locker" },
  { name: "locker.release-reservation", module: "locker" },
  { name: "locker.get-by-holder", module: "locker" },
  { name: "locker.transfer-reservation", module: "locker" },
  { name: "locker.restore", module: "locker" },

  // Activity & Types
  { name: "activity.view-any", module: "activity" },
  { name: "activity.view", module: "activity" },
  { name: "activity.create", module: "activity" },
  { name: "activity.update", module: "activity" },
  { name: "activity.delete", module: "activity" },
  { name: "activity.stats", module: "activity" },
  { name: "activity-type.view-any", module: "activity-type" },
  { name: "activity-type.view", module: "activity-type" },
  { name: "activity-type.create", module: "activity-type" },
  { name: "activity-type.update", module: "activity-type" },
  { name: "activity-type.delete", module: "activity-type" },

  // Payment
  { name: "payment.view-any", module: "payment" },
  { name: "payment.view", module: "payment" },
  { name: "payment.create", module: "payment" },
  { name: "payment.record", module: "payment" },
  { name: "payment.update", module: "payment" },
  { name: "payment.view-invoices", module: "payment" },
  { name: "payment.view-reports", module: "payment" },

  // Payroll & Payslips
  { name: "payroll.view-any", module: "payroll" },
  { name: "payroll.view", module: "payroll" },
  { name: "payroll.create", module: "payroll" },
  { name: "payroll.delete", module: "payroll" },
  { name: "payroll.process", module: "payroll" },
  { name: "payroll.generate-payslips", module: "payroll" },
  { name: "payslip.view-any", module: "payslip" },
  { name: "payslip.view", module: "payslip" },
  { name: "payslip.download", module: "payslip" },

  // Offer
  { name: "offer.view-any", module: "offer" },
  { name: "offer.view", module: "offer" },
  { name: "offer.create", module: "offer" },
  { name: "offer.update", module: "offer" },
  { name: "offer.delete", module: "offer" },
  { name: "offer.subscribe", module: "offer" },

  // Notification
  { name: "notification.view-any", module: "notification" },
  { name: "notification.view", module: "notification" },
  { name: "notification.admin.view-any", module: "notification" },
  { name: "notification.mark-read", module: "notification" },

  // User & Roles & Permissions
  { name: "user.view-any", module: "user" },
  { name: "user.view", module: "user" },
  { name: "user.create", module: "user" },
  { name: "user.update", module: "user" },
  { name: "user.delete", module: "user" },
  { name: "user.toggle-status", module: "user" },
  { name: "role.view-any", module: "role" },
  { name: "role.view", module: "role" },
  { name: "role.create", module: "role" },
  { name: "role.update", module: "role" },
  { name: "role.delete", module: "role" },
  { name: "role.sync-permissions", module: "role" },
  { name: "permission.view-any", module: "permission" },
  { name: "permission.view", module: "permission" },

  // Reports & System
  { name: "report.shifts.attendance", module: "report" },
  { name: "report.coaches.subscriptions", module: "report" },
  { name: "system.backup.download", module: "system" },
];

export function mergePermissionCatalog(permissions = [], role = null) {
  const byName = new Map();

  for (const permission of SYSTEM_PERMISSIONS_CATALOG) {
    const normalized = normalizePermission(permission);
    if (normalized) byName.set(normalized.name, normalized);
  }

  for (const permission of [...(permissions || []), ...(role?.permissions || [])]) {
    const normalized = normalizePermission(permission);
    if (normalized) byName.set(normalized.name, normalized);
  }

  return Array.from(byName.values()).sort((a, b) => a.name.localeCompare(b.name));
}

/**
 * Builds the system permission catalog from every loaded role.
 *
 * The permissions endpoint can be unavailable to a role editor that does not
 * have `permission.view-any`. In that case, using only the selected role would
 * make a permission disappear immediately after it is revoked. Other roles —
 * especially protected administrator roles — keep the complete permission
 * definitions available for adding back later.
 */
export function getPermissionsFromRoles(roles) {
  const byName = new Map();

  for (const role of roles) {
    for (const permission of Array.isArray(role?.permissions) ? role.permissions : []) {
      const normalized = normalizePermission(permission);
      if (normalized) byName.set(normalized.name, normalized);
    }
  }

  return Array.from(byName.values()).sort((a, b) => a.name.localeCompare(b.name));
}

/**
 * Keeps only permissions represented by an implemented web-dashboard workflow.
 */
export function filterWebPermissions(permissions) {
  return permissions.filter((permission) => {
    const normalized = normalizePermission(permission);
    if (!normalized) return false;

    const rule = WEB_PERMISSION_RULES[normalized.module];
    return rule === true || (Array.isArray(rule) && rule.includes(normalized.name));
  });
}

/**
 * Replaces the editable web selection while preserving assigned permissions
 * that are intentionally hidden from the web editor.
 */
export function createWebPermissionSyncPayload(
  selectedWebNames,
  currentRolePermissionNames,
  webPermissions,
) {
  const webNames = new Set(webPermissions.map((permission) => permission.name));
  const preservedNames = currentRolePermissionNames.filter((name) => !webNames.has(name));
  return Array.from(new Set([...selectedWebNames, ...preservedNames])).sort();
}

export function groupPermissions(permissions, search = "") {
  const term = search.trim().toLowerCase();
  const groups = new Map();

  for (const permission of permissions) {
    const moduleLabel = getModuleLabel(permission.module);
    const permissionLabel = getPermissionLabel(permission);
    if (
      term &&
      ![permission.name, permission.module, moduleLabel, permissionLabel].some((value) =>
        String(value).toLowerCase().includes(term),
      )
    ) {
      continue;
    }

    if (!groups.has(permission.module)) groups.set(permission.module, []);
    groups.get(permission.module).push(permission);
  }

  return Array.from(groups.entries())
    .map(([permissionModule, items]) => ({
      module: permissionModule,
      label: getModuleLabel(permissionModule),
      permissions: items,
    }))
    .sort((a, b) => a.label.localeCompare(b.label, "ar"));
}

export function filterRoles(roles, search) {
  const term = search.trim().toLowerCase();
  if (!term) return roles;

  return roles.filter((role) => {
    const presentation = getRolePresentation(role);
    return [role.name, presentation.label, presentation.description].some((value) =>
      String(value || "")
        .toLowerCase()
        .includes(term),
    );
  });
}

export function createRoleStats(roles, permissions) {
  const protectedRoles = roles.filter((role) => role.is_protected).length;
  return [
    {
      title: "إجمالي الأدوار",
      value: roles.length.toLocaleString("ar"),
      helper: "الأدوار المسجلة في النظام",
      tone: "yellow",
      compact: true,
    },
    {
      title: "الأدوار المحمية",
      value: protectedRoles.toLocaleString("ar"),
      helper: "لا يمكن حذفها من النظام",
      tone: "blue",
      compact: true,
    },
    {
      title: "الأدوار المخصصة",
      value: (roles.length - protectedRoles).toLocaleString("ar"),
      helper: "قابلة للإدارة والحذف",
      tone: "purple",
      compact: true,
    },
    {
      title: "صلاحيات النظام",
      value: permissions.length.toLocaleString("ar"),
      helper: "متاحة للتوزيع على الأدوار",
      tone: "green",
      compact: true,
    },
  ];
}

export function validateRoleName(name) {
  const normalized = String(name || "").trim();
  if (!normalized) return "اسم الدور مطلوب.";
  if (!ROLE_NAME_PATTERN.test(normalized)) {
    return "استخدم 3 إلى 50 حرفاً إنجليزياً صغيراً أو رقماً أو شرطة سفلية، وابدأ بحرف.";
  }
  return "";
}
