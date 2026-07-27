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

/**
 * Renders the searchable and filterable branches table.
 */
export default function BranchesTable({ state }) {
  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "اسم الفرع",
        align: "center",
        render: (_, branch) => (
          <span className="text-sm font-medium text-app-text">{getBranchDisplayName(branch)}</span>
        ),
      },
      {
        key: "gender_restriction",
        label: "التقييد الجنسي",
        align: "center",
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
        render: (value, branch) => (
          <div className="flex justify-center" onClick={(event) => event.stopPropagation()}>
            <ToggleSwitch
              checked={value}
              onChange={() => state.toggleStatus(branch)}
              disabled={state.isToggling}
              size="sm"
            />
          </div>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, branch) => (
          <RowActions
            disabled={state.isDeleting || state.isToggling}
            editHref={`/management/branches/create?mode=edit&id=${branch.id}`}
            onDelete={() => state.requestDelete(branch)}
          />
        ),
      },
    ],
    [state],
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
      totalPages={0}
      onRowClick={state.openDetails}
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
            {state.branches.length.toLocaleString("ar")}
          </span>
        </p>
      }
    />
  );
}
