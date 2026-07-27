import BrandLogo from "@/components/common/BrandLogo";
import Button from "@/components/ui/Button";

/**
 * Renders the public TechnoGYM landing experience.
 */
export default function HomePageContent() {
  return (
    <main className="dashboard-bg min-h-screen px-4 py-8 text-app-text">
      <div className="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl flex-col">
        <header className="flex items-center justify-between gap-4">
          <BrandLogo className="h-14 w-36 bg-black/30 ring-1 ring-app-yellow/20" priority />

          <Button href="/login" className="h-11 rounded-xl px-6 font-semibold">
            تسجيل الدخول
          </Button>
        </header>

        <section className="grid flex-1 place-items-center py-16 text-center">
          <div className="max-w-3xl">
            <p className="text-sm font-medium text-app-yellow">TechnoGYM</p>
            <h1 className="mt-5 text-4xl font-semibold leading-tight text-white sm:text-5xl lg:text-6xl">
              منصة متكاملة لإدارة النادي الرياضي
            </h1>
            <p className="mx-auto mt-6 max-w-2xl text-base leading-8 text-app-muted-light sm:text-lg">
              إدارة الأعضاء والاشتراكات والحضور والحسابات والتقارير من مكان واحد.
            </p>
            <Button href="/login" className="mt-9 h-12 rounded-xl px-8 font-semibold">
              الدخول إلى لوحة التحكم
            </Button>
          </div>
        </section>
      </div>
    </main>
  );
}
