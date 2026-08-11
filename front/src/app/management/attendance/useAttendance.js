import { useEffect, useMemo, useState } from "react";
import {
  useDeductAttendanceMutation,
  useBulkCheckOutMutation,
  useGetAttendancesQuery,
  useGetMemberQuery,
  useGetMemberSubscriptionsQuery,
  useManualCheckInMutation,
  useManualCheckOutMutation,
  useQrCheckInMutation,
  useQrCheckOutMutation,
  useRollbackAttendanceMutation,
} from "@/lib/api/attendanceApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  useGetLockersQuery,
  useLazyGetLockersQuery,
  useReserveLockerMutation,
  useReleaseLockerReservationMutation,
} from "@/lib/api/lockersApi";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useGetStaffQuery } from "@/lib/api/staffApi";
import { ATTENDANCE_SCAN_MODES } from "./attendanceConstants";
import {
  attachAttendanceLockers,
  createAttendanceDeductionBody,
  createAttendanceLockerReservation,
  createAttendanceBranchOptions,
  createAttendanceMember,
  createAttendanceRows,
  createAttendanceSubscriptions,
  createAvailableLockerOptions,
  createManualCheckInTimestamp,
  findAttendanceLockerId,
  getInitialAttendanceSelection,
  toggleRequiredSubscription,
} from "./attendanceUtils";

/**
 * Coordinates QR scanning, member data, subscription selection, and attendance mutations.
 */
