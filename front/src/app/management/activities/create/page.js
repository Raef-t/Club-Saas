import ActivitiesCreateClient from "./ActivitiesCreateClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";
import { getActivityRecord } from "../activityUtils";

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
  const activityRecord = getActivityRecord(activity);
  const branchId = activityRecord?.branch_id || activityRecord?.branch?.id || null;
  const shifts = branchId ? await safeRequestBackend(`branches/${branchId}/shifts`, { token }, []) : [];

  return (
    <ActivitiesCreateClient
      mode={mode}
      activityId={activityId}
      initialData={{ branches, activityTypes, activity, shifts }}
    />
  );
}
