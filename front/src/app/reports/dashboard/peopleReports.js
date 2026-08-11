import { getBranchName, getDisplayName, getPersonName } from "./reportSharedUtils";
import { getWorkStatusMeta } from "@/lib/workStatus";

/**
 * Creates a printable member registry report.
 */
export function createMembersReport(members) {
  const genderLabels = {
    male: "ذكر",
    female: "أنثى",
  };
  const rows = members.map((member) => ({
    membershipNumber: member?.member_number || member?.id || "-",
    member: getPersonName(member),
    gender: genderLabels[member?.person?.gender || member?.gender] || "غير محدد",
    phone: member?.person?.phone || member?.person?.mobile || member?.mobile || "-",
    status: member?.is_active === false ? "غير نشط" : "نشط",
    branch: getBranchName(member),
  }));

  return {
    id: "members",
    title: "سجل الأعضاء",
    description: "قائمة الأعضاء المسجلين وبيانات الاتصال والحالة الحالية.",
    metrics: [
      { label: "إجمالي الأعضاء", value: rows.length },
      { label: "الأعضاء النشطون", value: rows.filter((row) => row.status === "نشط").length },
      { label: "غير النشطين", value: rows.filter((row) => row.status === "غير نشط").length },
    ],
    columns: [
      { key: "membershipNumber", label: "رقم العضوية" },
      { key: "member", label: "الاسم" },
      { key: "gender", label: "الجنس" },
      { key: "phone", label: "الهاتف" },
      { key: "status", label: "الحالة" },
    ],
    rows,
    emptyMessage: "لا يوجد أعضاء في الفرع المختار.",
  };
}

/**
 * Creates a printable coach registry report.
 */
export function createCoachesReport(coaches) {
  const employmentLabels = {
    fixed_salary: "راتب ثابت",
    commission: "نسبة",
    hybrid: "هجين",
  };
  const rows = coaches.map((coach) => ({
    coach: getPersonName(coach),
    specialization:
      coach?.details?.specialization || getDisplayName(coach?.activities?.[0]?.name, "غير محدد"),
    employment: employmentLabels[coach?.employment_type] || coach?.employment_type || "غير محدد",
    phone: coach?.person?.phone || "-",
    status: getWorkStatusMeta(coach).label,
    branch: getBranchName(coach),
  }));

  return {
    id: "coaches",
    title: "سجل المدربين",
    description: "قائمة المدربين والتخصص ونظام الأجر والحالة الحالية.",
    metrics: [
      { label: "إجمالي المدربين", value: rows.length },
      { label: "المدربون النشطون", value: rows.filter((row) => row.status === "نشط").length },
      { label: "الموقوفون", value: rows.filter((row) => row.status === "موقوف").length },
      { label: "في إجازة", value: rows.filter((row) => row.status === "إجازة").length },
    ],
    columns: [
      { key: "coach", label: "المدرب" },
      { key: "specialization", label: "التخصص" },
      { key: "employment", label: "نظام الأجر" },
      { key: "phone", label: "الهاتف" },
      { key: "status", label: "الحالة" },
    ],
    rows,
    emptyMessage: "لا يوجد مدربون في الفرع المختار.",
  };
}
