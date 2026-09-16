import { Suspense } from "react";
import OffersClient from "./OffersClient";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "العروض الترويجية | TechnoGYM",
};

export default async function OffersPage() {
  await verifyPageAccess("/management/offers");

  return (
    <Suspense fallback={null}>
      <OffersClient />
    </Suspense>
  );
}
