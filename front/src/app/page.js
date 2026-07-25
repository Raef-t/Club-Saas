import Image from "next/image";
import Link from "next/link";

export default function HomePage() {
  return (
    <main className="dashboard-bg min-h-screen px-4 py-8 text-app-text">
      <div className="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl flex-col">
        <header className="flex items-center justify-between gap-4">
          <div className="grid h-14 w-36 place-items-center overflow-hidden rounded-2xl bg-black/30 ring-1 ring-app-yellow/20">
            <Image
              src="/img/logo.jpeg"
              alt="TechnoGYM"
              width={500}
              height={500}
              className="h-full w-full object-contain"
              priority
            />
          </div>

          <Link
            href="/login"
            className="inline-flex h-11 items-center justify-center rounded-xl bg-app-yellow px-6 text-sm font-semibold text-app-bg transition hover:opacity-90"
          >
            تسجيل الدخول
          </Link>
        </header>

        <section className="grid flex-1 place-items-center py-16 text-center">
          <div className="max-w-3xl">
            <p className="text-sm font-medium text-app-yellow">TechnoGYM</p>
            <h1 className="mt-5 text-4xl font-semibold leading-tight text-white sm:text-5xl lg:text-6xl">
              منصة متكاملة لإدارة النادي الرياضي
            </h1>
            <p className="mx-auto mt-6 max-w-2xl text-base leading-8 text-app-muted-light sm:text-lg">
              إدارة الأعضاء والاشتراكات والحضور والحسابات والتقارير من مكان
              واحد.
            </p>
            <Link
              href="/login"
              className="mt-9 inline-flex h-12 items-center justify-center rounded-xl bg-app-yellow px-8 font-semibold text-app-bg transition hover:opacity-90"
            >
              الدخول إلى لوحة التحكم
            </Link>
          </div>
        </section>
      </div>
    </main>
  );
}
