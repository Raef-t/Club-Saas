import ExpensesClient from "./ExpensesClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "سندات الصرف والمصروفات | نظام المحاسبة",
};

export default async function ExpensesPage() {
  const { token } = await verifySession();
  let initialJournals = [];
  let initialAccounts = [];
  let initialSafes = [];
  let initialCounterparties = [];

  try {
    const [journalsRes, accountsRes, safesRes, counterpartiesRes] = await Promise.all([
      requestBackend("accounting/journals?type=PV", { token }),
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
    <ExpensesClient
      initialJournals={initialJournals}
      initialAccounts={initialAccounts}
      initialSafes={initialSafes}
      initialCounterparties={initialCounterparties}
    />
  );
}
