export default function PageHeader({
  eyebrow,
  title,
  subtitle,
  action,
  className = "",
}) {
  return (
    <div
      className={`flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between ${className}`}
    >
      <div className="text-start">
        {eyebrow && (
          <p className="text-xs font-medium text-app-yellow">{eyebrow}</p>
        )}
        <h1 className="mt-2 text-xl font-medium text-app-text sm:text-2xl">{title}</h1>
        {subtitle && (
          <p className="mt-2 max-w-2xl text-sm text-app-muted-light">
            {subtitle}
          </p>
        )}
      </div>

      {action && (
        <div className="max-w-full self-start xl:self-auto">{action}</div>
      )}
    </div>
  );
}
