import { Suspense } from "react";
import StaffClient from "./StaffClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الموظفين | TechnoGYM",
};

export default async function StaffPage() {
  const { token } = await verifyPageAccess("/management/staff");
  const [staff, branches] = await Promise.all([
    requestBackend("staff", { token }),
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
  ]);

  return (
    <Suspense fallback={null}>
      <StaffClient initialData={{ staff, branches }} />
    </Suspense>
  );
}