export function useAttendance({ initialBranches } = {}) {
  const toast = useToast();
  const {
    branches: managementBranches,
    isAllBranches,
    selectedBranchId,
    setSelectedBranchId,
  } = useManagementBranch();
  const branchOptions = useMemo(
    () =>
      createAttendanceBranchOptions(
        managementBranches.length ? managementBranches : initialBranches,
      ),
    [initialBranches, managementBranches],
  );
  const branchId = isAllBranches ? "" : selectedBranchId;
  const [lastAttendanceId, setLastAttendanceId] = useState(null);
  const [pendingAttendanceIds, setPendingAttendanceIds] = useState([]);
  const [scanMode, setScanMode] = useState(ATTENDANCE_SCAN_MODES.CHECK_IN);
  const [scannedMemberId, setScannedMemberId] = useState(null);
  const [alwaysOn, setAlwaysOn] = useState(false);
  const [scannerActive, setScannerActive] = useState(false);
  const [selectedSubscriptionIds, setSelectedSubscriptionIds] = useState([]);
  const [selectedActivityId, setSelectedActivityId] = useState("");
  const [lockerNumber, setLockerNumber] = useState("");
  const [registeredMemberId, setRegisteredMemberId] = useState(null);
  const [attendanceTypeFilter, setAttendanceTypeFilter] = useState("all");
  const [attendanceFromDate, setAttendanceFromDate] = useState("");
  const [attendanceToDate, setAttendanceToDate] = useState("");

  const [qrCheckIn, { isLoading: isCheckingIn }] = useQrCheckInMutation();
  const [qrCheckOut, { isLoading: isCheckingOut }] = useQrCheckOutMutation();
  const [deductAttendance, { isLoading: isDeductingAttendance }] = useDeductAttendanceMutation();
  const [manualCheckIn, { isLoading: isManualCheckingIn }] = useManualCheckInMutation();
  const [manualCheckOut, { isLoading: isManualCheckingOut }] = useManualCheckOutMutation();
  const [bulkCheckOut, { isLoading: isBulkCheckingOut }] = useBulkCheckOutMutation();
  const [rollbackAttendance, { isLoading: isRollingBack }] = useRollbackAttendanceMutation();
  const [loadBranchLockers] = useLazyGetLockersQuery();
  const [reserveLocker, { isLoading: isAssigningLocker }] = useReserveLockerMutation();
  const [releaseLockerReservation, { isLoading: isReleasingLocker }] =
    useReleaseLockerReservationMutation();
  const peopleQueryParams = branchId ? { branch_id: branchId } : {};
  const { currentData: attendanceMembersResponse } = useGetMembersQuery(peopleQueryParams);
  const { currentData: attendanceStaffResponse } = useGetStaffQuery(peopleQueryParams);
  const availableLockersParams = useMemo(
    () => ({ branch_id: branchId, status: "available" }),
    [branchId],
  );
  const {
    currentData: availableLockersResponse,
    error: availableLockersError,
    isLoading: isAvailableLockersLoading,
    isFetching: isAvailableLockersFetching,
    refetch: refetchAvailableLockers,
  } = useGetLockersQuery(availableLockersParams, {
    skip: !branchId || !scannedMemberId,
  });
  const { currentData: branchLockersResponse, refetch: refetchBranchLockers } = useGetLockersQuery(
    branchId ? { branch_id: branchId } : {},
  );
  const attendanceHistoryParams = useMemo(
    () => ({
      attendable_type: attendanceTypeFilter,
      ...(attendanceFromDate ? { from: attendanceFromDate } : {}),
      ...(attendanceToDate ? { to: attendanceToDate } : {}),
    }),
    [attendanceFromDate, attendanceToDate, attendanceTypeFilter],
  );
  const {
    currentData: attendanceHistoryResponse,
    error: attendanceHistoryError,
    isLoading: isAttendanceHistoryLoading,
    isFetching: isAttendanceHistoryFetching,
    refetch: refetchAttendanceHistory,
  } = useGetAttendancesQuery(attendanceHistoryParams);

  const {
    currentData: memberResponse,
    error: memberError,
    isLoading: isMemberLoading,
    isFetching: isMemberFetching,
    refetch: refetchMember,
  } = useGetMemberQuery(scannedMemberId, {
    skip: !scannedMemberId,
  });
  const {
    currentData: memberSubscriptionsResponse,
    error: subscriptionsError,
    isLoading: isSubscriptionsLoading,
    isFetching: isSubscriptionsFetching,
    refetch: refetchSubscriptions,
  } = useGetMemberSubscriptionsQuery(scannedMemberId, {
    skip: !scannedMemberId,
  });
  const activeMember = useMemo(
    () => createAttendanceMember(memberResponse, scannedMemberId),
    [memberResponse, scannedMemberId],
  );
  const attendanceRows = useMemo(() => {
    const rows = attachAttendanceLockers(
      createAttendanceRows(attendanceHistoryResponse, activeMember, {
        members: attendanceMembersResponse,
        staff: attendanceStaffResponse,
      }),
      branchLockersResponse,
    );

    return pendingAttendanceIds.length
      ? rows.filter(
          (row) => !pendingAttendanceIds.some((pendingId) => String(row.id) === String(pendingId)),
        )
      : rows;
  }, [
    activeMember,
    attendanceHistoryResponse,
    attendanceMembersResponse,
    attendanceStaffResponse,
    branchLockersResponse,
    pendingAttendanceIds,
  ]);
  const playerSubscriptions = useMemo(
    () => createAttendanceSubscriptions(memberSubscriptionsResponse),
    [memberSubscriptionsResponse],
  );
  const availableLockerOptions = useMemo(
    () => createAvailableLockerOptions(availableLockersResponse),
    [availableLockersResponse],
  );
  const selectedLockerNumber = useMemo(
    () =>
      availableLockerOptions.some((option) => option.value === String(lockerNumber))
        ? String(lockerNumber)
        : "",
    [availableLockerOptions, lockerNumber],
  );
  const selectedLockerId = useMemo(
    () => findAttendanceLockerId(availableLockersResponse, selectedLockerNumber),
    [availableLockersResponse, selectedLockerNumber],
  );

  useEffect(() => {
    if (!scannedMemberId) return;

    const selection = getInitialAttendanceSelection(playerSubscriptions);
    setSelectedSubscriptionIds(selection.subscriptionIds);
    setSelectedActivityId(selection.activityId);
    setLockerNumber(selection.lockerNumber);
  }, [playerSubscriptions, scannedMemberId]);

  const selectedSubscription = useMemo(
    () =>
      playerSubscriptions.find((subscription) =>
        selectedSubscriptionIds.includes(String(subscription.id)),
      ) || playerSubscriptions[0],
    [playerSubscriptions, selectedSubscriptionIds],
  );
  const activityOptions = useMemo(
    () =>
      (selectedSubscription?.activities || []).map((activity) => ({
        value: String(activity.id),
        label: activity.label,
      })),
    [selectedSubscription],
  );
  const selectedActivity = useMemo(
    () =>
      selectedSubscription?.activities?.find(
        (activity) => String(activity.id) === String(selectedActivityId),
      ) || selectedSubscription?.activities?.[0],
    [selectedActivityId, selectedSubscription],
  );

  const memberErrorMessage = memberError
    ? getApiErrorMessage(memberError, "تعذر تحميل بيانات العضو.")
    : "";
  const subscriptionsErrorMessage = subscriptionsError
    ? getApiErrorMessage(subscriptionsError, "تعذر تحميل اشتراكات العضو.")
    : "";
  const attendancesErrorMessage = attendanceHistoryError
    ? getApiErrorMessage(attendanceHistoryError, "تعذر تحميل سجل الحضور.")
    : "";
  const availableLockersErrorMessage = availableLockersError
    ? getApiErrorMessage(availableLockersError, "تعذر تحميل الخزائن المتاحة.")
    : "";
  const isProcessingScan = isCheckingIn || isCheckingOut;

  /**
   * Synchronizes the attendance branch selector with the global navigation selection.
   */
  function setBranchId(value) {
    setSelectedBranchId(value || "all");
  }

  function resetAttendanceFilters() {
    setAttendanceTypeFilter("all");
    setAttendanceFromDate("");
    setAttendanceToDate("");
  }

  /**
   * Clears selections that belong to the previously scanned member.
   */
  function resetMemberSelection() {
    setSelectedSubscriptionIds([]);
    setSelectedActivityId("");
    setLockerNumber("");
    setRegisteredMemberId(null);
  }

  /**
   * Activates a scanned member and stores the attendance pending deduction.
   */
  function selectScannedMember(memberId, attendanceId = null) {
    resetMemberSelection();
    setScannedMemberId(memberId);
    setLastAttendanceId(attendanceId);
    if (attendanceId) {
      setPendingAttendanceIds((current) =>
        current.some((id) => String(id) === String(attendanceId))
          ? current
          : [...current, attendanceId],
      );
    }
  }

  /**
   * Checks whether the scanner can start in the current mode.
   */
  function canStartScanner() {
    if (scanMode === ATTENDANCE_SCAN_MODES.CHECK_IN && !branchId) {
      toast.warning("اختر الفرع قبل تشغيل قارئ الحضور.");
      return false;
    }

    return true;
  }

  /**
   * Synchronizes the permanent scanner option with the actual camera state.
   */
  function handleAlwaysOnChange(checked) {
    if (checked && !canStartScanner()) return;

    setAlwaysOn(checked);
    setScannerActive(checked);
  }

  /**
   * Starts a one-time camera scan after validating the selected branch.
   */
  function handleScanClick() {
    if (!canStartScanner()) return;
    setScannerActive(true);
  }

  /**
   * Updates the scan mode and clears a stopped permanent-scan preference.
   */
  function handleScanModeChange(mode) {
    setScanMode(mode);
  }

  /**
   * Reports camera permission and initialization failures to the operator.
   */
  function handleScannerError() {
    setScannerActive(false);
    setAlwaysOn(false);
    toast.error("تعذر تشغيل الكاميرا. تحقق من الصلاحيات ومن استخدام اتصال آمن.");
  }

  /**
   * Registers a backend check-in from a decoded member QR value.
   */
  async function handleCheckIn(decodedText) {
    if (!branchId) {
      toast.warning("اختر الفرع قبل تسجيل الدخول.");
      return;
    }

    try {
      const response = await qrCheckIn({
        qr_code: decodedText,
        branch_id: Number(branchId),
      }).unwrap();
      const memberId = response?.data?.member_id;
      const attendanceId = response?.data?.attendance_id || response?.data?.id || null;

      if (memberId) {
        selectScannedMember(memberId, attendanceId);
      }

      toast.success(response?.message || "تم تسجيل الدخول بنجاح.");

      if (!memberId) {
        toast.warning("تم تسجيل الدخول، لكن الاستجابة لم تتضمن رقم العضو.");
      } else if (!attendanceId) {
        toast.warning("تم تسجيل الدخول، لكن لا توجد حركة متاحة لخصم الجلسة.");
      }
    } catch (error) {
      toast.error(getApiErrorMessage(error, "فشل تسجيل الدخول."));
    }
  }

  /**
   * Releases the locker attached to an attendance record after checkout or rollback.
   */
  async function releaseAttendanceLocker(attendanceRecord) {
    const lockerNumber = attendanceRecord?.lockerNumber;
    let lockerId = attendanceRecord?.lockerId;

    if (!lockerId && lockerNumber) {
      try {
        const lockersResponse = await loadBranchLockers({
          branch_id: attendanceRecord?.branchId || branchId,
        }).unwrap();
        lockerId = findAttendanceLockerId(lockersResponse, lockerNumber);
      } catch (error) {
        toast.warning(getApiErrorMessage(error, `تعذر تحديد الخزانة ${lockerNumber} لفك حجزها.`));
        return false;
      }
    }

    if (!lockerId) {
      if (lockerNumber) {
        toast.warning(`اكتمل الإجراء، لكن تعذر تحديد الخزانة ${lockerNumber} لفك حجزها.`);
        return false;
      }
      return true;
    }

    try {
      await releaseLockerReservation(lockerId).unwrap();
      toast.success(`تم فك حجز الخزانة ${lockerNumber || `#${lockerId}`}.`);
      return true;
    } catch (error) {
      const status = error?.status || error?.originalStatus;
      if (status === 404) return true;

      toast.warning(
        getApiErrorMessage(
          error,
          `اكتمل الإجراء، لكن تعذر فك حجز الخزانة ${lockerNumber || `#${lockerId}`}.`,
        ),
      );
      return false;
    }
  }

  /**
   * Registers a backend check-out and refreshes the scanned member history.
   */
  async function handleCheckOut(decodedText) {
    try {
      const response = await qrCheckOut({ qr_code: decodedText }).unwrap();
      const memberId = response?.data?.member_id;
      const openAttendance = attendanceRows.find(
        (row) =>
          row.isOpen &&
          row.attendableType === "member" &&
          String(row.attendableId) === String(memberId),
      );

      if (openAttendance) {
        await releaseAttendanceLocker(openAttendance);
      }

      if (memberId) {
        selectScannedMember(memberId);
      }

      toast.success(response?.message || "تم تسجيل الخروج بنجاح.");
    } catch (error) {
      toast.error(getApiErrorMessage(error, "فشل تسجيل الخروج."));
    }
  }

  /**
   * Registers a manual check-in for a member or staff record.
   * When successful, activates the scanned-member card (same as QR flow).
   */
  async function handleManualCheckIn({ attendableType, attendableId, checkInTime = "" }) {
    if (!branchId) {
      toast.warning("اختر الفرع قبل تسجيل الدخول اليدوي.");
      return false;
    }

    try {
      const checkInAt = createManualCheckInTimestamp(checkInTime);
      const response = await manualCheckIn({
        attendable_type: attendableType,
        attendable_id: Number(attendableId),
        branch_id: Number(branchId),
        ...(checkInAt ? { check_in_at: checkInAt } : {}),
      }).unwrap();

      const memberId =
        response?.data?.member_id || (attendableType === "member" ? Number(attendableId) : null);
      const attendanceId = response?.data?.attendance_id || response?.data?.id || null;

      if (memberId) {
        selectScannedMember(memberId, attendanceId);
      }

      toast.success(response?.message || "تم تسجيل الدخول بنجاح.");
      return true;
    } catch (error) {
      toast.error(getApiErrorMessage(error, "فشل تسجيل الدخول اليدوي."));
      return false;
    }
  }

  /**
   * Checks out an open attendance record by its attendance id.
   */
  async function handleManualCheckOut(attendanceRecordOrId) {
    const attendanceRecord =
      typeof attendanceRecordOrId === "object"
        ? attendanceRecordOrId
        : attendanceRows.find((row) => String(row.id) === String(attendanceRecordOrId));
    const attendanceId = attendanceRecord?.id || attendanceRecordOrId;

    try {
      const response = await manualCheckOut(Number(attendanceId)).unwrap();
      await releaseAttendanceLocker(attendanceRecord);
      await refetchAttendanceHistory();
      toast.success(response?.message || "تم تسجيل الانصراف بنجاح.");
      return true;
    } catch (error) {
      toast.error(getApiErrorMessage(error, "فشل تسجيل الانصراف اليدوي."));
      return false;
    }
  }

  /**
   * Checks out all open attendance records for one branch and subscription plan.
   */
  async function handleBulkCheckOut(subscriptionPlanId) {
    if (!branchId) {
      toast.warning("اختر الفرع قبل تسجيل الانصراف الجماعي.");
      return null;
    }

    try {
      const response = await bulkCheckOut({
        branch_id: Number(branchId),
        subscription_plan_id: Number(subscriptionPlanId),
      }).unwrap();
      const result = response?.data || {};
      toast.success(response?.message || "اكتملت عملية الانصراف الجماعي.");
      return result;
    } catch (error) {
      toast.error(getApiErrorMessage(error, "فشل تسجيل الانصراف الجماعي."));
      return null;
    }
  }

  /**
   * Returns deducted sessions and optionally removes the complete attendance record.
   */
  async function handleRollbackAttendance(attendanceRecordOrId, playerSubscriptionIds = []) {
    const attendanceRecord =
      typeof attendanceRecordOrId === "object"
        ? attendanceRecordOrId
        : attendanceRows.find((row) => String(row.id) === String(attendanceRecordOrId));
    const attendanceId = attendanceRecord?.id || attendanceRecordOrId;

    try {
      const response = await rollbackAttendance({
        attendanceId: Number(attendanceId),
        playerSubscriptionIds,
      }).unwrap();
      await releaseAttendanceLocker(attendanceRecord);
      await refetchAttendanceHistory();
      toast.success(response?.message || "تم التراجع عن الحضور وإرجاع الجلسة بنجاح.");
      return true;
    } catch (error) {
      toast.error(getApiErrorMessage(error, "فشل التراجع عن سجل الحضور."));
      return false;
    }
  }

  /**
   * Routes a decoded QR value to the selected attendance operation.
   */
  async function handleScanSuccess(decodedText) {
    if (!alwaysOn) {
      setScannerActive(false);
    }

    if (scanMode === ATTENDANCE_SCAN_MODES.CHECK_OUT) {
      await handleCheckOut(decodedText);
      return;
    }

    await handleCheckIn(decodedText);
  }

  /**
   * Toggles a subscription while keeping at least one selection.
   */
  function handleSubscriptionToggle(subscriptionId) {
    setSelectedSubscriptionIds((current) => toggleRequiredSubscription(current, subscriptionId));
    setRegisteredMemberId(null);
  }

  /**
   * Updates the activity chosen for the pending attendance.
   */
  function handleActivityChange(value) {
    setSelectedActivityId(String(value));
    setRegisteredMemberId(null);
  }

  /**
   * Updates the optional locker reference for the pending attendance.
   */
  function handleLockerChange(value) {
    setLockerNumber(value);
    setRegisteredMemberId(null);
  }

  /**
   * Deducts the selected subscription sessions for the pending check-in.
   */
  async function handleRegister() {
    if (!activeMember || !lastAttendanceId) {
      toast.warning("امسح بطاقة الدخول قبل تأكيد الحضور.");
      return;
    }

    const deductionBody = createAttendanceDeductionBody(selectedSubscriptionIds);
    if (!deductionBody.player_subscription_ids.length) {
      toast.warning("اختر اشتراكًا واحدًا على الأقل.");
      return;
    }

    if (selectedLockerNumber && !selectedLockerId) {
      toast.error("تعذر تحديد الخزانة المختارة. أعد اختيارها ثم حاول مرة أخرى.");
      return;
    }

    let assignedLockerId = null;

    if (selectedLockerId) {
      try {
        await reserveLocker({
          id: selectedLockerId,
          ...createAttendanceLockerReservation(activeMember.id),
        }).unwrap();
        assignedLockerId = selectedLockerId;
      } catch (error) {
        toast.error(
          getApiErrorMessage(
            error,
            `تعذر إسناد الخزانة ${selectedLockerNumber}. لم يتم تأكيد الحضور.`,
          ),
        );
        return;
      }
    }

    try {
      const response = await deductAttendance({
        attendanceId: lastAttendanceId,
        body: deductionBody,
      }).unwrap();

      setRegisteredMemberId(activeMember.id);
      setPendingAttendanceIds((current) =>
        current.filter((attendanceId) => String(attendanceId) !== String(lastAttendanceId)),
      );
      setLastAttendanceId(null);
      toast.success(response?.message || "تم خصم الجلسة وتأكيد الحضور بنجاح.");
      await refetchAttendanceHistory();
      await refetchAvailableLockers();
      await refetchBranchLockers();
    } catch (error) {
      if (assignedLockerId) {
        try {
          await releaseLockerReservation(assignedLockerId).unwrap();
        } catch {
          toast.warning(`تعذر خصم الجلسة وتعذر إلغاء إسناد الخزانة ${selectedLockerNumber}.`);
        }
      }
      toast.error(getApiErrorMessage(error, "فشل خصم الجلسة."));
    }
  }

  return {
    activeMember,
    attendanceRows,
    playerSubscriptions,
    selectedSubscription,
    selectedActivity,
    activityOptions,
    selectedSubscriptionIds,
    selectedActivityId,
    lockerNumber: selectedLockerNumber,
    availableLockerOptions,
    branchId,
    branchOptions,
    scanMode,
    alwaysOn,
    scannerActive,
    scannedMemberId,
    memberErrorMessage,
    subscriptionsErrorMessage,
    attendancesErrorMessage,
    availableLockersErrorMessage,
    isMemberLoading: isMemberLoading || isMemberFetching,
    isSubscriptionsLoading: isSubscriptionsLoading || isSubscriptionsFetching,
    isAttendancesLoading: isAttendanceHistoryLoading || isAttendanceHistoryFetching,
    isAvailableLockersLoading:
      isAvailableLockersLoading ||
      (isAvailableLockersFetching && availableLockerOptions.length === 0),
    isProcessingScan,
    isRegistering: isDeductingAttendance || isAssigningLocker,
    isManualCheckingIn,
    isManualCheckingOut: isManualCheckingOut || isReleasingLocker,
    isBulkCheckingOut,
    isRollingBack: isRollingBack || isReleasingLocker,
    isRegistered: Boolean(activeMember) && registeredMemberId === activeMember?.id,
    isPendingDeduction: Boolean(lastAttendanceId),
    attendanceTypeFilter,
    attendanceFromDate,
    attendanceToDate,
    hasAttendanceFilters: Boolean(
      attendanceTypeFilter !== "all" || attendanceFromDate || attendanceToDate,
    ),
    setBranchId,
    setAttendanceTypeFilter,
    setAttendanceFromDate,
    setAttendanceToDate,
    resetAttendanceFilters,
    handleAlwaysOnChange,
    handleScanClick,
    handleScanModeChange,
    handleScanSuccess,
    handleScannerError,
    handleSubscriptionToggle,
    handleActivityChange,
    handleLockerChange,
    handleRegister,
    handleManualCheckIn,
    handleManualCheckOut,
    handleBulkCheckOut,
    handleRollbackAttendance,
    stopScanner: () => setScannerActive(false),
    retryMember: () => scannedMemberId && refetchMember(),
    retrySubscriptions: () => scannedMemberId && refetchSubscriptions(),
    retryAttendances: refetchAttendanceHistory,
    retryAvailableLockers: refetchAvailableLockers,
  };
}
