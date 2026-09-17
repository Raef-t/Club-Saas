import { Suspense } from "react";
import CoachesCreateClient from "./CoachesCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateCoachPage() {
  await verifyPageAccess("/management/coaches/create");

  return (
    <Suspense fallback={null}>
      <CoachesCreateClient />
    </Suspense>
  );
}
