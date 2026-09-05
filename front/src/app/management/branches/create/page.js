import BranchesCreateClient from "./BranchesCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إضافة أو تعديل فرع | TechnoGYM",
  description: "إنشاء فرع جديد أو تعديل بيانات فرع موجود.",
};

/**
 * Loads branch editor dependencies and the edited record on the server.
 */
export default async function CreateBranchPage({ searchParams }) {
  const query = await searchParams;
  const rawId = Array.isArray(query.id) ? query.id[0] : query.id;
  const branchId = /^\d+$/.test(rawId || "") ? Number(rawId) : null;
  const mode = query.mode === "edit" && branchId ? "edit" : "create";
  const { token } = await verifyPageAccess("/management/branches/create");
  const [clubs, branch] = await Promise.all([
    safeRequestBackend("clubs", { token, params: { per_page: "all" } }, []),
    mode === "edit" ? safeRequestBackend(`branches/${branchId}`, { token }, null) : null,
  ]);

  return <BranchesCreateClient mode={mode} branchId={branchId} initialData={{ clubs, branch }} />;
}
