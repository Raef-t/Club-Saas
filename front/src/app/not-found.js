import Link from "next/link";

export default function NotFound() {
  return (
    <main className="grid min-h-screen place-items-center bg-app-bg px-4 py-10 text-app-text">
      <section className="app-panel w-full max-w-xl rounded-2xl px-6 py-8 text-center sm:px-10">
        <div className="mx-auto mb-6 grid size-16 place-items-center rounded-2xl border border-app-line bg-app-yellow-soft text-3xl font-bold text-app-yellow">
          !
        </div>

        <p className="mb-3 text-sm font-medium text-app-yellow">
          هذه الصفحة غير جاهزة بعد
        </p>
        <h1 className="text-3xl font-bold text-app-text sm:text-4xl">
          الصفحة قيد التطوير
        </h1>
        <p className="mx-auto mt-4 max-w-md text-base leading-8 text-app-muted-light">
          نعمل حالياً على تجهيز هذه الصفحة. يمكنك الرجوع إلى الصفحات المتاحة
          ومتابعة العمل من هناك.
        </p>

        <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
          <Link
            href="/login"
            className="app-button-primary rounded-xl px-5 py-3 text-sm font-bold"
          >
            الرجوع لتسجيل الدخول
          </Link>
          <Link
            href="/management"
            className="app-button-dark rounded-xl px-5 py-3 text-sm font-bold"
          >
            لوحة الإدارة
          </Link>
        </div>
      </section>
    </main>
  );
}
