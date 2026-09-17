import AddEntryPage from "@/components/forms/AddEntryPage";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateRevenuePage() {
  await verifyPageAccess("/accounting/revenues/create");

  return <AddEntryPage variant="income" mode="summary" />;
}
