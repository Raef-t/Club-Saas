import LockersCreateClient from "./LockersCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إضافة خزانة | نظام إدارة النادي",
  description: "إضافة خزانة جديدة للنادي",
};

export default async function LockersCreatePage() {
  const { token } = await verifyPageAccess("/management/lockers/create");
  const branches = await safeRequestBackend(
    "branches",
    {
      token,
      params: { per_page: "all" },
    },
    [],
  );

  return <LockersCreateClient initialBranches={branches} />;
}
