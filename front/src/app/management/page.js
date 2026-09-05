import ManagementDashboard from "./dashboard/ManagementDashboard";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الإحصائيات | TechnoGYM",
};

/**
 * Loads the daily schedule without preventing the live statistics from connecting.
 */
async function loadDashboardResource(path, token) {
  try {
    return await requestBackend(path, { token });
  } catch {
    return null;
  }
}

/**
 * Loads and renders the management statistics route.
 */
export default async function ManagementPage() {
  const { token } = await verifyPageAccess("/management");
  const schedule = await loadDashboardResource("session-templates/schedule", token);

  return (
    <ManagementDashboard
      initialData={{
        schedule,
      }}
    />
  );
}
