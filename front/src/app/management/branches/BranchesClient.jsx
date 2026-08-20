"use client";

import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import Drawer from "@/components/ui/Drawer";
import StatsGrid from "@/components/ui/StatsGrid";
import { PlusIcon } from "@/components/icons/Icons";
import BranchDetails from "./BranchDetails";
import BranchesTable from "./BranchesTable";
import { getBranchDisplayName } from "./branchUtils";
import { useBranches } from "./useBranches";

/**
 * Composes the branch overview, details drawer, and deletion confirmation.
 */
export default function BranchesClient({ initialData }) {
  const state = useBranches({
    initialBranches: initialData?.branches,
  });

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة الفروع"
        subtitle="عرض فروع النادي المختلفة، تعديل بيانات الهاتف والعنوان، وتفعيل أو تعطيل الفروع."
        action={
          <Button
            href="/management/branches/create"
            icon={<PlusIcon className="size-4 text-black" />}
            className="text-black"
          >
            إضافة فرع
          </Button>
        }
      />

      <StatsGrid items={state.stats} />
      <BranchesTable state={state} />

      <Drawer
        open={Boolean(state.selectedBranch)}
        onClose={state.closeDetails}
        title="تفاصيل الفرع"
        subtitle={getBranchDisplayName(state.detailsBranch)}
      >
        <BranchDetails
          branch={state.detailsBranch}
          isLoading={state.isFetchingDetails}
          error={state.detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={Boolean(state.deleteTarget)}
        onClose={() => state.setDeleteTarget(null)}
        onConfirm={state.confirmDelete}
        title="تأكيد حذف الفرع"
        message={`هل أنت متأكد من رغبتك في حذف فرع "${getBranchDisplayName(state.deleteTarget)}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        requiredConfirmation="delete"
        confirmationValue={state.deleteConfirmation}
        onConfirmationChange={state.setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={state.isDeleting}
      />
    </div>
  );
}
