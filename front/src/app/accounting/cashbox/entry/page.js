import AddEntryPage from "@/components/forms/AddEntryPage";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateCashboxEntryWithSummaryPage() {
  await verifyPageAccess("/accounting/cashbox/entry");

  return <AddEntryPage variant="income" mode="summary" />;
}
