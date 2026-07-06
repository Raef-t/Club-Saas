import { Suspense } from "react";
import ClubsCreateClient from "./ClubsCreateClient";

export default function CreateClubPage() {
  return (
    <Suspense fallback={null}>
      <ClubsCreateClient />
    </Suspense>
  );
}
