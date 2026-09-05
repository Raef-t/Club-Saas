import ActivitiesClient from "./ActivitiesClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend, safeRequestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الأنشطة والرياضات | TechnoGYM",
  description: "إدارة الأنشطة الرياضية وتصنيفاتها والفروع المرتبطة بها.",
};

/**
 * Loads the activity workspace data on the server.
 */
export default async function ActivitiesPage() {
  const { token } = await verifyPageAccess("/management/activities");
  const [activities, branches, activityTypes] = await Promise.all([
    requestBackend("activities", { token }),
    safeRequestBackend("branches", { token, params: { per_page: "all" } }, []),
    safeRequestBackend("activity-types", { token }, []),
  ]);

  return <ActivitiesClient initialData={{ activities, branches, activityTypes }} />;
}
