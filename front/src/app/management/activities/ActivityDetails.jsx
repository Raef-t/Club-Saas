import DetailItem from "@/components/ui/DetailItem";
import SkeletonPage from "@/components/ui/Skeleton";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import { genderLabels } from "@/lib/constants";
import { getActivityName } from "./activityUtils";

/**
 * Renders the complete read-only details of a selected activity.
 */
export default function ActivityDetails({ activity, isLoading, error }) {
  if (isLoading) {
    return <SkeletonPage blocks={[{ type: "details", sections: 2, itemsPerSection: 3 }]} />;
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل النشاط.
      </div>
    );
  }

  if (!activity) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا النشاط.
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <h3 className="text-lg font-medium text-app-text">{getActivityName(activity)}</h3>
        <p className="mt-1 text-xs text-app-muted-light">رمز النشاط: #{activity.id}</p>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem
          label="الجمهور المسموح"
          value={genderLabels[activity.gender_allowed] || activity.gender_allowed}
          tone="yellow"
        />
        <DetailItem
          label="الحالة"
          value={activity.is_active ? "نشط" : "غير نشط"}
          tone={activity.is_active ? "green" : "default"}
        />
        <DetailItem
          label="نوع الفئة / التصنيف"
          value={formatLocalizedName(activity.activity_type?.name)}
        />
        <DetailItem label="الفرع" value={formatLocalizedName(activity.branch?.name)} />
        <DetailItem label="تاريخ الإنشاء" value={formatDate(activity.created_at)} />
        <DetailItem label="سبب آخر تعديل" value={activity.reason || "-"} />
      </section>

      {activity.description && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right">
          <h4 className="text-xs font-medium text-app-muted-light">الوصف</h4>
          <p className="mt-2 text-sm leading-relaxed text-app-text">{activity.description}</p>
        </div>
      )}
    </div>
  );
}
