export default function Breadcrumb({ title, subtitle, className = "" }) {
  return (
    <div className={`max-w-sm text-start ${className}`}>
      <h1 className="text-lg font-medium text-app-text md:text-2xl">
        {title}
      </h1>
      <p className="mt-1 text-xs text-app-muted-light md:text-sm">
        {subtitle?.includes(">") ? (
          <>
            {subtitle.split(">")[0].trim()}
            {" > "}
            <span className="text-app-yellow">
              {subtitle.split(">").slice(1).join(">").trim()}
            </span>
          </>
        ) : (
          subtitle
        )}
      </p>
    </div>
  );
}
