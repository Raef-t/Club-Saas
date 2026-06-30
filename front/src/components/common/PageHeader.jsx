export default function PageHeader({
  eyebrow,
  title,
  subtitle,
  action,
  className = "",
}) {
  return (
    <div
      className={`flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between ${className}`}
    >
      <div className="text-start">
        {eyebrow && (
          <p className="text-xs font-medium text-app-yellow">{eyebrow}</p>
        )}
        <h1 className="mt-2 text-2xl font-medium text-app-text">{title}</h1>
        {subtitle && (
          <p className="mt-2 max-w-2xl text-sm text-app-muted-light">
            {subtitle}
          </p>
        )}
      </div>

      {action && <div className="self-start lg:self-auto">{action}</div>}
    </div>
  );
}
