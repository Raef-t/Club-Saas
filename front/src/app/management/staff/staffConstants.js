export const STAFF_TABLE_GRID =
  "minmax(180px,1.35fr) 130px minmax(130px,1fr) 140px 130px 100px 100px";

export const STAFF_ROLE_LABELS = {
  admin: "مدير النظام",
  management_admin: "مدير الإدارة",
  manager: "مدير",
  coach: "مدرب",
  reception: "موظف استقبال",
  receptionist: "موظف استقبال",
  cleaner: "عامل نظافة",
  staff: "موظف",
  accountant: "محاسب",
  member_manager: "مدير شؤون الأعضاء",
  nursery_staff: "موظفة حضانة",
};

export const STAFF_ROLE_OPTIONS = [
  { value: "admin", label: STAFF_ROLE_LABELS.admin },
  { value: "accountant", label: STAFF_ROLE_LABELS.accountant },
  { value: "reception", label: STAFF_ROLE_LABELS.reception },
  { value: "cleaner", label: STAFF_ROLE_LABELS.cleaner },
  { value: "member_manager", label: STAFF_ROLE_LABELS.member_manager },
  { value: "nursery_staff", label: STAFF_ROLE_LABELS.nursery_staff },
];

export const STAFF_FILTER_ROLE_OPTIONS = [
  { value: "admin", label: STAFF_ROLE_LABELS.admin },
  { value: "coach", label: STAFF_ROLE_LABELS.coach },
  { value: "accountant", label: STAFF_ROLE_LABELS.accountant },
  { value: "reception", label: STAFF_ROLE_LABELS.reception },
  { value: "cleaner", label: STAFF_ROLE_LABELS.cleaner },
  { value: "member_manager", label: STAFF_ROLE_LABELS.member_manager },
  { value: "nursery_staff", label: STAFF_ROLE_LABELS.nursery_staff },
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

export const STAFF_GENDER_OPTIONS = [
  { value: "male", label: STAFF_GENDER_LABELS.male },
  { value: "female", label: STAFF_GENDER_LABELS.female },
];

export const SHIFT_GENDER_LABELS = {
  male: "ذكور",
  female: "إناث",
  mixed: "مختلط",
};
