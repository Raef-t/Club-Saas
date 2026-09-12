import ActivitiesCreateClient from "./ActivitiesCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "إضافة أو تعديل نشاط | TechnoGYM",
  description: "إنشاء نشاط رياضي جديد أو تعديل بيانات نشاط موجود.",
};

/**
 * Loads activity editor dependencies and the edited record on the server.
 */
export default async function CreateActivityPage({ searchParams }) {
  const query = await searchParams;
  const rawId = Array.isArray(query.id) ? query.id[0] : query.id;
  const activityId = /^\d+$/.test(rawId || "") ? Number(rawId) : null;
  const mode = query.mode === "edit" && activityId ? "edit" : "create";
  const { token } = await verifyPageAccess("/management/activities/create");
  const [branches, activityTypes, activity] = await Promise.all([
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("activity-types", { token }, []),
    mode === "edit" ? safeRequestBackend(`activities/${activityId}`, { token }, null) : null,
  ]);
  return (
    <ActivitiesCreateClient
      mode={mode}
      activityId={activityId}
      initialData={{ branches, activityTypes, activity }}
    />
  );
}
