import SafesClient from "./SafesClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الصناديق | نظام المحاسبة",
};

export default async function SafesPage() {
  const { token } = await verifyPageAccess("/accounting/safes");
  let initialSafes = [];
  let initialAccounts = [];

  try {
    const [safesRes, accountsRes] = await Promise.all([
      requestBackend("accounting/safes", { token }),
      requestBackend("accounting/accounts", { token }),
    ]);
    initialSafes = safesRes?.data || [];
    initialAccounts = accountsRes?.data || [];
  } catch {
    initialSafes = [];
    initialAccounts = [];
  }

  return <SafesClient initialSafes={initialSafes} initialAccounts={initialAccounts} />;
}
