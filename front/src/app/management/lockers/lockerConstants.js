export const LOCKER_OCCUPIED_STATUSES = [
  "assigned",
  "occupied",
  "rented",
  "with_member",
  "with_staff_or_coach",
  "with_coach",
  "with_staff",
];

export const LOCKER_STATUS_LABELS = {
  available: "متاح",
  assigned: "محجوز",
  with_member: "مع لاعب",
  with_coach: "مع كوتش",
  with_staff: "مع موظف",
  maintenance: "صيانة",
  disabled: "معطل",
};

export const LOCKER_STATUS_CLASSES = {
  available: "border-app-green/30 bg-app-green/20 text-app-green",
  assigned: "border-app-blue/30 bg-app-blue/20 text-app-blue",
  with_member: "border-app-blue/30 bg-app-blue/20 text-app-blue",
  with_coach: "border-app-purple/30 bg-app-purple/20 text-app-purple",
  with_staff: "border-app-blue/30 bg-app-blue/20 text-app-blue",
  maintenance: "border-app-orange/30 bg-app-orange/20 text-app-orange",
  disabled: "border-app-muted/30 bg-app-muted/20 text-app-muted-light",
};

/**
 * Product-facing states used by the locker list. The API exposes more detailed
 * reservation states, but the page intentionally groups them into these four
 * operational states.
 */
export const LOCKER_PAGE_STATE_LABELS = {
  with_member: "مع لاعب",
  with_staff_or_coach: "مع كوتش أو موظف",
  available: "متاحة",
  maintenance: "معطلة أو صيانة",
};

export const LOCKER_PAGE_STATE_CLASSES = {
  with_member: "border-app-blue/30 bg-app-blue/20 text-app-blue",
  with_staff_or_coach: "border-app-purple/30 bg-app-purple/20 text-app-purple",
  available: "border-app-green/30 bg-app-green/20 text-app-green",
  maintenance: "border-app-orange/30 bg-app-orange/20 text-app-orange",
};

export const LOCKER_FILTER_OPTIONS = [
  { value: "all", label: "كل الحالات" },
  { value: "with_member", label: LOCKER_PAGE_STATE_LABELS.with_member },
  {
    value: "with_staff_or_coach",
    label: LOCKER_PAGE_STATE_LABELS.with_staff_or_coach,
  },
  { value: "available", label: LOCKER_PAGE_STATE_LABELS.available },
  { value: "maintenance", label: LOCKER_PAGE_STATE_LABELS.maintenance },
];

export const LOCKER_STATUS_OPTIONS = Object.entries(LOCKER_STATUS_LABELS).map(([value, label]) => ({
  value,
  label,
}));

export const LOCKER_HOLDER_TYPE_OPTIONS = [
  { value: "", label: "لا يوجد" },
  { value: "member", label: "لاعب" },
  { value: "coach", label: "كوتش" },
  { value: "staff", label: "موظف" },
  { value: "guest", label: "زائر" },
];

export const LOCKER_RESERVATION_HOLDER_TYPES = ["member", "coach", "staff"];

export const LOCKER_RESERVATION_TYPE_OPTIONS = [
  { value: "rental", label: "إيجار" },
  { value: "assign", label: "إسناد مجاني" },
];
