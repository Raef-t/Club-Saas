import Button from "@/components/ui/Button";

export default function AccessDenied({
  title = "ليس لديك صلاحية للوصول",
  description = "هذا القسم غير متاح ضمن صلاحيات حسابك الحالية. تواصل مع مدير النظام إذا كنت تحتاج إلى الوصول.",
  backHref = null,
}) {
  return (
    <section
      className="card-shell flex min-h-[55vh] flex-col items-center justify-center rounded-3xl px-6 py-12 text-center"
      dir="rtl"
    >
      <div className="grid size-16 place-items-center rounded-full border border-app-red/30 bg-app-red/10 text-2xl text-app-red">
        403
      </div>
      <h1 className="mt-5 text-xl font-medium text-app-text">{title}</h1>
      <p className="mt-3 max-w-lg text-sm leading-7 text-app-muted-light">{description}</p>
      {backHref && (
        <Button href={backHref} tone="outline" className="mt-6">
          العودة إلى قسم متاح
        </Button>
      )}
    </section>
  );
}
