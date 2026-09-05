import { ChevronRight, TagIcon } from "@/components/icons/Icons";
import TimePickerSmart from "./TimePickerSmart";
import DatePickerSmart from "./DatePickerSmart";

export function Field({
  label,
  required = true,
  placeholder = "",
  value,
  type = "text",
  className = "",
  variant = "default",
  icon: Icon,
  children,
  name,
  onChange,
  compact = false,
  error,
  ...props
}) {
  const handlePickerChange = (val) => {
    if (!onChange) return;
    if (name) {
      onChange({
        target: { name, value: val },
        currentTarget: { name, value: val },
      });
    } else {
      onChange(val);
    }
  };

  if (type === "time") {
    return (
      <TimePickerSmart
        label={label}
        value={value}
        onChange={handlePickerChange}
        placeholder={placeholder || "HH:MM"}
        required={required}
        disabled={props.disabled}
        allowClear={true}
        compact={compact}
        error={error}
      />
    );
  }

  if (type === "date") {
    return (
      <DatePickerSmart
        label={label}
        value={value}
        onChange={handlePickerChange}
        placeholder={placeholder || "DD/MM/YYYY"}
        required={required}
        disabled={props.disabled}
        allowClear={true}
        compact={compact}
        error={error}
        minYear={props.minYear}
        maxYear={props.maxYear}
      />
    );
  }
  const borderClass = 
    variant === "ghost" 
      ? "border-transparent bg-transparent" 
      : error
      ? "border border-app-red bg-app-red/5 focus-within:ring-1 focus-within:ring-app-red"
      : variant === "search"
      ? "border border-app-line bg-app-card-soft focus-within:border-app-yellow focus-within:ring-1 focus-within:ring-app-yellow/40"
      : compact
      ? "border border-app-line bg-app-card-soft focus-within:border-app-yellow focus-within:ring-1 focus-within:ring-app-yellow/40"
      : "border border-app-muted/50 bg-app-panel-soft/40 focus:border-app-yellow focus-within:border-app-yellow focus-within:ring-1 focus-within:ring-app-yellow/40";

  const baseInputClass =
    `flex w-full items-center justify-between rounded-lg outline-none transition text-start ${
      compact ? "h-9 px-3 text-xs" : "h-[46px] px-4 text-sm"
    } ${borderClass}`;

  return (
    <label className={`block min-w-0 text-start ${className}`}>
      {label && (
        compact ? (
          <span className="mb-1.5 block text-xs text-app-muted-light text-right w-full">
            {label}
            {required ? <span className="text-app-red">*</span> : null}
          </span>
        ) : (
          <span className="mb-3 flex items-center gap-2 text-base font-medium text-app-text">
            <TagIcon className="size-4 shrink-0 text-app-yellow" />
            <span>{label}</span>
            {required ? <span className="text-app-red">*</span> : null}
          </span>
        )
      )}
      {type === "select" ? (
        <div className="relative w-full">
          <select
            name={name}
            required={required}
            value={onChange ? value ?? "" : undefined}
            defaultValue={onChange ? undefined : value ?? ""}
            onChange={onChange}
            aria-invalid={Boolean(error)}
            className={`${baseInputClass} appearance-none ps-4 pe-10 ${value ? "text-app-text" : "text-app-muted-light"}`}
            dir="rtl"
            {...props}
          >
            <option value="" disabled hidden>
              {placeholder}
            </option>
            {children}
          </select>
          <ChevronRight className="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 -rotate-90 text-app-muted-light" />
        </div>
      ) : (
        <div className={`${baseInputClass} ${Icon ? "gap-2" : ""}`}>
          {Icon && <Icon className="size-5 shrink-0 text-app-muted-light" />}
          <input
            type={type}
            name={name}
            required={required}
            value={onChange ? value ?? "" : undefined}
            defaultValue={onChange ? undefined : value}
            onChange={onChange}
            placeholder={placeholder}
            aria-invalid={Boolean(error)}
            className="w-full bg-transparent text-start text-app-text placeholder-app-muted outline-none"
            dir="rtl"
            {...props}
          />
        </div>
      )}
      {error && (
        <span
          className="mt-1.5 block text-xs text-app-red text-right w-full"
          role="alert"
        >
          {error}
        </span>
      )}
    </label>
  );
}
