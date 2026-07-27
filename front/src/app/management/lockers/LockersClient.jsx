"use client";

import { useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import Drawer from "@/components/ui/Drawer";
import { LockerIcon, PlusIcon } from "@/components/icons/Icons";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import LockerFilters from "./LockerFilters";
import LockerGrid from "./LockerGrid";
import LockerReserveForm from "./LockerReserveForm";
import { useLockers } from "./useLockers";
import {
  createLockerBranchOptions,
  createLockerMemberOptions,
  getLockerCollection,
} from "./lockerUtils";

/**
 * Composes locker filters, cards, reservations, and destructive confirmations.
 */
export default function LockersClient({ initialData }) {
  const { selectedBranchId } = useManagementBranch();
  const lockerState = useLockers({
    initialLockers: initialData?.lockers,
  });
  const branches = useMemo(
    () => getLockerCollection(initialData?.branches),
    [initialData?.branches],
  );
  const branchOptions = useMemo(
    () => createLockerBranchOptions(initialData?.branches, true),
    [initialData?.branches],
  );
  const branchMembers = useMemo(
    () => filterEntitiesByBranch(getLockerCollection(initialData?.members), selectedBranchId),
    [initialData?.members, selectedBranchId],
  );
  const memberOptions = useMemo(() => createLockerMemberOptions(branchMembers), [branchMembers]);

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="إدارة النادي"
        title="الخزائن"
        subtitle="إدارة الخزائن المتاحة، الحجوزات، والمستفيدين الحاليين."
        icon={LockerIcon}
        action={
          <Button
            href="/management/lockers/create"
            icon={<PlusIcon className="size-4 text-black" />}
            className="!text-black"
          >
            إضافة خزانة
          </Button>
        }
      />

      <LockerFilters
        search={lockerState.search}
        branchFilter={lockerState.branchFilter}
        statusFilter={lockerState.statusFilter}
        branchOptions={branchOptions}
        onSearchChange={lockerState.setSearch}
        onBranchChange={lockerState.setBranchFilter}
        onStatusChange={lockerState.setStatusFilter}
      />

      {lockerState.lockersErrorMessage && (
        <div
          className="flex flex-col gap-3 rounded-xl border border-app-red/30 bg-app-red/10 px-4 py-3 text-sm text-app-red sm:flex-row sm:items-center sm:justify-between"
          role="alert"
        >
          <p>{lockerState.lockersErrorMessage}</p>
          <Button
            type="button"
            tone="outline"
            className="h-9 px-3 text-xs"
            loading={lockerState.isFetching}
            onClick={lockerState.refetch}
          >
            إعادة المحاولة
          </Button>
        </div>
      )}

      <LockerGrid
        lockers={lockerState.lockers}
        branches={branches}
        memberOptions={memberOptions}
        isLoading={lockerState.isLoading || lockerState.isFetching}
        actionsDisabled={lockerState.actionsDisabled}
        onReserve={lockerState.openReserve}
        onRelease={lockerState.setReleaseTarget}
        onDelete={lockerState.setDeleteTarget}
      />

      <ConfirmDialog
        open={Boolean(lockerState.deleteTarget)}
        onClose={() => lockerState.setDeleteTarget(null)}
        onConfirm={lockerState.confirmDelete}
        title="حذف الخزانة"
        message={`هل أنت متأكد من حذف الخزانة "${lockerState.deleteTarget?.locker_number || ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={lockerState.isDeleting}
      />

      <ConfirmDialog
        open={Boolean(lockerState.releaseTarget)}
        onClose={() => lockerState.setReleaseTarget(null)}
        onConfirm={lockerState.confirmRelease}
        title="فك الحجز"
        message={`هل أنت متأكد من فك حجز الخزانة "${lockerState.releaseTarget?.locker_number || ""}"؟`}
        confirmLabel="فك الحجز"
        tone="primary"
        isLoading={lockerState.isReleasing}
      />

      <Drawer
        open={Boolean(lockerState.reserveTarget)}
        onClose={lockerState.closeReserve}
        title="حجز خزانة"
        subtitle={`حجز الخزانة ${lockerState.reserveTarget?.locker_number || ""}`}
      >
        {lockerState.reserveTarget && (
          <LockerReserveForm
            key={lockerState.reserveTarget.id}
            formId="reserve-locker-form"
            members={branchMembers}
            onSubmit={lockerState.handleReserve}
            onCancel={lockerState.closeReserve}
            isLoading={lockerState.isReserving}
            errorMessage={lockerState.reserveError}
          />
        )}
      </Drawer>
    </div>
  );
}
