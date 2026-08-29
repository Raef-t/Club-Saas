import RevenuesClient from "./RevenuesClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "سندات القبض والإيرادات | نظام المحاسبة",
};

export default async function RevenuesPage() {
  const { token } = await verifySession();
  let initialJournals = [];
  let initialAccounts = [];
  let initialSafes = [];
  let initialCounterparties = [];

  try {
    const [journalsRes, accountsRes, safesRes, counterpartiesRes] = await Promise.all([
      requestBackend("accounting/journals?type=RV", { token }),
      requestBackend("accounting/accounts", { token }),
      requestBackend("accounting/safes", { token }),
      requestBackend("accounting/counterparties", { token }),
    ]);
    initialJournals = journalsRes?.data || [];
    initialAccounts = accountsRes?.data || [];
    initialSafes = safesRes?.data || [];
    initialCounterparties = counterpartiesRes?.data || [];
  } catch {
    initialJournals = [];
    initialAccounts = [];
    initialSafes = [];
    initialCounterparties = [];
  }

  return (
    <RevenuesClient
      initialJournals={initialJournals}
      initialAccounts={initialAccounts}
      initialSafes={initialSafes}
      initialCounterparties={initialCounterparties}
    />
  );
}
