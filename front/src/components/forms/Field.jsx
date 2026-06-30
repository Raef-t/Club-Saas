import { ChevronRight, TagIcon } from "@/components/icons/Icons";
import TimePickerSmart from "./TimePickerSmart";
import DatePickerSmart from "./DatePickerSmart";

export function Field({
  label,
  required = true,
  placeholder = "اختر الفئة",
  value,
  type = "select",
  className = "",
  variant = "default",
  icon: Icon,
  children,
  name,
  onChange,
  ...props
}) {
  if (type === "time") {
    return (
      <TimePickerSmart
        label={label}
        value={value}
        onChange={onChange}
        placeholder={placeholder || "HH:MM"}
        required={required}
        disabled={props.disabled}
        allowClear={true}
      />
    );
  }

  if (type === "date") {
    return (
      <DatePickerSmart
        label={label}
        value={value}
        onChange={onChange}
        placeholder={placeholder || "DD/MM/YYYY"}
        required={required}
        disabled={props.disabled}
        allowClear={true}
      />
    );
  }
  const borderClass = 
    variant === "ghost" 
      ? "border-transparent bg-transparent" 
      : variant === "search"
      ? "border-transparent bg-white/5 focus-within:ring-1 focus-within:ring-app-yellow"
      : "border border-app-muted/50 bg-app-panel-soft/40 focus:border-app-yellow focus-within:border-app-yellow";

  const baseInputClass =
    `flex h-[46px] w-full items-center justify-between rounded-lg px-4 text-sm outline-none transition text-start ${borderClass}`;

  return (
    <label className={`block min-w-0 text-start ${className}`}>
      {label && (
        <span className="mb-3 flex items-center gap-2 text-base font-medium text-white">
          <TagIcon className="size-4 shrink-0 text-app-yellow" />
          <span>{label}</span>
          {required ? <span className="text-app-red">*</span> : null}
        </span>
      )}
      {type === "select" ? (
        <div className="relative w-full">
          <select
            name={name}
            required={required}
            defaultValue={value || ""}
            onChange={onChange}
            className={`${baseInputClass} appearance-none pr-4 pl-10 ${value ? "text-white" : "text-app-muted-light"}`}
            dir="rtl"
            {...props}
          >
            <option value="" disabled hidden>
              {placeholder}
            </option>
            {children}
          </select>
          <ChevronRight className="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 -rotate-90 text-app-muted-light" />
        </div>
      ) : (
        <div className={`${baseInputClass} ${Icon ? "gap-2" : ""}`}>
          {Icon && <Icon className="size-5 shrink-0 text-app-muted-light" />}
          <input
            type={type}
            name={name}
            required={required}
            defaultValue={value}
            onChange={onChange}
            placeholder={placeholder}
            className="w-full bg-transparent text-white placeholder-app-muted-light outline-none"
            dir=""
            {...props}
          />
        </div>
      )}
    </label>
  );
}
