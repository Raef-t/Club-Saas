import { useMemo } from "react";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import RowActions from "@/components/ui/RowActions";
import SearchInput from "@/components/ui/SearchInput";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import { FilterIcon } from "@/components/icons/Icons";
import { genderLabels } from "@/lib/constants";
import { BRANCH_GENDER_FILTER_OPTIONS, BRANCH_TABLE_COLUMNS } from "./branchConstants";
import { getBranchDisplayName } from "./branchUtils";
import { usePermissions } from "@/lib/PermissionContext";
import { PAGE_SIZE_OPTIONS } from "@/lib/pagination";

/**
 * Renders the searchable and filterable branches table.
 */
export default function BranchesTable({ state }) {
  const { can } = usePermissions();
  const canView = can("branch.view");
  const canUpdate = can("branch.update");
  const canDelete = can("branch.delete");
  const canToggleStatus = can("branch.toggle-status");
  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "اسم الفرع",
        align: "center",
        sortValue: (branch) => getBranchDisplayName(branch) || "",
        render: (_, branch) => (
          <span className="text-sm font-medium text-app-text">{getBranchDisplayName(branch)}</span>
        ),
      },
      {
        key: "gender_restriction",
        label: "التقييد الجنسي",
        align: "center",
        sortValue: (branch) =>
          genderLabels[branch.gender_restriction] || branch.gender_restriction || "",
        render: (value) => (
          <span className="text-xs text-app-muted-light">{genderLabels[value] || value}</span>
        ),
      },
      {
        key: "address",
        label: "العنوان",
        align: "center",
        render: (value) => (
          <span className="block max-w-44 truncate text-xs text-app-muted-light">
            {value || "-"}
          </span>
        ),
      },
      {
        key: "phone",
        label: "الهاتف",
        align: "center",
        sortValue: (branch) =>
          branch.phone ? `${branch.country_code || ""} ${branch.phone}`.trim() : "",
        render: (_, branch) => (
          <span className="text-xs text-app-muted-light" dir="ltr">
            {branch.phone ? `${branch.country_code || ""} ${branch.phone}` : "-"}
          </span>
        ),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        sortValue: (branch) => (branch.is_active ? 1 : 0),
        render: (value, branch) => (
          <div className="flex justify-center" onClick={(event) => event.stopPropagation()}>
            <ToggleSwitch
              checked={value}
              onChange={canToggleStatus ? () => state.toggleStatus(branch) : undefined}
              disabled={!canToggleStatus || state.isToggling}
              size="sm"
            />
          </div>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        sortable: false,
        render: (_, branch) => (
          <RowActions
            disabled={state.isDeleting || state.isToggling}
            editHref={
              canUpdate ? `/management/branches/create?mode=edit&id=${branch.id}` : undefined
            }
            onDelete={canDelete ? () => state.requestDelete(branch) : undefined}
          />
        ),
      },
    ],
    [canDelete, canToggleStatus, canUpdate, state],
  );

  return (
    <DataTable
      title="قائمة الفروع"
      columns={columns}
      rows={state.branches}
      minWidth="750px"
      tableColumns={BRANCH_TABLE_COLUMNS}
      showAdd={false}
      showSearch={false}
      showFilter={false}
      showExport={false}
      defaultSortColumn="name"
      isLoading={state.isLoading}
      emptyMessage={
        state.errorMessage ? (
          <div className="space-y-3 text-center">
            <p className="text-app-red">{state.errorMessage}</p>
            <Button type="button" tone="outline" className="h-9 px-3 text-xs" onClick={state.retry}>
              إعادة المحاولة
            </Button>
          </div>
        ) : (
          "لا توجد فروع مسجلة حالياً."
        )
      }
      rowClassName="gap-2 px-3 py-4"
      headerClassName="gap-2 px-3"
      currentPage={state.pagination.currentPage}
      totalPages={state.pagination.lastPage}
      totalItems={state.pagination.total}
      pageSize={state.pagination.perPage}
      pageSizeOptions={PAGE_SIZE_OPTIONS}
      onPageChange={state.pagination.setPage}
      onPageSizeChange={state.pagination.setPerPage}
      onRowClick={canView ? state.openDetails : undefined}
      getRowKey={(branch) => branch.id}
      toolbarActions={
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <SearchInput
            value={state.search}
            onChange={(event) => state.setSearch(event.target.value)}
            placeholder="البحث باسم الفرع أو العنوان..."
          />
          <Dropdown
            className="min-w-48 border-app-line bg-app-card-soft text-white"
            icon={FilterIcon}
            value={state.genderFilter}
            options={BRANCH_GENDER_FILTER_OPTIONS}
            onChange={state.setGenderFilter}
          />
        </div>
      }
      toolbarMeta={
        <p className="text-sm text-app-muted-light">
          النتائج:{" "}
          <span className="font-medium text-app-text">
            {state.totalResults.toLocaleString("ar")}
          </span>
        </p>
      }
    />
  );
}
