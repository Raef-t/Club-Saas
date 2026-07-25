"use client";

export default function Checkbox({
  checked,
  onChange,
  label,
  className = "",
  labelClassName = "",
  disabled = false,
  ...props
}) {
  return (
    <label
      className={`flex items-center gap-2.5 cursor-pointer group select-none ${
        disabled ? "opacity-50 cursor-not-allowed" : ""
      } ${className}`}
    >
      <input
        type="checkbox"
        checked={checked}
        onChange={disabled ? undefined : onChange}
        disabled={disabled}
        className="peer sr-only"
        {...props}
      />
      <div className="flex size-[18px] shrink-0 items-center justify-center rounded border border-app-line bg-app-card-soft transition-all duration-200 group-hover:border-app-yellow/70 peer-focus-visible:ring-2 peer-focus-visible:ring-app-yellow/40 peer-checked:border-app-yellow peer-checked:bg-app-yellow">
        <svg
          className="size-3 text-black scale-0 transition-transform duration-200 ease-out peer-checked:scale-100"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          strokeWidth="4"
        >
          <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      {label && (
        <span className={`text-sm text-app-muted group-hover:text-app-text transition-colors duration-200 ${labelClassName}`}>
          {label}
        </span>
      )}
    </label>
  );
}
