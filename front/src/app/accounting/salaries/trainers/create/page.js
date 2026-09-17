import SalarySheetPage from "@/components/forms/SalarySheetPage";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateTrainerSalarySheetPage() {
  await verifyPageAccess("/accounting/salaries/trainers/create");

  return <SalarySheetPage />;
}
