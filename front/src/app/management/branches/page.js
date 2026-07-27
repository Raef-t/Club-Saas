import BranchesClient from "./BranchesClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الفروع | TechnoGYM",
  description: "إدارة فروع الأندية وبيانات الاتصال وحالة التشغيل.",
};

/**
 * Loads the branch workspace data on the server.
 */
export default async function BranchesPage() {
  const { token } = await verifySession();
  const [branches, clubs] = await Promise.all([
    requestBackend("branches", { token }),
    requestBackend("clubs", { token }),
  ]);

  return <BranchesClient initialData={{ branches, clubs }} />;
}
