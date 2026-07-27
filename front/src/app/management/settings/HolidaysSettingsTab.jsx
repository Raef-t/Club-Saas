import { useMemo } from "react";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DataTable from "@/components/ui/DataTable";
import { PlusIcon } from "@/components/icons/Icons";
import BranchSelector from "./BranchSelector";
import HolidayEditorDrawer from "./HolidayEditorDrawer";
import SettingsLoadError from "./SettingsLoadError";
import SettingsTableActions from "./SettingsTableActions";
import { DAYS_MAP, HOLIDAY_TYPE_MAP } from "./settingsConstants";
import { getHolidayPeriod } from "./settingsUtils";

/**
 * Renders the branch holidays table and its create, edit, and delete actions.
 */
export default function HolidaysSettingsTab({
  branches,
  selectedBranchId,
  onBranchChange,
  isLoadingBranches,
  state,
}) {
  const columns = useMemo(
    () => [
      {
        key: "type",
        label: "نوع العطلة / الإجازة",
        align: "center",
        width: "180px",
        render: (value) => HOLIDAY_TYPE_MAP[value] ?? value,
      },
      {
        key: "details",
        label: "تفاصيل الفترة",
        align: "center",
        width: "240px",
        render: (_, row) => getHolidayPeriod(row, DAYS_MAP),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        width: "140px",
        render: (_, row) => (
          <SettingsTableActions
            onEdit={() => state.openEdit(row)}
            onDelete={() => state.setDeleteTarget(row)}
          />
        ),
      },
    ],
    [state],
  );

  return (
    <>
      <section className="space-y-6 animate-fade-in">
        <div className="flex flex-col gap-4 border-b border-app-line pb-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 className="text-right text-lg font-medium text-app-text">عطلات وإجازات الفرع</h3>
            <p className="mt-1 text-right text-sm text-app-muted-light">
              ضبط الإجازات الرسمية والعطلات الأسبوعية للفرع المحدد.
            </p>
          </div>
          {selectedBranchId && (
            <Button
              type="button"
              tone="primary"
              onClick={state.openCreate}
              className="flex h-10 items-center gap-2 self-start text-xs sm:self-center"
              icon={<PlusIcon className="size-4 shrink-0" />}
            >
              إضافة إجازة
            </Button>
          )}
        </div>

        <BranchSelector
          branches={branches}
          value={selectedBranchId}
          onChange={onBranchChange}
          isLoading={isLoadingBranches}
          label="اختر الفرع لعرض إجازاته"
        />

        <SettingsLoadError
          message={state.errorMessage}
          onRetry={state.retry}
          isRetrying={state.isFetching}
        />

        {selectedBranchId && (
          <DataTable
            columns={columns}
            rows={state.holidays}
            isLoading={state.isLoading}
            minWidth="600px"
            showToolbar={false}
          />
        )}
      </section>

      <HolidayEditorDrawer state={state} />

      <ConfirmDialog
        open={Boolean(state.deleteTarget)}
        onClose={() => state.setDeleteTarget(null)}
        onConfirm={state.confirmDelete}
        isLoading={state.isDeleting}
        title="حذف العطلة / الإجازة"
        message="هل أنت متأكد من حذف هذه العطلة أو الإجازة؟ لا يمكن التراجع عن هذا الإجراء."
      />
    </>
  );
}
