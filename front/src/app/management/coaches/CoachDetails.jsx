"use client";

import Button from "@/components/ui/Button";
import DetailItem from "@/components/ui/DetailItem";
import Dropdown from "@/components/ui/Dropdown";
import SkeletonPage from "@/components/ui/Skeleton";
import { TrashIcon } from "@/components/icons/Icons";
import { formatDate, formatMoney } from "@/lib/utils";
import {
  COACH_GENDER_LABELS as genderLabels,
  EMPLOYMENT_LABELS as employmentLabels,
} from "./coachConstants";
import { getCoachBranchNames, getUnassignedActivities } from "./coachDetailsUtils";
export default function CoachDetails({
  coach,
  branches = [],
  allActivities = [],
  isLoading,
  error,
  selectedActivityId,
  setSelectedActivityId,
  onAddActivity,
  onRemoveActivity,
  isAddingActivity,
  isRemovingActivity,
}) {
  if (isLoading) {
    return <SkeletonPage blocks={[{ type: "details", sections: 2, itemsPerSection: 3 }]} />;
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل المدرب.
      </div>
    );
  }

  if (!coach) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل متوفرة.
      </div>
    );
  }

  const branchNames = getCoachBranchNames(coach, branches);
  const coachActivities = coach.activities || [];
  const unassignedActivities = getUnassignedActivities(coachActivities, allActivities);

  return (
    <div className="space-y-6">
      {/* Basic Profile */}
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right flex items-center justify-between gap-4">
        <div>
          <h3 className="text-lg font-medium text-white">{coach.person?.full_name || "-"}</h3>
          <p className="mt-1 text-xs text-app-muted-light">
            كود الموظف: #{coach.id} | دور: {coach.role}
          </p>
        </div>
        <span
          className={`rounded px-2.5 py-1 text-xs font-semibold ${
            coach.is_active ? "bg-app-green/10 text-app-green" : "bg-app-red/10 text-app-red"
          }`}
        >
          {coach.is_active ? "نشط" : "غير نشط"}
        </span>
      </div>

      {/* Info Grid */}
      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="الفرع" value={branchNames} tone="yellow" />
        {/* <DetailItem
          label="التخصص"
          value={coach.details?.specialization || "غير محدد"}
        /> */}
        <DetailItem
          label="سنوات الخبرة"
          value={coach.details?.experience_years ? `${coach.details.experience_years} سنوات` : "-"}
        />
        <DetailItem
          label="الجنس"
          value={genderLabels[coach.person?.gender] || coach.person?.gender}
        />
        <DetailItem label="تاريخ الميلاد" value={formatDate(coach.person?.dob)} />
        <DetailItem
          label="نوع التوظيف"
          value={employmentLabels[coach.employment_type] || coach.employment_type}
        />
        <DetailItem label="الراتب الأساسي" value={formatMoney(coach.base_salary)} tone="green" />
        <DetailItem label="العنوان" value={coach.person?.address} />
        {/* <DetailItem label="البريد الإلكتروني" value={coach.person?.email} /> */}
        <DetailItem label="رقم الهوية الوطنية" value={coach.person?.national_id} />
        <DetailItem label="تاريخ المباشرة" value={formatDate(coach.start_date)} />
      </section>

      {/* Activities Section */}
      <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right space-y-4">
        <h4 className="text-sm font-semibold text-white">الأنشطة والرياضات المنسوبة للمدرب</h4>

        {coachActivities.length === 0 ? (
          <p className="text-xs text-app-muted-light">
            لم يتم إسناد أي أنشطة رياضية لهذا المدرب بعد.
          </p>
        ) : (
          <div className="space-y-2">
            {coachActivities.map((act) => (
              <div
                key={act.id}
                className="flex items-center justify-between p-2 rounded-lg bg-black/35 border border-app-line text-sm"
              >
                <button
                  type="button"
                  onClick={() => onRemoveActivity(act.id)}
                  disabled={isRemovingActivity}
                  className="text-app-red hover:bg-app-red/10 p-1.5 rounded transition disabled:opacity-50"
                  title="إلغاء إسناد النشاط"
                >
                  <TrashIcon className="size-4" />
                </button>
                <span className="font-medium text-app-text">
                  {typeof act.name === "string" ? act.name : act.name?.ar || act.name?.en || "-"}
                </span>
              </div>
            ))}
          </div>
        )}

        {/* Add Activity Controls */}
        {unassignedActivities.length > 0 && (
          <div className="pt-2 border-t border-app-line flex items-end gap-3">
            <div className="flex-1">
              <label className="block text-xs text-app-muted-light mb-1.5">إسناد نشاط جديد</label>
              <Dropdown
                className="text-white bg-black/40 rounded-lg text-sm"
                buttonClassName="h-10 border-app-line"
                value={selectedActivityId}
                onChange={setSelectedActivityId}
                options={unassignedActivities.map((act) => ({
                  value: act.id,
                  label:
                    typeof act.name === "string" ? act.name : act.name?.ar || act.name?.en || "",
                }))}
                placeholder="اختر نشاطاً رياضياً..."
              />
            </div>
            <Button
              type="button"
              className="h-10 px-4 text-sm font-medium"
              disabled={!selectedActivityId || isAddingActivity}
              loading={isAddingActivity}
              onClick={onAddActivity}
            >
              إسناد
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}
