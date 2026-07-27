import DetailItem from "@/components/ui/DetailItem";
import SkeletonPage from "@/components/ui/Skeleton";
import { formatDate } from "@/lib/utils";
import { getClubName } from "./clubUtils";
import { ClubLogo } from "./ClubVisuals";

/**
 * Renders the complete read-only details of a selected club.
 */
export default function ClubDetails({ club, isLoading, error }) {
  if (isLoading) {
    return <SkeletonPage blocks={[{ type: "details", sections: 2, itemsPerSection: 3 }]} />;
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل النادي.
      </div>
    );
  }

  if (!club) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا النادي.
      </div>
    );
  }

  const name = getClubName(club);

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <div className="flex items-center justify-between gap-4">
          <div className="min-w-0">
            <h3 className="text-lg font-medium text-app-text">{name}</h3>
            <p className="mt-1 text-xs text-app-muted-light">معرف النادي: #{club.id}</p>
          </div>
          <ClubLogo src={club.logo_url} name={name} className="size-16" />
        </div>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem
          label="حالة النشاط"
          value={club.is_active ? "نشط" : "غير نشط"}
          tone={club.is_active ? "green" : "red"}
        />
        <DetailItem label="تاريخ الإنشاء" value={formatDate(club.created_at)} />
        <DetailItem label="تاريخ التحديث" value={formatDate(club.updated_at)} />
      </section>
    </div>
  );
}
