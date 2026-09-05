"use client";

import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import Drawer from "@/components/ui/Drawer";
import StatsGrid from "@/components/ui/StatsGrid";
import { PlusIcon } from "@/components/icons/Icons";
import ClubDetails from "./ClubDetails";
import ClubsTable from "./ClubsTable";
import { getClubName } from "./clubUtils";
import { useClubs } from "./useClubs";
import { usePermissions } from "@/lib/PermissionContext";

/**
 * Composes the club overview, details drawer, and deletion confirmation.
 */
export default function ClubsClient({ initialData }) {
  const { can } = usePermissions();
  const canCreate = can("club.create");
  const canDelete = can("club.delete");
  const state = useClubs({ initialClubs: initialData?.clubs });

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة النوادي"
        subtitle="عرض النوادي المسجلة في النظام، وتعديل بياناتها أو إضافة نوادٍ جديدة."
        action={
          canCreate ? (
            <Button
              href="/management/clubs/create"
              icon={<PlusIcon className="size-4 text-black" />}
              className="!text-black font-semibold"
            >
              إضافة نادي
            </Button>
          ) : null
        }
      />

      <StatsGrid items={state.stats} />
      <ClubsTable state={state} />

      <Drawer
        open={Boolean(state.selectedClub)}
        onClose={state.closeDetails}
        title="تفاصيل النادي"
        subtitle={getClubName(state.detailsClub)}
      >
        <ClubDetails
          club={state.detailsClub}
          isLoading={state.isFetchingDetails}
          error={state.detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={canDelete && Boolean(state.deleteTarget)}
        onClose={() => state.setDeleteTarget(null)}
        onConfirm={state.confirmDelete}
        title="تأكيد حذف النادي"
        message={`هل أنت متأكد من رغبتك في حذف نادي "${getClubName(state.deleteTarget)}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        requiredConfirmation="delete"
        confirmationValue={state.deleteConfirmation}
        onConfirmationChange={state.setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={state.isDeleting}
      />
    </div>
  );
}
