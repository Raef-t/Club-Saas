export default function ToggleSwitch({
  checked = false,
  onChange,
  label,
  size = "md",
  disabled = false,
}) {
  const trackSize = size === "sm" ? "h-5 w-9" : "h-6 w-11";
  const thumbSize =
    size === "sm"
      ? "after:h-4 after:w-4 after:top-[2px] after:right-[2px] peer-checked:after:-translate-x-full"
      : "after:h-5 after:w-5 after:top-[2px] after:right-[2px] peer-checked:after:-translate-x-[18px]";

  return (
    <div className={`flex items-center gap-3 ${disabled ? "opacity-50 pointer-events-none" : ""}`}>
      <label className="relative inline-flex cursor-pointer items-center">
        <input
          type="checkbox"
          checked={checked}
          onChange={onChange}
          className="peer sr-only"
          disabled={disabled}
        />
        <div
          className={`peer ${trackSize} rounded-full bg-app-line after:absolute after:rounded-full after:bg-white after:transition-all peer-checked:bg-app-yellow ${thumbSize}`}
        />
      </label>
      {label && <span className="text-sm font-medium text-white">{label}</span>}
    </div>
  );
}
