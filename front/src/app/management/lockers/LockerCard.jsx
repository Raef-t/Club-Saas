"use client";

import RowActions from "@/components/ui/RowActions";
import { CalendarIcon, LockerIcon, XIcon } from "@/components/icons/Icons";
import { LOCKER_STATUS_CLASSES, LOCKER_STATUS_LABELS } from "./lockerConstants";
import { getLockerBranchName, getLockerHolderLabel, isLockerOccupied } from "./lockerUtils";

/**
 * Renders one locker with its status, holder, and available actions.
 */
export default function LockerCard({
  locker,
  branches,
  memberOptions,
  actionsDisabled,
  onReserve,
  onRelease,
  onDelete,
}) {
  const statusClass = LOCKER_STATUS_CLASSES[locker.status] || LOCKER_STATUS_CLASSES.disabled;
  const holderLabel = getLockerHolderLabel(locker, memberOptions);
  const occupied = isLockerOccupied(locker);

  return (
    <article className="flex min-h-64 flex-col items-center justify-between rounded-xl border border-app-line bg-app-card p-4 text-center transition hover:border-app-yellow/50 hover:shadow-lg">
      <div className="flex flex-col items-center">
        <div className="mb-4 flex size-14 items-center justify-center rounded-full bg-[#3a3a3a]">
          <LockerIcon className="size-6 text-[#aaaaaa]" />
        </div>
        <h2 className="text-2xl font-bold tracking-wider text-white" dir="ltr">
          {locker.locker_number || "-"}
        </h2>
        {locker.key_number && (
          <p className="mt-1 text-sm text-app-text font-medium" dir="ltr">
            Key: {locker.key_number}
          </p>
        )}
        <p className="mt-2 text-xs text-app-muted-light">{getLockerBranchName(locker, branches)}</p>
      </div>

      <div className="mt-5 flex w-full flex-col items-center gap-2">
        <span className={`rounded-full border px-3 py-1 text-[11px] font-medium ${statusClass}`}>
          {LOCKER_STATUS_LABELS[locker.status] || locker.status || "-"}
        </span>
        {holderLabel && <p className="max-w-full truncate text-xs text-app-text">{holderLabel}</p>}
      </div>

      <div className="mt-5 flex items-center justify-center gap-2 border-t border-app-line pt-3">
        {locker.status === "available" && !occupied && (
          <button
            type="button"
            onClick={() => onReserve(locker)}
            disabled={actionsDisabled}
            className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-green transition hover:border-app-green/60 hover:bg-app-green/10 disabled:cursor-not-allowed disabled:opacity-50"
            title="حجز الخزانة"
            aria-label={`حجز الخزانة ${locker.locker_number}`}
          >
            <CalendarIcon className="size-4" />
          </button>
        )}

        {occupied && (
          <button
            type="button"
            onClick={() => onRelease(locker)}
            disabled={actionsDisabled}
            className="grid size-8 place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-orange transition hover:border-app-orange/60 hover:bg-app-orange/10 disabled:cursor-not-allowed disabled:opacity-50"
            title="فك الحجز"
            aria-label={`فك حجز الخزانة ${locker.locker_number}`}
          >
            <XIcon className="size-4" />
          </button>
        )}

        <RowActions
          editHref={`/management/lockers/${locker.id}/edit`}
          editTitle="تعديل الخزانة"
          onDelete={() => onDelete(locker)}
          deleteTitle="حذف الخزانة"
          disabled={actionsDisabled}
        />
      </div>
    </article>
  );
}
