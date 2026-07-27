import SkeletonPage from "@/components/ui/Skeleton";
import { LockerIcon } from "@/components/icons/Icons";
import LockerCard from "./LockerCard";

/**
 * Renders locker loading, empty, and populated grid states.
 */
export default function LockerGrid({
  lockers,
  branches,
  memberOptions,
  isLoading,
  actionsDisabled,
  onReserve,
  onRelease,
  onDelete,
}) {
  if (isLoading && lockers.length === 0) {
    return <SkeletonPage blocks={[{ type: "grid", cards: 8 }]} />;
  }

  if (lockers.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-app-line bg-app-card-soft p-12 text-center">
        <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-app-muted/30">
          <LockerIcon className="size-6 text-app-muted-light" />
        </div>
        <h3 className="mt-4 text-lg font-medium text-white">لا توجد خزائن</h3>
        <p className="mt-1 text-sm text-app-muted-light">
          لم يتم العثور على خزائن مطابقة للتصفية الحالية.
        </p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
      {lockers.map((locker) => (
        <LockerCard
          key={locker.id}
          locker={locker}
          branches={branches}
          memberOptions={memberOptions}
          actionsDisabled={actionsDisabled}
          onReserve={onReserve}
          onRelease={onRelease}
          onDelete={onDelete}
        />
      ))}
    </div>
  );
}
