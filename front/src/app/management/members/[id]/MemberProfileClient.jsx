"use client";

import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import MemberDetails from "../MemberDetails";
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
} from "../memberProfileUtils";

function getMemberDisplayName(member) {
  return (
    member?.person?.full_name ||
    `${member?.first_name || ""} ${member?.last_name || ""}`.trim() ||
    `اللاعب #${member?.id || "-"}`
  );
}

export default function MemberProfileClient({ memberId, initialMember, initialBranches = [] }) {
  const [subscriptionModalOpen, setSubscriptionModalOpen] = useState(false);

  const {
    data: subscriptionsResponse,
    error: subscriptionsError,
    isLoading: subscriptionsLoading,
    isFetching: subscriptionsFetching,
    refetch: refetchSubscriptions,
  } = useGetPlayerSubscriptionsQuery({ member_id: memberId });
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
  } = useGetLockersQuery({ branch_id: initialMember?.branch_id || undefined });
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

  const subscriptionsListLoading = subscriptionsLoading || subscriptionsFetching;
  const currentSubscriptionLoading =
    subscriptionsListLoading ||
    Boolean(subscriptionId && (subscriptionDetailLoading || subscriptionDetailFetching));
  const playerName = getMemberDisplayName(initialMember);

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة اللاعبين"
        title="الملف الشامل للاعب"
        subtitle={`${playerName} — عرض موحّد للعضوية والاشتراكات والحضور والمدفوعات والخزائن.`}
        action={
          <div className="flex flex-wrap gap-2">
            <Button href="/management/members" tone="outline">
              العودة إلى اللاعبين
            </Button>
            <Button
              href={`/management/members/create?mode=edit&id=${memberId}`}
              style={{ color: "#000000" }}
            >
              تعديل بيانات اللاعب
            </Button>
          </div>
        }
      />

      <MemberDetails
        member={initialMember}
        branches={initialBranches}
        subscription={currentSubscription}
        subscriptions={subscriptions}
        attendances={attendances}
        lockers={lockers}
        summary={summary}
        onShowSubscription={() => setSubscriptionModalOpen(true)}
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

      <Modal
        open={subscriptionModalOpen}
        onClose={() => setSubscriptionModalOpen(false)}
        title="تفاصيل اشتراك اللاعب"
        subtitle={playerName}
      >
        <SubscriptionDetails
          subscription={currentSubscription}
          error={subscriptionsError || subscriptionDetailError}
          isLoading={currentSubscriptionLoading}
          onRetry={retrySubscription}
          showActions={false}
        />
      </Modal>
    </div>
  );
}
