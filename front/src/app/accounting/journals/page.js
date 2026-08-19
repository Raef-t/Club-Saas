import JournalsClient from "./JournalsClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "سندات القيود اليومية | نظام المحاسبة",
};

export default async function JournalsPage() {
  const { token } = await verifySession();
  let initialJournals = [];
  let initialAccounts = [];
  let initialSafes = [];

  try {
    const [journalsRes, accountsRes, safesRes] = await Promise.all([
      requestBackend("accounting/journals", { token }),
      requestBackend("accounting/accounts", { token }),
      requestBackend("accounting/safes", { token }),
    ]);
    initialJournals = journalsRes?.data || [];
    initialAccounts = accountsRes?.data || [];
    initialSafes = safesRes?.data || [];
  } catch {
    initialJournals = [];
    initialAccounts = [];
    initialSafes = [];
  }

  return (
    <JournalsClient
      initialJournals={initialJournals}
      initialAccounts={initialAccounts}
      initialSafes={initialSafes}
    />
  );
}
