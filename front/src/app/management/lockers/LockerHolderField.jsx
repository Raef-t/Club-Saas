"use client";

import { Field } from "@/components/forms/FormControls";
import Dropdown from "@/components/ui/Dropdown";

/**
 * Renders a searchable person selector or a numeric identifier for guests.
 */
export default function LockerHolderField({
  holderType,
  holderId,
  memberOptions = [],
  staffOptions = [],
  onChange,
  error,
  required = false,
}) {
  if (!holderType) return null;

  const label = required ? "المستفيد" : "المستفيد (اختياري)";

  if (holderType === "member" || holderType === "staff") {
    const isStaff = holderType === "staff";
    const options = isStaff ? staffOptions : memberOptions;

    return (
      <div className="flex flex-col gap-1.5 text-start">
        <label className="flex items-center gap-1 text-sm font-medium text-white">
          {label}
          {required && <span className="text-app-red">*</span>}
        </label>
        <Dropdown
          searchable
          options={options}
          value={String(holderId || "")}
          onChange={onChange}
          error={error}
          placeholder={isStaff ? "ابحث عن موظف بالاسم..." : "ابحث عن لاعب بالاسم..."}
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
      placeholder="أدخل رقم الزائر"
      min="1"
    />
  );
}
