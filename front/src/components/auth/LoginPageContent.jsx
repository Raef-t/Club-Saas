import Link from "next/link";
import LoginForm from "@/components/auth/LoginForm";

/**
 * Composes the login form and its supporting product introduction.
 */
export default function LoginPageContent() {
  return (
    <main className="dashboard-bg grid min-h-screen place-items-center px-4 py-10">
      <div className="w-full max-w-5xl">
        <div className="grid items-center justify-center gap-8 lg:grid-cols-[440px_1fr] lg:justify-normal">
          <div className="mx-auto w-full max-w-md lg:mx-0">
            <Link
              href="/"
              className="mb-6 inline-flex text-sm text-app-yellow hover:text-app-yellow/80"
            >
              العودة للصفحة الرئيسية
            </Link>
            <LoginForm />
          </div>

          <section className="hidden text-start lg:ms-24 lg:block">
            <p className="text-sm text-app-yellow">TechnoGYM</p>
            <h2 className="mt-4 max-w-xl text-5xl font-semibold leading-tight text-white">
              نظام موحد لإدارة النادي والاشتراكات والتقارير
            </h2>
            <p className="mt-5 max-w-lg text-app-muted-light">
              لوحة تشغيلية مبنية بمكونات قابلة لإعادة الاستخدام وربط مباشر مع واجهات النظام الخلفية.
            </p>
          </section>
        </div>
      </div>
    </main>
  );
}
