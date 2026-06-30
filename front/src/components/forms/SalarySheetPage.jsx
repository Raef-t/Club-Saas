import { Field, FormCard, SaveButton, TextAreaField } from "@/components/forms/FormControls";

export default function SalarySheetPage() {
  return (
    <div className="form-page space-y-6">
      <div className="flex justify-end">
        <SaveButton />
      </div>
      <FormCard title="بيانات الكشف" className="form-page-container p-7">
        <div className="form-grid-3">
          <Field label="اسم الكشف" value="كشف رواتب موظفين" type="text" />
          <Field label="تاريخ الكشف" />
          <Field label="تاريخ الاستحقاق" />
          <Field label="الصندوق" />
          <Field label="نظام الرواتب" />
          <Field label="اسم المدرب" />
        </div>
        <TextAreaField label="ملاحظات" placeholder="" className="mt-8" />
      </FormCard>
    </div>
  );
}
