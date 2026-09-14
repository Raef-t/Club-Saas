"use client";

import { useState, useMemo } from "react";
import { formatDate } from "@/lib/utils";
import { formatAttendanceTime } from "@/app/management/attendance/attendanceUtils";
import { useGetMemberAttendancesQuery } from "@/lib/api/attendanceApi";

const PERIODS = [
  { id: "all", label: "الكل" },
  { id: "daily", label: "اليوم" },
  { id: "weekly", label: "هذا الأسبوع" },
  { id: "monthly", label: "هذا الشهر" },
];

function getRecordMinutes(record) {
  if (typeof record.duration_minutes === "number" && record.duration_minutes > 0) {
    return record.duration_minutes;
  }
  const checkIn = record.check_in_at || record.check_in;
  const checkOut = record.check_out_at || record.check_out;
  if (checkIn && checkOut) {
    const diffMs = new Date(checkOut).getTime() - new Date(checkIn).getTime();
    if (!isNaN(diffMs) && diffMs > 0) {
      return Math.round(diffMs / 60000);
    }
  }
  return 0;
}

function formatSessionDuration(record) {
  const checkOut = record.check_out_at || record.check_out;
  const isCurrent = !checkOut || record.status === "checked_in";
  if (isCurrent) return "جلسة حالية";

  if (record.duration_formatted) {
    return record.duration_formatted;
  }

  const mins = getRecordMinutes(record);
  if (!mins || mins <= 0) return "مكتمل";
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  if (h > 0 && m > 0) return `${h} س ${m} د`;
  if (h > 0) return `${h} ساعة`;
  return `${m} دقيقة`;
}

