import {
  Field,
  FormCard,
  SaveButton,
  TextAreaField,
  UploadBox,
} from "@/components/forms/FormControls";

export default function PaymentPage() {
  return (
    <div className="form-page space-y-6">
      <div className="flex justify-end">
        <SaveButton />
      </div>

      <div className="form-page-container space-y-5">
        <FormCard title="بيانات العضو" className="p-7">
          <div className="form-grid-3">
            <Field label="العضو" type="text" />
            <Field label="نوع الاشتراك" />
            <Field label="تاريخ بداية الاشتراك" />
            <Field label="تاريخ نهاية الاشتراك" />
          </div>
        </FormCard>

        <FormCard title="معلومات الدفعة" className="p-7">
          <div className="form-grid-3">
            <Field label="الصندوق" value="محمد الاسعد" type="text" />
            <Field label="طريقة الدفع" />
            <Field label="رقم الدفعة" />
            <Field label="تاريخ الدفعة" />
          </div>
        </FormCard>

        <FormCard title="تفاصيل الدفعة" className="p-7">
          <div className="form-grid-3">
            <Field label="سبب الدفعة" value="اشتراك سنوي" type="text" />
            <Field label="المبلغ قبل الخصم" />
            <Field label="الخصم" />
          </div>
          <div className="mt-7 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <UploadBox compact />
            <TextAreaField label="" placeholder="تفاصيل إضافية عن الدفعة" />
          </div>
        </FormCard>
      </div>
    </div>
  );
}
