"use client";

import { Field } from "@/components/forms/FormControls";
import Dropdown from "@/components/ui/Dropdown";

/**
 * Renders a member selector or a numeric holder identifier for other holder types.
 */
export default function LockerHolderField({
  holderType,
  holderId,
  memberOptions,
  onChange,
  error,
  required = false,
}) {
  if (!holderType) return null;

  const label = required ? "المستفيد" : "المستفيد (اختياري)";

  if (holderType === "member") {
    return (
      <div className="flex flex-col gap-1.5 text-start">
        <label className="flex items-center gap-1 text-sm font-medium text-white">
          {label}
          {required && <span className="text-app-red">*</span>}
        </label>
        <Dropdown
          searchable
          options={memberOptions}
          value={String(holderId || "")}
          onChange={onChange}
          error={error}
          placeholder="ابحث عن لاعب..."
        />
      </div>
    );
  }

  return (
    <Field
      label={label}
      type="number"
      required={required}
      value={holderId}
      onChange={(event) => onChange(event.target.value)}
      error={error}
      placeholder={holderType === "staff" ? "أدخل رقم الموظف" : "أدخل رقم الزائر"}
      min="1"
    />
  );
}
