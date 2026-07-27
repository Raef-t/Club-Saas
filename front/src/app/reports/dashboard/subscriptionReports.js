import {
  DAY_IN_MILLISECONDS,
  formatReportDate,
  getBranchName,
  getDisplayName,
  getPersonName,
  isActiveSubscription,
  parseReportDate,
  resolveSubscriptionStatus,
} from "./reportSharedUtils";

/**
 * Creates the expired subscription report.
 */
export function createExpiredSubscriptionsReport(subscriptions, now) {
  const today = new Date(now);
  today.setHours(0, 0, 0, 0);
  const rows = subscriptions
    .filter((subscription) => {
      const status = resolveSubscriptionStatus(subscription, today);
      const endDate = parseReportDate(subscription?.end_date);
      return status !== "cancelled" && (status === "expired" || (endDate && endDate < today));
    })
    .map((subscription) => {
      const endDate = parseReportDate(subscription?.end_date);
      const elapsedDays = endDate
        ? Math.max(0, Math.floor((today - endDate) / DAY_IN_MILLISECONDS))
        : 0;

      return {
        membershipNumber: subscription?.member?.member_number || subscription?.member_number || "-",
        member: getPersonName(subscription),
        plan: getDisplayName(
          subscription?.plan?.name ||
            subscription?.subscription_plan?.name ||
            subscription?.plan_name,
        ),
        endDate: formatReportDate(subscription?.end_date),
        elapsed: `${elapsedDays.toLocaleString("ar")} يوم`,
        remainingAmount: Number(subscription?.remaining_amount || 0).toLocaleString("en-US"),
        branch: getBranchName(subscription),
        elapsedDays,
      };
    })
    .sort((first, second) => second.elapsedDays - first.elapsedDays);
  const endedRecently = rows.filter((row) => row.elapsedDays <= 30).length;
  const withBalance = rows.filter(
    (row) => Number(String(row.remainingAmount).replaceAll(",", "")) > 0,
  ).length;

  return {
    id: "expired",
    title: "الاشتراكات المنتهية",
    description: "المشتركون الذين انتهى تاريخ اشتراكهم ولم تُلغَ اشتراكاتهم يدوياً.",
    metrics: [
      { label: "إجمالي المنتهي", value: rows.length },
      { label: "آخر 30 يوماً", value: endedRecently },
      { label: "برصيد متبقٍ", value: withBalance },
    ],
    columns: [
      { key: "membershipNumber", label: "رقم العضوية" },
      { key: "member", label: "المشترك" },
      { key: "plan", label: "الخطة" },
      { key: "endDate", label: "تاريخ الانتهاء" },
      { key: "elapsed", label: "منذ الانتهاء" },
      { key: "remainingAmount", label: "المبلغ المتبقي" },
    ],
    rows,
    emptyMessage: "لا توجد اشتراكات منتهية في الفرع المختار.",
    count: rows.length,
  };
}

/**
 * Creates a status distribution report for all subscriptions.
 */
export function createSubscriptionStatusReport(subscriptions, now) {
  const labels = {
    active: "نشط",
    frozen: "مجمّد",
    expired: "منتهٍ",
    cancelled: "ملغي",
  };
  const totals = new Map();

  subscriptions.forEach((subscription) => {
    const status = resolveSubscriptionStatus(subscription, now);
    totals.set(status, (totals.get(status) || 0) + 1);
  });

  const rows = [...totals.entries()]
    .map(([status, count]) => ({
      status: labels[status] || status || "غير محدد",
      count,
      percentage: subscriptions.length
        ? `${Math.round((count / subscriptions.length) * 100)}%`
        : "0%",
    }))
    .sort((first, second) => second.count - first.count);
  const sevenDaysLater = new Date(now);
  sevenDaysLater.setDate(sevenDaysLater.getDate() + 7);
  const expiringSoon = subscriptions.filter((subscription) => {
    const endDate = parseReportDate(subscription?.end_date);
    return (
      isActiveSubscription(subscription, now) &&
      endDate &&
      endDate >= now &&
      endDate <= sevenDaysLater
    );
  }).length;
  const activeCount = subscriptions.filter((item) => isActiveSubscription(item, now)).length;

  return {
    id: "subscriptions",
    title: "حالة الاشتراكات",
    description: "توزيع حالات الاشتراك ونسبة كل حالة من إجمالي اشتراكات الفرع.",
    metrics: [
      { label: "إجمالي الاشتراكات", value: subscriptions.length },
      { label: "الاشتراكات النشطة", value: activeCount },
      { label: "تنتهي خلال 7 أيام", value: expiringSoon },
    ],
    columns: [
      { key: "status", label: "الحالة" },
      { key: "count", label: "العدد" },
      { key: "percentage", label: "النسبة" },
    ],
    rows,
    emptyMessage: "لا توجد اشتراكات لعرض حالاتها.",
    activeCount,
  };
}
