import AddEntryPage from "@/components/forms/AddEntryPage";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateExpensePage() {
  await verifyPageAccess("/accounting/expenses/create");

  return <AddEntryPage variant="expense" mode="summary" />;
}
