import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import AccountSetupForm from "@/components/auth/AccountSetupForm";
import { AUTH_SETUP_COOKIE } from "@/lib/authSession";
import { verifySession } from "@/lib/server/auth";

export const metadata = {
  title: "إعداد الحساب | TechnoGYM",
};

export default async function AccountSetupPage() {
  const [session, cookieStore] = await Promise.all([verifySession(), cookies()]);

  if (cookieStore.get(AUTH_SETUP_COOKIE)?.value !== "required") {
    redirect("/");
  }

  return (
    <AccountSetupForm
      userId={session.user?.user_id || session.user?.id}
      displayName={session.user?.person?.full_name || session.user?.full_name || ""}
      systemUsername={session.user?.username || ""}
    />
  );
}
