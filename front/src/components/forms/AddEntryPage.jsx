import { Field, FormCard, SaveButton, SummaryPanel, TextAreaField, TransactionTypeSelector, UploadBox, WideSelect } from "@/components/forms/FormControls";

const baseFields = [
  { label: "الفئة" },
  { label: "التاريخ", type: "date" },
  { label: "المبلغ" },
  { label: "طريقة الدفع" },
  { label: "الفرع" },
  { label: "الحساب" },
];

const expenseFields = [
  { label: "الفئة" },
  { label: "التاريخ", type: "date" },
  { label: "المبلغ" },
  { label: "طريقة الدفع" },
  { label: "النوع" },
  { label: "الحساب" },
];

export default function AddEntryPage({ variant = "income", mode = "summary" }) {
  const isExpense = variant === "expense";
  const fields = isExpense ? expenseFields : baseFields;
  const heading = isExpense ? "بيانات المصروف" : "بيانات الإيراد";

  if (mode === "wide") {
    return (
      <div className="form-page space-y-6">
        <div className="flex justify-end">
          <SaveButton />
        </div>
        <FormCard className="form-page-container p-7">
          <div className="form-grid-3">
            <Field label="الفئة" />
            <Field label="التاريخ" type="date" />
            <Field label="رقم القيد" value="23/2/2025" type="text" />
            <Field label="نوع القيد" />
            <Field label="المبلغ" />
            <Field label="نوع العملة" />
            <Field label="مدين / دائن" />
            <Field label="الدفعة" />
          </div>
          <WideSelect label="العضو / العميل" className="mt-7" />
          <div className="mt-7 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <UploadBox compact />
            <TextAreaField label="الوصف / البيان" />
          </div>
        </FormCard>
      </div>
    );
  }

  return (
    <div className="form-page space-y-6">
      <div className="flex justify-end">
        <SaveButton />
      </div>
      <div className="form-summary-layout">
        <main className="rounded-2xl bg-[#212121] p-5 ring-1 ring-white/5">
          <TransactionTypeSelector selected={variant} />
          <FormCard title={heading} className="mt-4 bg-[#2b2b2b]">
            <div className="mb-5 inline-flex rounded-lg border border-app-yellow bg-[rgba(255,222,78,0.2)] px-4 py-1 text-sm text-app-yellow">98865654#</div>
            <div className="form-grid-2">
              {fields.map((field) => <Field key={field.label} {...field} />)}
            </div>
            <WideSelect label="العضو / العميل" className="mt-6" />
            <TextAreaField className="mt-6" />
          </FormCard>
          <UploadBox className="mt-4" />
        </main>
        <SummaryPanel type={variant} compact />
      </div>
    </div>
  );
}
