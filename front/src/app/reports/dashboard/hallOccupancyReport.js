import {
  formatReportTime,
  getBranchName,
  getDisplayName,
  getPersonName,
  parseReportDate,
} from "./reportSharedUtils";

/**
 * Normalizes Arabic and English activity names for reliable classification.
 */
function normalizeActivityName(value) {
  return String(value || "")
    .normalize("NFKD")
    .replace(/[\u064B-\u065F\u0670]/g, "")
    .replace(/\s+/g, " ")
    .trim()
    .toLowerCase();
}

/**
 * Classifies a hall activity as general, private, or another category.
 */
function classifyHallActivity(value) {
  const name = normalizeActivityName(value);

  if (name.includes("خاص") || name.includes("private")) return "private";
  if (name.includes("عام") || name.includes("general")) return "general";
  return "other";
}

/**
 * Returns the activity label attached to an attendance record.
 */
function getAttendanceActivityName(record) {
  return getDisplayName(
    record?.activity?.name ||
      record?.activity_name ||
      record?.subscription_item?.activity?.name ||
      record?.plan_name,
    "نشاط غير محدد",
  );
}

/**
 * Returns the latest attendance key for one member.
 */
function getAttendanceMemberKey(record, index) {
  return String(
    record?.member_id ||
      record?.attendable_id ||
      record?.member?.id ||
      record?.attendable?.id ||
      `attendance-${record?.id || index}`,
  );
}

/**
 * Checks whether the latest attendance record means the member is still inside.
 */
function isCurrentlyInside(record) {
  const status = String(record?.status || "").toLowerCase();
  const hasCheckOut = Boolean(
    record?.check_out || record?.checked_out_at || record?.checkout_at || record?.left_at,
  );

  if (hasCheckOut || ["checked_out", "completed", "cancelled"].includes(status)) {
    return false;
  }

  return ["checked_in", "active", "in"].includes(status) || Boolean(record?.check_in);
}

/**
 * Creates the current hall occupancy report from the latest record per member.
 */
export function createHallOccupancyReport(attendances) {
  const latestByMember = new Map();

  attendances.forEach((record, index) => {
    const key = getAttendanceMemberKey(record, index);
    const currentTime = parseReportDate(record?.check_in)?.getTime() || 0;
    const previousTime = parseReportDate(latestByMember.get(key)?.check_in)?.getTime() || -1;

    if (!latestByMember.has(key) || currentTime >= previousTime) {
      latestByMember.set(key, record);
    }
  });

  const currentAttendances = [...latestByMember.values()].filter(isCurrentlyInside);
  const rows = currentAttendances.map((record) => {
    const activity = getAttendanceActivityName(record);
    const category = classifyHallActivity(activity);

    return {
      member: getPersonName(record),
      membershipNumber:
        record?.member?.member_number || record?.member_number || record?.attendable_id || "-",
      activity,
      category:
        category === "general" ? "أجهزة عام" : category === "private" ? "أجهزة خاص" : "نشاط آخر",
      checkIn: formatReportTime(record?.check_in),
      coach: record?.coach?.person?.full_name || record?.coach?.name || record?.coach_name || "-",
      branch: getBranchName(record),
    };
  });
  const generalCount = rows.filter((row) => row.category === "أجهزة عام").length;
  const privateCount = rows.filter((row) => row.category === "أجهزة خاص").length;

  return {
    id: "hall",
    title: "المشتركون الموجودون حالياً بالصالة",
    description: "آخر حالة دخول لكل مشترك مع فصل أجهزة العام عن الخاص.",
    metrics: [
      { label: "إجمالي الموجودين", value: rows.length },
      { label: "أجهزة عام", value: generalCount },
      { label: "أجهزة خاص", value: privateCount },
      { label: "أنشطة أخرى", value: rows.length - generalCount - privateCount },
    ],
    columns: [
      { key: "membershipNumber", label: "رقم العضوية" },
      { key: "member", label: "المشترك" },
      { key: "activity", label: "النشاط" },
      { key: "category", label: "التصنيف" },
      { key: "checkIn", label: "وقت الدخول" },
      { key: "coach", label: "المدرب" },
    ],
    rows,
    emptyMessage: "لا يوجد مشتركون مسجلون داخل الصالة حالياً.",
    counts: {
      total: rows.length,
      general: generalCount,
      private: privateCount,
    },
  };
}
