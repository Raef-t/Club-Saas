import { Suspense } from "react";
import StaffClient from "./StaffClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الموظفين | TechnoGYM",
};

export default async function StaffPage() {
  const { token } = await verifySession();
  const [staff, branches] = await Promise.all([
    requestBackend("staff", { token }),
    requestBackend("branches", { token }),
  ]);

  return (
    <Suspense fallback={null}>
      <StaffClient initialData={{ staff, branches }} />
    </Suspense>
  );
}
