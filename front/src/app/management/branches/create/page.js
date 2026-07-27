import BranchesCreateClient from "./BranchesCreateClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

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
  const { token } = await verifySession();
  const [clubs, branch] = await Promise.all([
    requestBackend("clubs", { token }),
    mode === "edit" ? requestBackend(`branches/${branchId}`, { token }) : null,
  ]);

  return <BranchesCreateClient mode={mode} branchId={branchId} initialData={{ clubs, branch }} />;
}
