import BrandLogo from "@/components/common/BrandLogo";
import Button from "@/components/ui/Button";

/**
 * Explains the supported password recovery process.
 */
export default function ForgotPasswordCard() {
  return (
    <main className="dashboard-bg grid min-h-screen place-items-center px-4 py-10">
      <section
        className="card-shell mx-auto w-full max-w-md rounded-3xl p-6 text-center md:p-8"
        dir="rtl"
      >
        <BrandLogo className="mx-auto mb-6 h-16 w-40 bg-black/30 ring-1 ring-app-yellow/20" />

        <h1 className="text-2xl font-semibold text-white">استعادة كلمة المرور</h1>
        <p className="mt-4 leading-7 text-app-muted-light">
          حفاظًا على أمان حسابك، تواصل مع مسؤول النظام ليتحقق من هويتك ويعيد تعيين كلمة المرور.
        </p>

        <Button href="/login" className="mt-7 h-12 w-full rounded-xl font-semibold">
          العودة إلى تسجيل الدخول
        </Button>
      </section>
    </main>
  );
}
