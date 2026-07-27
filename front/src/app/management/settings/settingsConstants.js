export const SETTINGS_TABS = [
  { id: "general", label: "الإعدادات العامة" },
  { id: "appearance", label: "المظهر" },
  { id: "shifts", label: "ورديات العمل" },
  { id: "holidays", label: "العطلات والإجازات" },
];

export const DAYS_MAP = {
  0: "الأحد",
  1: "الاثنين",
  2: "الثلاثاء",
  3: "الأربعاء",
  4: "الخميس",
  5: "الجمعة",
  6: "السبت",
};

export const DAY_OPTIONS = Object.entries(DAYS_MAP).map(([value, label]) => ({
  value,
  label,
}));

export const HOLIDAY_TYPE_MAP = {
  weekly: "عطلة أسبوعية متكررة",
  specific_dates: "إجازة تاريخ محدد",
};

export const HOLIDAY_TYPE_OPTIONS = [
  { value: "weekly", label: "عطلة أسبوعية متكررة" },
  { value: "specific_dates", label: "إجازة محددة بالتاريخ" },
];

export const GENDER_MAP = {
  mixed: "مختلط",
  male: "ذكور",
  female: "إناث",
};

export const GENDER_OPTIONS = Object.entries(GENDER_MAP).map(([value, label]) => ({
  value,
  label,
}));