export default function PlayerAttendanceTab({
  memberId,
  attendances: initialAttendances = [],
  isLoading: initialLoading,
  error: initialError,
  onRetry: initialRetry,
  branchId,
}) {
  const [selectedPeriod, setSelectedPeriod] = useState("all");

  const queryParams = useMemo(() => {
    return {
      memberId,
      period: selectedPeriod,
      branch_id: branchId,
    };
  }, [memberId, selectedPeriod, branchId]);

  const {
    data: attendanceApiResponse,
    isLoading: isQueryLoading,
    isFetching: isQueryFetching,
    error: queryError,
    refetch,
  } = useGetMemberAttendancesQuery(queryParams, {
    skip: !memberId,
  });

  const isLoading = (memberId ? isQueryLoading || isQueryFetching : initialLoading);
  const error = memberId ? queryError : initialError;
  const handleRetry = memberId ? refetch : initialRetry;

  // Extract records
  const attendancesList = useMemo(() => {
    if (attendanceApiResponse) {
      const payload = attendanceApiResponse?.data?.data || attendanceApiResponse?.data || [];
      return Array.isArray(payload) ? payload : [];
    }
    return initialAttendances;
  }, [attendanceApiResponse, initialAttendances]);

  // Client safety filter by branch
  const filteredAttendances = useMemo(() => {
    if (!branchId) return attendancesList;
    return attendancesList.filter(
      (a) => !a.branch_id || String(a.branch_id) === String(branchId),
    );
  }, [attendancesList, branchId]);

  // Backend stats if provided
  const backendStats = attendanceApiResponse?.stats || attendanceApiResponse?.data?.stats;

  // Compute total minutes
  const totalMinutes = useMemo(() => {
    if (backendStats?.total_training_minutes != null) {
      return Number(backendStats.total_training_minutes);
    }
    return filteredAttendances.reduce((acc, curr) => acc + getRecordMinutes(curr), 0);
  }, [backendStats, filteredAttendances]);

  const totalVisits = backendStats?.total_attendances != null
    ? Number(backendStats.total_attendances)
    : filteredAttendances.length;

  const totalHoursWhole = Math.floor(totalMinutes / 60);
  const remainingMinutes = totalMinutes % 60;
  const decimalHours = (totalMinutes / 60).toFixed(1);

  return (
    <div className="space-y-6">
      {/* Header & Period Filters */}
      <div className="space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h2 className="text-base font-semibold text-white">سجل الحضور وساعات التدريب</h2>
            <p className="text-xs text-app-muted-light mt-0.5">
              تتبع فترات وساعات تمرينك في النادي مع إمكانية الفلترة اليومية، الأسبوعية، والشهرية.
            </p>
          </div>

          {/* Period Filter Buttons */}
          <div className="flex items-center gap-1.5 rounded-xl border border-app-line bg-app-card-soft/80 p-1 self-start sm:self-auto">
            {PERIODS.map((period) => {
              const isSelected = selectedPeriod === period.id;
              return (
                <button
                  key={period.id}
                  type="button"
                  onClick={() => setSelectedPeriod(period.id)}
                  className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                    isSelected
                      ? "bg-app-yellow text-black shadow-sm"
                      : "text-app-muted-light hover:text-white"
                  }`}
                >
                  {period.label}
                </button>
              );
            })}
          </div>
        </div>

        {/* Stat Cards */}
        <div className="grid grid-cols-2 gap-3 sm:gap-4">
          {/* Total Visits Card */}
          <div className="flex flex-col justify-between rounded-2xl border border-app-line bg-app-card-soft/70 p-4 sm:p-5 transition hover:border-app-yellow/40">
            <div className="flex items-center gap-2.5 text-app-muted-light">
              <span className="grid size-8 place-items-center rounded-xl bg-app-yellow/10 text-app-yellow text-sm">
                📅
              </span>
              <span className="text-xs font-semibold text-white/90">
                إجمالي الزيارات ({PERIODS.find((p) => p.id === selectedPeriod)?.label})
              </span>
            </div>
            <div className="mt-3">
              <p className="text-2xl sm:text-3xl font-black text-white">
                {totalVisits.toLocaleString("ar")}{" "}
                <span className="text-xs font-normal text-app-muted-light">زيارة</span>
              </p>
              <p className="text-[11px] text-app-muted-light mt-0.5">
                {selectedPeriod === "daily"
                  ? "زيارات اليوم"
                  : selectedPeriod === "weekly"
                    ? "زيارات الأسبوع الحالي"
                    : selectedPeriod === "monthly"
                      ? "زيارات الشهر الحالي"
                      : "كافة زيارات الحضور المسجلة"}
              </p>
            </div>
          </div>

          {/* Total Training Hours Card */}
          <div className="flex flex-col justify-between rounded-2xl border border-app-line bg-app-card-soft/70 p-4 sm:p-5 transition hover:border-app-yellow/40">
            <div className="flex items-center gap-2.5 text-app-muted-light">
              <span className="grid size-8 place-items-center rounded-xl bg-app-yellow/10 text-app-yellow text-sm">
                ⏱️
              </span>
              <span className="text-xs font-semibold text-white/90">
                إجمالي ساعات التدريب ({PERIODS.find((p) => p.id === selectedPeriod)?.label})
              </span>
            </div>
            <div className="mt-3">
              <p className="text-2xl sm:text-3xl font-black text-app-yellow">
                {totalMinutes === 0 ? (
                  "0 دقيقة"
                ) : (
                  <>
                    {totalHoursWhole > 0 && <span>{totalHoursWhole.toLocaleString("ar")} س </span>}
                    {remainingMinutes > 0 && (
                      <span>
                        {totalHoursWhole > 0 ? "و " : ""}
                        {remainingMinutes.toLocaleString("ar")} د
                      </span>
                    )}
                  </>
                )}
              </p>
              <p className="text-[11px] text-app-muted-light mt-0.5">
                {totalMinutes > 0
                  ? `ما يعادل ${decimalHours} ساعة تدريب`
                  : "لا توجد ساعات مسجلة لهذه الفترة"}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Attendance Records List */}
      {isLoading ? (
        <div className="space-y-3">
          {[1, 2, 3].map((item) => (
            <div key={item} className="h-16 animate-pulse rounded-xl bg-app-card-soft/50" />
          ))}
        </div>
      ) : error ? (
        <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-center text-xs text-app-red">
          <p>{error?.data?.message || error?.message || "تعذر تحميل سجل الحضور."}</p>
          {handleRetry && (
            <button
              type="button"
              onClick={handleRetry}
              className="mt-2 rounded-lg bg-app-red/20 px-3 py-1 text-xs font-semibold hover:bg-app-red/30"
            >
              إعادة المحاولة
            </button>
          )}
        </div>
      ) : filteredAttendances.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-app-line p-8 text-center text-sm text-app-muted-light">
          لا يوجد سجل حضور مسجل في هذا الفرع للفترة المحددة ({PERIODS.find((p) => p.id === selectedPeriod)?.label}).
        </div>
      ) : (
        <div className="space-y-2.5">
          {filteredAttendances.map((record) => {
            const checkInTime = record.check_in_at || record.check_in;
            const checkOutTime = record.check_out_at || record.check_out;
            const isCurrent = !checkOutTime || record.status === "checked_in";
            const durationLabel = formatSessionDuration(record);
            const branchName = record.branch?.name || record.branch_name || "فرع النادي";

            return (
              <div
                key={record.id}
                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-app-line bg-app-card-soft/60 p-4 transition hover:border-app-yellow/40 hover:bg-app-card-soft"
              >
                <div className="flex items-center gap-3">
                  <div
                    className={`grid size-10 place-items-center rounded-xl ${
                      isCurrent
                        ? "bg-emerald-500/15 text-emerald-400 animate-pulse"
                        : "bg-app-yellow/10 text-app-yellow"
                    }`}
                  >
                    <svg
                      className="size-5"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="1.7"
                    >
                      <circle cx="12" cy="12" r="9" />
                      <path d="M12 7v5l3 3" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-white">
                      {checkInTime ? formatDate(checkInTime) : "-"}
                    </p>
                    <p className="text-xs text-app-muted-light mt-0.5">
                      وقت الدخول: {checkInTime ? formatAttendanceTime(checkInTime) : "-"} • {branchName}
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-4 text-xs">
                  {checkOutTime && (
                    <div className="text-right">
                      <span className="text-app-muted">الخروج: </span>
                      <span className="font-medium text-white">
                        {formatAttendanceTime(checkOutTime)}
                      </span>
                    </div>
                  )}
                  <span
                    className={`rounded-full px-2.5 py-1 font-medium ${
                      isCurrent
                        ? "bg-emerald-500/15 text-emerald-400 border border-emerald-500/30"
                        : "bg-app-card-hover text-app-yellow"
                    }`}
                  >
                    {durationLabel}
                  </span>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
