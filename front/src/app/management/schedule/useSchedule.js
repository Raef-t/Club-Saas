import { useMemo } from "react";
import { useGetScheduleQuery } from "@/lib/api/scheduleApi";
import { useGetBranchHolidaysQuery, useGetBranchSettingsQuery } from "@/lib/api/branchesApi";
import { useToast } from "@/components/ui/Toast";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import { getApiErrorMessage } from "@/lib/apiError";
import {
  createScheduleDataFromApi,
  createScheduleSettingsFromApi,
  createWeeklyHolidayDayKeys,
  generateTimeSlots,
} from "./scheduleUtils";
import { openSchedulePrintWindow } from "./schedulePrint";

/**
 * Coordinates backend schedule data and print actions.
 */
export function useSchedule({ initialSchedule, selectedBranchId = "all" } = {}) {
  const toast = useToast();
  const { formatTime } = useTimeFormat();
  const initialScopedSchedule = selectedBranchId === "all" ? initialSchedule : null;
  const branchId = selectedBranchId !== "all" ? selectedBranchId : null;
  const {
    currentData: apiSchedule,
    error: scheduleError,
    isLoading: isScheduleLoading,
    isFetching: isScheduleFetching,
    refetch: refetchSchedule,
  } = useGetScheduleQuery(selectedBranchId);
  const {
    currentData: branchSettings,
    error: settingsError,
    isLoading: isSettingsLoading,
    isFetching: isSettingsFetching,
    refetch: refetchSettings,
  } = useGetBranchSettingsQuery(branchId, { skip: !branchId });
  const {
    currentData: branchHolidays,
    error: holidaysError,
    isLoading: isHolidaysLoading,
    isFetching: isHolidaysFetching,
    refetch: refetchHolidays,
  } = useGetBranchHolidaysQuery(branchId, { skip: !branchId });

  const resolvedSchedule = apiSchedule || initialScopedSchedule;
  const settings = useMemo(() => createScheduleSettingsFromApi(branchSettings), [branchSettings]);
  const morningSlots = useMemo(
    () => generateTimeSlots(settings.morningStart, settings.morningEnd, settings.slotDuration),
    [settings],
  );
  const eveningSlots = useMemo(
    () => generateTimeSlots(settings.eveningStart, settings.eveningEnd, settings.slotDuration),
    [settings],
  );
  const holidayDayKeys = useMemo(
    () => createWeeklyHolidayDayKeys(branchHolidays),
    [branchHolidays],
  );
  const scheduleData = useMemo(
    () => createScheduleDataFromApi(resolvedSchedule, morningSlots, eveningSlots, selectedBranchId),
    [eveningSlots, morningSlots, resolvedSchedule, selectedBranchId],
  );

  /**
   * Opens the sanitized printable schedule in a separate browser window.
   */
  function handlePrint() {
    const opened = openSchedulePrintWindow({
      morningSlots,
      eveningSlots,
      scheduleData,
      holidayDayKeys,
      formatTime,
    });

    if (!opened) {
      toast.warning("اسمح بالنوافذ المنبثقة لطباعة الجدول.");
    }
  }

  /**
   * Retries the schedule and the selected branch display settings together.
   */
  function retrySchedule() {
    refetchSchedule();
    if (branchId) {
      refetchSettings();
      refetchHolidays();
    }
  }

  const scheduleErrorMessage = scheduleError
    ? getApiErrorMessage(scheduleError, "تعذر تحديث بيانات الجدول.")
    : settingsError
      ? getApiErrorMessage(settingsError, "تعذر تحميل ساعات عمل الفرع.")
      : holidaysError
        ? getApiErrorMessage(holidaysError, "تعذر تحميل عطلة الفرع.")
        : "";

  return {
    settings,
    scheduleData,
    morningSlots,
    eveningSlots,
    holidayDayKeys,
    scheduleErrorMessage,
    isLoading:
      (!resolvedSchedule && isScheduleLoading) ||
      Boolean(branchId && (isSettingsLoading || isHolidaysLoading)),
    isRefreshing: isScheduleFetching || isSettingsFetching || isHolidaysFetching,
    handlePrint,
    retrySchedule,
  };
}
