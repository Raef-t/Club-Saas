import AddEntryPage from "@/components/forms/AddEntryPage";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateCashboxEntryPage() {
  await verifyPageAccess("/accounting/cashbox/create");

  return <AddEntryPage mode="wide" />;
}
