import Image from "next/image";
import Link from "next/link";

export const metadata = {
  title: "استعادة كلمة المرور | TechnoGYM",
};

export default function ForgotPasswordPage() {
  return (
    <main className="dashboard-bg grid min-h-screen place-items-center px-4 py-10">
      <section
        className="card-shell mx-auto w-full max-w-md rounded-3xl p-6 text-center md:p-8"
        dir="rtl"
      >
        <div className="mx-auto mb-6 grid h-16 w-40 place-items-center overflow-hidden rounded-2xl bg-black/30 ring-1 ring-app-yellow/20">
          <Image
            src="/img/logo.jpeg"
            alt="TechnoGYM"
            width={500}
            height={500}
            className="h-full w-full object-contain"
          />
        </div>

        <h1 className="text-2xl font-semibold text-white">
          استعادة كلمة المرور
        </h1>
        <p className="mt-4 leading-7 text-app-muted-light">
          حفاظًا على أمان حسابك، تواصل مع مسؤول النظام ليتحقق من هويتك ويعيد
          تعيين كلمة المرور.
        </p>

        <Link
          href="/login"
          className="mt-7 inline-flex h-12 w-full items-center justify-center rounded-xl bg-app-yellow font-semibold text-app-bg transition hover:opacity-90"
        >
          العودة إلى تسجيل الدخول
        </Link>
      </section>
    </main>
  );
}
