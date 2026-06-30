export function CheckboxField({
  label,
  checked,
  onChange,
  className = "",
  name,
  disabled = false,
  ...props
}) {
  return (
    <div
      className={`flex items-center justify-between rounded-lg border border-app-line bg-app-card-soft/40 p-3 text-right ${className}`}
    >
      {label && <span className="text-sm text-app-muted-light">{label}</span>}
      <input
        type="checkbox"
        name={name}
        checked={checked}
        onChange={onChange}
        disabled={disabled}
        className="size-4 rounded border-app-line bg-app-card-soft text-app-yellow accent-[#fccd03] focus:ring-0 disabled:opacity-50"
        {...props}
      />
    </div>
  );
}
