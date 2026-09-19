import { Suspense } from "react";
import StaffCreateClient from "./StaffCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function CreateStaffPage() {
  await verifyPageAccess("/management/staff/create");

  return (
    <Suspense fallback={null}>
      <StaffCreateClient />
    </Suspense>
  );
}
