import Button from "@/components/ui/Button";
import { Field } from "@/components/forms/FormControls";
import { Skeleton } from "@/components/ui/Skeleton";
import BranchSelector from "./BranchSelector";
import SettingsLoadError from "./SettingsLoadError";

/**
 * Renders and submits the financial and operational settings of one branch.
 */
export default function GeneralSettingsTab({
  branches,
  selectedBranchId,
  onBranchChange,
  isLoadingBranches,
  settings,
}) {
  const { form, errors } = settings;

  /**
   * Changes the active branch and clears a stale branch-selection error.
   */
  function handleBranchChange(value) {
    onBranchChange(value);
    settings.clearError("selectedBranchId");
  }

  return (
    <form noValidate onSubmit={settings.saveSettings} className="space-y-6">
      <div>
        <h3 className="text-right text-lg font-medium text-app-text">إعدادات فروع النادي</h3>
        <p className="mt-1 text-right text-sm text-app-muted-light">
          ضبط الإعدادات المالية وأوقات العمل لكل فرع من فروع النادي.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <BranchSelector
          branches={branches}
          value={selectedBranchId}
          onChange={handleBranchChange}
          isLoading={isLoadingBranches}
          label="اختر الفرع لتعديل إعداداته"
          error={errors.selectedBranchId}
        />
      </div>

      <SettingsLoadError
        message={settings.errorMessage}
        onRetry={settings.retry}
        isRetrying={settings.isLoading}
      />

      {selectedBranchId &&
        (settings.isLoading ? (
          <SettingsFieldsSkeleton />
        ) : (
          <BranchSettingsFields form={form} errors={errors} onFieldChange={settings.setField} />
        ))}

      <div className="mt-8 flex items-center justify-end gap-3 border-t border-app-line pt-5">
        <Button
          type="submit"
          tone="primary"
          loading={settings.isSaving}
          loadingLabel="جاري الحفظ"
          disabled={!selectedBranchId || settings.isLoading}
          className="px-8"
        >
          حفظ التغييرات
        </Button>
      </div>
    </form>
  );
}

/**
 * Renders a stable placeholder while the selected branch settings are loading.
 */
function SettingsFieldsSkeleton() {
  return (
    <div className="grid gap-4 sm:grid-cols-2">
      {Array.from({ length: 8 }, (_, index) => (
        <Skeleton key={index} className="h-[70px] w-full" />
      ))}
    </div>
  );
}

/**
 * Renders all editable fields for branch settings.
 */
function BranchSettingsFields({ form, errors, onFieldChange }) {
  return (
    <>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field
          label="نسبة عمولة النادي الافتراضية (%)"
          type="number"
          required
          min="0"
          max="100"
          step="0.01"
          value={form.defaultClubCommission}
          onChange={(event) => onFieldChange("defaultClubCommission", event.target.value)}
          placeholder="40"
          error={errors.defaultClubCommission}
        />
        <Field
          label="نسبة عمولة المدرب الافتراضية (%)"
          type="number"
          required
          min="0"
          max="100"
          step="0.01"
          value={form.defaultCoachCommission}
          onChange={(event) => onFieldChange("defaultCoachCommission", event.target.value)}
          placeholder="60"
          error={errors.defaultCoachCommission}
        />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Field
          label="راتب الموظف الافتراضي"
          type="number"
          required
          min="0"
          step="0.01"
          value={form.defaultEmployeeSalary}
          onChange={(event) => onFieldChange("defaultEmployeeSalary", event.target.value)}
          placeholder="3500"
          error={errors.defaultEmployeeSalary}
        />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Field
          label="بداية ساعات العمل للفرع"
          type="time"
          required={false}
          value={form.workingHoursStart}
          onChange={(value) => onFieldChange("workingHoursStart", value)}
          error={errors.workingHoursStart}
        />
        <Field
          label="نهاية ساعات العمل للفرع"
          type="time"
          required={false}
          value={form.workingHoursEnd}
          onChange={(value) => onFieldChange("workingHoursEnd", value)}
          error={errors.workingHoursEnd}
        />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Field
          label="سعر الدخول اليومي"
          type="number"
          required
          min="0"
          step="0.01"
          value={form.dailyEntryPrice}
          onChange={(event) => onFieldChange("dailyEntryPrice", event.target.value)}
          placeholder="0"
          error={errors.dailyEntryPrice}
        />
        <Field
          label="سعر الخزانة"
          type="number"
          required
          min="0"
          step="0.01"
          value={form.lockerPrice}
          onChange={(event) => onFieldChange("lockerPrice", event.target.value)}
          placeholder="30000"
          error={errors.lockerPrice}
        />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <SettingsCheckbox
          checked={form.allowFreeze}
          title="السماح بالتجميد"
          description="السماح للمشتركين بتجميد اشتراكاتهم"
          onChange={(value) => onFieldChange("allowFreeze", value)}
        />
        <SettingsCheckbox
          checked={form.displayMixedActivities}
          title="عرض الأنشطة المختلطة"
          description="إظهار الأنشطة المختلطة في جدول الحصص"
          onChange={(value) => onFieldChange("displayMixedActivities", value)}
        />
      </div>
    </>
  );
}

/**
 * Renders a labeled boolean setting with a consistent visual treatment.
 */
function SettingsCheckbox({ checked, title, description, onChange }) {
  return (
    <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-app-line bg-app-panel-soft p-4 transition-colors hover:border-app-yellow/50">
      <input
        type="checkbox"
        checked={checked}
        onChange={(event) => onChange(event.target.checked)}
        className="size-5 rounded border-app-line bg-app-panel text-app-yellow focus:ring-app-yellow focus:ring-offset-app-bg"
      />
      <span className="flex flex-col text-right">
        <span className="font-medium text-app-text">{title}</span>
        <span className="mt-0.5 text-sm text-app-muted-light">{description}</span>
      </span>
    </label>
  );
}
