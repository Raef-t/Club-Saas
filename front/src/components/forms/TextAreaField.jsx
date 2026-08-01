export function TextAreaField({ label = "الوصف / البيان", placeholder = "تفاصيل إضافية عن المعاملة", className = "", name, value, onChange, error, ...props }) {
  return (
    <label className={`block text-start ${className}`}>
      {label ? <span className="mb-3 block text-base font-medium text-app-text">{label}</span> : null}
      <textarea
        name={name}
        value={onChange ? value ?? "" : undefined}
        defaultValue={onChange ? undefined : value}
        onChange={onChange}
        aria-invalid={Boolean(error)}
        className={`min-h-[102px] w-full resize-none rounded-lg border bg-app-card-soft p-4 text-start text-sm text-app-text placeholder-app-muted outline-none transition ${
          error
            ? "border-app-red bg-app-red/5 focus:border-app-red"
            : "border-app-muted/50 focus:border-app-yellow"
        }`}
        placeholder={placeholder}
        dir="rtl"
        {...props}
      />
      {error && (
        <span className="mt-1.5 block w-full text-right text-xs text-app-red" role="alert">
          {error}
        </span>
      )}
    </label>
  );
}
