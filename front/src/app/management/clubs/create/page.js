import ClubsCreateClient from "./ClubsCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إضافة أو تعديل نادٍ | TechnoGYM",
  description: "إنشاء نادٍ جديد أو تعديل بيانات نادٍ موجود.",
};

/**
 * Loads the club list and edited record on the server.
 */
export default async function CreateClubPage({ searchParams }) {
  const query = await searchParams;
  const rawId = Array.isArray(query.id) ? query.id[0] : query.id;
  const clubId = /^\d+$/.test(rawId || "") ? Number(rawId) : null;
  const mode = query.mode === "edit" && clubId ? "edit" : "create";
  const { token } = await verifyPageAccess("/management/clubs/create");
  const [clubs, club] = await Promise.all([
    safeRequestBackend("clubs", { token, params: { per_page: "all" } }, []),
    mode === "edit" ? safeRequestBackend(`clubs/${clubId}`, { token }, null) : null,
  ]);

  return <ClubsCreateClient mode={mode} clubId={clubId} initialData={{ clubs, club }} />;
}
