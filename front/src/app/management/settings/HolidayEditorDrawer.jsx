import Button from "@/components/ui/Button";
import Drawer from "@/components/ui/Drawer";
import Dropdown from "@/components/ui/Dropdown";
import { Field } from "@/components/forms/FormControls";
import { TagIcon } from "@/components/icons/Icons";
import { DAY_OPTIONS, HOLIDAY_TYPE_OPTIONS } from "./settingsConstants";

const HOLIDAY_FORM_ID = "branch-holiday-form";

/**
 * Renders the create and edit form for a weekly or date-range holiday.
 */
export default function HolidayEditorDrawer({ state }) {
  const { editor, form, errors } = state;

  return (
    <Drawer
      open={Boolean(editor)}
      onClose={state.closeEditor}
      title={editor?.mode === "create" ? "إضافة عطلة / إجازة" : "تعديل عطلة / إجازة"}
      subtitle="حدد نوع وتاريخ العطلة أو الإجازة الرسمية للفرع."
      footer={
        <div className="flex items-center justify-end gap-3">
          <Button type="button" tone="outline" onClick={state.closeEditor} className="px-6">
            إلغاء
          </Button>
          <Button
            type="submit"
            form={HOLIDAY_FORM_ID}
            tone="primary"
            loading={state.isSaving}
            className="px-6"
          >
            حفظ
          </Button>
        </div>
      }
    >
      <form id={HOLIDAY_FORM_ID} noValidate onSubmit={state.saveHoliday} className="space-y-4">
        <label className="block text-right">
          <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
            <TagIcon className="size-4 shrink-0 text-app-yellow" />
            <span>نوع العطلة / الإجازة</span>
          </span>
          <Dropdown
            options={HOLIDAY_TYPE_OPTIONS}
            value={form.holidayType}
            onChange={state.setHolidayType}
            placeholder="اختر النوع"
            buttonClassName="h-[46px]"
          />
        </label>

        {form.holidayType === "weekly" ? (
          <label className="block text-right">
            <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
              <TagIcon className="size-4 shrink-0 text-app-yellow" />
              <span>اليوم</span>
            </span>
            <Dropdown
              options={DAY_OPTIONS}
              value={form.holidayDay}
              onChange={(value) => state.setField("holidayDay", value)}
              placeholder="اختر اليوم"
              buttonClassName={`h-[46px] ${errors.holidayDay ? "border-app-red bg-app-red/5" : ""}`}
            />
            {errors.holidayDay && (
              <span className="mt-1.5 block text-xs text-app-red" role="alert">
                {errors.holidayDay}
              </span>
            )}
          </label>
        ) : (
          <>
            <Field
              label="تاريخ البدء"
              type="date"
              required
              value={form.holidayStartDate}
              onChange={(value) => state.setField("holidayStartDate", value)}
              error={errors.holidayStartDate}
            />
            <Field
              label="تاريخ الانتهاء"
              type="date"
              required
              value={form.holidayEndDate}
              onChange={(value) => state.setField("holidayEndDate", value)}
              error={errors.holidayEndDate}
            />
          </>
        )}
      </form>
    </Drawer>
  );
}
