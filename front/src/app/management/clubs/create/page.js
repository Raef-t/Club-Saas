import ClubsCreateClient from "./ClubsCreateClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

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
  const { token } = await verifySession();
  const [clubs, club] = await Promise.all([
    requestBackend("clubs", { token }),
    mode === "edit" ? requestBackend(`clubs/${clubId}`, { token }) : null,
  ]);

  return <ClubsCreateClient mode={mode} clubId={clubId} initialData={{ clubs, club }} />;
}
