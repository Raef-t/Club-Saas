export const SUBSCRIPTION_STATUS_LABELS = {
  active: "فعال",
  finished: "منتهي",
  frozen: "مجمد",
  terminated: "تم إنهاؤه من الإدارة",
};

export const SUBSCRIPTION_STATUS_CLASSES = {
  active: "status-success",
  finished: "status-danger",
  frozen: "status-warning",
  terminated: "status-danger",
};

export const SUBSCRIPTION_STATUS_OPTIONS = [
  { value: "all", label: "كل الحالات" },
  ...Object.entries(SUBSCRIPTION_STATUS_LABELS).map(([value, label]) => ({
    value,
    label,
  })),
];
