export const MEMBER_TABLE_GRID = "minmax(180px,1.2fr) 140px 100px 120px 140px 100px 90px";

export const MEMBER_GENDER_LABELS = {
  male: "ذكر",
  female: "أنثى",
};
//test 
export const RELATION_OPTIONS = [
  { value: "Father", label: "الأب" },
  { value: "Mother", label: "الأم" },
  { value: "Brother", label: "الأخ" },
  { value: "Sister", label: "الأخت" },
  { value: "Friend", label: "صديق" },
  { value: "Other", label: "قريب / آخر" },
];

export const RELATION_LABELS = Object.fromEntries(
  RELATION_OPTIONS.map(({ value, label }) => [value, label]),
);

export const MEMBER_INITIAL_FORM = {
  first_name: "",
  last_name: "",
  mobile_country_code: "+963",
  mobile: "",
  gender: "male",
  dob: "",
  age: "",
  branch_id: "",
  emergency_name: "",
  emergency_relation: "Father",
  emergency_country_code: "+963",
  emergency_phone: "",
  plan_id: "",
  paid_amount: "",
};
