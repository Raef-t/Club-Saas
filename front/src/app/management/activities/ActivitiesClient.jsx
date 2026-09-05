"use client";

import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import Drawer from "@/components/ui/Drawer";
import StatsGrid from "@/components/ui/StatsGrid";
import { PlusIcon } from "@/components/icons/Icons";
import ActivitiesTable from "./ActivitiesTable";
import ActivityDetails from "./ActivityDetails";
import { getActivityName } from "./activityUtils";
import { useActivities } from "./useActivities";
import { usePermissions } from "@/lib/PermissionContext";

/**
 * Composes the activity overview, details drawer, and deletion confirmation.
 */
export default function ActivitiesClient({ initialData }) {
  const { can } = usePermissions();
  const canCreate = can("activity.create");
  const canDelete = can("activity.delete");
  const state = useActivities({
    initialActivities: initialData?.activities,
  });

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="الأنشطة الرياضية"
        subtitle="عرض وتعديل وتصنيف الرياضات والأنشطة الجماعية والتدريبات الخاصة في النادي."
        action={
          canCreate ? (
            <Button
              href="/management/activities/create"
              icon={<PlusIcon className="size-4 text-black" />}
              className="!text-black"
            >
              إضافة نشاط
            </Button>
          ) : null
        }
      />

      <StatsGrid items={state.stats} />
      <ActivitiesTable state={state} />

      <Drawer
        open={Boolean(state.selectedActivity)}
        onClose={state.closeDetails}
        title="تفاصيل النشاط"
        subtitle={getActivityName(state.detailsActivity)}
      >
        <ActivityDetails
          activity={state.detailsActivity}
          isLoading={state.isFetchingDetails}
          error={state.detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={canDelete && Boolean(state.deleteTarget)}
        onClose={() => state.setDeleteTarget(null)}
        onConfirm={state.confirmDelete}
        title="تأكيد حذف النشاط"
        message={`هل أنت متأكد من رغبتك في حذف نشاط "${getActivityName(state.deleteTarget)}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        requiredConfirmation="delete"
        confirmationValue={state.deleteConfirmation}
        onConfirmationChange={state.setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={state.isDeleting}
      />
    </div>
  );
}
