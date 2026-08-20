import { useMemo } from "react";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import RowActions from "@/components/ui/RowActions";
import SearchInput from "@/components/ui/SearchInput";
import { formatDate } from "@/lib/utils";
import { CLUB_TABLE_COLUMNS } from "./clubConstants";
import { getClubName } from "./clubUtils";
import { ClubLogo, ClubStatusBadge } from "./ClubVisuals";

/**
 * Renders the searchable clubs table and its row actions.
 */
export default function ClubsTable({ state }) {
  const columns = useMemo(
    () => [
      {
        key: "logo_url",
        label: "الشعار",
        align: "center",
        sortable: false,
        render: (value, club) => (
          <div className="flex justify-center">
            <ClubLogo
              src={club.logo || club.logo_url || value}
              name={getClubName(club)}
              className="size-10"
            />
          </div>
        ),
      },
      {
        key: "name",
        label: "الاسم",
        align: "center",
        sortValue: (club) => getClubName(club) || "",
        render: (_, club) => (
          <span className="text-sm font-medium text-app-text">{getClubName(club)}</span>
        ),
      },
      {
        key: "created_at",
        label: "تاريخ الإنشاء",
        align: "center",
        sortValue: (club) => club.created_at || "",
        render: (value) => formatDate(value),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        sortValue: (club) => (club.is_active ? 1 : 0),
        render: (value) => <ClubStatusBadge active={value} />,
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        sortable: false,
        render: (_, club) => (
          <RowActions
            disabled={state.isDeleting}
            editHref={`/management/clubs/create?mode=edit&id=${club.id}`}
            onDelete={() => state.requestDelete(club)}
          />
        ),
      },
    ],
    [state],
  );

  return (
    <DataTable
      title="قائمة النوادي"
      columns={columns}
      rows={state.clubs}
      minWidth="750px"
      tableColumns={CLUB_TABLE_COLUMNS}
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
          "لا توجد نوادٍ مسجلة حالياً."
        )
      }
      rowClassName="gap-2 px-3 py-4"
      headerClassName="gap-2 px-3"
      onRowClick={state.openDetails}
      getRowKey={(club) => club.id}
      toolbarActions={
        <SearchInput
          value={state.search}
          onChange={(event) => state.setSearch(event.target.value)}
          placeholder="البحث باسم النادي..."
          className="min-w-72"
        />
      }
      toolbarMeta={
        <p className="text-sm text-app-muted-light">
          النتائج:{" "}
          <span className="font-medium text-app-text">
            {state.clubs.length.toLocaleString("ar")}
          </span>
        </p>
      }
    />
  );
}
