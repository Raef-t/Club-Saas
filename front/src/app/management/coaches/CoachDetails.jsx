"use client";

import Button from "@/components/ui/Button";
import DetailItem from "@/components/ui/DetailItem";
import ProfileIdentityCard from "@/components/ui/ProfileIdentityCard";
import SkeletonPage from "@/components/ui/Skeleton";
import { formatDate, formatMoney } from "@/lib/utils";
import { getWorkStatusMeta } from "@/lib/workStatus";
import {
  COACH_GENDER_LABELS as genderLabels,
  EMPLOYMENT_LABELS as employmentLabels,
} from "./coachConstants";
import {
  getCoachAge,
  getCoachBranchNames,
  getCoachCommissionItems,
  getCoachCompensationVisibility,
} from "./coachDetailsUtils";
export default function CoachDetails({ coach, branches = [], isLoading, error }) {
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
  const compensation = getCoachCompensationVisibility(coach);
  const commissionItems = getCoachCommissionItems(coach);
  const startDate = coach.start_date || coach.details?.start_date;
  const coachAge = getCoachAge(coach);
  const workStatus = getWorkStatusMeta(coach);
  const employmentLabel =
    employmentLabels[compensation.paymentType] || compensation.paymentType || "-";

  return (
    <div className="space-y-6">
      <ProfileIdentityCard
        name={coach.person?.full_name}
        username={coach.username || coach.generated_username}
        qrCode={coach.qr_code}
        status={workStatus}
      />

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
        <DetailItem label="العمر" value={coachAge !== null ? `${coachAge} سنة` : "-"} />
        <DetailItem label="نوع التوظيف" value={employmentLabel} />
        {compensation.showSalary && (
          <DetailItem label="الراتب الأساسي" value={formatMoney(coach.base_salary)} tone="green" />
        )}
        {compensation.showCommission &&
          commissionItems.map((item) => (
            <DetailItem key={item.key} label={item.label} value={item.value} tone="green" />
          ))}
        <DetailItem label="العنوان" value={coach.person?.address} />
        {/* <DetailItem label="البريد الإلكتروني" value={coach.person?.email} /> */}
        {/* <DetailItem label="رقم الهوية الوطنية" value={coach.person?.national_id} /> */}
        <DetailItem label="تاريخ المباشرة" value={startDate ? formatDate(startDate) : "غير مسجل"} />
        <DetailItem label="سبب آخر تعديل" value={coach.reason || "-"} />
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
                className="flex items-center justify-end rounded-lg border border-app-line bg-black/35 p-2 text-sm"
              >
                <span className="font-medium text-app-text">
                  {typeof act.name === "string" ? act.name : act.name?.ar || act.name?.en || "-"}
                </span>
              </div>
            ))}
          </div>
        )}

        <div className="flex justify-start border-t border-app-line pt-3">
          <Button href={`/management/coaches/create?mode=edit&id=${coach.id}`} tone="outline">
            تعديل الأنشطة والشفتات
          </Button>
        </div>
      </div>
    </div>
  );
}
