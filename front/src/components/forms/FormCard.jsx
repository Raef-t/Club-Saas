export function FormCard({ title, children, className = "" }) {
  return (
    <section className={`form-card rounded-2xl bg-app-card/95 p-5 ring-1 ring-white/5 ${className}`}>
      {title ? <h3 className="mb-5 text-start text-lg font-medium text-white">{title}</h3> : null}
      {children}
    </section>
  );
}
