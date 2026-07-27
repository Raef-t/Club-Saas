import DetailItem from "@/components/ui/DetailItem";
import SkeletonPage from "@/components/ui/Skeleton";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import { genderLabels } from "@/lib/constants";
import { getBranchDisplayName } from "./branchUtils";

/**
 * Renders the complete read-only details of a selected branch.
 */
export default function BranchDetails({ branch, isLoading, error }) {
  if (isLoading) {
    return <SkeletonPage blocks={[{ type: "details", sections: 2, itemsPerSection: 3 }]} />;
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل الفرع.
      </div>
    );
  }

  if (!branch) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا الفرع.
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <h3 className="text-lg font-medium text-app-text">{getBranchDisplayName(branch)}</h3>
        <p className="mt-1 text-xs text-app-muted-light">رقم الفرع: #{branch.id}</p>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="النادي" value={formatLocalizedName(branch.club?.name)} />
        <DetailItem
          label="التقييد الجنسي"
          value={genderLabels[branch.gender_restriction] || branch.gender_restriction}
          tone="yellow"
        />
        <DetailItem
          label="حالة النشاط"
          value={branch.is_active ? "نشط" : "غير نشط"}
          tone={branch.is_active ? "green" : "red"}
        />
        <DetailItem
          label="رقم الهاتف"
          value={branch.phone ? `${branch.country_code || ""} ${branch.phone}` : "-"}
        />
        <DetailItem label="العنوان" value={branch.address || "-"} />
        <DetailItem label="تاريخ الإنشاء" value={formatDate(branch.created_at)} />
      </section>
    </div>
  );
}
