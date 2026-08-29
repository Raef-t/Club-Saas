"use client";

import PageHeader from "@/components/common/PageHeader";
import Modal from "@/components/ui/Modal";
import AttendancePlayerCard from "./AttendancePlayerCard";
import AttendanceScanner from "./AttendanceScanner";
import AttendanceTable from "./AttendanceTable";
import ManualAttendanceForm from "./ManualAttendanceForm";
import { useAttendance } from "./useAttendance";

/**
 * Composes the interactive attendance workflow from its focused components.
 */
export default function AttendanceClient({ initialBranches }) {
  const attendance = useAttendance({ initialBranches });
  const isPlayerModalBusy =
    attendance.isRegistering || (attendance.requiresCheckInNote && attendance.isManualCheckingIn);

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النادي"
        title="الحضور والمغادرة"
        subtitle="مسح بطاقة العضو، التحقق من اشتراكاته، وتأكيد خصم الجلسة."
      />

      <section className="mx-auto grid w-full max-w-6xl min-w-0 items-start gap-5 xl:grid-cols-2">
        <ManualAttendanceForm attendance={attendance} />
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

      <Modal
        open={attendance.isPlayerModalOpen}
        onClose={isPlayerModalBusy ? undefined : attendance.closePlayerModal}
        title="تأكيد حضور اللاعب"
        subtitle={
          attendance.requiresCheckInNote
            ? "أدخل سبب الحضور خارج الموعد ثم أعد تسجيل الدخول."
            : "راجع الاشتراك وأدخل سبب الحضور عند الحاجة قبل خصم الجلسة."
        }
        contentClassName="p-0"
      >
        <div className="w-full">
          <AttendancePlayerCard
            member={attendance.activeMember}
            selectedSubscription={attendance.selectedSubscription}
            selectedActivity={attendance.selectedActivity}
            playerSubscriptions={attendance.playerSubscriptions}
            selectedSubscriptionIds={attendance.selectedSubscriptionIds}
            attendanceNote={attendance.attendanceNote}
            attendanceErrorMessage={attendance.attendanceModalErrorMessage}
            requiresCheckInNote={attendance.requiresCheckInNote}
            lockerNumber={attendance.lockerNumber}
            availableLockerOptions={attendance.availableLockerOptions}
            isMemberLoading={attendance.isMemberLoading}
            memberErrorMessage={attendance.memberErrorMessage}
            isSubscriptionsLoading={attendance.isSubscriptionsLoading}
            subscriptionsErrorMessage={attendance.subscriptionsErrorMessage}
            isAvailableLockersLoading={attendance.isAvailableLockersLoading}
            availableLockersErrorMessage={attendance.availableLockersErrorMessage}
            isRegistered={attendance.isRegistered}
            isPendingDeduction={attendance.isPendingDeduction}
            isRegistering={isPlayerModalBusy}
            onRetryMember={attendance.retryMember}
            onRetrySubscriptions={attendance.retrySubscriptions}
            onRetryAvailableLockers={attendance.retryAvailableLockers}
            onSubscriptionToggle={attendance.handleSubscriptionToggle}
            onLockerChange={attendance.handleLockerChange}
            onAttendanceNoteChange={attendance.handleAttendanceNoteChange}
            onRegister={
              attendance.requiresCheckInNote
                ? attendance.handleRetryManualCheckIn
                : attendance.handleRegister
            }
          />
        </div>
      </Modal>

      <AttendanceTable
        rows={attendance.attendanceRows}
        isLoading={attendance.isAttendancesLoading}
        errorMessage={attendance.attendancesErrorMessage}
        onRetry={attendance.retryAttendances}
        typeFilter={attendance.attendanceTypeFilter}
        statusFilter={attendance.attendanceStatusFilter}
        fromDate={attendance.attendanceFromDate}
        toDate={attendance.attendanceToDate}
        hasFilters={attendance.hasAttendanceFilters}
        onTypeFilterChange={attendance.setAttendanceTypeFilter}
        onStatusFilterChange={attendance.setAttendanceStatusFilter}
        onFromDateChange={attendance.setAttendanceFromDate}
        onToDateChange={attendance.setAttendanceToDate}
        onResetFilters={attendance.resetAttendanceFilters}
        onCheckOut={attendance.handleManualCheckOut}
        onRollback={attendance.handleRollbackAttendance}
        isCheckingOut={attendance.isManualCheckingOut}
        isRollingBack={attendance.isRollingBack}
      />
    </div>
  );
}
