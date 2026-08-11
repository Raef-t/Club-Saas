"use client";

import Button from "@/components/ui/Button";
import DetailItem from "@/components/ui/DetailItem";
import ProfileIdentityCard from "@/components/ui/ProfileIdentityCard";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import { formatDate, formatMoney } from "@/lib/utils";
import { getWorkStatusMeta } from "@/lib/workStatus";
import {
  SHIFT_GENDER_LABELS,
  STAFF_EMPLOYMENT_LABELS,
  STAFF_GENDER_LABELS,
} from "./staffConstants";
import { getStaffBranchNames } from "./staffUtils";

export default function StaffDetails({ staff, branches = [], isLoading, error }) {
  const { formatTime } = useTimeFormat();

  if (isLoading && !staff) {
    return (
      <p className="py-10 text-center text-sm text-app-muted-light">جاري تحميل بيانات الموظف...</p>
    );
  }

  if (error && !staff) {
    return <p className="py-10 text-center text-sm text-app-red">تعذر تحميل بيانات الموظف.</p>;
  }

  if (!staff) {
    return <p className="py-10 text-center text-sm text-app-muted-light">لا توجد بيانات متاحة.</p>;
  }

  const branchNames = getStaffBranchNames(staff, branches);
  const shifts = Array.isArray(staff.shifts) ? staff.shifts : [];
  const phone = [staff.person?.country_code, staff.person?.phone_number].filter(Boolean).join(" ");
  const workStatus = getWorkStatusMeta(staff);

  return (
    <div className="space-y-5">
      <ProfileIdentityCard
        name={staff.person?.full_name || "موظف بدون اسم"}
        username={staff.username || staff.generated_username}
        qrCode={staff.qr_code}
        status={workStatus}
      />

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <DetailItem
          label="نوع التوظيف"
          value={STAFF_EMPLOYMENT_LABELS[staff.employment_type] || staff.employment_type}
        />
        <DetailItem label="الراتب الأساسي" value={formatMoney(staff.base_salary)} tone="green" />
        <DetailItem label="رقم الهاتف" value={phone || "-"} />
        <DetailItem
          label="الجنس"
          value={STAFF_GENDER_LABELS[staff.person?.gender] || staff.person?.gender || "-"}
        />
        <DetailItem label="تاريخ المباشرة" value={formatDate(staff.start_date)} />
        <DetailItem label="تاريخ الانتهاء" value={formatDate(staff.end_date)} />
        <DetailItem label="تاريخ الإضافة" value={formatDate(staff.created_at)} />
      </div>

      <div className="rounded-xl border border-app-line bg-app-card-soft p-4 text-right">
        <p className="text-xs text-app-muted-light">العنوان</p>
        <p className="mt-2 text-sm leading-6 text-app-text">{staff.person?.address || "-"}</p>
      </div>

      <section className="space-y-2 text-right">
        <h4 className="text-sm font-medium text-app-text">الفروع</h4>
        <div className="flex flex-wrap gap-2">
          {branchNames.length ? (
            branchNames.map((name) => (
              <span
                key={name}
                className="rounded-full bg-app-yellow/10 px-3 py-1 text-xs text-app-yellow"
              >
                {name}
              </span>
            ))
          ) : (
            <span className="text-xs text-app-muted-light">لا توجد فروع مرتبطة.</span>
          )}
        </div>
      </section>

      <section className="space-y-2 text-right">
        <h4 className="text-sm font-medium text-app-text">الورديات</h4>
        {shifts.length ? (
          <div className="space-y-2">
            {shifts.map((item) => {
              const shift = item.branch_shift || item;
              return (
                <div
                  key={item.id || shift.id}
                  className="rounded-lg border border-app-line bg-app-card-soft p-3"
                >
                  <div className="flex items-center justify-between gap-3">
                    <span className="text-sm font-medium text-app-text">
                      {shift.name || "وردية"}
                    </span>
                    <span className="text-[11px] text-app-muted-light">
                      {SHIFT_GENDER_LABELS[shift.gender_allowed] || shift.gender_allowed || "مختلط"}
                    </span>
                  </div>
                  <p className="mt-1 text-xs text-app-muted-light" dir="ltr">
                    {formatTime(shift.start_time)} - {formatTime(shift.end_time)}
                  </p>
                </div>
              );
            })}
          </div>
        ) : (
          <p className="text-xs text-app-muted-light">لا توجد ورديات مسندة لهذا الموظف.</p>
        )}
      </section>

      <Button
        href={`/management/staff/create?mode=edit&id=${staff.id}`}
        tone="outline"
        className="w-full"
      >
        تعديل بيانات الموظف
      </Button>
    </div>
  );
}
