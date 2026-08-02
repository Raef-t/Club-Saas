import Link from "next/link";

function CreatePageBreadcrumb({ subtitle, backHref }) {
  if (!subtitle) return null;

  const parts = subtitle
    .split(">")
    .map((part) => part.trim())
    .filter(Boolean);

  if (parts.length < 2 || !backHref) {
    return <p className="mt-1 text-sm text-app-muted-light">{subtitle}</p>;
  }

  return (
    <nav className="mt-1" aria-label="مسار التنقل">
      <ol className="flex flex-wrap items-center gap-1 text-sm text-app-muted-light">
        <li>
          <Link
            href={backHref}
            className="rounded-sm transition hover:text-app-yellow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-app-yellow"
          >
            {parts[0]}
          </Link>
        </li>
        <li aria-hidden="true">‹</li>
        <li className="text-app-yellow" aria-current="page">
          {parts.slice(1).join(" ‹ ")}
        </li>
      </ol>
    </nav>
  );
}

export default function ManagementCreatePage({
  title,
  subtitle,
  formId,
  backHref,
  children,
  isSubmitting = false,
  submitLabel = "حفظ",
  backLabel = "رجوع",
  maxWidth = "1040px",
}) {
  return (
    <div
      className="management-create-page space-y-6"
      dir="rtl"
      style={{ "--entry-page-max": maxWidth }}
    >
      <div className="text-right">
        <h1 className="text-2xl font-medium text-app-text">{title}</h1>
        <CreatePageBreadcrumb subtitle={subtitle} backHref={backHref} />
      </div>

      <div className="entry-form-surface">{children}</div>
    </div>
  );
}
