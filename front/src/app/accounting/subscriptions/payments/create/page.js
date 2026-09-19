import PaymentPage from "@/components/forms/PaymentPage";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreatePaymentPage() {
  await verifyPageAccess("/accounting/subscriptions/payments/create");

  return <PaymentPage />;
}
