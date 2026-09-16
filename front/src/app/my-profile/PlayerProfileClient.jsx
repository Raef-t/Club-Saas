"use client";

import { useMemo, useState, useEffect } from "react";
import Modal from "@/components/ui/Modal";
import MemberDetails from "@/app/management/members/MemberDetails";
import SubscriptionDetails from "@/app/management/subscriptions/SubscriptionDetails";
import {
  useGetPlayerSubscriptionQuery,
  useGetPlayerSubscriptionsQuery,
} from "@/lib/api/playerSubscriptionsApi";
import { useGetMemberAttendancesQuery } from "@/lib/api/attendanceApi";
import { useGetLockersQuery } from "@/lib/api/lockersApi";
import {
  getCurrentMemberSubscription,
  getSubscriptionDetail,
} from "@/app/management/subscriptions/subscriptionUtils";
import {
  createMemberProfileSummary,
  getMemberProfileAttendances,
  getMemberProfileLockers,
  getMemberProfileSubscriptions,
} from "@/app/management/members/memberProfileUtils";
import PlayerAppHeader from "./PlayerAppHeader";
import PlayerNavbar from "./PlayerNavbar";
import PlayerCoachesTab from "./PlayerCoachesTab";
import PlayerPlansTab from "./PlayerPlansTab";
import PlayerAttendanceTab from "./PlayerAttendanceTab";
import PlayerPaymentsTab from "./PlayerPaymentsTab";

function getMemberDisplayName(member) {
  return (
    member?.person?.full_name ||
    `${member?.first_name || ""} ${member?.last_name || ""}`.trim() ||
    `اللاعب #${member?.id || "-"}`
  );
}

