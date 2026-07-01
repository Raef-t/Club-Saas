"use client";

import { useEffect, useMemo } from "react";
import { countries } from "@/lib/countries";
import Dropdown from "@/components/ui/Dropdown";

export default function PhoneField({
  label,
  phoneValue,
  onPhoneChange,
  codeValue,
  onCodeChange,
  required = false,
  className = "",
  error,
}) {
  // If no codeValue is set, default to Syria (+963)
  useEffect(() => {
    if (!codeValue) {
      onCodeChange("+963");
    }
  }, [codeValue, onCodeChange]);

  const selectedCountry = useMemo(() => {
    return countries.find((c) => c.dialCode === codeValue) || countries.find((c) => c.code === "SY");
  }, [codeValue]);

  // Options for dropdown: display dialCode and country name, deduplicate by dialCode
  const codeOptions = useMemo(() => {
    const unique = [];
    const seen = new Set();
    for (const c of countries) {
      if (!seen.has(c.dialCode)) {
        seen.add(c.dialCode);
        unique.push({
          value: c.dialCode,
          label: `${c.dialCode} ${c.name}`
        });
      }
    }
    return unique;
  }, []);

  const handlePhoneChange = (e) => {
    let val = e.target.value.replace(/\D/g, ""); // Allow only digits
    if (selectedCountry && val.length > selectedCountry.maxLength) {
      val = val.slice(0, selectedCountry.maxLength);
    }
    onPhoneChange(val);
  };

  return (
    <div className={`space-y-2 ${className}`}>
      {label && (
        <label className="block text-right text-sm text-app-muted-light">
          {label} {required && <span className="text-app-red">*</span>}
        </label>
      )}
      <div className="flex gap-2 w-full" dir="ltr">
        <Dropdown
          options={codeOptions}
          value={codeValue || "+963"}
          onChange={onCodeChange}
          buttonClassName={`border border-app-line h-11 w-[105px] px-2 text-sm ${error ? "bg-app-red/5 border-app-red" : "bg-app-card-soft"}`}
          menuClassName="w-48 max-h-64 overflow-y-auto"
          className="text-white shrink-0"
        />
        <input
          type="tel"
          value={phoneValue || ""}
          onChange={handlePhoneChange}
          className={`app-input h-11 px-3 outline-none text-white flex-1 min-w-0 placeholder-app-muted text-sm border focus:ring-1 ${error ? "border-app-red bg-app-red/5 focus:ring-app-red focus:border-app-red" : "bg-app-card-soft border-transparent focus:border-app-yellow/70 focus:ring-transparent"}`}
          placeholder={selectedCountry ? `أقصى ${selectedCountry.maxLength} أرقام` : ""}
          required={required}
          dir="ltr"
        />
      </div>
      {error && (
        <span className="mt-1.5 block text-xs text-app-red text-right w-full">
          {error}
        </span>
      )}
    </div>
  );
}
