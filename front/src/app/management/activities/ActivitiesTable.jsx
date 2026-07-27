import { useMemo } from "react";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import RowActions from "@/components/ui/RowActions";
import SearchInput from "@/components/ui/SearchInput";
import { genderLabels } from "@/lib/constants";
import { ACTIVITY_TABLE_COLUMNS } from "./activityConstants";
import { getActivityName } from "./activityUtils";

/**
 * Renders the searchable activity table and its row actions.
 */
export default function ActivitiesTable({ state }) {
  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "النشاط",
        align: "center",
        render: (_, activity) => (
          <span className="text-sm font-medium text-app-text">{getActivityName(activity)}</span>
        ),
      },
      {
        key: "description",
        label: "الوصف",
        align: "center",
        render: (value) => (
          <span className="block max-w-64 truncate text-center text-xs text-app-muted-light">
            {value || "-"}
          </span>
        ),
      },
      {
        key: "gender_allowed",
        label: "الفئة",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light">{genderLabels[value] || value}</span>
        ),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
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
        render: (_, activity) => (
          <RowActions
            disabled={state.isDeleting}
            editHref={`/management/activities/create?mode=edit&id=${activity.id}`}
            onDelete={() => state.requestDelete(activity)}
          />
        ),
      },
    ],
    [state],
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
      totalPages={0}
      onRowClick={state.openDetails}
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
            {state.activities.length.toLocaleString("ar")}
          </span>
        </p>
      }
    />
  );
}
