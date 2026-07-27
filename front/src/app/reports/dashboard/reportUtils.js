import { createActivitySubscribersReport } from "./activitySubscribersReport";
import { createHallOccupancyReport } from "./hallOccupancyReport";
import { createCoachesReport, createMembersReport } from "./peopleReports";
import {
  createExpiredSubscriptionsReport,
  createSubscriptionStatusReport,
} from "./subscriptionReports";

export { getReportCollection } from "./reportSharedUtils";

/**
 * Builds every operational report and the statistics displayed above them.
 */
export function createOperationalReports({
  members = [],
  coaches = [],
  subscriptions = [],
  activities = [],
  attendances = [],
  now = new Date(),
}) {
  const normalizedNow = new Date(now);
  normalizedNow.setHours(0, 0, 0, 0);
  const hallReport = createHallOccupancyReport(attendances);
  const expiredReport = createExpiredSubscriptionsReport(subscriptions, normalizedNow);
  const activityReport = createActivitySubscribersReport(subscriptions, activities, normalizedNow);
  const subscriptionReport = createSubscriptionStatusReport(subscriptions, normalizedNow);
  const reports = [
    hallReport,
    expiredReport,
    activityReport,
    subscriptionReport,
    createMembersReport(members),
    createCoachesReport(coaches),
  ];

  return {
    reports,
    stats: [
      {
        title: "أجهزة عام داخل الصالة",
        value: hallReport.counts.general.toLocaleString("ar"),
        helper: "الموجودون حالياً",
        tone: "green",
        iconKey: "generalTraining",
        compact: true,
      },
      {
        title: "أجهزة خاص داخل الصالة",
        value: hallReport.counts.private.toLocaleString("ar"),
        helper: "الموجودون حالياً",
        tone: "blue",
        iconKey: "privateTraining",
        compact: true,
      },
      {
        title: "الاشتراكات المنتهية",
        value: expiredReport.count.toLocaleString("ar"),
        helper: "حسب تاريخ النهاية",
        tone: "orange",
        iconKey: "expiring",
        compact: true,
      },
      {
        title: "مشتركو الفعاليات",
        value: activityReport.uniqueSubscribers.toLocaleString("ar"),
        helper: "مشتركون فريدون",
        tone: "purple",
        iconKey: "activities",
        compact: true,
      },
      {
        title: "الاشتراكات النشطة",
        value: subscriptionReport.activeCount.toLocaleString("ar"),
        helper: "فعالة حالياً",
        tone: "yellow",
        iconKey: "subscriptions",
        compact: true,
      },
      {
        title: "إجمالي الأعضاء",
        value: members.length.toLocaleString("ar"),
        helper: "في الفرع المختار",
        tone: "cyan",
        iconKey: "members",
        compact: true,
      },
    ],
  };
}
