import { Suspense } from "react";
import LockersClient from "./LockersClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الخزائن | نظام إدارة النادي",
  description: "إدارة الخزائن المتاحة وتتبع حالتها.",
};

export default async function LockersPage() {
  const { token } = await verifySession();
  const [lockers, branches, members, coaches, staff] = await Promise.all([
    requestBackend("lockers", { token }),
    requestBackend("branches", { token }),
    requestBackend("members", { token }),
    requestBackend("coaches", { token }),
    requestBackend("staff", { token }),
  ]);

  return (
    <Suspense fallback={null}>
      <LockersClient
        initialData={{
          lockers,
          branches,
          members,
          coaches,
          staff,
        }}
      />
    </Suspense>
  );
}
