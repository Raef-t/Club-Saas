import { useMemo } from "react";
import { useGetScheduleQuery } from "@/lib/api/scheduleApi";
import { useToast } from "@/components/ui/Toast";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { SCHEDULE_DEFAULT_SETTINGS } from "./scheduleConstants";
import { createScheduleDataFromApi, generateTimeSlots } from "./scheduleUtils";
import { openSchedulePrintWindow } from "./schedulePrint";

const DEFAULT_MORNING_SLOTS = generateTimeSlots(
  SCHEDULE_DEFAULT_SETTINGS.morningStart,
  SCHEDULE_DEFAULT_SETTINGS.morningEnd,
  SCHEDULE_DEFAULT_SETTINGS.slotDuration,
);
const DEFAULT_EVENING_SLOTS = generateTimeSlots(
  SCHEDULE_DEFAULT_SETTINGS.eveningStart,
  SCHEDULE_DEFAULT_SETTINGS.eveningEnd,
  SCHEDULE_DEFAULT_SETTINGS.slotDuration,
);

/**
 * Coordinates backend schedule data and print actions.
 */
export function useSchedule({ initialSchedule, selectedBranchId = "all" } = {}) {
  const toast = useToast();
  const { formatTime } = useTimeFormat();
  const initialScopedSchedule = selectedBranchId === "all" ? initialSchedule : null;
  const {
    currentData: apiSchedule,
    error: scheduleError,
    isLoading,
    isFetching,
    refetch,
  } = useGetScheduleQuery(selectedBranchId);

  const resolvedSchedule = apiSchedule || initialScopedSchedule;
  const scheduleData = useMemo(
    () =>
      createScheduleDataFromApi(
        resolvedSchedule,
        DEFAULT_MORNING_SLOTS,
        DEFAULT_EVENING_SLOTS,
        selectedBranchId,
      ),
    [resolvedSchedule, selectedBranchId],
  );

  /**
   * Opens the sanitized printable schedule in a separate browser window.
   */
  function handlePrint() {
    const opened = openSchedulePrintWindow({
      morningSlots: DEFAULT_MORNING_SLOTS,
      eveningSlots: DEFAULT_EVENING_SLOTS,
      scheduleData,
      formatTime,
    });

    if (!opened) {
      toast.warning("اسمح بالنوافذ المنبثقة لطباعة الجدول.");
    }
  }

  return {
    settings: SCHEDULE_DEFAULT_SETTINGS,
    scheduleData,
    morningSlots: DEFAULT_MORNING_SLOTS,
    eveningSlots: DEFAULT_EVENING_SLOTS,
    scheduleErrorMessage: scheduleError
      ? getApiErrorMessage(scheduleError, "تعذر تحديث بيانات الجدول.")
      : "",
    isLoading: !resolvedSchedule && isLoading,
    isRefreshing: isFetching,
    handlePrint,
    retrySchedule: refetch,
  };
}
