import { TextAreaField } from "@/components/forms/TextAreaField";

export default function ModificationReasonField({
  value,
  onChange,
  error,
  label = "سبب التعديل *",
}) {
  return (
    <TextAreaField
      label={label}
      name="reason"
      value={value}
      onChange={(event) => onChange(event.target.value)}
      error={error}
      placeholder="اكتب سبب هذا التعديل"
      required
      rows={4}
    />
  );
}
