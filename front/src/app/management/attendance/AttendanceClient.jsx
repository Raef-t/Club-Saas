"use client";

import PageHeader from "@/components/common/PageHeader";
import AttendancePlayerCard from "./AttendancePlayerCard";
import AttendanceScanner from "./AttendanceScanner";
import AttendanceTable from "./AttendanceTable";
import { useAttendance } from "./useAttendance";

/**
 * Composes the interactive attendance workflow from its focused components.
 */
export default function AttendanceClient({ initialBranches }) {
  const attendance = useAttendance({ initialBranches });

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النادي"
        title="الحضور والانصراف"
        subtitle="مسح بطاقة العضو، التحقق من اشتراكاته، وتأكيد خصم الجلسة."
      />

      <section
        className="mx-auto grid w-full max-w-5xl min-w-0 items-start justify-center gap-6 xl:grid-cols-[minmax(0,420px)_minmax(0,520px)]"
        dir="ltr"
      >
        <AttendancePlayerCard
          member={attendance.activeMember}
          selectedSubscription={attendance.selectedSubscription}
          selectedActivity={attendance.selectedActivity}
          playerSubscriptions={attendance.playerSubscriptions}
          activityOptions={attendance.activityOptions}
          selectedSubscriptionIds={attendance.selectedSubscriptionIds}
          selectedActivityId={attendance.selectedActivityId}
          lockerNumber={attendance.lockerNumber}
          isMemberLoading={attendance.isMemberLoading}
          memberErrorMessage={attendance.memberErrorMessage}
          isSubscriptionsLoading={attendance.isSubscriptionsLoading}
          subscriptionsErrorMessage={attendance.subscriptionsErrorMessage}
          isRegistered={attendance.isRegistered}
          isPendingDeduction={attendance.isPendingDeduction}
          isRegistering={attendance.isRegistering}
          onRetryMember={attendance.retryMember}
          onRetrySubscriptions={attendance.retrySubscriptions}
          onSubscriptionToggle={attendance.handleSubscriptionToggle}
          onActivityChange={attendance.handleActivityChange}
          onLockerChange={attendance.handleLockerChange}
          onRegister={attendance.handleRegister}
        />

        <AttendanceScanner
          alwaysOn={attendance.alwaysOn}
          scannerActive={attendance.scannerActive}
          scanMode={attendance.scanMode}
          branchId={attendance.branchId}
          branchOptions={attendance.branchOptions}
          isProcessing={attendance.isProcessingScan}
          onScanModeChange={attendance.handleScanModeChange}
          onBranchChange={attendance.setBranchId}
          onAlwaysOnChange={attendance.handleAlwaysOnChange}
          onScanClick={attendance.handleScanClick}
          onScanSuccess={attendance.handleScanSuccess}
          onScannerError={attendance.handleScannerError}
          onStop={attendance.stopScanner}
        />
      </section>

      <AttendanceTable
        rows={attendance.attendanceRows}
        hasScannedMember={Boolean(attendance.scannedMemberId)}
        isLoading={attendance.isAttendancesLoading}
        errorMessage={attendance.attendancesErrorMessage}
        onRetry={attendance.retryAttendances}
      />
    </div>
  );
}
