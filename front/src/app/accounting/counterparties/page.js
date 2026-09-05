import CounterpartiesClient from "./CounterpartiesClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الذمم والأطراف الخارجية | نظام المحاسبة",
};

export default async function CounterpartiesPage() {
  const { token } = await verifyPageAccess("/accounting/counterparties");
  let initialCounterparties = [];

  try {
    const res = await requestBackend("accounting/counterparties", { token });
    initialCounterparties = res?.data || [];
  } catch {
    initialCounterparties = [];
  }

  return <CounterpartiesClient initialCounterparties={initialCounterparties} />;
}
