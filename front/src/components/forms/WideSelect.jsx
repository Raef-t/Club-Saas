import { ChevronRight, TagIcon } from "@/components/icons/Icons";

export function WideSelect({ label, placeholder = "بحث عن عضو ...", required = true, className = "", children, name, value, onChange, ...props }) {
  return (
    <label className={`block text-start ${className}`}>
      <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
        <TagIcon className="size-4 text-app-yellow" />
        <span>{label}</span>
        {required ? <span className="text-app-red">*</span> : null}
      </span>
      <div className="relative w-full">
        <select
          name={name}
          required={required}
          defaultValue={value || ""}
          onChange={onChange}
          className="flex h-[46px] w-full appearance-none items-center justify-between rounded-lg border border-app-muted/50 bg-app-panel-soft/40 pr-4 pl-10 text-sm text-app-muted-light outline-none transition focus:border-app-yellow focus-within:border-app-yellow"
          dir="rtl"
          {...props}
        >
          <option value="" disabled hidden>{placeholder}</option>
          {children}
        </select>
        <ChevronRight className="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 -rotate-90 text-app-muted-light" />
      </div>
    </label>
  );
}
