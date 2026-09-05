import { useMemo } from "react";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import RowActions from "@/components/ui/RowActions";
import SearchInput from "@/components/ui/SearchInput";
import { formatLocalizedName } from "@/lib/utils";
import { ACTIVITY_TABLE_COLUMNS } from "./activityConstants";
import { getActivityName } from "./activityUtils";
import { usePermissions } from "@/lib/PermissionContext";
import { PAGE_SIZE_OPTIONS } from "@/lib/pagination";

/**
 * Renders the searchable activity table and its row actions.
 */
export default function ActivitiesTable({ state }) {
  const { can } = usePermissions();
  const canView = can("activity.view");
  const canUpdate = can("activity.update");
  const canDelete = can("activity.delete");
  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "النشاط",
        align: "center",
        sortValue: (activity) => getActivityName(activity) || "",
        render: (_, activity) => (
          <span className="text-sm font-medium text-app-text">{getActivityName(activity)}</span>
        ),
      },
      {
        key: "description",
        label: "الوصف",
        align: "center",
        sortValue: (activity) => activity.description || "",
        render: (value) => (
          <span className="block max-w-64 truncate text-center text-xs text-app-muted-light">
            {value || "-"}
          </span>
        ),
      },
      {
        key: "activity_type",
        label: "الفئة",
        align: "center",
        sortValue: (activity) => formatLocalizedName(activity.activity_type?.name) || "",
        render: (_, activity) => (
          <span className="text-xs text-app-muted-light">
            {formatLocalizedName(activity.activity_type?.name)}
          </span>
        ),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        sortValue: (activity) => (activity.is_active ? 1 : 0),
        render: (value) => (
          <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
              value ? "bg-app-green/10 text-app-green" : "bg-app-red/10 text-app-red"
            }`}
          >
            {value ? "نشط" : "غير نشط"}
          </span>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        sortable: false,
        render: (_, activity) => (
          <RowActions
            disabled={state.isDeleting}
            editHref={
              canUpdate ? `/management/activities/create?mode=edit&id=${activity.id}` : undefined
            }
            onDelete={canDelete ? () => state.requestDelete(activity) : undefined}
          />
        ),
      },
    ],
    [canDelete, canUpdate, state],
  );

  return (
    <DataTable
      title="قائمة الأنشطة والرياضات"
      columns={columns}
      rows={state.activities}
      minWidth="800px"
      tableColumns={ACTIVITY_TABLE_COLUMNS}
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
          "لا توجد أنشطة مسجلة حالياً."
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
      getRowKey={(activity) => activity.id}
      toolbarActions={
        <SearchInput
          value={state.search}
          onChange={(event) => state.setSearch(event.target.value)}
          placeholder="البحث باسم النشاط أو الوصف..."
          className="min-w-80"
        />
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
