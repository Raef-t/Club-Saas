"use client";

import Modal from "@/components/ui/Modal";
import DetailItem from "@/components/ui/DetailItem";
import { ClockIcon, UsersIcon } from "@/components/icons/Icons";

export default function ShiftAttendanceDetailsModal({ record, onClose }) {
  if (!record) return null;

  const percentage = Math.min(Math.max(record.crowdPercentage, 0), 100);
  const crowdTone =
    percentage >= 80 ? "red" : percentage >= 40 ? "yellow" : "green";

  return (
    <Modal
      open={Boolean(record)}
      onClose={onClose}
      title={`تفاصيل وردية: ${record.shiftName}`}
      subtitle={`الفرع: ${record.branchName}`}
      className="max-w-2xl"
    >
      <div className="space-y-6 text-right">
        {/* Crowd Indicator */}
        <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-4 space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-app-muted-light">معدل الازدحام الحالي:</span>
            <span
              className={`text-sm font-bold ${
                crowdTone === "red"
                  ? "text-app-red"
                  : crowdTone === "yellow"
                    ? "text-app-yellow"
                    : "text-app-green"
              }`}
            >
              {record.crowdPercentage.toLocaleString("ar", { maximumFractionDigits: 1 })}%
            </span>
          </div>
          <div className="h-2 w-full overflow-hidden rounded-full bg-black/40">
            <div
              className={`h-full transition-all duration-500 rounded-full ${
                crowdTone === "red"
                  ? "bg-app-red"
                  : crowdTone === "yellow"
                    ? "bg-app-yellow"
                    : "bg-app-green"
              }`}
              style={{ width: `${percentage}%` }}
            />
          </div>
        </div>

        {/* Shift Timings */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 border-b border-app-line pb-2">
            <ClockIcon className="size-4 text-app-yellow" />
            <h3 className="text-sm font-semibold text-app-text">توقيت الوردية</h3>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <DetailItem label="اسم الوردية" value={record.shiftName} />
            <DetailItem label="وقت البدء" value={record.startTime} tone="yellow" />
            <DetailItem label="وقت الانتهاء" value={record.endTime} tone="yellow" />
            <DetailItem label="النطاق الزمني" value={record.timeFormatted} />
            <DetailItem label="الفرع" value={record.branchName} />
          </div>
        </div>

        {/* Attendance Breakdown */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 border-b border-app-line pb-2">
            <UsersIcon className="size-4 text-app-yellow" />
            <h3 className="text-sm font-semibold text-app-text">إحصائيات الحضور</h3>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <DetailItem
              label="إجمالي مرات الحضور المسجلة"
              value={`${record.attendedPlayersCount.toLocaleString("ar")} لاعب`}
              tone="green"
            />
            <DetailItem
              label="عدد اللاعبين الفعليين (الفريدين)"
              value={`${record.uniquePlayersCount.toLocaleString("ar")} لاعب`}
              tone="blue"
            />
          </div>
        </div>
      </div>
    </Modal>
  );
}
