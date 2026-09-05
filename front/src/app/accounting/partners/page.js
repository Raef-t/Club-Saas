import PartnersClient from "./PartnersClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الشركاء ورأس المال | نظام المحاسبة",
};

export default async function PartnersPage() {
  const { token } = await verifyPageAccess("/accounting/partners");
  let initialPartners = [];
  let initialSafes = [];

  try {
    const [partnersRes, safesRes] = await Promise.all([
      requestBackend("accounting/partners", { token }),
      requestBackend("accounting/safes", { token }),
    ]);
    initialPartners = partnersRes?.data || [];
    initialSafes = safesRes?.data || [];
  } catch {
    initialPartners = [];
    initialSafes = [];
  }

  return <PartnersClient initialPartners={initialPartners} initialSafes={initialSafes} />;
}
