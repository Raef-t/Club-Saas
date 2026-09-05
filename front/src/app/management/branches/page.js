import BranchesClient from "./BranchesClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إدارة الفروع | TechnoGYM",
  description: "إدارة فروع الأندية وبيانات الاتصال وحالة التشغيل.",
};

/**
 * Loads the branch workspace data on the server.
 */
export default async function BranchesPage() {
  const { token } = await verifyPageAccess("/management/branches");
  const [branches, clubs] = await Promise.all([
    requestBackend("branches", { token }),
    safeRequestBackend("clubs", { token, params: { per_page: "all" } }, []),
  ]);

  return <BranchesClient initialData={{ branches, clubs }} />;
}
