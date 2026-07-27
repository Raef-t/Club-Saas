import ManagementDashboard from "./dashboard/ManagementDashboard";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الإحصائيات | TechnoGYM",
};

/**
 * Loads one dashboard resource without blocking the remaining statistics.
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
  const { token } = await verifySession();
  const [members, coaches, subscriptions, schedule] = await Promise.all([
    loadDashboardResource("members", token),
    loadDashboardResource("coaches", token),
    loadDashboardResource("player-subscriptions", token),
    loadDashboardResource("session-templates/schedule", token),
  ]);

  return (
    <ManagementDashboard
      initialData={{
        members,
        coaches,
        subscriptions,
        schedule,
      }}
    />
  );
}
