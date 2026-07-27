import { useEffect, useMemo, useState } from "react";
import {
  useDeductAttendanceMutation,
  useGetMemberAttendancesQuery,
  useGetMemberQuery,
  useGetMemberSubscriptionsQuery,
  useQrCheckInMutation,
  useQrCheckOutMutation,
} from "@/lib/api/attendanceApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { ATTENDANCE_SCAN_MODES } from "./attendanceConstants";
import {
  createAttendanceBranchOptions,
  createAttendanceMember,
  createAttendanceRows,
  createAttendanceSubscriptions,
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
  const [scanMode, setScanMode] = useState(ATTENDANCE_SCAN_MODES.CHECK_IN);
  const [scannedMemberId, setScannedMemberId] = useState(null);
  const [alwaysOn, setAlwaysOn] = useState(false);
  const [scannerActive, setScannerActive] = useState(false);
  const [selectedSubscriptionIds, setSelectedSubscriptionIds] = useState([]);
  const [selectedActivityId, setSelectedActivityId] = useState("");
  const [lockerNumber, setLockerNumber] = useState("");
  const [registeredMemberId, setRegisteredMemberId] = useState(null);

  const [qrCheckIn, { isLoading: isCheckingIn }] = useQrCheckInMutation();
  const [qrCheckOut, { isLoading: isCheckingOut }] = useQrCheckOutMutation();
  const [deductAttendance, { isLoading: isRegistering }] = useDeductAttendanceMutation();

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
  const {
    currentData: memberAttendancesResponse,
    error: attendancesError,
    isLoading: isAttendancesLoading,
    isFetching: isAttendancesFetching,
    refetch: refetchAttendances,
  } = useGetMemberAttendancesQuery(scannedMemberId, {
    skip: !scannedMemberId,
  });

  const activeMember = useMemo(
    () => createAttendanceMember(memberResponse, scannedMemberId),
    [memberResponse, scannedMemberId],
  );
  const attendanceRows = useMemo(
    () => createAttendanceRows(memberAttendancesResponse, activeMember),
    [activeMember, memberAttendancesResponse],
  );
  const playerSubscriptions = useMemo(
    () => createAttendanceSubscriptions(memberSubscriptionsResponse),
    [memberSubscriptionsResponse],
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
  const attendancesErrorMessage = attendancesError
    ? getApiErrorMessage(attendancesError, "تعذر تحميل سجل حضور العضو.")
    : "";
  const isProcessingScan = isCheckingIn || isCheckingOut;

  /**
   * Synchronizes the attendance branch selector with the global navigation selection.
   */
  function setBranchId(value) {
    setSelectedBranchId(value || "all");
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
   * Registers a backend check-out and refreshes the scanned member history.
   */
  async function handleCheckOut(decodedText) {
    try {
      const response = await qrCheckOut({ qr_code: decodedText }).unwrap();
      const memberId = response?.data?.member_id;

      if (memberId) {
        selectScannedMember(memberId);
      }

      toast.success(response?.message || "تم تسجيل الخروج بنجاح.");
    } catch (error) {
      toast.error(getApiErrorMessage(error, "فشل تسجيل الخروج."));
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

    const subscriptionIds = selectedSubscriptionIds.map(Number).filter(Number.isFinite);
    if (!subscriptionIds.length) {
      toast.warning("اختر اشتراكًا واحدًا على الأقل.");
      return;
    }

    try {
      const response = await deductAttendance({
        attendanceId: lastAttendanceId,
        body: {
          player_subscription_ids: subscriptionIds,
        },
      }).unwrap();

      setRegisteredMemberId(activeMember.id);
      setLastAttendanceId(null);
      toast.success(response?.message || "تم خصم الجلسة وتأكيد الحضور بنجاح.");
      await refetchAttendances();
    } catch (error) {
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
    lockerNumber,
    branchId,
    branchOptions,
    scanMode,
    alwaysOn,
    scannerActive,
    scannedMemberId,
    memberErrorMessage,
    subscriptionsErrorMessage,
    attendancesErrorMessage,
    isMemberLoading: isMemberLoading || isMemberFetching,
    isSubscriptionsLoading: isSubscriptionsLoading || isSubscriptionsFetching,
    isAttendancesLoading: isAttendancesLoading || isAttendancesFetching,
    isProcessingScan,
    isRegistering,
    isRegistered: Boolean(activeMember) && registeredMemberId === activeMember?.id,
    isPendingDeduction: Boolean(lastAttendanceId),
    setBranchId,
    handleAlwaysOnChange,
    handleScanClick,
    handleScanModeChange,
    handleScanSuccess,
    handleScannerError,
    handleSubscriptionToggle,
    handleActivityChange,
    handleLockerChange,
    handleRegister,
    stopScanner: () => setScannerActive(false),
    retryMember: () => scannedMemberId && refetchMember(),
    retrySubscriptions: () => scannedMemberId && refetchSubscriptions(),
    retryAttendances: () => scannedMemberId && refetchAttendances(),
  };
}
