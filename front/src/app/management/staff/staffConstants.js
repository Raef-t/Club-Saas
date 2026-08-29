export const STAFF_TABLE_GRID =
  "minmax(180px,1.35fr) 130px minmax(130px,1fr) 140px 130px 100px 100px";

export const STAFF_ROLE_LABELS = {
  admin: "مدير النظام",
  management_admin: "مدير الإدارة",
  manager: "مدير",
  coach: "مدرب",
  receptionist: "موظف استقبال",
  cleaner: "عامل نظافة",
  staff: "موظف",
};

export const STAFF_ROLE_OPTIONS = [
  { value: "admin", label: STAFF_ROLE_LABELS.admin },
  { value: "management_admin", label: STAFF_ROLE_LABELS.management_admin },
  { value: "manager", label: STAFF_ROLE_LABELS.manager },
  { value: "receptionist", label: STAFF_ROLE_LABELS.receptionist },
  { value: "cleaner", label: STAFF_ROLE_LABELS.cleaner },
  { value: "staff", label: STAFF_ROLE_LABELS.staff },
];

export const STAFF_FILTER_ROLE_OPTIONS = [
  ...STAFF_ROLE_OPTIONS.slice(0, 3),
  { value: "coach", label: STAFF_ROLE_LABELS.coach },
  ...STAFF_ROLE_OPTIONS.slice(3),
];

export const STAFF_EMPLOYMENT_LABELS = {
  fixed_salary: "راتب ثابت",
  commission_based: "نسبة",
  hybrid: "راتب ونسبة",
};

export const STAFF_EMPLOYMENT_OPTIONS = [
  { value: "fixed_salary", label: STAFF_EMPLOYMENT_LABELS.fixed_salary },
  { value: "commission_based", label: STAFF_EMPLOYMENT_LABELS.commission_based },
  { value: "hybrid", label: STAFF_EMPLOYMENT_LABELS.hybrid },
];

export const STAFF_WORK_STATUS_LABELS = {
  active: "نشط",
  suspended: "غير نشط",
  on_leave: "إجازة",
};

export const STAFF_WORK_STATUS_OPTIONS = Object.entries(STAFF_WORK_STATUS_LABELS).map(
  ([value, label]) => ({ value, label }),
);

export const STAFF_GENDER_LABELS = {
  male: "ذكر",
  female: "أنثى",
  mixed: "مختلط",
};

export const SHIFT_GENDER_LABELS = {
  male: "ذكور",
  female: "إناث",
  mixed: "مختلط",
};
