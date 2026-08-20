"use client";

import { useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import Drawer from "@/components/ui/Drawer";
import StatsGrid from "@/components/ui/StatsGrid";
import { LockerIcon, PlusIcon } from "@/components/icons/Icons";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetStaffQuery } from "@/lib/api/staffApi";
import LockerFilters from "./LockerFilters";
import LockerGrid from "./LockerGrid";
import LockerReserveForm from "./LockerReserveForm";
import LockerReleaseDialog from "./LockerReleaseDialog";
import { useLockers } from "./useLockers";
import {
  createLockerCoachOptions,
  createLockerBranchOptions,
  createLockerMemberOptions,
  createLockerStaffOptions,
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
  const peopleQueryParams = useMemo(
    () => (selectedBranchId === "all" ? {} : { branch_id: Number(selectedBranchId) }),
    [selectedBranchId],
  );
  const { currentData: coachesResponse } = useGetCoachesQuery(peopleQueryParams);
  const coaches = useMemo(
    () =>
      getLockerCollection(
        coachesResponse || (selectedBranchId === "all" ? initialData?.coaches : null),
      ),
    [coachesResponse, initialData?.coaches, selectedBranchId],
  );
  const coachOptions = useMemo(() => createLockerCoachOptions(coaches), [coaches]);
  const { currentData: staffResponse } = useGetStaffQuery(peopleQueryParams);
  const staff = useMemo(
    () =>
      getLockerCollection(
        staffResponse || (selectedBranchId === "all" ? initialData?.staff : null),
      ),
    [initialData?.staff, selectedBranchId, staffResponse],
  );
  const staffOptions = useMemo(() => createLockerStaffOptions(staff), [staff]);

  const statItems = useMemo(() => {
    if (!lockerState.lockerSummary) return null;
    const {
      available_lockers_count = 0,
      unavailable_lockers_count = 0,
      assigned_to_member_count = 0,
      assigned_to_coach_count = 0,
      rented_lockers_count = 0,
    } = lockerState.lockerSummary;

    return [
      {
        title: "متاحة",
        value: available_lockers_count,
        tone: "green",
        onClick: () =>
          lockerState.setStatusFilter(
            lockerState.statusFilter === "available" ? "all" : "available",
          ),
        active: lockerState.statusFilter === "available",
      },
      {
        title: "مؤجرة",
        value: rented_lockers_count,
        iconKey: "subscriptions",
        tone: "blue",
        onClick: () =>
          lockerState.setStatusFilter(lockerState.statusFilter === "rented" ? "all" : "rented"),
        active: lockerState.statusFilter === "rented",
      },
      {
        title: "مع الأعضاء",
        value: assigned_to_member_count,
        iconKey: "members",
        tone: "cyan",
        onClick: () =>
          lockerState.setStatusFilter(
            lockerState.statusFilter === "with_member" ? "all" : "with_member",
          ),
        active: lockerState.statusFilter === "with_member",
      },
      {
        title: "مع المدربين",
        value: assigned_to_coach_count,
        iconKey: "coaches",
        tone: "purple",
        onClick: () =>
          lockerState.setStatusFilter(
            lockerState.statusFilter === "with_coach" ? "all" : "with_coach",
          ),
        active: lockerState.statusFilter === "with_coach",
      },
      {
        title: "غير متاحة",
        value: unavailable_lockers_count,
        tone: "orange",
        onClick: () =>
          lockerState.setStatusFilter(
            lockerState.statusFilter === "maintenance" ||
              lockerState.statusFilter === "disabled" ||
              lockerState.statusFilter === "unavailable"
              ? "all"
              : "unavailable",
          ),
        active:
          lockerState.statusFilter === "maintenance" ||
          lockerState.statusFilter === "disabled" ||
          lockerState.statusFilter === "unavailable",
      },
    ];
  }, [lockerState]);

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

      {statItems && <StatsGrid items={statItems} variant="compact" />}

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
        coachOptions={coachOptions}
        staffOptions={staffOptions}
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
        requiredConfirmation="delete"
        confirmationValue={lockerState.deleteConfirmation}
        onConfirmationChange={lockerState.setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={lockerState.isDeleting}
      />

      <LockerReleaseDialog
        locker={lockerState.releaseTarget}
        onClose={() => lockerState.setReleaseTarget(null)}
        onConfirm={lockerState.confirmRelease}
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
            coaches={coaches}
            staff={staff}
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
