export const LOCKER_OCCUPIED_STATUSES = ["assigned", "with_member", "with_staff"];

export const LOCKER_STATUS_LABELS = {
  available: "متاح",
  assigned: "محجوز",
  with_member: "مع لاعب",
  with_staff: "مع موظف",
  maintenance: "صيانة",
  disabled: "معطل",
};

export const LOCKER_STATUS_CLASSES = {
  available: "border-app-green/30 bg-app-green/20 text-app-green",
  assigned: "border-app-blue/30 bg-app-blue/20 text-app-blue",
  with_member: "border-app-blue/30 bg-app-blue/20 text-app-blue",
  with_staff: "border-app-blue/30 bg-app-blue/20 text-app-blue",
  maintenance: "border-app-orange/30 bg-app-orange/20 text-app-orange",
  disabled: "border-app-muted/30 bg-app-muted/20 text-app-muted-light",
};

export const LOCKER_FILTER_OPTIONS = [
  { value: "all", label: "كل الحالات" },
  { value: "available", label: "متاحة" },
  { value: "occupied", label: "مشغولة" },
  { value: "maintenance", label: "صيانة" },
  { value: "disabled", label: "معطلة" },
];

export const LOCKER_STATUS_OPTIONS = Object.entries(LOCKER_STATUS_LABELS).map(([value, label]) => ({
  value,
  label,
}));

export const LOCKER_HOLDER_TYPE_OPTIONS = [
  { value: "", label: "لا يوجد" },
  { value: "member", label: "لاعب" },
  { value: "staff", label: "موظف" },
  { value: "guest", label: "زائر" },
];

export const LOCKER_RESERVATION_TYPE_OPTIONS = [
  { value: "rental", label: "إيجار" },
  { value: "assign", label: "إسناد مجاني" },
];
