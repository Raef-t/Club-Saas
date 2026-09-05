import { Suspense } from "react";
import LockersClient from "./LockersClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الخزائن | نظام إدارة النادي",
  description: "إدارة الخزائن المتاحة وتتبع حالتها.",
};

export default async function LockersPage() {
  const { token } = await verifyPageAccess("/management/lockers");
  const [lockers, branches, members, coaches, staff] = await Promise.all([
    requestBackend("lockers", { token, params: { per_page: "all" } }),
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("members", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("coaches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("staff", { token, params: { per_page: "all" } }, []),
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
