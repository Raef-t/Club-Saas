export function TextAreaField({ label = "الوصف / البيان", placeholder = "تفاصيل إضافية عن المعاملة", className = "", name, value, onChange, ...props }) {
  return (
    <label className={`block text-start ${className}`}>
      {label ? <span className="mb-3 block text-base font-medium text-white">{label}</span> : null}
      <textarea
        name={name}
        defaultValue={value}
        onChange={onChange}
        className="min-h-[102px] w-full resize-none rounded-lg border border-app-muted/50 bg-app-panel-soft/40 p-4 text-start text-sm text-white placeholder-app-muted-light outline-none transition focus:border-app-yellow"
        placeholder={placeholder}
        dir="rtl"
        {...props}
      />
    </label>
  );
}
