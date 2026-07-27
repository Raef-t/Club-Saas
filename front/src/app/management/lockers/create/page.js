import LockersCreateClient from "./LockersCreateClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إضافة خزانة | نظام إدارة النادي",
  description: "إضافة خزانة جديدة للنادي",
};

export default async function LockersCreatePage() {
  const { token } = await verifySession();
  const branches = await requestBackend("branches", { token });

  return <LockersCreateClient initialBranches={branches} />;
}
