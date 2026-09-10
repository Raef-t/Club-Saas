"use client";

import ToggleSwitch from "@/components/ui/ToggleSwitch";
import Button from "@/components/ui/Button";
import {
  DownloadIcon,
  PencilIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import { formatPublishDate } from "./appVersionsUtils";

export default function AppVersionsTable({
  versions = [],
  onEdit,
  onDelete,
  onToggleStatus,
  canUpdate = true,
  canDelete = true,
}) {
  return (
    <div className="card-shell overflow-hidden rounded-2xl border border-app-line bg-app-panel">
      <div className="overflow-x-auto">
        <table className="w-full text-right text-sm" dir="rtl">
          <thead className="border-b border-app-line bg-app-card-soft text-xs text-app-muted font-medium">
            <tr>
              <th className="px-5 py-3.5">الإصدار / التطبيق</th>
              <th className="px-5 py-3.5 text-center">نظام التشغيل</th>
              <th className="px-5 py-3.5 text-center">نوع التحديث</th>
              <th className="px-5 py-3.5 text-center">الملف / التنزيل</th>
              <th className="px-5 py-3.5 text-center">الحالة</th>
              <th className="px-5 py-3.5 text-center">تاريخ النشر</th>
              <th className="px-5 py-3.5 text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-app-line">
            {versions.map((item) => (
              <tr
                key={item.id}
                className="transition hover:bg-app-card-hover/50 text-app-text"
              >
                {/* Version & Build */}
                <td className="px-5 py-4">
                  <div className="flex items-center gap-3">
                    <div
                      className={`grid size-10 shrink-0 place-items-center rounded-xl border ${
                        item.platform === "android"
                          ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-500"
                          : "border-sky-500/30 bg-sky-500/10 text-sky-500"
                      }`}
                    >
                      {item.platform === "android" ? (
                        <svg className="size-5 fill-current" viewBox="0 0 24 24">
                          <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993.0001.5511-.4483.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993 0 .5511-.4483.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5837 7.915 13.8427 7.4 12 7.4s-3.5837.515-5.1368 1.5498L4.8409 5.4468a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.152.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.741h24c-.3432-4.0821-2.6889-7.5543-6.1185-9.4196" />
                        </svg>
                      ) : (
                        <svg className="size-5 fill-current" viewBox="0 0 24 24">
                          <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.85-.9.04-2 .6-2.64 1.35-.56.65-1.05 1.71-.92 2.74 1.01.08 2.03-.49 2.64-1.24z" />
                        </svg>
                      )}
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-app-text text-base" dir="ltr">
                          v{item.version_number}
                        </span>
                        <span className="rounded-full border border-app-line bg-app-card px-2 py-0.5 text-[11px] text-app-muted" dir="ltr">
                          Build #{item.build_number}
                        </span>
                      </div>
                      <span className="text-xs text-app-muted-light block mt-0.5">
                        {item.app_name || "تطبيق المتدرب"}
                      </span>
                      {item.release_notes && (
                        <p className="mt-1 text-xs text-app-muted line-clamp-1 max-w-xs">
                          {item.release_notes}
                        </p>
                      )}
                    </div>
                  </div>
                </td>

                {/* Platform */}
                <td className="px-5 py-4 text-center">
                  <span
                    className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium ${
                      item.platform === "android"
                        ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        : "border-sky-500/30 bg-sky-500/10 text-sky-600 dark:text-sky-400"
                    }`}
                  >
                    {item.platform === "android" ? "Android" : "iOS (Apple)"}
                  </span>
                </td>

                {/* Update Type */}
                <td className="px-5 py-4 text-center">
                  {item.is_force_update ? (
                    <span className="inline-flex items-center gap-1 rounded-full border border-app-red/30 bg-app-red/10 px-2.5 py-1 text-xs font-medium text-app-red">
                      تحديث إجباري
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1 rounded-full border border-app-line bg-app-card px-2.5 py-1 text-xs text-app-muted">
                      تحديث اختياري
                    </span>
                  )}
                </td>

                {/* Download / File */}
                <td className="px-5 py-4 text-center">
                  {item.download_url ? (
                    <div className="flex flex-col items-center gap-1">
                      <a
                        href={item.download_url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center gap-1.5 rounded-lg border border-app-yellow/40 bg-app-yellow-soft px-3 py-1.5 text-xs font-medium text-app-text hover:bg-app-yellow transition"
                        download
                      >
                        <DownloadIcon className="size-3.5 text-app-yellow" />
                        <span>تحميل الملف</span>
                      </a>
                      {item.formatted_file_size && (
                        <span className="text-[11px] text-app-muted-light" dir="ltr">
                          {item.formatted_file_size}
                        </span>
                      )}
                    </div>
                  ) : (
                    <span className="text-xs text-app-muted">-</span>
                  )}
                </td>

                {/* Status Toggle */}
                <td className="px-5 py-4 text-center">
                  <div className="inline-flex items-center gap-2">
                    <ToggleSwitch
                      checked={Boolean(item.is_active)}
                      onChange={() => onToggleStatus?.(item)}
                      disabled={!canUpdate}
                      aria-label="تغيير حالة الإصدار"
                    />
                    <span
                      className={`text-xs font-medium ${
                        item.is_active ? "text-app-green" : "text-app-muted"
                      }`}
                    >
                      {item.is_active ? "مفعل" : "معطل"}
                    </span>
                  </div>
                </td>

                {/* Published Date */}
                <td className="px-5 py-4 text-center text-xs text-app-muted-light">
                  {formatPublishDate(item.created_at)}
                </td>

                {/* Actions */}
                <td className="px-5 py-4 text-center">
                  <div className="flex items-center justify-center gap-1.5">
                    {canUpdate && (
                      <button
                        type="button"
                        onClick={() => onEdit?.(item)}
                        className="grid size-8 place-items-center rounded-lg border border-app-line hover:border-app-yellow hover:bg-app-yellow-soft text-app-muted hover:text-app-text transition"
                        title="تعديل الإصدار"
                      >
                        <PencilIcon className="size-4" />
                      </button>
                    )}
                    {canDelete && (
                      <button
                        type="button"
                        onClick={() => onDelete?.(item)}
                        className="grid size-8 place-items-center rounded-lg border border-app-line hover:border-app-red hover:bg-app-red/10 text-app-muted hover:text-app-red transition"
                        title="حذف الإصدار"
                      >
                        <TrashIcon className="size-4" />
                      </button>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
