import { Suspense } from "react";
import AttendanceClient from "./AttendanceClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الحضور و المغادرة | TechnoGYM",
};

export default async function AttendancePage() {
  const { token } = await verifyPageAccess("/management/attendance");
  const branches = await safeRequestBackend(
    "branches",
    {
      token,
      params: { per_page: "all" },
    },
    [],
  );

  return (
    <Suspense fallback={null}>
      <AttendanceClient initialBranches={branches} />
    </Suspense>
  );
}