export default function PlayerProfileClient({
  memberId,
  initialMember,
  initialBranches = [],
  currentUser,
}) {
  const [activeTab, setActiveTab] = useState("profile");
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState(null);
  const [showIosTip, setShowIosTip] = useState(true);

  useEffect(() => {
    try {
      if (typeof window !== "undefined") {
        const isDismissed = localStorage.getItem("dismissed_ios_pwa_tip");
        if (isDismissed === "true") {
          setShowIosTip(false);
        }
      }
    } catch {
      // Ignore storage errors
    }
  }, []);

  function handleDismissTip() {
    setShowIosTip(false);
    try {
      if (typeof window !== "undefined") {
        localStorage.setItem("dismissed_ios_pwa_tip", "true");
      }
    } catch {
      // Ignore storage errors
    }
  }

  const {
    data: subscriptionsResponse,
    error: subscriptionsError,
    isLoading: subscriptionsLoading,
    isFetching: subscriptionsFetching,
    refetch: refetchSubscriptions,
  } = useGetPlayerSubscriptionsQuery({ member_id: memberId, per_page: "all" });

  const subscriptions = useMemo(
    () => getMemberProfileSubscriptions(subscriptionsResponse, memberId),
    [memberId, subscriptionsResponse],
  );

  const subscriptionSummary = useMemo(
    () => getCurrentMemberSubscription({ data: subscriptions }, memberId),
    [memberId, subscriptions],
  );

  const subscriptionId = subscriptionSummary?.id || null;

  const {
    data: subscriptionDetailResponse,
    error: subscriptionDetailError,
    isLoading: subscriptionDetailLoading,
    isFetching: subscriptionDetailFetching,
    refetch: refetchSubscriptionDetail,
  } = useGetPlayerSubscriptionQuery(subscriptionId, { skip: !subscriptionId });

  const currentSubscription = useMemo(
    () => getSubscriptionDetail(subscriptionDetailResponse) || subscriptionSummary,
    [subscriptionDetailResponse, subscriptionSummary],
  );

  const selectedSubscriptionSummary = useMemo(
    () =>
      subscriptions.find(
        (subscription) => String(subscription.id) === String(selectedSubscriptionId),
      ) || null,
    [selectedSubscriptionId, subscriptions],
  );

  const {
    data: selectedSubscriptionResponse,
    error: selectedSubscriptionError,
    isLoading: selectedSubscriptionLoading,
    isFetching: selectedSubscriptionFetching,
    refetch: refetchSelectedSubscription,
  } = useGetPlayerSubscriptionQuery(selectedSubscriptionId, { skip: !selectedSubscriptionId });

  const selectedSubscription = useMemo(
    () => getSubscriptionDetail(selectedSubscriptionResponse) || selectedSubscriptionSummary,
    [selectedSubscriptionResponse, selectedSubscriptionSummary],
  );

  const {
    data: attendancesResponse,
    error: attendancesError,
    isLoading: attendancesLoading,
    isFetching: attendancesFetching,
    refetch: refetchAttendances,
  } = useGetMemberAttendancesQuery(memberId);

  const attendances = useMemo(
    () => getMemberProfileAttendances(attendancesResponse, memberId),
    [attendancesResponse, memberId],
  );

  const {
    data: lockersResponse,
    error: lockersError,
    isLoading: lockersLoading,
    isFetching: lockersFetching,
    refetch: refetchLockers,
  } = useGetLockersQuery({
    branch_id: initialMember?.branch_id || undefined,
    per_page: "all",
  });

  const lockers = useMemo(
    () => getMemberProfileLockers(lockersResponse, memberId),
    [lockersResponse, memberId],
  );

  const summary = useMemo(
    () =>
      createMemberProfileSummary({
        subscriptions,
        attendances,
        lockers,
        memberId,
      }),
    [attendances, lockers, memberId, subscriptions],
  );

  function retrySubscription() {
    if (subscriptionsError || !subscriptionId) {
      refetchSubscriptions();
      return;
    }
    refetchSubscriptionDetail();
  }

  function showSubscription(subscription) {
    setSelectedSubscriptionId(subscription?.id || null);
  }

  const subscriptionsListLoading = subscriptionsLoading || subscriptionsFetching;
  const currentSubscriptionLoading =
    subscriptionsListLoading ||
    Boolean(subscriptionId && (subscriptionDetailLoading || subscriptionDetailFetching));
  const playerName = getMemberDisplayName(initialMember);

  const playerBranchId = initialMember?.branch_id || currentUser?.branch_id;

  const branchAttendances = useMemo(() => {
    if (!playerBranchId) return attendances;
    return attendances.filter(
      (a) => !a.branch_id || String(a.branch_id) === String(playerBranchId)
    );
  }, [attendances, playerBranchId]);

  return (
    <div className="min-h-screen dashboard-bg text-app-text" dir="rtl">
      <PlayerAppHeader user={currentUser || initialMember} />

      <main className="mx-auto max-w-5xl px-4 pb-14 sm:px-6">
        {/* Navigation Bar */}
        <PlayerNavbar activeTab={activeTab} onTabChange={setActiveTab} />

        {/* iPhone PWA Tip */}
        {showIosTip && (
          <div className="mb-6 flex items-start justify-between gap-3 rounded-2xl border border-app-yellow/30 bg-app-yellow/10 p-4 text-xs text-app-yellow-soft sm:text-sm">
            <div className="flex items-start gap-3">
              <span className="text-xl leading-none">📱</span>
              <div className="space-y-1 text-right">
                <p className="font-semibold text-white">طريقة استخدام حسابك كتطبيق على iPhone:</p>
                <p className="text-app-muted-light">
                  اضغط على زر المشاركة{" "}
                  <span className="inline-block rounded bg-white/10 px-1 py-0.5 text-app-yellow">
                    (Share ⎋)
                  </span>{" "}
                  في متصفح Safari، ثم اختر{" "}
                  <span className="font-semibold text-white">إضافة إلى الشاشة الرئيسية (Add to Home Screen)</span>{" "}
                  ليتم تثبيت أيقونة النادي وتفتح كتطبيق كامل.
                </p>
              </div>
            </div>
            <button
              type="button"
              onClick={handleDismissTip}
              className="rounded-lg p-1 text-app-muted-light transition hover:bg-white/10 hover:text-white"
              aria-label="إغلاق التنبيه"
            >
              ✕
            </button>
          </div>
        )}

        {/* Tab Contents */}
        {activeTab === "profile" && (
          <MemberDetails
            member={initialMember}
            branches={initialBranches}
            subscription={currentSubscription}
            subscriptions={subscriptions}
            attendances={branchAttendances}
            lockers={lockers}
            summary={summary}
            onShowSubscription={showSubscription}
            currentSubscriptionLoading={currentSubscriptionLoading}
            subscriptionsLoading={subscriptionsListLoading}
            attendancesLoading={attendancesLoading || attendancesFetching}
            lockersLoading={lockersLoading || lockersFetching}
            subscriptionsError={subscriptionsError}
            currentSubscriptionError={subscriptionsError || subscriptionDetailError}
            attendancesError={attendancesError}
            lockersError={lockersError}
            onRetrySubscriptions={retrySubscription}
            onRetryAttendances={refetchAttendances}
            onRetryLockers={refetchLockers}
          />
        )}

        {activeTab === "attendance" && (
          <PlayerAttendanceTab
            memberId={memberId}
            attendances={branchAttendances}
            isLoading={attendancesLoading || attendancesFetching}
            error={attendancesError}
            onRetry={refetchAttendances}
            branchId={playerBranchId}
          />
        )}

        {activeTab === "payments" && (
          <PlayerPaymentsTab
            subscriptions={subscriptions}
            isLoading={subscriptionsListLoading}
            branchId={playerBranchId}
          />
        )}

        {activeTab === "plans" && <PlayerPlansTab branchId={playerBranchId} />}

        {activeTab === "coaches" && <PlayerCoachesTab branchId={playerBranchId} />}

        {/* Subscription Details Modal */}
        <Modal
          open={Boolean(selectedSubscriptionId)}
          onClose={() => setSelectedSubscriptionId(null)}
          title="تفاصيل الاشتراك"
          subtitle={playerName}
        >
          <SubscriptionDetails
            subscription={selectedSubscription}
            memberFallback={initialMember}
            error={selectedSubscriptionError}
            isLoading={selectedSubscriptionLoading || selectedSubscriptionFetching}
            onRetry={refetchSelectedSubscription}
            showActions={false}
          />
        </Modal>
      </main>
    </div>
  );
}
