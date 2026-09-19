import { Suspense } from "react";
import OffersCreateClient from "./OffersCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "إنشاء عرض ترويجي | TechnoGYM",
};

export default async function CreateOfferPage() {
  await verifyPageAccess("/management/offers/create");

  return (
    <Suspense fallback={null}>
      <OffersCreateClient />
    </Suspense>
  );
}
