export const SUBSCRIPTION_STATUS_LABELS = {
  active: "فعال",
  expiring_soon: "تنتهي قريباً",
  finished: "منتهي",
  frozen: "مجمد",
  terminated: "تم إنهاؤه من الإدارة",
};

export const SUBSCRIPTION_STATUS_CLASSES = {
  active: "status-success",
  expiring_soon: "status-warning",
  finished: "status-danger",
  frozen: "status-warning",
  terminated: "status-danger",
};

export const SUBSCRIPTION_STATUS_OPTIONS = [
  { value: "all", label: "كل الحالات" },
  { value: "active", label: "فعال" },
  { value: "expiring_soon", label: "تنتهي قريباً (خلال 7 أيام)" },
  { value: "finished", label: "منتهي" },
  { value: "frozen", label: "مجمد" },
  { value: "terminated", label: "تم إنهاؤه من الإدارة" },
];
