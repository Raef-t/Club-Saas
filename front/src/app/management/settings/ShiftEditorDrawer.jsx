import Button from "@/components/ui/Button";
import Drawer from "@/components/ui/Drawer";
import Dropdown from "@/components/ui/Dropdown";
import { Field } from "@/components/forms/FormControls";
import { TagIcon } from "@/components/icons/Icons";
import { GENDER_OPTIONS } from "./settingsConstants";

const SHIFT_FORM_ID = "branch-shift-form";

/**
 * Renders the create and edit form for a branch shift.
 */
export default function ShiftEditorDrawer({ state }) {
  const { editor, form, errors } = state;

  return (
    <Drawer
      open={Boolean(editor)}
      onClose={state.closeEditor}
      title={editor?.mode === "create" ? "إضافة وردية جديدة" : "تعديل الوردية"}
      subtitle="حدد خيارات الوردية اليومية للفرع."
      footer={
        <div className="flex items-center justify-end gap-3">
          <Button type="button" tone="outline" onClick={state.closeEditor} className="px-6">
            إلغاء
          </Button>
          <Button
            type="submit"
            form={SHIFT_FORM_ID}
            tone="primary"
            loading={state.isSaving}
            className="px-6"
          >
            حفظ
          </Button>
        </div>
      }
    >
      <form id={SHIFT_FORM_ID} noValidate onSubmit={state.saveShift} className="space-y-4">
        <Field
          label="اسم الوردية"
          type="text"
          required={false}
          value={form.shiftName}
          onChange={(event) => state.setField("shiftName", event.target.value)}
          placeholder="مثال: وردية الصباح"
          error={errors.shiftName}
        />
        <Field
          label="وقت البدء"
          type="time"
          required
          value={form.shiftStartTime}
          onChange={(value) => state.setField("shiftStartTime", value)}
          error={errors.shiftStartTime}
        />
        <Field
          label="وقت الانتهاء"
          type="time"
          required
          value={form.shiftEndTime}
          onChange={(value) => state.setField("shiftEndTime", value)}
          error={errors.shiftEndTime}
        />
        <label className="block text-right">
          <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
            <TagIcon className="size-4 shrink-0 text-app-yellow" />
            <span>الفئة المسموح بها</span>
          </span>
          <Dropdown
            options={GENDER_OPTIONS}
            value={form.shiftGender}
            onChange={(value) => state.setField("shiftGender", value)}
            placeholder="اختر الفئة"
            buttonClassName={`h-[46px] ${errors.shiftGender ? "border-app-red bg-app-red/5" : ""}`}
          />
          {errors.shiftGender && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.shiftGender}
            </span>
          )}
        </label>
      </form>
    </Drawer>
  );
}
