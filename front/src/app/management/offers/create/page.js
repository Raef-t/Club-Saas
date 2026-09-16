import { Suspense } from "react";
import OffersCreateClient from "./OffersCreateClient";

export default function CreateOfferPage() {
  return (
    <Suspense fallback={null}>
      <OffersCreateClient />
    </Suspense>
  );
}
