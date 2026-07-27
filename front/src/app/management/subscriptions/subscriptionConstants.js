export const SUBSCRIPTION_STATUS_LABELS = {
  active: "نشط",
  expired: "منتهي",
  cancelled: "ملغي",
  frozen: "مجمّد",
  pending: "قيد الانتظار",
};

export const SUBSCRIPTION_STATUS_CLASSES = {
  active: "status-success",
  expired: "status-danger",
  cancelled: "status-danger",
  frozen: "status-warning",
  pending: "status-review",
};

export const SUBSCRIPTION_STATUS_OPTIONS = [
  { value: "all", label: "كل الحالات" },
  ...Object.entries(SUBSCRIPTION_STATUS_LABELS).map(([value, label]) => ({
    value,
    label,
  })),
];
