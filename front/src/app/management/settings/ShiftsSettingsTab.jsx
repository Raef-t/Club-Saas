import { useMemo } from "react";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DataTable from "@/components/ui/DataTable";
import { PlusIcon } from "@/components/icons/Icons";
import BranchSelector from "./BranchSelector";
import SettingsLoadError from "./SettingsLoadError";
import SettingsTableActions from "./SettingsTableActions";
import ShiftEditorDrawer from "./ShiftEditorDrawer";
import { GENDER_MAP } from "./settingsConstants";

/**
 * Renders the branch shifts table and its create, edit, print, and delete actions.
 */
export default function ShiftsSettingsTab({
  branches,
  selectedBranchId,
  onBranchChange,
  isLoadingBranches,
  formatTime,
  state,
}) {
  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "اسم الوردية",
        align: "right",
        width: "150px",
        render: (value) => value || "-",
      },
      {
        key: "start_time",
        label: "وقت البدء",
        align: "center",
        width: "120px",
        render: (value) => formatTime(value),
      },
      {
        key: "end_time",
        label: "وقت الانتهاء",
        align: "center",
        width: "120px",
        render: (value) => formatTime(value),
      },
      {
        key: "gender_allowed",
        label: "الفئة المسموح بها",
        align: "center",
        width: "140px",
        sortValue: (row) => GENDER_MAP[row.gender_allowed] ?? row.gender_allowed ?? "",
        render: (value) => GENDER_MAP[value] ?? value,
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        width: "140px",
        sortable: false,
        render: (_, row) => (
          <SettingsTableActions
            onEdit={() => state.openEdit(row)}
            onDelete={() => state.setDeleteTarget(row)}
          />
        ),
      },
    ],
    [formatTime, state],
  );

  return (
    <>
      <section className="space-y-6 animate-fade-in">
        <div className="flex flex-col justify-between gap-4 border-b border-app-line pb-4 md:flex-row md:items-center">
          <div>
            <h3 className="text-right text-lg font-medium text-app-text">ورديات عمل الفرع</h3>
            <p className="mt-1 text-right text-sm text-app-muted-light">
              عرض وإدارة ورديات العمل اليومية وتحديد فترات العمل والفئة المستهدفة.
            </p>
          </div>
          {selectedBranchId && (
            <div className="flex flex-wrap items-center gap-3">
              <Button
                type="button"
                tone="outline"
                onClick={state.printShifts}
                className="flex h-10 items-center gap-2 text-xs"
                icon={<PrintIcon className="size-4" />}
              >
                طباعة
              </Button>
              <Button
                type="button"
                tone="primary"
                onClick={state.openCreate}
                className="flex h-10 items-center gap-2 text-xs"
                icon={<PlusIcon className="size-4 shrink-0" />}
              >
                إضافة وردية
              </Button>
            </div>
          )}
        </div>

        <BranchSelector
          branches={branches}
          value={selectedBranchId}
          onChange={onBranchChange}
          isLoading={isLoadingBranches}
          label="اختر الفرع لعرض وردياته"
        />

        <SettingsLoadError
          message={state.errorMessage}
          onRetry={state.retry}
          isRetrying={state.isFetching}
        />

        {selectedBranchId && (
          <DataTable
            columns={columns}
            rows={state.shifts}
            isLoading={state.isLoading}
            minWidth="600px"
            showToolbar={false}
          />
        )}
      </section>

      <ShiftEditorDrawer state={state} />

      <ConfirmDialog
        open={Boolean(state.deleteTarget)}
        onClose={() => state.setDeleteTarget(null)}
        onConfirm={state.confirmDelete}
        isLoading={state.isDeleting}
        title="حذف الوردية"
        message={`هل أنت متأكد من حذف الوردية "${state.deleteTarget?.name || ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        requiredConfirmation="delete"
        confirmationValue={state.deleteConfirmation}
        onConfirmationChange={state.setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
      />
    </>
  );
}

/**
 * Renders the printer symbol used by the shifts action.
 */
function PrintIcon({ className = "size-5" }) {
  return (
    <svg
      className={className}
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      strokeWidth="2"
      aria-hidden="true"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
      />
    </svg>
  );
}
