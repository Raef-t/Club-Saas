import ActivitiesClient from "./ActivitiesClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الأنشطة والرياضات | TechnoGYM",
  description: "إدارة الأنشطة الرياضية وتصنيفاتها والفروع المرتبطة بها.",
};

/**
 * Loads the activity workspace data on the server.
 */
export default async function ActivitiesPage() {
  const { token } = await verifySession();
  const [activities, branches, activityTypes] = await Promise.all([
    requestBackend("activities", { token }),
    requestBackend("branches", { token }),
    requestBackend("activity-types", { token }),
  ]);

  return <ActivitiesClient initialData={{ activities, branches, activityTypes }} />;
}
