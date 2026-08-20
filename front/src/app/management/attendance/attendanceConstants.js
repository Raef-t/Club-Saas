export const ATTENDANCE_SCAN_MODES = {
  CHECK_IN: "check-in",
  CHECK_OUT: "check-out",
};

export const ATTENDANCE_STATUS_LABELS = {
  checked_in: "دخول",
  checked_out: "خروج",
  completed: "مكتمل",
  cancelled: "ملغي",
};

export const ATTENDANCE_STATUS_CLASSES = {
  دخول: "bg-app-green/20 text-[#00dc1a]",
  خروج: "bg-white/10 text-app-muted-light",
  مكتمل: "bg-app-yellow/15 text-app-yellow",
  ملغي: "bg-app-red/15 text-app-red",
};

export const ATTENDANCE_STATUS_FILTER_OPTIONS = [
  { value: "all", label: "كل الحالات" },
  { value: "checked_in", label: "حاضر (في التدريب)" },
  { value: "checked_out", label: "انصرف (تم الخروج)" },
];

export const ATTENDANCE_TABLE_COLUMNS =
  "minmax(0,0.45fr) minmax(0,0.6fr) minmax(0,1.4fr) minmax(0,1.9fr) minmax(0,1.55fr) minmax(0,0.9fr) minmax(0,1.15fr) minmax(0,0.65fr) minmax(0,0.75fr) minmax(0,0.75fr)";

