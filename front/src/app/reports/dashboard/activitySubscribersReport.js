import { getDisplayName, isActiveSubscription } from "./reportSharedUtils";

/**
 * Extracts every activity assigned to a subscription.
 */
function getSubscriptionActivities(subscription) {
  const candidates = [
    ...(Array.isArray(subscription?.activities) ? subscription.activities : []),
    ...(Array.isArray(subscription?.items) ? subscription.items : []),
    ...(Array.isArray(subscription?.plan?.activities) ? subscription.plan.activities : []),
    ...(Array.isArray(subscription?.subscription_plan?.activities)
      ? subscription.subscription_plan.activities
      : []),
  ];
  const labels = candidates
    .map((item) => getDisplayName(item?.activity_name || item?.activity?.name || item?.name, ""))
    .filter(Boolean);

  if (!labels.length) {
    const planName = getDisplayName(
      subscription?.plan?.name || subscription?.subscription_plan?.name || subscription?.plan_name,
      "",
    );
    if (planName) labels.push(planName);
  }

  return [...new Set(labels)];
}

/**
 * Creates the activity subscriber report from active subscriptions.
 */
export function createActivitySubscribersReport(subscriptions, activities, now) {
  const groupedActivities = new Map();

  activities.forEach((activity) => {
    const label = getDisplayName(activity?.name, "");
    if (label) {
      groupedActivities.set(label, {
        memberIds: new Set(),
        subscriptionIds: new Set(),
      });
    }
  });

  subscriptions
    .filter((subscription) => isActiveSubscription(subscription, now))
    .forEach((subscription, index) => {
      const memberId = String(
        subscription?.member_id ||
          subscription?.member?.id ||
          subscription?.player_id ||
          `member-${index}`,
      );
      const subscriptionId = String(subscription?.id || `subscription-${index}`);

      getSubscriptionActivities(subscription).forEach((label) => {
        if (!groupedActivities.has(label)) {
          groupedActivities.set(label, {
            memberIds: new Set(),
            subscriptionIds: new Set(),
          });
        }

        groupedActivities.get(label).memberIds.add(memberId);
        groupedActivities.get(label).subscriptionIds.add(subscriptionId);
      });
    });

  const rows = [...groupedActivities.entries()]
    .map(([activity, values]) => ({
      activity,
      subscribers: values.memberIds.size,
      subscriptions: values.subscriptionIds.size,
    }))
    .sort((first, second) => second.subscribers - first.subscribers);
  const uniqueSubscribers = new Set();

  groupedActivities.forEach((values) => {
    values.memberIds.forEach((memberId) => uniqueSubscribers.add(memberId));
  });

  return {
    id: "activities",
    title: "مشتركو الفعاليات والأنشطة",
    description: "توزيع المشتركين الفعّالين على الأنشطة والفعاليات المسندة لاشتراكاتهم.",
    metrics: [
      { label: "عدد الفعاليات", value: rows.length },
      { label: "مشتركون فريدون", value: uniqueSubscribers.size },
      {
        label: "إجمالي الإسنادات",
        value: rows.reduce((sum, row) => sum + row.subscriptions, 0),
      },
    ],
    columns: [
      { key: "activity", label: "الفعالية أو النشاط" },
      { key: "subscribers", label: "عدد المشتركين" },
      { key: "subscriptions", label: "الاشتراكات المرتبطة" },
    ],
    rows,
    emptyMessage: "لا توجد فعاليات أو أنشطة مرتبطة باشتراكات الفرع المختار.",
    uniqueSubscribers: uniqueSubscribers.size,
  };
}
