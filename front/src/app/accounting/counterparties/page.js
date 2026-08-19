import CounterpartiesClient from "./CounterpartiesClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الذمم والأطراف الخارجية | نظام المحاسبة",
};

export default async function CounterpartiesPage() {
  const { token } = await verifySession();
  let initialCounterparties = [];

  try {
    const res = await requestBackend("accounting/counterparties", { token });
    initialCounterparties = res?.data || [];
  } catch {
    initialCounterparties = [];
  }

  return <CounterpartiesClient initialCounterparties={initialCounterparties} />;
}
