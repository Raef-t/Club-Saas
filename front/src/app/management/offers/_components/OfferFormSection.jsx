export default function OfferFormSection({ number, title, description, children, className = "" }) {
  return (
    <section className={`app-card overflow-hidden rounded-2xl ${className}`}>
      <header className="flex items-start gap-3 border-b border-app-line px-4 py-4 sm:px-5">
        <span className="grid size-9 shrink-0 place-items-center rounded-xl bg-app-yellow text-sm font-bold text-app-on-accent">
          {number}
        </span>
        <div>
          <h2 className="text-base font-semibold text-app-text">{title}</h2>
          {description && <p className="mt-1 text-xs text-app-muted-light">{description}</p>}
        </div>
      </header>
      <div className="space-y-5 p-4 sm:p-5">{children}</div>
    </section>
  );
}
