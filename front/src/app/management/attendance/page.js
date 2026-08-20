import { Suspense } from "react";
import AttendanceClient from "./AttendanceClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الحضور و المغادرة | TechnoGYM",
};

export default async function AttendancePage() {
  const { token } = await verifySession();
  const branches = await requestBackend("branches", { token });

  return (
    <Suspense fallback={null}>
      <AttendanceClient initialBranches={branches} />
    </Suspense>
  );
}
