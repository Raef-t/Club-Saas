import AccountsClient from "./AccountsClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "شجرة ودليل الحسابات | نظام المحاسبة",
};

export default async function AccountsPage() {
  const { token } = await verifyPageAccess("/accounting/accounts");
  let initialAccounts = [];

  try {
    const res = await requestBackend("accounting/accounts", { token });
    initialAccounts = res?.data || [];
  } catch (error) {
    initialAccounts = [];
  }

  return <AccountsClient initialAccounts={initialAccounts} />;
}
